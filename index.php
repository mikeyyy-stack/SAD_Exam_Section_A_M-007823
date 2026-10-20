<?php
require_once 'config.php';

if (is_logged_in()) {
    $role = get_user_role();
    if ($role === 'staff') {
        header('Location: staff/dashboard.php');
    } else {
        header('Location: scholar/dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit;
