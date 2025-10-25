<?php
// Secure Mail Handler - Only accessible from server-side calls
// This script processes form data and calls the actual mail script

// Security: Check for secret token (hidden from attackers)
$secret_token = 'minimal_secure_' . date('Y-m-d'); // Changes daily
$provided_token = $_POST['_token'] ?? '';

// Check if request has valid secret token
if ($provided_token !== $secret_token) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// Get form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

// Input validation and sanitization
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

// Rate limiting (simple session-based)
session_start();
$ip = $_SERVER['REMOTE_ADDR'];
$key = 'mail_rate_' . $ip;
$last_sent = $_SESSION[$key] ?? 0;

if (time() - $last_sent < 60) { // 1 minute cooldown
    http_response_code(429);
    echo "Please wait before sending another message.";
    exit;
}
$_SESSION[$key] = time();

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
