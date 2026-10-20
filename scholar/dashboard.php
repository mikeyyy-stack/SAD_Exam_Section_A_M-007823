<?php
require_once __DIR__ . '/../config.php';
require_auth();

// Scholars only - enforce role at backend level
if (get_user_role() !== 'scholar') {
    header('Location: ../staff/dashboard.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholar Dashboard | Scholarship Monitor</title>
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
                <li><a href="dashboard.php" class="active">📊 My Dashboard</a></li>
                <li><a href="submit.php">📝 Submit Grades</a></li>
            </ul>
            <div class="navbar-user">
                <div class="user-badge">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <span class="role-tag scholar">Scholar</span>
                </div>
                <a href="../logout.php" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>📊 My Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>)</p>
            </div>
            <a href="submit.php" class="btn btn-primary">📝 Submit Grades</a>
        </div>

        <!-- My Submissions -->
        <div class="card">
            <div class="card-header">
                <h3>📋 My Submissions</h3>
                <button class="btn btn-outline btn-sm" onclick="loadMySubmissions()">🔄 Refresh</button>
            </div>
            <div id="submissionsTable">
                <div class="empty-state"><div class="spinner"></div><p class="mt-1">Loading...</p></div>
            </div>
        </div>

        <!-- Submission Detail Modal -->
        <div id="detailCard" class="card mt-2 hidden">
            <div class="card-header">
                <h3 id="detailTitle">📋 Submission Details</h3>
                <button class="btn btn-outline btn-sm" onclick="document.getElementById('detailCard').classList.add('hidden')">✕ Close</button>
            </div>
            <div id="detailContent"></div>
        </div>
    </main>
</div>

<script>
const API_BASE = '../api/handler.php';
const USER_ID = '<?php echo $_SESSION['user_id']; ?>';

async function apiCall(action, params = {}) {
    const url = new URL(API_BASE, window.location.href);
    url.searchParams.set('action', action);
    for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);
    return (await fetch(url)).json();
}

async function loadMySubmissions() {
    const data = await apiCall('get_submissions');
    const container = document.getElementById('submissionsTable');
    
    if (!data || data.error || data.length === 0) {
        container.innerHTML = `<div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No submissions yet</h3>
            <p>Submit your semester grades to get started.</p>
            <a href="submit.php" class="btn btn-primary mt-2">📝 Submit Grades</a>
        </div>`;
        return;
    }
    
    let html = '<div class="table-container"><table><thead><tr><th>AY / Semester</th><th>Program</th><th>GWA</th><th>Units</th><th>F/I/D</th><th>Verification</th><th>Compliance</th><th>Renewal</th><th>Submitted</th><th>Actions</th></tr></thead><tbody>';
    
    data.forEach(s => {
        const program = s.scholar_assignments?.scholarship_programs?.program_name ?? '';
        const submitted = new Date(s.submitted_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
        
        html += `<tr>
            <td><strong>${esc(s.academic_year)}</strong><br>Semester ${s.semester}</td>
            <td><span class="badge badge-info">${esc(program)}</span></td>
            <td><strong>${s.gwa}</strong></td>
            <td>${s.enrolled_units}</td>
            <td>${s.failed_count}/${s.incomplete_count}/${s.dropped_count}</td>
            <td>${statusBadge(s.verification_status)}</td>
            <td>${s.compliance_status ? statusBadge(s.compliance_status) : '<span class="text-muted">—</span>'}</td>
            <td>${s.renewal_decision ? statusBadge(s.renewal_decision) : '<span class="text-muted">—</span>'}</td>
            <td><span style="font-size:0.75rem">${submitted}</span>${s.is_late ? '<br><span class="badge badge-returned">LATE</span>' : ''}</td>
            <td><button class="btn btn-outline btn-sm" onclick="viewDetail('${s.id}')">View</button>
                ${s.verification_status === 'Returned' ? '<br><a href="submit.php?resubmit=' + s.id + '" class="btn btn-warning btn-sm mt-1">Resubmit</a>' : ''}
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

async function viewDetail(id) {
    const s = await apiCall('get_submission', { id });
    if (s.error) {
        alert(s.error);
        return;
    }
    
    const program = s.scholar_assignments?.scholarship_programs ?? {};
    const detailCard = document.getElementById('detailCard');
    detailCard.classList.remove('hidden');
    
    document.getElementById('detailTitle').textContent = `📋 ${s.academic_year} Semester ${s.semester}`;
    
    let html = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <div>
            <h4 class="mb-1" style="color:var(--text-secondary)">Academic Summary</h4>
            <div class="compliance-check"><span class="check-label">GWA</span><span class="check-value"><strong>${s.gwa}</strong></span></div>
            <div class="compliance-check"><span class="check-label">Enrolled Units</span><span class="check-value">${s.enrolled_units}</span></div>
            <div class="compliance-check"><span class="check-label">Failed Subjects</span><span class="check-value">${s.failed_count}</span></div>
            <div class="compliance-check"><span class="check-label">Incomplete</span><span class="check-value">${s.incomplete_count}</span></div>
            <div class="compliance-check"><span class="check-label">Dropped</span><span class="check-value">${s.dropped_count}</span></div>
            <div class="compliance-check"><span class="check-label">Evidence</span><span class="check-value">${esc(s.evidence_reference || 'None')}</span></div>
            <div class="compliance-check"><span class="check-label">Submitted</span><span class="check-value" style="font-size:0.8rem">${new Date(s.submitted_at).toLocaleString('en-PH')}</span></div>
        </div>
        <div>
            <h4 class="mb-1" style="color:var(--text-secondary)">Status</h4>
            <div class="compliance-check"><span class="check-label">Verification</span><span class="check-value">${statusBadge(s.verification_status)}</span></div>
            ${s.verification_notes ? '<div class="compliance-check"><span class="check-label">Notes</span><span class="check-value" style="font-size:0.8rem">' + esc(s.verification_notes) + '</span></div>' : ''}
            <div class="compliance-check"><span class="check-label">Compliance</span><span class="check-value">${s.compliance_status ? statusBadge(s.compliance_status) : '<span class="text-muted">Pending evaluation</span>'}</span></div>
            ${s.compliance_reasons ? '<div class="compliance-check"><span class="check-label">Reasons</span><span class="check-value" style="font-size:0.8rem">' + esc(s.compliance_reasons) + '</span></div>' : ''}
            <div class="compliance-check"><span class="check-label">Renewal</span><span class="check-value">${s.renewal_decision ? statusBadge(s.renewal_decision) : '<span class="text-muted">Pending decision</span>'}</span></div>
            ${s.renewal_notes ? '<div class="compliance-check"><span class="check-label">Decision Notes</span><span class="check-value" style="font-size:0.8rem">' + esc(s.renewal_notes) + '</span></div>' : ''}
        </div>
    </div>`;
    
    // Load audit
    const audit = await apiCall('get_audit_log', { submission_id: id });
    if (audit && audit.length > 0) {
        html += '<h4 class="mt-3 mb-1" style="color:var(--text-secondary)">History</h4><div class="timeline">';
        audit.forEach(a => {
            html += `<div class="timeline-item">
                <div class="timeline-time">${new Date(a.created_at).toLocaleString('en-PH')}</div>
                <div class="timeline-action"><strong>${esc(a.action)}</strong></div>
                <div class="timeline-detail">${esc(a.details || '')}</div>
            </div>`;
        });
        html += '</div>';
    }
    
    document.getElementById('detailContent').innerHTML = html;
    detailCard.scrollIntoView({ behavior: 'smooth' });
}

function statusBadge(status) {
    const cls = {
        'Pending': 'badge-pending', 'Verified': 'badge-verified', 'Returned': 'badge-returned',
        'Compliant': 'badge-compliant', 'With Deficiency': 'badge-deficiency',
        'Approved': 'badge-approved', 'Held': 'badge-held'
    };
    return `<span class="badge ${cls[status] || 'badge-info'}">${esc(status)}</span>`;
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

loadMySubmissions();
</script>
</body>
</html>
