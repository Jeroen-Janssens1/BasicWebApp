<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
session_set_cookie_params([
  'lifetime' => 0,
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

// Simple routing based on path and method
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = preg_replace('#/+#', '/', $path);

$routeMap = [
  '/health' => __DIR__ . '/routes/health.php',
  '/register' => __DIR__ . '/routes/register.php',
  '/login' => __DIR__ . '/routes/login.php',
  '/logout' => __DIR__ . '/routes/logout.php',
  '/user' => __DIR__ . '/routes/user.php',
  '/users' => __DIR__ . '/routes/users.php',
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