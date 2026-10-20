<?php
/**
 * API Router - Backend for Scholarship Monitoring System
 * All data operations go through here with role-based access enforcement
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

// Require authentication for all API calls
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$token = get_token();
$user_role = get_user_role();
$user_id = $_SESSION['user_id'];

switch ($action) {
    // ─── SCHOLAR MANAGEMENT (Staff only) ───
    case 'get_scholars':
        enforce_role('staff');
        $result = supabase_request('user_profiles?role=eq.scholar&select=*,scholar_assignments(*,scholarship_programs(*))', 'GET', null, $token);
        echo json_encode($result['data'] ?? []);
        break;

    case 'get_all_scholars_simple':
        enforce_role('staff');
        $result = supabase_request('user_profiles?role=eq.scholar&select=id,full_name,student_id,email', 'GET', null, $token);
        echo json_encode($result['data'] ?? []);
        break;

    case 'register_scholar':
        enforce_role('staff');
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create auth user
        $authResult = supabase_auth('signup', [
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        
        if ($authResult['status'] !== 200 && $authResult['status'] !== 201) {
            echo json_encode(['error' => $authResult['data']['msg'] ?? 'Failed to create auth user']);
            break;
        }
        
        $auth_id = $authResult['data']['user']['id'] ?? null;
        
        // Create profile
        $profileData = [
            'auth_id' => $auth_id,
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'role' => 'scholar',
            'student_id' => $data['student_id']
        ];
        
        $result = supabase_request('user_profiles', 'POST', $profileData, $token);
        
        if ($result['status'] === 201 || $result['status'] === 200) {
            echo json_encode(['success' => true, 'data' => $result['data']]);
        } else {
            echo json_encode(['error' => 'Failed to create profile', 'details' => $result['data']]);
        }
        break;

    case 'assign_scholarship':
        enforce_role('staff');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $assignData = [
            'scholar_id' => $data['scholar_id'],
            'program_id' => $data['program_id'],
            'academic_year' => $data['academic_year'],
            'assigned_by' => $user_id,
            'status' => 'Active'
        ];
        
        $result = supabase_request('scholar_assignments', 'POST', $assignData, $token);
        
        if ($result['status'] === 201 || $result['status'] === 200) {
            echo json_encode(['success' => true, 'data' => $result['data']]);
        } else {
            echo json_encode(['error' => 'Failed to assign scholarship. May already be assigned.', 'details' => $result['data']]);
        }
        break;

    // ─── PROGRAMS ───
    case 'get_programs':
        $result = supabase_request('scholarship_programs?select=*', 'GET', null, $token);
        echo json_encode($result['data'] ?? []);
        break;

    // ─── SUBMISSIONS ───
    case 'get_submissions':
        if ($user_role === 'staff') {
            $query = 'semester_submissions?select=*,user_profiles!semester_submissions_scholar_id_fkey(full_name,student_id,email),scholar_assignments!inner(scholarship_programs(*))&order=submitted_at.desc';
            
            // Apply filters
            if (!empty($_GET['program'])) {
                $query .= '&scholar_assignments.program_id=eq.' . urlencode($_GET['program']);
            }
            if (!empty($_GET['compliance'])) {
                $query .= '&compliance_status=eq.' . urlencode($_GET['compliance']);
            }
            if (!empty($_GET['verification'])) {
                $query .= '&verification_status=eq.' . urlencode($_GET['verification']);
            }
            if (!empty($_GET['search'])) {
                // Search handled client-side for simplicity
            }
        } else {
            // Scholars can only see their own
            $query = 'semester_submissions?scholar_id=eq.' . $user_id . '&select=*,scholar_assignments!inner(scholarship_programs(*))&order=submitted_at.desc';
        }
        
        $result = supabase_request($query, 'GET', null, $token);
        echo json_encode($result['data'] ?? []);
        break;

    case 'get_submission':
        $id = $_GET['id'] ?? '';
        $query = 'semester_submissions?id=eq.' . urlencode($id) . '&select=*,user_profiles!semester_submissions_scholar_id_fkey(full_name,student_id,email),scholar_assignments!inner(scholarship_programs(*))';
        
        $result = supabase_request($query, 'GET', null, $token);
        
        if (empty($result['data'])) {
            echo json_encode(['error' => 'Submission not found']);
            break;
        }
        
        $submission = $result['data'][0];
        
        // Role enforcement: scholars can only see their own
        if ($user_role === 'scholar' && $submission['scholar_id'] !== $user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You can only view your own submissions.']);
            break;
        }
        
        echo json_encode($submission);
        break;

    case 'submit_grades':
        // Scholar submitting or staff submitting on behalf
        $data = json_decode(file_get_contents('php://input'), true);
        
        $scholar_id = $data['scholar_id'] ?? $user_id;
        
        // If scholar, can only submit for themselves
        if ($user_role === 'scholar' && $scholar_id !== $user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You can only submit for yourself.']);
            break;
        }
        
        // Validate inputs
        $errors = [];
        $gwa = floatval($data['gwa']);
        $units = intval($data['enrolled_units']);
        $failed = intval($data['failed_count']);
        $incomplete = intval($data['incomplete_count']);
        $dropped = intval($data['dropped_count']);
        
        if ($gwa < 1.00 || $gwa > 5.00) $errors[] = 'GWA must be between 1.00 and 5.00';
        if ($units < 0) $errors[] = 'Enrolled units must be a non-negative integer';
        if ($failed < 0) $errors[] = 'Failed count must be a non-negative integer';
        if ($incomplete < 0) $errors[] = 'Incomplete count must be a non-negative integer';
        if ($dropped < 0) $errors[] = 'Dropped count must be a non-negative integer';
        if (empty($data['evidence_reference'])) $errors[] = 'Evidence reference is required';
        if (empty($data['academic_year'])) $errors[] = 'Academic year is required';
        if (empty($data['semester'])) $errors[] = 'Semester is required';
        
        if (!empty($errors)) {
            echo json_encode(['error' => implode('; ', $errors)]);
            break;
        }
        
        // Check for duplicate
        $dupCheck = supabase_request(
            'semester_submissions?scholar_id=eq.' . urlencode($scholar_id) . '&academic_year=eq.' . urlencode($data['academic_year']) . '&semester=eq.' . intval($data['semester']),
            'GET', null, $token
        );
        
        if (!empty($dupCheck['data'])) {
            // Check if returned - allow resubmission
            $existing = $dupCheck['data'][0];
            if ($existing['verification_status'] === 'Returned') {
                // Update existing submission (correction/resubmission)
                $updateData = [
                    'gwa' => $gwa,
                    'enrolled_units' => $units,
                    'failed_count' => $failed,
                    'incomplete_count' => $incomplete,
                    'dropped_count' => $dropped,
                    'evidence_reference' => $data['evidence_reference'],
                    'submitted_at' => date('c'),
                    'verification_status' => 'Pending',
                    'verification_notes' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                    'compliance_status' => null,
                    'compliance_reasons' => null,
                    'evaluated_by' => null,
                    'evaluated_at' => null,
                    'renewal_decision' => null,
                    'renewal_notes' => null,
                    'decided_by' => null,
                    'decided_at' => null
                ];
                
                // Check if late
                $assignCheck = supabase_request(
                    'scholar_assignments?id=eq.' . urlencode($existing['assignment_id']) . '&select=*,scholarship_programs(*)',
                    'GET', null, $token
                );
                if (!empty($assignCheck['data'])) {
                    $deadline = strtotime($assignCheck['data'][0]['scholarship_programs']['submission_deadline']);
                    $now = time();
                    $updateData['is_late'] = ($now > $deadline);
                }
                
                $result = supabase_request(
                    'semester_submissions?id=eq.' . urlencode($existing['id']),
                    'PATCH', $updateData, $token
                );
                
                // Audit log
                log_audit($existing['id'], 'Resubmission (Correction)', $user_id, $_SESSION['user_name'],
                    'Scholar corrected and resubmitted after return. Status reset to Pending.', $token);
                
                if ($result['status'] === 200) {
                    echo json_encode(['success' => true, 'message' => 'Submission corrected and resubmitted successfully.', 'data' => $result['data']]);
                } else {
                    echo json_encode(['error' => 'Failed to resubmit', 'details' => $result['data']]);
                }
                break;
            } else {
                echo json_encode(['error' => 'Duplicate submission rejected. A record for ' . $data['academic_year'] . ' Semester ' . $data['semester'] . ' already exists.']);
                break;
            }
        }
        
        // Get assignment
        $assignments = supabase_request(
            'scholar_assignments?scholar_id=eq.' . urlencode($scholar_id) . '&academic_year=eq.' . urlencode($data['academic_year']) . '&status=eq.Active&select=*,scholarship_programs(*)',
            'GET', null, $token
        );
        
        if (empty($assignments['data'])) {
            echo json_encode(['error' => 'No active scholarship assignment found for this academic year.']);
            break;
        }
        
        $assignment = $assignments['data'][0];
        $deadline = strtotime($assignment['scholarship_programs']['submission_deadline']);
        $now = time();
        $is_late = ($now > $deadline);
        
        $submitData = [
            'scholar_id' => $scholar_id,
            'assignment_id' => $assignment['id'],
            'academic_year' => $data['academic_year'],
            'semester' => intval($data['semester']),
            'gwa' => $gwa,
            'enrolled_units' => $units,
            'failed_count' => $failed,
            'incomplete_count' => $incomplete,
            'dropped_count' => $dropped,
            'evidence_reference' => $data['evidence_reference'],
            'submitted_at' => date('c'),
            'is_late' => $is_late,
            'verification_status' => 'Pending'
        ];
        
        $result = supabase_request('semester_submissions', 'POST', $submitData, $token);
        
        if ($result['status'] === 201 || $result['status'] === 200) {
            $subId = $result['data'][0]['id'] ?? 'unknown';
            log_audit($subId, 'Grade Submission', $user_id, $_SESSION['user_name'],
                'Submitted grades for ' . $data['academic_year'] . ' Sem ' . $data['semester'] .
                '. GWA: ' . $gwa . ', Units: ' . $units . ($is_late ? ' (LATE)' : ' (On time)'), $token);
            
            echo json_encode(['success' => true, 'data' => $result['data']]);
        } else {
            echo json_encode(['error' => 'Failed to submit grades', 'details' => $result['data']]);
        }
        break;

    // ─── VERIFICATION (Staff only) ───
    case 'verify_submission':
        enforce_role('staff');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $updateData = [
            'verification_status' => $data['status'], // 'Verified' or 'Returned'
            'verification_notes' => $data['notes'] ?? null,
            'verified_by' => $user_id,
            'verified_at' => date('c')
        ];
        
        // If returning, clear any previous evaluation
        if ($data['status'] === 'Returned') {
            $updateData['compliance_status'] = null;
            $updateData['compliance_reasons'] = null;
            $updateData['evaluated_by'] = null;
            $updateData['evaluated_at'] = null;
            $updateData['renewal_decision'] = null;
            $updateData['renewal_notes'] = null;
            $updateData['decided_by'] = null;
            $updateData['decided_at'] = null;
        }
        
        $result = supabase_request(
            'semester_submissions?id=eq.' . urlencode($data['submission_id']),
            'PATCH', $updateData, $token
        );
        
        $prev = $data['status'] === 'Verified' ? 'Pending' : 'Pending';
        log_audit($data['submission_id'], 'Verification: ' . $data['status'], $user_id, $_SESSION['user_name'],
            'Evidence marked as ' . $data['status'] . '. Notes: ' . ($data['notes'] ?? 'None'),
            $token, $prev, $data['status']);
        
        echo json_encode(['success' => true, 'data' => $result['data'] ?? null]);
        break;

    // ─── COMPLIANCE EVALUATION (Staff only) ───
    case 'evaluate_compliance':
        enforce_role('staff');
        $data = json_decode(file_get_contents('php://input'), true);
        $submission_id = $data['submission_id'];
        
        // Get submission with program details
        $sub = supabase_request(
            'semester_submissions?id=eq.' . urlencode($submission_id) . '&select=*,scholar_assignments!inner(scholarship_programs(*))',
            'GET', null, $token
        );
        
        if (empty($sub['data'])) {
            echo json_encode(['error' => 'Submission not found']);
            break;
        }
        
        $submission = $sub['data'][0];
        
        // Must be verified first
        if ($submission['verification_status'] !== 'Verified') {
            echo json_encode(['error' => 'Cannot evaluate. Submission must be verified first.']);
            break;
        }
        
        $program = $submission['scholar_assignments']['scholarship_programs'];
        $reasons = [];
        $compliant = true;
        
        // Check GWA (lower is better)
        if (floatval($submission['gwa']) > floatval($program['max_gwa'])) {
            $reasons[] = 'GWA ' . $submission['gwa'] . ' exceeds maximum allowed ' . $program['max_gwa'] . ' for ' . $program['program_name'];
            $compliant = false;
        }
        
        // Check minimum units
        if (intval($submission['enrolled_units']) < intval($program['min_units'])) {
            $reasons[] = 'Enrolled units ' . $submission['enrolled_units'] . ' below minimum required ' . $program['min_units'];
            $compliant = false;
        }
        
        // Check failed subjects
        if (intval($submission['failed_count']) > intval($program['allowed_failed'])) {
            $reasons[] = 'Failed subjects ' . $submission['failed_count'] . ' exceeds allowed ' . $program['allowed_failed'];
            $compliant = false;
        }
        
        // Check incomplete grades
        if (intval($submission['incomplete_count']) > intval($program['allowed_incomplete'])) {
            $reasons[] = 'Incomplete grades ' . $submission['incomplete_count'] . ' exceeds allowed ' . $program['allowed_incomplete'];
            $compliant = false;
        }
        
        // Check dropped subjects
        if (intval($submission['dropped_count']) > intval($program['allowed_dropped'])) {
            $reasons[] = 'Dropped subjects ' . $submission['dropped_count'] . ' exceeds allowed ' . $program['allowed_dropped'];
            $compliant = false;
        }
        
        // Check late submission
        if ($submission['is_late']) {
            $reasons[] = 'Submission was late (after deadline ' . $program['submission_deadline'] . ')';
            $compliant = false;
        }
        
        $status = $compliant ? 'Compliant' : 'With Deficiency';
        $reasonText = $compliant ? 'All policy requirements met.' : implode('; ', $reasons);
        
        $updateData = [
            'compliance_status' => $status,
            'compliance_reasons' => $reasonText,
            'evaluated_by' => $user_id,
            'evaluated_at' => date('c')
        ];
        
        $result = supabase_request(
            'semester_submissions?id=eq.' . urlencode($submission_id),
            'PATCH', $updateData, $token
        );
        
        log_audit($submission_id, 'Compliance Evaluation: ' . $status, $user_id, $_SESSION['user_name'],
            $reasonText, $token, null, $status);
        
        echo json_encode([
            'success' => true,
            'compliance_status' => $status,
            'reasons' => $reasonText,
            'checks' => [
                'gwa' => ['passed' => floatval($submission['gwa']) <= floatval($program['max_gwa']),
                          'value' => $submission['gwa'], 'limit' => $program['max_gwa']],
                'units' => ['passed' => intval($submission['enrolled_units']) >= intval($program['min_units']),
                           'value' => $submission['enrolled_units'], 'limit' => $program['min_units']],
                'failed' => ['passed' => intval($submission['failed_count']) <= intval($program['allowed_failed']),
                            'value' => $submission['failed_count'], 'limit' => $program['allowed_failed']],
                'incomplete' => ['passed' => intval($submission['incomplete_count']) <= intval($program['allowed_incomplete']),
                                'value' => $submission['incomplete_count'], 'limit' => $program['allowed_incomplete']],
                'dropped' => ['passed' => intval($submission['dropped_count']) <= intval($program['allowed_dropped']),
                             'value' => $submission['dropped_count'], 'limit' => $program['allowed_dropped']],
                'deadline' => ['passed' => !$submission['is_late'],
                              'value' => $submission['is_late'] ? 'Late' : 'On time']
            ]
        ]);
        break;

    // ─── RENEWAL DECISION (Staff only) ───
    case 'renewal_decision':
        enforce_role('staff');
        $data = json_decode(file_get_contents('php://input'), true);
        $submission_id = $data['submission_id'];
        $decision = $data['decision']; // 'Approved' or 'Held'
        
        // Get submission
        $sub = supabase_request(
            'semester_submissions?id=eq.' . urlencode($submission_id) . '&select=*',
            'GET', null, $token
        );
        
        if (empty($sub['data'])) {
            echo json_encode(['error' => 'Submission not found']);
            break;
        }
        
        $submission = $sub['data'][0];
        
        // Must be verified first
        if ($submission['verification_status'] !== 'Verified') {
            echo json_encode(['error' => 'Cannot make renewal decision. Submission must be verified first.']);
            break;
        }
        
        // Must be evaluated first
        if (empty($submission['compliance_status'])) {
            echo json_encode(['error' => 'Cannot make renewal decision. Compliance must be evaluated first.']);
            break;
        }
        
        // Cannot approve if deficient
        if ($decision === 'Approved' && $submission['compliance_status'] === 'With Deficiency') {
            echo json_encode(['error' => 'Cannot approve renewal for a submission with deficiency. Only "Held" is allowed.']);
            break;
        }
        
        $updateData = [
            'renewal_decision' => $decision,
            'renewal_notes' => $data['notes'] ?? null,
            'decided_by' => $user_id,
            'decided_at' => date('c')
        ];
        
        $result = supabase_request(
            'semester_submissions?id=eq.' . urlencode($submission_id),
            'PATCH', $updateData, $token
        );
        
        $prevDecision = $submission['renewal_decision'] ?? 'None';
        log_audit($submission_id, 'Renewal Decision: ' . $decision, $user_id, $_SESSION['user_name'],
            'Decision: ' . $decision . '. Notes: ' . ($data['notes'] ?? 'None'),
            $token, $prevDecision, $decision);
        
        echo json_encode(['success' => true]);
        break;

    // ─── AUDIT LOG ───
    case 'get_audit_log':
        $submission_id = $_GET['submission_id'] ?? '';
        
        if ($user_role === 'scholar') {
            // Verify the submission belongs to this scholar
            $subCheck = supabase_request(
                'semester_submissions?id=eq.' . urlencode($submission_id) . '&scholar_id=eq.' . $user_id,
                'GET', null, $token
            );
            if (empty($subCheck['data'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied.']);
                break;
            }
        }
        
        $result = supabase_request(
            'audit_log?submission_id=eq.' . urlencode($submission_id) . '&order=created_at.desc&select=*',
            'GET', null, $token
        );
        echo json_encode($result['data'] ?? []);
        break;

    // ─── DASHBOARD STATS (Staff only) ───
    case 'get_dashboard_stats':
        enforce_role('staff');
        
        $semester = $_GET['semester'] ?? '1';
        $academic_year = $_GET['academic_year'] ?? '2026-2027';
        
        // Total scholars
        $scholars = supabase_request('user_profiles?role=eq.scholar&select=id', 'GET', null, $token);
        $total_scholars = count($scholars['data'] ?? []);
        
        // Active assignments
        $assignments = supabase_request('scholar_assignments?status=eq.Active&select=id', 'GET', null, $token);
        $total_assigned = count($assignments['data'] ?? []);
        
        // Submissions for the selected period
        $subs = supabase_request(
            'semester_submissions?academic_year=eq.' . urlencode($academic_year) . '&semester=eq.' . $semester . '&select=*',
            'GET', null, $token
        );
        $submissions = $subs['data'] ?? [];
        
        $pending = 0; $verified = 0; $returned = 0;
        $compliant = 0; $deficient = 0; $unevaluated = 0;
        $approved = 0; $held = 0;
        
        foreach ($submissions as $s) {
            if ($s['verification_status'] === 'Pending') $pending++;
            if ($s['verification_status'] === 'Verified') $verified++;
            if ($s['verification_status'] === 'Returned') $returned++;
            if ($s['compliance_status'] === 'Compliant') $compliant++;
            if ($s['compliance_status'] === 'With Deficiency') $deficient++;
            if (empty($s['compliance_status'])) $unevaluated++;
            if ($s['renewal_decision'] === 'Approved') $approved++;
            if ($s['renewal_decision'] === 'Held') $held++;
        }
        
        echo json_encode([
            'total_scholars' => $total_scholars,
            'total_assigned' => $total_assigned,
            'total_submissions' => count($submissions),
            'pending_verification' => $pending,
            'verified' => $verified,
            'returned' => $returned,
            'compliant' => $compliant,
            'with_deficiency' => $deficient,
            'unevaluated' => $unevaluated,
            'approved' => $approved,
            'held' => $held
        ]);
        break;

    // ─── SEARCH ───
    case 'search_scholars':
        enforce_role('staff');
        $q = $_GET['q'] ?? '';
        
        if (strlen($q) < 2) {
            echo json_encode([]);
            break;
        }
        
        // Search by student_id or name (using ilike)
        $result = supabase_request(
            'user_profiles?role=eq.scholar&or=(student_id.ilike.*' . urlencode($q) . '*,full_name.ilike.*' . urlencode($q) . '*)&select=*,scholar_assignments(*,scholarship_programs(*))',
            'GET', null, $token
        );
        echo json_encode($result['data'] ?? []);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
        break;
}

// ─── Helper Functions ───

function enforce_role($required_role) {
    if ($_SESSION['user_role'] !== $required_role) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Required role: ' . $required_role]);
        exit;
    }
}

function log_audit($submission_id, $action, $actor_id, $actor_name, $details, $token, $prev = null, $new = null) {
    $logData = [
        'submission_id' => $submission_id,
        'action' => $action,
        'actor_id' => $actor_id,
        'actor_name' => $actor_name,
        'details' => $details,
        'previous_value' => $prev,
        'new_value' => $new
    ];
    
    supabase_request('audit_log', 'POST', $logData, $token);
}
