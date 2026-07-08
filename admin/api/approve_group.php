<?php
<?php
header('Content-Type: application/json');
http_response_code(410);
echo json_encode(['error' => 'Approval workflow disabled']);
exit;
