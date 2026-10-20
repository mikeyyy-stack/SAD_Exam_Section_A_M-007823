<?php
require_once __DIR__ . '/../config.php';
require_auth();
require_role('staff');
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions Management | Scholarship Monitor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-container">
    <nav class="navbar">
        <div class="navbar-inner">
            <div class="navbar-brand">
                <div class="brand-icon">🎓</div>
                <span>Scholarship Monitor</span>
            </div>
            <ul class="navbar-nav">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="scholars.php">👥 Scholars</a></li>
                <li><a href="submissions.php" class="active">📋 Submissions</a></li>
            </ul>
            <div class="navbar-user">
                <div class="user-badge">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <span class="role-tag staff">Staff</span>
                </div>
                <a href="../logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <!-- List View -->
        <div id="listView">
            <div class="page-header">
                <div>
                    <h1>📋 Submissions</h1>
                    <p>Review, verify, evaluate compliance, and manage renewal decisions</p>
                </div>
            </div>

            <div class="filter-bar">
                <input type="text" id="filterSearch" class="form-control" placeholder="Search by name or ID...">
                <select id="filterProgram" class="form-control">
                    <option value="">All Programs</option>
                </select>
                <select id="filterVerification" class="form-control">
                    <option value="">All Verification</option>
                    <option value="Pending">Pending</option>
                    <option value="Verified">Verified</option>
                    <option value="Returned">Returned</option>
                </select>
                <select id="filterCompliance" class="form-control">
                    <option value="">All Compliance</option>
                    <option value="Compliant">Compliant</option>
                    <option value="With Deficiency">With Deficiency</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadSubmissions()">🔍 Filter</button>
            </div>

            <div class="card">
                <div id="submissionsTable">
                    <div class="empty-state"><div class="spinner"></div><p class="mt-1">Loading...</p></div>
                </div>
            </div>
        </div>

        <!-- Detail View -->
        <div id="detailView" class="hidden">
            <div class="page-header">
                <div>
                    <h1 id="detailTitle">📋 Submission Detail</h1>
                    <p id="detailSubtitle"></p>
                </div>
                <button class="btn btn-outline" onclick="showListView()">← Back to List</button>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
                <!-- Left Column -->
                <div>
                    <!-- Academic Summary -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3>📊 Academic Summary</h3>
                            <span id="detailLateTag"></span>
                        </div>
                        <div id="detailAcademic"></div>
                    </div>

                    <!-- Compliance Checks -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3>📋 Policy Compliance</h3>
                            <span id="detailComplianceBadge"></span>
                        </div>
                        <div id="detailCompliance"></div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Verification -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3>✔️ Verification</h3>
                            <span id="detailVerifyBadge"></span>
                        </div>
                        <div id="detailVerification"></div>
                    </div>

                    <!-- Renewal Decision -->
                    <div class="card mb-2">
                        <div class="card-header">
                            <h3>🎓 Renewal Decision</h3>
                            <span id="detailRenewalBadge"></span>
                        </div>
                        <div id="detailRenewal"></div>
                    </div>

                    <!-- Audit History -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📝 Audit History</h3>
                        </div>
                        <div id="detailAudit"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const API_BASE = '../api/handler.php';
let allSubmissions = [];
let currentSubmission = null;

async function apiCall(action, params = {}) {
    const url = new URL(API_BASE, window.location.href);
    url.searchParams.set('action', action);
    for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);
    return (await fetch(url)).json();
}

async function apiPost(action, data) {
    const url = new URL(API_BASE, window.location.href);
    url.searchParams.set('action', action);
    return (await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })).json();
}

async function loadSubmissions() {
    const search = document.getElementById('filterSearch').value.toLowerCase();
    const program = document.getElementById('filterProgram').value;
    const verification = document.getElementById('filterVerification').value;
    const compliance = document.getElementById('filterCompliance').value;
    
    const params = {};
    if (program) params.program = program;
    if (verification) params.verification = verification;
    if (compliance) params.compliance = compliance;
    
    const data = await apiCall('get_submissions', params);
    allSubmissions = data || [];
    
    // Client-side search filter
    let filtered = allSubmissions;
    if (search) {
        filtered = filtered.filter(s => {
            const name = (s.user_profiles?.full_name || '').toLowerCase();
            const sid = (s.user_profiles?.student_id || '').toLowerCase();
            return name.includes(search) || sid.includes(search);
        });
    }
    
    renderSubmissions(filtered);
}

function renderSubmissions(data) {
    const container = document.getElementById('submissionsTable');
    
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><h3>No submissions found</h3><p>Try adjusting your filters.</p></div>';
        return;
    }
    
    let html = '<div class="table-container"><table><thead><tr><th>Student</th><th>Program</th><th>AY / Sem</th><th>GWA</th><th>Units</th><th>F/I/D</th><th>Evidence</th><th>Submitted</th><th>Verification</th><th>Compliance</th><th>Renewal</th><th>Actions</th></tr></thead><tbody>';
    
    data.forEach(s => {
        const name = s.user_profiles?.full_name ?? 'Unknown';
        const sid = s.user_profiles?.student_id ?? '';
        const program = s.scholar_assignments?.scholarship_programs?.program_name ?? '';
        const submitted = new Date(s.submitted_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        
        html += `<tr>
            <td><strong>${esc(name)}</strong><br><span class="text-muted" style="font-size:0.7rem">${esc(sid)}</span></td>
            <td><span class="badge badge-info">${esc(program)}</span></td>
            <td>${esc(s.academic_year)}<br>Sem ${s.semester}</td>
            <td><strong>${s.gwa}</strong></td>
            <td>${s.enrolled_units}</td>
            <td>${s.failed_count}/${s.incomplete_count}/${s.dropped_count}</td>
            <td>${s.evidence_reference ? '<span class="badge badge-verified" title="'+esc(s.evidence_reference)+'">📄 Yes</span>' : '<span class="badge badge-returned">None</span>'}</td>
            <td><span style="font-size:0.75rem">${submitted}</span>${s.is_late ? '<br><span class="badge badge-returned">LATE</span>' : ''}</td>
            <td>${statusBadge(s.verification_status)}</td>
            <td>${s.compliance_status ? statusBadge(s.compliance_status) : '<span class="text-muted">—</span>'}</td>
            <td>${s.renewal_decision ? statusBadge(s.renewal_decision) : '<span class="text-muted">—</span>'}</td>
            <td><button class="btn btn-primary btn-sm" onclick="viewSubmission('${s.id}')">Review</button></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

async function viewSubmission(id) {
    const sub = await apiCall('get_submission', { id });
    if (sub.error) {
        alert(sub.error);
        return;
    }
    currentSubmission = sub;
    renderDetail(sub);
    
    document.getElementById('listView').classList.add('hidden');
    document.getElementById('detailView').classList.remove('hidden');
}

function renderDetail(s) {
    const name = s.user_profiles?.full_name ?? 'Unknown';
    const sid = s.user_profiles?.student_id ?? '';
    const program = s.scholar_assignments?.scholarship_programs ?? {};
    
    document.getElementById('detailTitle').textContent = `📋 ${name} (${sid})`;
    document.getElementById('detailSubtitle').textContent = `${program.program_name ?? ''} | ${s.academic_year} Semester ${s.semester}`;
    
    // Late tag
    document.getElementById('detailLateTag').innerHTML = s.is_late ? '<span class="badge badge-returned">⏰ LATE SUBMISSION</span>' : '<span class="badge badge-verified">✅ On Time</span>';
    
    // Academic summary
    document.getElementById('detailAcademic').innerHTML = `
        <div class="compliance-check"><span class="check-label">GWA</span><span class="check-value">${s.gwa}</span></div>
        <div class="compliance-check"><span class="check-label">Enrolled Units</span><span class="check-value">${s.enrolled_units}</span></div>
        <div class="compliance-check"><span class="check-label">Failed Subjects</span><span class="check-value">${s.failed_count}</span></div>
        <div class="compliance-check"><span class="check-label">Incomplete Grades</span><span class="check-value">${s.incomplete_count}</span></div>
        <div class="compliance-check"><span class="check-label">Dropped Subjects</span><span class="check-value">${s.dropped_count}</span></div>
        <div class="compliance-check"><span class="check-label">Evidence Reference</span><span class="check-value">${esc(s.evidence_reference || 'None')}</span></div>
        <div class="compliance-check"><span class="check-label">Submitted At</span><span class="check-value" style="font-size:0.8rem">${new Date(s.submitted_at).toLocaleString('en-PH')}</span></div>
        <div class="compliance-check"><span class="check-label">Program Limits</span><span class="check-value" style="font-size:0.75rem">Max GWA: ${program.max_gwa} | Min Units: ${program.min_units} | F:${program.allowed_failed} I:${program.allowed_incomplete} D:${program.allowed_dropped}</span></div>
    `;
    
    // Verification section
    renderVerification(s);
    
    // Compliance section
    renderCompliance(s);
    
    // Renewal section
    renderRenewal(s);
    
    // Audit log
    loadAudit(s.id);
}

function renderVerification(s) {
    document.getElementById('detailVerifyBadge').innerHTML = statusBadge(s.verification_status);
    
    let html = '';
    if (s.verification_status === 'Pending' || s.verification_status === 'Returned') {
        html = `
            <p class="text-secondary mb-2">Review the evidence reference and mark verification status:</p>
            <div class="form-group">
                <label class="form-label">Verification Notes</label>
                <textarea id="verifyNotes" class="form-control" placeholder="Enter verification notes or reason for return...">${esc(s.verification_notes || '')}</textarea>
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-success" onclick="verifySubmission('Verified')">✅ Verify</button>
                <button class="btn btn-danger" onclick="verifySubmission('Returned')">↩️ Return</button>
            </div>
        `;
    } else {
        html = `
            <div class="compliance-check"><span class="check-label">Status</span><span class="check-value">${statusBadge(s.verification_status)}</span></div>
            <div class="compliance-check"><span class="check-label">Notes</span><span class="check-value">${esc(s.verification_notes || 'None')}</span></div>
            <div class="compliance-check"><span class="check-label">Verified At</span><span class="check-value" style="font-size:0.8rem">${s.verified_at ? new Date(s.verified_at).toLocaleString('en-PH') : 'N/A'}</span></div>
            <div class="mt-2">
                <button class="btn btn-warning btn-sm" onclick="resetVerification()">↩️ Re-verify / Return</button>
            </div>
        `;
    }
    document.getElementById('detailVerification').innerHTML = html;
}

function renderCompliance(s) {
    const program = s.scholar_assignments?.scholarship_programs ?? {};
    
    if (s.compliance_status) {
        document.getElementById('detailComplianceBadge').innerHTML = statusBadge(s.compliance_status);
        
        // Show detailed checks
        const checks = [
            { label: 'GWA ≤ ' + program.max_gwa, passed: parseFloat(s.gwa) <= parseFloat(program.max_gwa), value: s.gwa },
            { label: 'Units ≥ ' + program.min_units, passed: parseInt(s.enrolled_units) >= parseInt(program.min_units), value: s.enrolled_units },
            { label: 'Failed ≤ ' + program.allowed_failed, passed: parseInt(s.failed_count) <= parseInt(program.allowed_failed), value: s.failed_count },
            { label: 'Incomplete ≤ ' + program.allowed_incomplete, passed: parseInt(s.incomplete_count) <= parseInt(program.allowed_incomplete), value: s.incomplete_count },
            { label: 'Dropped ≤ ' + program.allowed_dropped, passed: parseInt(s.dropped_count) <= parseInt(program.allowed_dropped), value: s.dropped_count },
            { label: 'Submitted on time', passed: !s.is_late, value: s.is_late ? 'Late' : 'On time' }
        ];
        
        let html = '';
        checks.forEach(c => {
            html += `<div class="compliance-check">
                <div class="check-icon ${c.passed ? 'pass' : 'fail'}">${c.passed ? '✓' : '✕'}</div>
                <span class="check-label">${c.label}</span>
                <span class="check-value ${c.passed ? 'text-success' : 'text-danger'}">${c.value}</span>
            </div>`;
        });
        
        if (s.compliance_reasons) {
            html += `<div class="mt-2 alert ${s.compliance_status === 'Compliant' ? 'alert-success' : 'alert-danger'}">
                <span>${s.compliance_status === 'Compliant' ? '✅' : '⚠️'}</span>
                <span>${esc(s.compliance_reasons)}</span>
            </div>`;
        }
        
        document.getElementById('detailCompliance').innerHTML = html;
    } else {
        document.getElementById('detailComplianceBadge').innerHTML = '<span class="badge badge-pending">Not Evaluated</span>';
        
        if (s.verification_status === 'Verified') {
            document.getElementById('detailCompliance').innerHTML = `
                <p class="text-secondary mb-2">Submission is verified. Click to evaluate compliance against program policy.</p>
                <button class="btn btn-primary" onclick="evaluateCompliance()">📋 Evaluate Compliance</button>
            `;
        } else {
            document.getElementById('detailCompliance').innerHTML = `
                <div class="alert alert-warning"><span>⏳</span><span>Submission must be verified before compliance evaluation.</span></div>
            `;
        }
    }
}

function renderRenewal(s) {
    if (s.renewal_decision) {
        document.getElementById('detailRenewalBadge').innerHTML = statusBadge(s.renewal_decision);
        document.getElementById('detailRenewal').innerHTML = `
            <div class="compliance-check"><span class="check-label">Decision</span><span class="check-value">${statusBadge(s.renewal_decision)}</span></div>
            <div class="compliance-check"><span class="check-label">Notes</span><span class="check-value">${esc(s.renewal_notes || 'None')}</span></div>
            <div class="compliance-check"><span class="check-label">Decided At</span><span class="check-value" style="font-size:0.8rem">${s.decided_at ? new Date(s.decided_at).toLocaleString('en-PH') : 'N/A'}</span></div>
        `;
    } else {
        document.getElementById('detailRenewalBadge').innerHTML = '<span class="badge badge-pending">Pending</span>';
        
        if (s.verification_status !== 'Verified') {
            document.getElementById('detailRenewal').innerHTML = `
                <div class="alert alert-warning"><span>⏳</span><span>Submission must be verified before renewal decision.</span></div>
            `;
        } else if (!s.compliance_status) {
            document.getElementById('detailRenewal').innerHTML = `
                <div class="alert alert-warning"><span>⏳</span><span>Compliance must be evaluated before renewal decision.</span></div>
            `;
        } else {
            const canApprove = s.compliance_status === 'Compliant';
            document.getElementById('detailRenewal').innerHTML = `
                <div class="form-group">
                    <label class="form-label">Decision Notes</label>
                    <textarea id="renewalNotes" class="form-control" placeholder="Enter decision notes..."></textarea>
                </div>
                <div class="d-flex gap-1">
                    ${canApprove ? '<button class="btn btn-success" onclick="makeRenewalDecision(\'Approved\')">✅ Approve Renewal</button>' : '<div class="alert alert-danger mb-1" style="margin:0"><span>⚠️</span><span>Cannot approve: submission has deficiency.</span></div>'}
                    <button class="btn btn-warning" onclick="makeRenewalDecision('Held')">🔒 Hold Renewal</button>
                </div>
            `;
        }
    }
}

async function verifySubmission(status) {
    const notes = document.getElementById('verifyNotes')?.value || '';
    
    if (status === 'Returned' && !notes.trim()) {
        alert('Please provide a reason for returning the submission.');
        return;
    }
    
    const result = await apiPost('verify_submission', {
        submission_id: currentSubmission.id,
        status: status,
        notes: notes
    });
    
    if (result.success) {
        viewSubmission(currentSubmission.id);
    } else {
        alert(result.error || 'Failed to verify');
    }
}

function resetVerification() {
    currentSubmission.verification_status = 'Pending';
    renderVerification(currentSubmission);
}

async function evaluateCompliance() {
    const result = await apiPost('evaluate_compliance', {
        submission_id: currentSubmission.id
    });
    
    if (result.success || result.compliance_status) {
        viewSubmission(currentSubmission.id);
    } else {
        alert(result.error || 'Failed to evaluate');
    }
}

async function makeRenewalDecision(decision) {
    const notes = document.getElementById('renewalNotes')?.value || '';
    
    const result = await apiPost('renewal_decision', {
        submission_id: currentSubmission.id,
        decision: decision,
        notes: notes
    });
    
    if (result.success) {
        viewSubmission(currentSubmission.id);
    } else {
        alert(result.error || 'Failed to record decision');
    }
}

async function loadAudit(submissionId) {
    const data = await apiCall('get_audit_log', { submission_id: submissionId });
    const container = document.getElementById('detailAudit');
    
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state"><p class="text-muted">No audit records yet.</p></div>';
        return;
    }
    
    let html = '<div class="timeline">';
    data.forEach(a => {
        const time = new Date(a.created_at).toLocaleString('en-PH');
        html += `<div class="timeline-item">
            <div class="timeline-time">${time}</div>
            <div class="timeline-action"><strong>${esc(a.action)}</strong></div>
            <div class="timeline-detail">By: ${esc(a.actor_name || 'System')}</div>
            <div class="timeline-detail">${esc(a.details || '')}</div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function showListView() {
    document.getElementById('detailView').classList.add('hidden');
    document.getElementById('listView').classList.remove('hidden');
    loadSubmissions();
}

async function loadPrograms() {
    const programs = await apiCall('get_programs');
    const sel = document.getElementById('filterProgram');
    programs.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.program_name;
        sel.appendChild(opt);
    });
}

function statusBadge(status) {
    const cls = {
        'Pending': 'badge-pending',
        'Verified': 'badge-verified',
        'Returned': 'badge-returned',
        'Compliant': 'badge-compliant',
        'With Deficiency': 'badge-deficiency',
        'Approved': 'badge-approved',
        'Held': 'badge-held'
    };
    return `<span class="badge ${cls[status] || 'badge-info'}">${esc(status)}</span>`;
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// Check if URL has ?view= param
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('view')) {
    viewSubmission(urlParams.get('view'));
} else {
    loadSubmissions();
}
loadPrograms();

document.getElementById('filterSearch').addEventListener('keypress', e => {
    if (e.key === 'Enter') loadSubmissions();
});
</script>
</body>
</html>
