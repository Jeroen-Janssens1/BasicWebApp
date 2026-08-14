<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

// Simple routing based on path and method
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// Expected patterns:
// /api/health
// /api/users
if ($path === '/health') {
  require __DIR__ . '/routes/health.php';
  exit;
}

http_response_code(404);
echo json_encode([
  'error' => 'Not found',
  'path' => $path,
  'uri' => $_SERVER['REQUEST_URI'],
  'method' => $method
]);