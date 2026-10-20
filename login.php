<?php
require_once 'config.php';

// If already logged in, redirect
if (is_logged_in()) {
    $role = get_user_role();
    if ($role === 'staff') {
        header('Location: staff/dashboard.php');
    } else {
        header('Location: scholar/dashboard.php');
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = supabase_auth('token?grant_type=password', [
            'email' => $email,
            'password' => $password
        ]);
        
        if ($result['status'] === 200 && isset($result['data']['access_token'])) {
            $_SESSION['access_token'] = $result['data']['access_token'];
            $_SESSION['auth_user'] = $result['data']['user'];
            
            // Get user profile
            $profile = supabase_request(
                'user_profiles?email=eq.' . urlencode($email) . '&select=*',
                'GET',
                null,
                $result['data']['access_token']
            );
            
            if ($profile['status'] === 200 && !empty($profile['data'])) {
                $_SESSION['user'] = $profile['data'][0];
                $_SESSION['user_role'] = $profile['data'][0]['role'];
                $_SESSION['user_id'] = $profile['data'][0]['id'];
                $_SESSION['user_name'] = $profile['data'][0]['full_name'];
                
                if ($profile['data'][0]['role'] === 'staff') {
                    header('Location: staff/dashboard.php');
                } else {
                    header('Location: scholar/dashboard.php');
                }
                exit;
            } else {
                $error = 'User profile not found. Please contact administrator.';
            }
        } else {
            $error = $result['data']['error_description'] ?? $result['data']['msg'] ?? 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Student Scholarship Monitoring and Academic Compliance System - Login">
    <title>Login | Scholarship Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="brand">
                <div class="brand-icon-lg">🎓</div>
                <h1>Scholarship Monitor</h1>
                <p>Academic Compliance System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-full btn-lg" id="loginBtn">
                    <span>Sign In</span>
                </button>
            </form>
            
            <div class="mt-3 text-center">
                <p class="text-muted" style="font-size: 0.8rem;">
                    Student Scholarship Monitoring &amp; Academic Compliance System<br>
                    SAD Laboratory Examination | Section A
                </p>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Signing in...';
        });
    </script>
</body>
</html>
