<?php
require_once '../config/database.php';

$db = Database::getInstance()->getConnection();
$result = $db->query('SELECT COUNT(*) FROM password_reset_tokens');
$count = $result->fetchColumn();
echo "Total tokens: $count\n\n";

$tokens = $db->query('SELECT token, user_id, expires_at, used FROM password_reset_tokens ORDER BY expires_at DESC LIMIT 5');
foreach ($tokens as $t) {
    $token = $t['token'];
    echo "Token: " . substr($token, 0, 15) . "..." . substr($token, -5) . "\n";
    echo "  User ID: " . $t['user_id'] . "\n";
    echo "  Used: " . ($t['used'] ? 'YES' : 'NO') . "\n";
    echo "  Expires: " . $t['expires_at'] . "\n";
    echo "  Valid Now: " . (strtotime($t['expires_at']) > time() ? 'YES' : 'NO') . "\n\n";
}
?>
