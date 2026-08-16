<?php

$sessionIdleTimeout = 60 * 60;
$sessionAbsoluteLifetime = 24 * 60 * 60;

function destroy_session_and_clear_cookie(): void {
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
}

function enforce_session_policy(int $idleTimeout = 3600, int $absoluteLifetime = 86400): bool {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    return false;
  }

  $now = time();
  $sessionStartedAt = isset($_SESSION['session_started_at']) ? (int) $_SESSION['session_started_at'] : $now;
  $lastActivityAt = isset($_SESSION['last_activity_at']) ? (int) $_SESSION['last_activity_at'] : $now;
  $absoluteDeadline = isset($_SESSION['session_absolute_deadline']) ? (int) $_SESSION['session_absolute_deadline'] : $sessionStartedAt + $absoluteLifetime;

  $idleExpired = ($now - $lastActivityAt) > $idleTimeout;
  $absoluteExpired = $now > $absoluteDeadline;

  if ($idleExpired || $absoluteExpired) {
    destroy_session_and_clear_cookie();
    return false;
  }

  $_SESSION['session_started_at'] = $sessionStartedAt;
  $_SESSION['last_activity_at'] = $now;
  $_SESSION['session_absolute_deadline'] = $absoluteDeadline;

  return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
session_set_cookie_params([
  'lifetime' => $sessionIdleTimeout,
  'path' => '/',
  'domain' => '',
  'secure' => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_name('basicwebapp_session');
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

enforce_session_policy($sessionIdleTimeout, $sessionAbsoluteLifetime);

// Simple routing based on path and method
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = preg_replace('#/+#', '/', $path);

$routeMap = [
  '/health' => __DIR__ . '/routes/health.php',
  '/api/health' => __DIR__ . '/routes/health.php',
  '/register' => __DIR__ . '/routes/register.php',
  '/api/register' => __DIR__ . '/routes/register.php',
  '/login' => __DIR__ . '/routes/login.php',
  '/api/login' => __DIR__ . '/routes/login.php',
  '/logout' => __DIR__ . '/routes/logout.php',
  '/api/logout' => __DIR__ . '/routes/logout.php',
  '/user' => __DIR__ . '/routes/user.php',
  '/api/user' => __DIR__ . '/routes/user.php',
  '/users' => __DIR__ . '/routes/users.php',
  '/api/users' => __DIR__ . '/routes/users.php',
];

if (isset($routeMap[$path])) {
  require $routeMap[$path];
  exit;
}

http_response_code(404);
echo json_encode([
  'error' => 'Not found',
  'path' => $path,
  'uri' => $_SERVER['REQUEST_URI'],
  'method' => $method
]);