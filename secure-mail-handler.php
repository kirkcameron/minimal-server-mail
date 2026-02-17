<?php
// Secure Mail Handler - Hardened version
// This script processes form data and calls the actual mail script

// Security: Configure allowed origins (comma-separated domains)
$allowed_origins = ['kraemer.co.at'];

// Start session
session_start();

// Check Origin header to prevent CSRF
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Validate origin
$origin_valid = false;
if ($origin) {
    foreach ($allowed_origins as $allowed) {
        if (stripos($origin, $allowed) !== false) {
            $origin_valid = true;
            break;
        }
    }
}

// Also check referer as backup
if (!$origin_valid && $referer) {
    foreach ($allowed_origins as $allowed) {
        if (stripos($referer, $allowed) !== false) {
            $origin_valid = true;
            break;
        }
    }
}

// For development/local, you might want to skip this check
// Remove this in production if you get false positives
$dev_mode = false; // Set to true only for testing

if (!$origin_valid && !$dev_mode) {
    http_response_code(403);
    echo "Access denied - invalid origin.";
    exit;
}

// Generate random token if not exists
if (!isset($_SESSION['mail_token'])) {
    $_SESSION['mail_token'] = bin2hex(random_bytes(32));
}

// Handle GET request - return token
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo $_SESSION['mail_token'];
    exit;
}

// Handle POST request
$provided_token = $_POST['_token'] ?? '';
$session_token = $_SESSION['mail_token'] ?? '';

// Validate token
if (!hash_equals($session_token, $provided_token)) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// Honeypot check - hidden field that should remain empty
$honeypot = $_POST['website'] ?? '';
if (!empty($honeypot)) {
    // Bot detected - silently reject
    http_response_code(200);
    echo "Message sent successfully!"; // Fake success to confuse bots
    exit;
}

// Get form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

// Input validation
if (!$name || !$email || !$message) {
    http_response_code(400);
    echo "All fields are required.";
    exit;
}

// Length limits
if (strlen($name) > 100 || strlen($email) > 100 || strlen($message) > 1000) {
    http_response_code(400);
    echo "Input too long.";
    exit;
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "Invalid email format.";
    exit;
}

// Sanitize inputs
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Prevent header injection
$name = str_replace(["\r", "\n"], "", $name);
$email = str_replace(["\r", "\n"], "", $email);

// Rate limiting - IP + Session combined
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip_key = 'mail_rate_ip_' . md5($ip);
$session_key = 'mail_rate_session';

// Check IP-based rate limit (strict)
$ip_last = $_SESSION[$ip_key] ?? 0;
if (time() - $ip_last < 60) { // 1 minute per IP
    http_response_code(429);
    echo "Please wait before sending another message.";
    exit;
}

// Update rate limit timestamps
$_SESSION[$ip_key] = time();
$_SESSION[$session_key] = time();

// Load configuration
require_once 'config.php';

// Send email
$to = $config['to_email'];
$subject = 'Message from ' . $name . ' via webform';
$body = "Name: $name\nEmail: $email\nMessage: $message";
$headers = "From: " . $config['from_email'] . "\r\nReply-To: $email\r\nX-Mailer: PHP/" . phpversion();

if (mail($to, $subject, $body, $headers)) {
    echo "Message sent successfully!";
} else {
    http_response_code(500);
    echo "Failed to send message.";
}
?>
