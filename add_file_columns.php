<?php
require_once __DIR__ . '/config/database.php';

try {
  echo '<pre>';
  
  // Add missing columns
  $pdo->exec('
    ALTER TABLE group_messages 
    ADD COLUMN file_path VARCHAR(255) NULL AFTER message,
    ADD COLUMN file_type ENUM("image","video","pdf","document","other") NULL AFTER file_path,
    ADD COLUMN file_name VARCHAR(255) NULL AFTER file_type
  ');
  
  echo "✅ Colonnes ajoutées avec succès!\n";
  
  // Verify
  $result = $pdo->query('DESCRIBE group_messages')->fetchAll();
  echo "\nStructure mise à jour:\n";
  foreach ($result as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
  }
  
  echo "\n✅ Prêt! Les messages s'afficheront maintenant.\n";
  echo '</pre>';
  
} catch (Exception $e) {
  echo '<pre>';
  echo 'Erreur: ' . $e->getMessage();
  echo '</pre>';
  error_log('Add columns error: ' . $e->getMessage());
}
