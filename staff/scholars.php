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
    <title>Scholar Management | Scholarship Monitor</title>
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
                <li><a href="scholars.php" class="active">👥 Scholars</a></li>
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

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>👥 Scholar Management</h1>
                <p>Register scholars and assign scholarship programs</p>
            </div>
            <div class="d-flex gap-1">
                <button class="btn btn-primary" onclick="showRegisterModal()">+ Register Scholar</button>
                <button class="btn btn-outline" onclick="showAssignModal()">🔗 Assign Program</button>
            </div>
        </div>

        <!-- Scholars Table -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Registered Scholars</h3>
                <button class="btn btn-outline btn-sm" onclick="loadScholars()">🔄 Refresh</button>
            </div>
            <div id="scholarsTable">
                <div class="empty-state"><div class="spinner"></div><p class="mt-1">Loading...</p></div>
            </div>
        </div>
    </main>
</div>

<!-- Register Scholar Modal -->
<div id="registerModal" class="modal-overlay hidden" onclick="if(event.target===this)closeModal('registerModal')">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Register New Scholar</h3>
            <button class="modal-close" onclick="closeModal('registerModal')">✕</button>
        </div>
        <form id="registerForm" onsubmit="registerScholar(event)">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required placeholder="e.g. Juan Dela Cruz">
            </div>
            <div class="form-group">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_id" class="form-control" required placeholder="e.g. S-A01">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required placeholder="e.g. scholar@example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6" placeholder="Min 6 characters">
            </div>
            <div id="registerMsg"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('registerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="registerBtn">Register Scholar</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Program Modal -->
<div id="assignModal" class="modal-overlay hidden" onclick="if(event.target===this)closeModal('assignModal')">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Scholarship Program</h3>
            <button class="modal-close" onclick="closeModal('assignModal')">✕</button>
        </div>
        <form id="assignForm" onsubmit="assignProgram(event)">
            <div class="form-group">
                <label class="form-label">Scholar</label>
                <select name="scholar_id" id="assignScholar" class="form-control" required>
                    <option value="">Select Scholar...</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Scholarship Program</label>
                <select name="program_id" id="assignProgram" class="form-control" required>
                    <option value="">Select Program...</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Academic Year</label>
                <select name="academic_year" class="form-control" required>
                    <option value="2026-2027" selected>2026-2027</option>
                    <option value="2025-2026">2025-2026</option>
                </select>
            </div>
            <div id="assignMsg"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('assignModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="assignBtn">Assign Program</button>
            </div>
        </form>
    </div>
</div>

<script>
const API_BASE = '../api/handler.php';

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

async function loadScholars() {
    const data = await apiCall('get_scholars');
    const container = document.getElementById('scholarsTable');
    
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">👥</div><h3>No scholars registered</h3><p>Click "Register Scholar" to add scholars to the system.</p></div>';
        return;
    }
    
    let html = '<div class="table-container"><table><thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Programs</th><th>Status</th></tr></thead><tbody>';
    
    data.forEach(s => {
        const assignments = s.scholar_assignments || [];
        const programList = assignments.map(a => {
            const pName = a.scholarship_programs?.program_name ?? 'Unknown';
            return `<span class="badge badge-active">${escHtml(pName)}</span>`;
        }).join(' ') || '<span class="text-muted">Not assigned</span>';
        
        html += `<tr>
            <td><strong>${escHtml(s.student_id || '')}</strong></td>
            <td>${escHtml(s.full_name)}</td>
            <td>${escHtml(s.email)}</td>
            <td>${programList}</td>
            <td><span class="badge badge-verified">Active</span></td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

async function loadPrograms() {
    const programs = await apiCall('get_programs');
    const sel = document.getElementById('assignProgram');
    sel.innerHTML = '<option value="">Select Program...</option>';
    programs.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = `${p.program_name} (Max GWA: ${p.max_gwa}, Min Units: ${p.min_units})`;
        sel.appendChild(opt);
    });
}

async function loadScholarOptions() {
    const scholars = await apiCall('get_all_scholars_simple');
    const sel = document.getElementById('assignScholar');
    sel.innerHTML = '<option value="">Select Scholar...</option>';
    (scholars || []).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.student_id || ''} - ${s.full_name}`;
        sel.appendChild(opt);
    });
}

function showRegisterModal() {
    document.getElementById('registerForm').reset();
    document.getElementById('registerMsg').innerHTML = '';
    document.getElementById('registerModal').classList.remove('hidden');
}

function showAssignModal() {
    loadScholarOptions();
    loadPrograms();
    document.getElementById('assignMsg').innerHTML = '';
    document.getElementById('assignModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

async function registerScholar(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('registerBtn');
    const msg = document.getElementById('registerMsg');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Registering...';
    
    const data = {
        full_name: form.full_name.value,
        student_id: form.student_id.value,
        email: form.email.value,
        password: form.password.value
    };
    
    const result = await apiPost('register_scholar', data);
    
    if (result.success) {
        msg.innerHTML = '<div class="alert alert-success">✅ Scholar registered successfully!</div>';
        loadScholars();
        setTimeout(() => closeModal('registerModal'), 1500);
    } else {
        msg.innerHTML = `<div class="alert alert-danger">⚠️ ${escHtml(result.error || 'Registration failed')}</div>`;
    }
    
    btn.disabled = false;
    btn.textContent = 'Register Scholar';
}

async function assignProgram(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('assignBtn');
    const msg = document.getElementById('assignMsg');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Assigning...';
    
    const data = {
        scholar_id: form.scholar_id.value,
        program_id: form.program_id.value,
        academic_year: form.academic_year.value
    };
    
    const result = await apiPost('assign_scholarship', data);
    
    if (result.success) {
        msg.innerHTML = '<div class="alert alert-success">✅ Program assigned successfully!</div>';
        loadScholars();
        setTimeout(() => closeModal('assignModal'), 1500);
    } else {
        msg.innerHTML = `<div class="alert alert-danger">⚠️ ${escHtml(result.error || 'Assignment failed')}</div>`;
    }
    
    btn.disabled = false;
    btn.textContent = 'Assign Program';
}

function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

loadScholars();
</script>
</body>
</html>
