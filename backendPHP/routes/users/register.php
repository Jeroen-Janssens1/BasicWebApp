<?php
require_once __DIR__ . '/../../db.php';

function api_response_register(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  api_response_register(405, ['error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
$data = $raw !== false && trim($raw) !== '' ? json_decode($raw, true) : [];
if (!is_array($data)) {
  api_response_register(400, ['error' => 'Invalid JSON body']);
}

$username = trim((string) ($data['username'] ?? ''));
$password = (string) ($data['password'] ?? '');

if ($username === '' || $password === '') {
  api_response_register(400, ['error' => 'Username and password are required']);
}

if (strlen($username) > 255) {
  api_response_register(400, ['error' => 'Username is too long']);
}

try {
  $pdo = db();
  $check = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
  $check->execute([':username' => $username]);

  if ($check->fetch() !== false) {
    api_response_register(409, ['error' => 'Username already exists']);
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $insert = $pdo->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
  $insert->execute([
    ':username' => $username,
    ':password' => $hash,
  ]);

  api_response_register(201, [
    'message' => 'User created successfully',
    'user' => ['username' => $username],
  ]);
} catch (Throwable $e) {
  api_response_register(500, ['error' => 'Failed to create user account']);
}
