<?php
require_once __DIR__ . '/../db.php';

function api_response_user(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  api_response_user(405, ['error' => 'Method not allowed']);
}

if (empty($_SESSION['user_id'])) {
  api_response_user(401, ['error' => 'Authentication required']);
}

$userId = (int) $_SESSION['user_id'];

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT username FROM users WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $userId]);
  $user = $stmt->fetch();

  if ($user === false) {
    $_SESSION = [];
    session_destroy();
    api_response_user(404, ['error' => 'User not found']);
  }

  api_response_user(200, ['user' => ['username' => $user['username']]]);
} catch (Throwable $e) {
  api_response_user(500, ['error' => 'Failed to retrieve user information']);
}
