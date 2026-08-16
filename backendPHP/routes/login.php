<?php
require_once __DIR__ . '/../db.php';

function api_response_login(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  api_response_login(405, ['error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = $raw !== false && trim($raw) !== '' ? json_decode($raw, true) : [];
if (!is_array($data)) {
  api_response_login(400, ['error' => 'Invalid JSON body']);
}

$username = trim((string) ($data['username'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($username === '' || $password === '') {
  api_response_login(400, ['error' => 'Username and password are required']);
}

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = :username LIMIT 1');
  $stmt->execute([':username' => $username]);
  $user = $stmt->fetch();

  if ($user === false || !password_verify($password, $user['password'])) {
    api_response_login(401, ['error' => 'Invalid username or password']);
  }

  session_regenerate_id(true);
  $now = time();
  $_SESSION['user_id'] = (int) $user['id'];
  $_SESSION['username'] = $user['username'];
  $_SESSION['logged_in'] = true;
  $_SESSION['session_started_at'] = $now;
  $_SESSION['last_activity_at'] = $now;
  $_SESSION['session_absolute_deadline'] = strtotime('tomorrow 00:00:00');

  api_response_login(200, [
    'message' => 'Login successful',
    'user' => ['username' => $user['username']],
  ]);
} catch (Throwable $e) {
  api_response_login(500, ['error' => 'Login failed']);
}
