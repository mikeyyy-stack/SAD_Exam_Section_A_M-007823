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
    <meta name="description" content="Staff Dashboard - Scholarship Monitoring System">
    <title>Staff Dashboard | Scholarship Monitor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-container">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-inner">
            <div class="navbar-brand">
                <div class="brand-icon">🎓</div>
                <span>Scholarship Monitor</span>
            </div>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="scholars.php">👥 Scholars</a></li>
                <li><a href="submissions.php">📋 Submissions</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>📊 Staff Dashboard</h1>
                <p>Scholarship monitoring overview and analytics</p>
            </div>
            <div class="d-flex gap-1 align-center">
                <select id="filterYear" class="form-control" style="max-width:180px">
                    <option value="2026-2027" selected>AY 2026-2027</option>
                    <option value="2025-2026">AY 2025-2026</option>
                </select>
                <select id="filterSemester" class="form-control" style="max-width:150px">
                    <option value="1" selected>Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="3">Summer</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="loadDashboard()">🔄 Refresh</button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card primary">
                <div class="stat-icon">👥</div>
                <div class="stat-value" id="statAssigned">-</div>
                <div class="stat-label">Total Assigned Scholars</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">⏳</div>
                <div class="stat-value" id="statPending">-</div>
                <div class="stat-label">Pending Verification</div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">✅</div>
                <div class="stat-value" id="statCompliant">-</div>
                <div class="stat-label">Compliant</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value" id="statDeficient">-</div>
                <div class="stat-label">With Deficiency</div>
            </div>
        </div>

        <!-- Additional Stats -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-icon">📋</div>
                <div class="stat-value" id="statSubmissions">-</div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">✔️</div>
                <div class="stat-value" id="statVerified">-</div>
                <div class="stat-label">Verified</div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">🎉</div>
                <div class="stat-value" id="statApproved">-</div>
                <div class="stat-label">Renewals Approved</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">🔒</div>
                <div class="stat-value" id="statHeld">-</div>
                <div class="stat-label">Renewals Held</div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="card mt-2">
            <div class="card-header">
                <h3>🔍 Quick Search</h3>
            </div>
            <div class="filter-bar">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by Student ID or Name..." style="max-width:300px">
                <select id="searchProgram" class="form-control" style="max-width:200px">
                    <option value="">All Programs</option>
                </select>
                <select id="searchCompliance" class="form-control" style="max-width:200px">
                    <option value="">All Compliance</option>
                    <option value="Compliant">Compliant</option>
                    <option value="With Deficiency">With Deficiency</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="searchScholars()">Search</button>
            </div>
            <div id="searchResults"></div>
        </div>

        <!-- Recent Submissions -->
        <div class="card mt-2">
            <div class="card-header">
                <h3>📋 Recent Submissions</h3>
                <a href="submissions.php" class="btn btn-outline btn-sm">View All →</a>
            </div>
            <div class="table-container" id="recentSubmissions">
                <div class="empty-state">
                    <div class="spinner"></div>
                    <p class="mt-1">Loading...</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const API_BASE = '../api/handler.php';

async function apiCall(action, params = {}) {
    const url = new URL(API_BASE, window.location.href);
    url.searchParams.set('action', action);
    for (const [k, v] of Object.entries(params)) {
        url.searchParams.set(k, v);
    }
    const res = await fetch(url);
    return res.json();
}

async function loadDashboard() {
    const year = document.getElementById('filterYear').value;
    const sem = document.getElementById('filterSemester').value;
    
    try {
        const stats = await apiCall('get_dashboard_stats', { academic_year: year, semester: sem });
        
        document.getElementById('statAssigned').textContent = stats.total_assigned ?? 0;
        document.getElementById('statPending').textContent = stats.pending_verification ?? 0;
        document.getElementById('statCompliant').textContent = stats.compliant ?? 0;
        document.getElementById('statDeficient').textContent = stats.with_deficiency ?? 0;
        document.getElementById('statSubmissions').textContent = stats.total_submissions ?? 0;
        document.getElementById('statVerified').textContent = stats.verified ?? 0;
        document.getElementById('statApproved').textContent = stats.approved ?? 0;
        document.getElementById('statHeld').textContent = stats.held ?? 0;
    } catch (e) {
        console.error('Failed to load dashboard:', e);
    }
    
    loadRecentSubmissions();
}

async function loadRecentSubmissions() {
    try {
        const data = await apiCall('get_submissions');
        const container = document.getElementById('recentSubmissions');
        
        if (!data || data.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><h3>No submissions yet</h3><p>Submissions will appear here once scholars submit their grades.</p></div>';
            return;
        }
        
        // Show latest 10
        const recent = data.slice(0, 10);
        let html = '<table><thead><tr><th>Student</th><th>Program</th><th>AY / Sem</th><th>GWA</th><th>Verification</th><th>Compliance</th><th>Renewal</th><th>Actions</th></tr></thead><tbody>';
        
        recent.forEach(s => {
            const name = s.user_profiles ? s.user_profiles.full_name : 'Unknown';
            const sid = s.user_profiles ? s.user_profiles.student_id : '';
            const program = s.scholar_assignments?.scholarship_programs?.program_name ?? '';
            
            html += `<tr>
                <td><strong>${escHtml(name)}</strong><br><span class="text-muted" style="font-size:0.75rem">${escHtml(sid)}</span></td>
                <td><span class="badge badge-info">${escHtml(program)}</span></td>
                <td>${escHtml(s.academic_year)} / Sem ${s.semester}</td>
                <td><strong>${s.gwa}</strong></td>
                <td>${statusBadge(s.verification_status, 'verification')}</td>
                <td>${s.compliance_status ? statusBadge(s.compliance_status, 'compliance') : '<span class="text-muted">—</span>'}</td>
                <td>${s.renewal_decision ? statusBadge(s.renewal_decision, 'renewal') : '<span class="text-muted">—</span>'}</td>
                <td><a href="submissions.php?view=${s.id}" class="btn btn-outline btn-sm">View</a></td>
            </tr>`;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (e) {
        console.error('Failed to load submissions:', e);
    }
}

async function loadPrograms() {
    const programs = await apiCall('get_programs');
    const sel = document.getElementById('searchProgram');
    programs.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.program_name;
        sel.appendChild(opt);
    });
}

async function searchScholars() {
    const q = document.getElementById('searchInput').value;
    const container = document.getElementById('searchResults');
    
    if (q.length < 2) {
        container.innerHTML = '<div class="alert alert-warning">Please enter at least 2 characters to search.</div>';
        return;
    }
    
    const data = await apiCall('search_scholars', { q });
    
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>No scholars found matching "' + escHtml(q) + '"</p></div>';
        return;
    }
    
    let html = '<div class="table-container mt-1"><table><thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Programs</th><th>Actions</th></tr></thead><tbody>';
    
    data.forEach(s => {
        const programs = (s.scholar_assignments || []).map(a => a.scholarship_programs?.program_name ?? '').join(', ');
        html += `<tr>
            <td>${escHtml(s.student_id || '')}</td>
            <td><strong>${escHtml(s.full_name)}</strong></td>
            <td>${escHtml(s.email)}</td>
            <td>${escHtml(programs) || '<span class="text-muted">None</span>'}</td>
            <td><a href="scholars.php?view=${s.id}" class="btn btn-outline btn-sm">View</a></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function statusBadge(status, type) {
    const classes = {
        'Pending': 'badge-pending',
        'Verified': 'badge-verified',
        'Returned': 'badge-returned',
        'Compliant': 'badge-compliant',
        'With Deficiency': 'badge-deficiency',
        'Approved': 'badge-approved',
        'Held': 'badge-held'
    };
    return `<span class="badge ${classes[status] || 'badge-info'}">${escHtml(status)}</span>`;
}

function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Initialize
loadDashboard();
loadPrograms();

// Auto-refresh
document.getElementById('filterYear').addEventListener('change', loadDashboard);
document.getElementById('filterSemester').addEventListener('change', loadDashboard);

// Search on Enter
document.getElementById('searchInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') searchScholars();
});
</script>
</body>
</html>
