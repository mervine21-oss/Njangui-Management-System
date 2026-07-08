<?php
require_once __DIR__ . '/config/database.php';

try {
  // Check if table exists and show structure
  $result = $pdo->query('DESCRIBE group_messages')->fetchAll();
  echo '<pre>';
  echo "Table group_messages structure:\n";
  print_r($result);
  
  // Check message count
  $count = $pdo->query('SELECT COUNT(*) as cnt FROM group_messages')->fetch();
  echo "\nTotal messages: " . $count['cnt'];
  
  // Show sample message
  $sample = $pdo->query('SELECT * FROM group_messages LIMIT 1')->fetch();
  echo "\nSample message:\n";
  print_r($sample);
  
  echo '</pre>';
} catch (Exception $e) {
  echo 'Error: ' . $e->getMessage();
}
