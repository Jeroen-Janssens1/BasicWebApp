<?php
require_once __DIR__ . '/../../db.php';

function api_response_movie(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  api_response_movie(405, ['error' => 'Method not allowed']);
}

$movieId = null;
if (isset($_GET['id'])) {
  $movieId = (int) $_GET['id'];
}

if ($movieId === null || $movieId <= 0) {
  api_response_movie(400, ['error' => 'Movie id is required']);
}

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT id, movieName AS name, movieDescription AS description, avg_rating, ratings_count FROM movies WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $movieId]);
  $movie = $stmt->fetch();

  if ($movie === false) {
    api_response_movie(404, ['error' => 'Movie not found']);
  }

  api_response_movie(200, ['movie' => $movie]);
} catch (Throwable $e) {
  api_response_movie(500, ['error' => 'Failed to fetch movie']);
}
