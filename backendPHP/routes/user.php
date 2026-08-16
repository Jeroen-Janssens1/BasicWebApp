<?php
require_once __DIR__ . '/../db.php';

function api_response_user(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
  api_response_user(405, ['error' => 'Method not allowed']);
}

if (empty($_SESSION['user_id'])) {
  api_response_user(401, ['error' => 'Authentication required']);
}

$userId = (int) $_SESSION['user_id'];

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);

    if ($stmt->rowCount() === 0) {
      api_response_user(404, ['error' => 'User not found']);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
      );
    }
    session_destroy();

    api_response_user(200, ['message' => 'User account deleted successfully']);
  }

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
  api_response_user(500, ['error' => 'Failed to process user request']);
}
