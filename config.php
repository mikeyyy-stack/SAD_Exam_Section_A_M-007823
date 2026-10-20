<?php
/**
 * Supabase Configuration
 * Student Scholarship Monitoring and Academic Compliance System
 */

// Supabase connection settings
define('SUPABASE_URL', 'https://sfyukkrrnjbtpejegqdb.supabase.co');
define('SUPABASE_ANON_KEY', 'sb_publishable_uc8XWz93hsWbzetr_53tPg_8nww1MzB');

// Timezone
date_default_timezone_set('Asia/Manila');

// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Make a request to Supabase REST API
 */
function supabase_request($endpoint, $method = 'GET', $data = null, $token = null, $extra_headers = []) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    } else {
        $headers[] = 'Authorization: Bearer ' . SUPABASE_ANON_KEY;
    }
    
    foreach ($extra_headers as $h) {
        $headers[] = $h;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'status' => 0];
    }
    
    return ['data' => json_decode($response, true), 'status' => $http_code, 'raw' => $response];
}

/**
 * Supabase Auth request
 */
function supabase_auth($endpoint, $data) {
    $url = SUPABASE_URL . '/auth/v1/' . $endpoint;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['data' => json_decode($response, true), 'status' => $http_code];
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['access_token']) && isset($_SESSION['user']);
}

/**
 * Get current user role
 */
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user's access token
 */
function get_token() {
    return $_SESSION['access_token'] ?? null;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function require_auth() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require specific role
 */
function require_role($role) {
    require_auth();
    if (get_user_role() !== $role) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Required role: ' . $role]);
        exit;
    }
}
