<?php
require_once __DIR__ . '/../../db.php';

function api_response_movies(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  api_response_movies(405, ['error' => 'Method not allowed']);
}

try {
  $pdo = db();
  $stmt = $pdo->query('SELECT id, movieName AS name, movieDescription AS description, avg_rating, ratings_count FROM movies ORDER BY movieName ASC');
  $movies = $stmt->fetchAll();

  api_response_movies(200, ['movies' => $movies]);
} catch (Throwable $e) {
  api_response_movies(500, ['error' => 'Failed to fetch movies']);
}
