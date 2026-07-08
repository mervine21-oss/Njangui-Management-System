<?php
// Email configuration for DigiTon — update with your SMTP details.
return [
  // SMTP transport settings. If you don't use SMTP, leave 'enabled' => false
  'smtp' => [
    'enabled' => false,
    'host' => 'smtp.example.com',
    'username' => 'smtp_user',
    'password' => 'smtp_password',
    'port' => 587,
    'secure' => 'tls', // '', 'ssl' or 'tls'
    'auth' => true,
  ],
  // Default from address
  'from_email' => 'noreply@digiton.local',
  'from_name' => 'DigiTon',
];
