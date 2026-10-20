<?php
require_once __DIR__ . '/../config.php';
require_auth();

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
    <title>Submit Grades | Scholarship Monitor</title>
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
                <li><a href="dashboard.php">📊 My Dashboard</a></li>
                <li><a href="submit.php" class="active">📝 Submit Grades</a></li>
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
                <h1>📝 Submit Semester Grades</h1>
                <p>Submit your academic summary and grade document reference</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
        </div>

        <div class="card" style="max-width:700px">
            <div class="card-header">
                <h3>📄 Grade Submission Form</h3>
            </div>
            
            <div id="formMsg"></div>
            
            <form id="submitForm" onsubmit="submitGrades(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Academic Year</label>
                        <select name="academic_year" class="form-control" required>
                            <option value="2026-2027" selected>2026-2027</option>
                            <option value="2025-2026">2025-2026</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-control" required>
                            <option value="1" selected>1st Semester</option>
                            <option value="2">2nd Semester</option>
                            <option value="3">Summer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">General Weighted Average (GWA)</label>
                    <input type="number" name="gwa" class="form-control" required 
                           min="1.00" max="5.00" step="0.01" placeholder="e.g. 1.75">
                    <div class="form-text">Scale: 1.00 (best) to 5.00 (failing). 3.00 is passing.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Enrolled Units</label>
                        <input type="number" name="enrolled_units" class="form-control" required 
                               min="0" step="1" placeholder="e.g. 18">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Failed Subjects</label>
                        <input type="number" name="failed_count" class="form-control" required 
                               min="0" step="1" value="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Incomplete Grades</label>
                        <input type="number" name="incomplete_count" class="form-control" required 
                               min="0" step="1" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dropped Subjects</label>
                        <input type="number" name="dropped_count" class="form-control" required 
                               min="0" step="1" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Grade Document Reference</label>
                    <input type="text" name="evidence_reference" class="form-control" required 
                           placeholder="e.g. Official Transcript of Records - 2026-2027-S1">
                    <div class="form-text">Reference to supporting grade document (e.g., transcript or grade report).</div>
                </div>

                <div class="alert alert-info">
                    <span>ℹ️</span>
                    <span>Submission deadline: <strong>October 30, 2026 at 5:00 PM (Manila Time)</strong>. Submissions after this time will be marked as late.</span>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full" id="submitBtn">
                    📤 Submit Academic Summary
                </button>
            </form>
        </div>
    </main>
</div>

<script>
const API_BASE = '../api/handler.php';

async function submitGrades(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('submitBtn');
    const msg = document.getElementById('formMsg');
    
    // Client-side validation
    const gwa = parseFloat(form.gwa.value);
    const units = parseInt(form.enrolled_units.value);
    const failed = parseInt(form.failed_count.value);
    const incomplete = parseInt(form.incomplete_count.value);
    const dropped = parseInt(form.dropped_count.value);
    
    if (gwa < 1.00 || gwa > 5.00) {
        msg.innerHTML = '<div class="alert alert-danger">⚠️ GWA must be between 1.00 and 5.00</div>';
        return;
    }
    if (units < 0 || failed < 0 || incomplete < 0 || dropped < 0) {
        msg.innerHTML = '<div class="alert alert-danger">⚠️ Counts and units must be non-negative integers</div>';
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Submitting...';
    
    const data = {
        academic_year: form.academic_year.value,
        semester: form.semester.value,
        gwa: gwa,
        enrolled_units: units,
        failed_count: failed,
        incomplete_count: incomplete,
        dropped_count: dropped,
        evidence_reference: form.evidence_reference.value
    };
    
    try {
        const url = new URL(API_BASE, window.location.href);
        url.searchParams.set('action', 'submit_grades');
        
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            msg.innerHTML = '<div class="alert alert-success">✅ Grades submitted successfully! Redirecting to dashboard...</div>';
            setTimeout(() => window.location.href = 'dashboard.php', 2000);
        } else {
            msg.innerHTML = `<div class="alert alert-danger">⚠️ ${escHtml(result.error || 'Submission failed')}</div>`;
        }
    } catch (err) {
        msg.innerHTML = `<div class="alert alert-danger">⚠️ Network error: ${err.message}</div>`;
    }
    
    btn.disabled = false;
    btn.innerHTML = '📤 Submit Academic Summary';
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>
</body>
</html>
