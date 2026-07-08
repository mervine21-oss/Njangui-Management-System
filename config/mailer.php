<?php
// Simple simulated mailer for DigiTon.
// In production, replace this with an SMTP / transactional email provider.

define('MAIL_FROM_ADDRESS', 'no-reply@digiton.local');
define('MAIL_FROM_NAME', 'DigiTon Support');

function send_mail_simulated(string $to, string $subject, string $body): bool
{
    $logEntry = "[DigiTon Email Simulation] " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "To: $to\n";
    $logEntry .= "Subject: $subject\n";
    $logEntry .= "Body:\n$body\n";
    $logEntry .= str_repeat('-', 80) . "\n";
    error_log($logEntry);
    return true;
}

function send_invite_email(string $to, string $subject, string $body): bool
{
    return send_mail_simulated($to, $subject, $body);
}

function send_password_reset_email(string $to, string $name, string $resetLink): bool
{
    $subject = 'DigiTon Password Reset Request';
    $body = "<h2>Hello " . htmlspecialchars($name, ENT_QUOTES) . "</h2>";
    $body .= "<p>We received a request to reset your DigiTon password.</p>";
    $body .= "<p>Click the link below to choose a new password:</p>";
    $body .= "<p><a href='" . htmlspecialchars($resetLink, ENT_QUOTES) . "'>Reset your password</a></p>";
    $body .= "<p>If you did not request this, you can safely ignore this email.</p>";
    $body .= "<p>Link expires in 1 hour.</p>";

    return send_mail_simulated($to, $subject, $body);
}
