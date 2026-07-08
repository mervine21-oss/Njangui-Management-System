<?php
// Email helper that uses PHPMailer (if available via composer) for SMTP,
// otherwise falls back to PHP mail() and always logs to storage/emails.log
function send_email(string $to, string $subject, string $body, array $opts = []): bool {
  $config = [];
  $cfgFile = __DIR__ . '/../config/email.php';
  if (file_exists($cfgFile)) {
    $config = include $cfgFile;
  }

  $fromEmail = $opts['from'] ?? ($config['from_email'] ?? ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));
  $fromName = $opts['from_name'] ?? ($config['from_name'] ?? 'DigiTon');
  $replyTo = $opts['reply_to'] ?? null;

  $sent = false;

  // Helper to decrypt encrypted SMTP password if present. Expects EMAIL_SECRET env var.
  $decrypt_password = function($val) {
    if (!is_string($val)) return $val;
    if (strpos($val, 'ENC:') !== 0) return $val;
    $b = substr($val, 4);
    $raw = base64_decode($b);
    if ($raw === false || strlen($raw) < 16) return '';
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $key = getenv('EMAIL_SECRET') ?: null;
    if (!$key) return '';
    $decoded = @openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $decoded === false ? '' : $decoded;
  };

  // Prefer PHPMailer if composer autoload is available
  $autoload = __DIR__ . '/../vendor/autoload.php';
  if (file_exists($autoload)) {
    try {
      require_once $autoload;
      if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
          // SMTP config
          if (!empty($config['smtp']) && !empty($config['smtp']['enabled'])) {
            $mail->isSMTP();
            $mail->Host = $config['smtp']['host'];
            if (!empty($config['smtp']['auth'])) {
              $mail->SMTPAuth = true;
              $mail->Username = $config['smtp']['username'];
              // support encrypted password: store as ENC:<base64(iv|ciphertext)>
              $mail->Password = $decrypt_password($config['smtp']['password']);
            }
            $mail->SMTPSecure = $config['smtp']['secure'] ?? '';
            $mail->Port = $config['smtp']['port'] ?? 25;
          }
          $mail->setFrom($fromEmail, $fromName);
          $mail->addAddress($to);
          if ($replyTo) $mail->addReplyTo($replyTo);
          $mail->Subject = $subject;
          $mail->Body = $body;
          $mail->AltBody = strip_tags($body);
          $sent = (bool)$mail->send();
        } catch (Exception $e) {
          $sent = false;
        }
      }
    } catch (Throwable $e) {
      $sent = false;
    }
  }

  // Fallback to mail() if not sent
  if (!$sent) {
    $headers = "From: {$fromName} <{$fromEmail}>\r\n";
    if ($replyTo) $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    if (function_exists('mail')) {
      try { $sent = (bool) @mail($to, $subject, $body, $headers); } catch (Throwable $e) { $sent = false; }
    }
  }

  // Ensure storage exists and append to log for local debugging
  $logDir = __DIR__ . '/../storage';
  if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
  $logFile = $logDir . '/emails.log';
  $entry = '[' . date('Y-m-d H:i:s') . "] sent=" . ($sent ? '1' : '0') . " to={$to} subject={$subject}\nFrom: {$fromName} <{$fromEmail}>\n" . $body . "\n---\n";
  @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

  return $sent;
}
