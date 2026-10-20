<?php
/**
 * Setup & Seed Script
 * Creates test accounts and seeds 6 fictional scholars with semester submissions
 * 
 * Run this script once from the browser: http://localhost/SAD_Exam_Section_A_M-007823/setup/seed.php
 * 
 * TEST ACCOUNTS:
 * ─────────────────────────────────────────────────
 * STAFF ACCOUNT:
 *   Email:    staff@scholarship.edu
 *   Password: Staff@123
 * 
 * SCHOLAR ACCOUNTS:
 *   scholar.a01@scholarship.edu / Scholar@123  (Merit Scholar, A01)
 *   scholar.a02@scholarship.edu / Scholar@123  (Merit Scholar, A02)
 *   scholar.a03@scholarship.edu / Scholar@123  (Community Scholar, A03)
 *   scholar.a04@scholarship.edu / Scholar@123  (Community Scholar, A04)
 *   scholar.a05@scholarship.edu / Scholar@123  (Merit Scholar, A05)
 *   scholar.a06@scholarship.edu / Scholar@123  (Community Scholar, A06)
 * ─────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config.php';

// Increase execution time
set_time_limit(120);

$output = [];
$errors = [];

function log_msg($msg) {
    global $output;
    $output[] = $msg;
    echo "<p>$msg</p>";
    flush();
}

function log_err($msg) {
    global $errors;
    $errors[] = $msg;
    echo "<p style='color:red'>❌ $msg</p>";
    flush();
}

echo "<!DOCTYPE html><html><head><title>Seed Data Setup</title>
<style>body{font-family:monospace;background:#111;color:#eee;padding:2rem;max-width:900px;margin:auto}
p{margin:0.3rem 0}h1{color:#818cf8}h2{color:#a78bfa;margin-top:1.5rem}
.success{color:#10b981}.error{color:#ef4444}.info{color:#3b82f6}
</style></head><body>";

echo "<h1>🎓 Scholarship System - Seed Setup</h1>";
echo "<p class='info'>Starting seed process...</p><hr>";

// ─── Step 1: Register Auth Users ───
echo "<h2>Step 1: Creating Auth Users</h2>";

$users = [
    ['email' => 'staff@scholarship.edu', 'password' => 'Staff@123', 'role' => 'staff', 'name' => 'Admin Staff', 'student_id' => null],
    ['email' => 'scholar.a01@scholarship.edu', 'password' => 'Scholar@123', 'role' => 'scholar', 'name' => 'Maria Santos', 'student_id' => 'S-A01'],
    ['email' => 'scholar.a02@scholarship.edu', 'password' => 'Scholar@123', 'role' => 'scholar', 'name' => 'Juan Dela Cruz', 'student_id' => 'S-A02'],
    ['email' => 'scholar.a03@scholarship.edu', 'password' => 'Scholar@123', 'role' => 'scholar', 'name' => 'Ana Reyes', 'student_id' => 'S-A03'],
    ['email' => 'scholar.a04@scholarship.edu', 'password' => 'Scholar@123', 'role' => 'scholar', 'name' => 'Carlos Garcia', 'student_id' => 'S-A04'],
    ['email' => 'scholar.a05@scholarship.edu', 'password' => 'Scholar@123', 'role' => 'scholar', 'name' => 'Elena Mendoza', 'student_id' => 'S-A05'],
    ['email' => 'scholar.a06@scholarship.edu', 'password' => 'Scholar@123', 'role' => 'scholar', 'name' => 'Roberto Cruz', 'student_id' => 'S-A06'],
];

$profile_ids = [];

foreach ($users as $u) {
    // Create auth user
    $authResult = supabase_auth('signup', [
        'email' => $u['email'],
        'password' => $u['password']
    ]);
    
    $auth_id = $authResult['data']['user']['id'] ?? null;
    
    if ($authResult['status'] == 200 || $authResult['status'] == 201) {
        log_msg("✅ Auth user created: {$u['email']} (auth_id: $auth_id)");
    } else {
        $msg = $authResult['data']['msg'] ?? json_encode($authResult['data']);
        log_msg("⚠️ Auth user {$u['email']}: $msg (may already exist)");
    }
    
    // Create profile
    $profileData = [
        'auth_id' => $auth_id,
        'email' => $u['email'],
        'full_name' => $u['name'],
        'role' => $u['role'],
        'student_id' => $u['student_id']
    ];
    
    $profileResult = supabase_request('user_profiles', 'POST', $profileData);
    
    if ($profileResult['status'] == 201 || $profileResult['status'] == 200) {
        if (!empty($profileResult['data'][0]['id'])) {
            $profile_ids[$u['student_id'] ?? $u['role']] = $profileResult['data'][0]['id'];
            log_msg("✅ Profile created: {$u['name']} ({$u['role']}) ID: " . $profileResult['data'][0]['id']);
        } else {
            log_msg("✅ Profile insert attempted: {$u['name']}");
        }
    } else {
        $msg = json_encode($profileResult['data']);
        log_err("Profile creation issue for {$u['email']}: $msg");
        
        // Try to fetch existing profile
        $existing = supabase_request('user_profiles?email=eq.' . urlencode($u['email']) . '&select=id');
        if (!empty($existing['data'][0]['id'])) {
            $profile_ids[$u['student_id'] ?? $u['role']] = $existing['data'][0]['id'];
            log_msg("ℹ️ Using existing profile ID: " . $existing['data'][0]['id']);
        }
    }
    
    usleep(300000); // 300ms delay to avoid rate limiting
}

// ─── Step 2: Get Program IDs ───
echo "<h2>Step 2: Getting Scholarship Programs</h2>";

$programs = supabase_request('scholarship_programs?select=*');
$program_map = [];

if (!empty($programs['data'])) {
    foreach ($programs['data'] as $p) {
        $program_map[$p['program_name']] = $p['id'];
        log_msg("✅ Program found: {$p['program_name']} (ID: {$p['id']})");
    }
} else {
    log_err("No scholarship programs found! Run schema.sql first.");
}

// ─── Step 3: Get Staff ID ───
$staff_result = supabase_request('user_profiles?email=eq.staff@scholarship.edu&select=id');
$staff_id = $staff_result['data'][0]['id'] ?? null;

if (!$staff_id && isset($profile_ids['staff'])) {
    $staff_id = $profile_ids['staff'];
}

echo "<h2>Step 3: Assigning Scholars to Programs</h2>";

// Scholar-program assignments
$assignments = [
    ['student_id' => 'S-A01', 'program' => 'Merit Scholar'],
    ['student_id' => 'S-A02', 'program' => 'Merit Scholar'],
    ['student_id' => 'S-A03', 'program' => 'Community Scholar'],
    ['student_id' => 'S-A04', 'program' => 'Community Scholar'],
    ['student_id' => 'S-A05', 'program' => 'Merit Scholar'],
    ['student_id' => 'S-A06', 'program' => 'Community Scholar'],
];

$assignment_ids = [];

foreach ($assignments as $a) {
    $scholar_id = $profile_ids[$a['student_id']] ?? null;
    $program_id = $program_map[$a['program']] ?? null;
    
    if (!$scholar_id) {
        // Try lookup
        $lookup = supabase_request('user_profiles?student_id=eq.' . urlencode($a['student_id']) . '&select=id');
        $scholar_id = $lookup['data'][0]['id'] ?? null;
    }
    
    if (!$scholar_id || !$program_id) {
        log_err("Missing IDs for assignment: scholar={$a['student_id']} program={$a['program']}");
        continue;
    }
    
    $assignData = [
        'scholar_id' => $scholar_id,
        'program_id' => $program_id,
        'academic_year' => '2026-2027',
        'assigned_by' => $staff_id,
        'status' => 'Active'
    ];
    
    $result = supabase_request('scholar_assignments', 'POST', $assignData);
    
    if ($result['status'] == 201 || $result['status'] == 200) {
        $aid = $result['data'][0]['id'] ?? null;
        $assignment_ids[$a['student_id']] = $aid;
        log_msg("✅ Assigned: {$a['student_id']} → {$a['program']} (Assignment ID: $aid)");
    } else {
        log_err("Assignment failed for {$a['student_id']}: " . json_encode($result['data']));
        // Try to fetch existing
        $existing = supabase_request('scholar_assignments?scholar_id=eq.' . $scholar_id . '&academic_year=eq.2026-2027&select=id');
        if (!empty($existing['data'][0]['id'])) {
            $assignment_ids[$a['student_id']] = $existing['data'][0]['id'];
            log_msg("ℹ️ Using existing assignment: " . $existing['data'][0]['id']);
        }
    }
    
    usleep(200000);
}

// ─── Step 4: Seed Submissions ───
echo "<h2>Step 4: Seeding Test Submissions</h2>";

// Deadline: 2026-10-30 17:00:00 Asia/Manila
$deadline_str = '2026-10-30T17:00:00+08:00';
$one_day_before = '2026-10-29T17:00:00+08:00';
$at_deadline = '2026-10-30T17:00:00+08:00';
$one_min_late = '2026-10-30T17:01:00+08:00';

$submissions = [
    [
        'student_id' => 'S-A01', 'gwa' => 1.75, 'units' => 18, 'failed' => 0, 'inc' => 0, 'drop' => 0,
        'submitted_at' => $at_deadline, 'is_late' => false,
        'evidence' => 'OTR-2026-2027-S1-A01', 'note' => 'At deadline boundary'
    ],
    [
        'student_id' => 'S-A02', 'gwa' => 1.76, 'units' => 18, 'failed' => 0, 'inc' => 0, 'drop' => 0,
        'submitted_at' => $one_day_before, 'is_late' => false,
        'evidence' => 'OTR-2026-2027-S1-A02', 'note' => 'GWA above Merit limit'
    ],
    [
        'student_id' => 'S-A03', 'gwa' => 2.25, 'units' => 15, 'failed' => 0, 'inc' => 0, 'drop' => 1,
        'submitted_at' => $one_day_before, 'is_late' => false,
        'evidence' => 'OTR-2026-2027-S1-A03', 'note' => '1 drop allowed for Community'
    ],
    [
        'student_id' => 'S-A04', 'gwa' => 2.25, 'units' => 14, 'failed' => 0, 'inc' => 1, 'drop' => 0,
        'submitted_at' => $one_day_before, 'is_late' => false,
        'evidence' => 'OTR-2026-2027-S1-A04', 'note' => 'Multiple deficiencies: units + incomplete'
    ],
    [
        'student_id' => 'S-A05', 'gwa' => 1.50, 'units' => 18, 'failed' => 1, 'inc' => 0, 'drop' => 0,
        'submitted_at' => $one_min_late, 'is_late' => true,
        'evidence' => 'OTR-2026-2027-S1-A05', 'note' => 'Failed subject + 1 min late'
    ],
    [
        'student_id' => 'S-A06', 'gwa' => 2.00, 'units' => 15, 'failed' => 0, 'inc' => 0, 'drop' => 0,
        'submitted_at' => $one_day_before, 'is_late' => false,
        'evidence' => 'OTR-2026-2027-S1-A06', 'note' => 'Pending verification (no verify)'
    ],
];

foreach ($submissions as $sub) {
    $scholar_id = $profile_ids[$sub['student_id']] ?? null;
    $assignment_id = $assignment_ids[$sub['student_id']] ?? null;
    
    if (!$scholar_id) {
        $lookup = supabase_request('user_profiles?student_id=eq.' . urlencode($sub['student_id']) . '&select=id');
        $scholar_id = $lookup['data'][0]['id'] ?? null;
    }
    
    if (!$assignment_id) {
        $lookup = supabase_request('scholar_assignments?scholar_id=eq.' . $scholar_id . '&academic_year=eq.2026-2027&select=id');
        $assignment_id = $lookup['data'][0]['id'] ?? null;
    }
    
    if (!$scholar_id || !$assignment_id) {
        log_err("Missing IDs for submission: {$sub['student_id']}");
        continue;
    }
    
    // Set verification status: A06 stays Pending, others are Verified
    $verification_status = ($sub['student_id'] === 'S-A06') ? 'Pending' : 'Verified';
    $verified_by = ($verification_status === 'Verified') ? $staff_id : null;
    $verified_at = ($verification_status === 'Verified') ? date('c') : null;
    
    $subData = [
        'scholar_id' => $scholar_id,
        'assignment_id' => $assignment_id,
        'academic_year' => '2026-2027',
        'semester' => 1,
        'gwa' => $sub['gwa'],
        'enrolled_units' => $sub['units'],
        'failed_count' => $sub['failed'],
        'incomplete_count' => $sub['inc'],
        'dropped_count' => $sub['drop'],
        'evidence_reference' => $sub['evidence'],
        'submitted_at' => $sub['submitted_at'],
        'is_late' => $sub['is_late'],
        'verification_status' => $verification_status,
        'verified_by' => $verified_by,
        'verified_at' => $verified_at
    ];
    
    $result = supabase_request('semester_submissions', 'POST', $subData);
    
    if ($result['status'] == 201 || $result['status'] == 200) {
        $sid = $result['data'][0]['id'] ?? 'unknown';
        log_msg("✅ Submission seeded: {$sub['student_id']} GWA={$sub['gwa']} Units={$sub['units']} F={$sub['failed']} I={$sub['inc']} D={$sub['drop']} ({$sub['note']}) - Verification: $verification_status");
        
        // Log audit for verified submissions
        if ($verification_status === 'Verified' && $staff_id) {
            supabase_request('audit_log', 'POST', [
                'submission_id' => $sid,
                'action' => 'Verification: Verified',
                'actor_id' => $staff_id,
                'actor_name' => 'Admin Staff',
                'details' => 'Evidence verified during seed setup.',
                'new_value' => 'Verified'
            ]);
        }
    } else {
        log_err("Submission failed for {$sub['student_id']}: " . json_encode($result['data']));
    }
    
    usleep(200000);
}

// ─── Summary ───
echo "<hr><h2>✅ Seed Complete</h2>";
echo "<div style='background:#1e293b;padding:1.5rem;border-radius:12px;border:1px solid #334155'>";
echo "<h3 style='color:#818cf8;margin-top:0'>Test Accounts</h3>";
echo "<table style='width:100%;border-collapse:collapse'>";
echo "<tr style='border-bottom:1px solid #334155'><th style='text-align:left;padding:8px;color:#94a3b8'>Role</th><th style='text-align:left;padding:8px;color:#94a3b8'>Email</th><th style='text-align:left;padding:8px;color:#94a3b8'>Password</th><th style='text-align:left;padding:8px;color:#94a3b8'>Student ID</th></tr>";
echo "<tr style='border-bottom:1px solid #334155'><td style='padding:8px'><span style='background:#6366f1;padding:2px 8px;border-radius:20px;font-size:0.8rem'>Staff</span></td><td style='padding:8px'>staff@scholarship.edu</td><td style='padding:8px'>Staff@123</td><td style='padding:8px'>—</td></tr>";

$scholar_map = [
    ['S-A01', 'scholar.a01@scholarship.edu', 'Merit Scholar'],
    ['S-A02', 'scholar.a02@scholarship.edu', 'Merit Scholar'],
    ['S-A03', 'scholar.a03@scholarship.edu', 'Community Scholar'],
    ['S-A04', 'scholar.a04@scholarship.edu', 'Community Scholar'],
    ['S-A05', 'scholar.a05@scholarship.edu', 'Merit Scholar'],
    ['S-A06', 'scholar.a06@scholarship.edu', 'Community Scholar'],
];

foreach ($scholar_map as $s) {
    echo "<tr style='border-bottom:1px solid #334155'><td style='padding:8px'><span style='background:#10b981;padding:2px 8px;border-radius:20px;font-size:0.8rem'>Scholar</span> {$s[2]}</td><td style='padding:8px'>{$s[1]}</td><td style='padding:8px'>Scholar@123</td><td style='padding:8px'>{$s[0]}</td></tr>";
}

echo "</table>";
echo "</div>";

echo "<div style='background:#1e293b;padding:1.5rem;border-radius:12px;border:1px solid #334155;margin-top:1rem'>";
echo "<h3 style='color:#f59e0b;margin-top:0'>Expected Test Results</h3>";
echo "<table style='width:100%;border-collapse:collapse'>";
echo "<tr style='border-bottom:1px solid #334155'><th style='text-align:left;padding:8px;color:#94a3b8'>ID</th><th style='text-align:left;padding:8px;color:#94a3b8'>Program</th><th style='text-align:left;padding:8px;color:#94a3b8'>Expected Compliance</th><th style='text-align:left;padding:8px;color:#94a3b8'>Reason</th></tr>";
echo "<tr><td style='padding:8px'>A01</td><td style='padding:8px'>Merit</td><td style='padding:8px;color:#10b981'>Compliant</td><td style='padding:8px'>All checks pass at boundaries</td></tr>";
echo "<tr><td style='padding:8px'>A02</td><td style='padding:8px'>Merit</td><td style='padding:8px;color:#ef4444'>With Deficiency</td><td style='padding:8px'>GWA 1.76 > 1.75 limit</td></tr>";
echo "<tr><td style='padding:8px'>A03</td><td style='padding:8px'>Community</td><td style='padding:8px;color:#10b981'>Compliant</td><td style='padding:8px'>1 drop allowed by Community program</td></tr>";
echo "<tr><td style='padding:8px'>A04</td><td style='padding:8px'>Community</td><td style='padding:8px;color:#ef4444'>With Deficiency</td><td style='padding:8px'>Units 14 < 15 AND 1 incomplete > 0</td></tr>";
echo "<tr><td style='padding:8px'>A05</td><td style='padding:8px'>Merit</td><td style='padding:8px;color:#ef4444'>With Deficiency</td><td style='padding:8px'>1 failed > 0 AND late submission</td></tr>";
echo "<tr><td style='padding:8px'>A06</td><td style='padding:8px'>Community</td><td style='padding:8px;color:#f59e0b'>Blocked</td><td style='padding:8px'>Pending verification</td></tr>";
echo "</table></div>";

echo "<p class='info' style='margin-top:1rem'>✅ Setup complete! <a href='../login.php' style='color:#818cf8'>→ Go to Login</a></p>";
echo "</body></html>";
