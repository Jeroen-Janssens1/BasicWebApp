<?php
require_once __DIR__ . '/../../db.php';

function api_response_movie_ratings(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  api_response_movie_ratings(405, ['error' => 'Method not allowed']);
}

$movieId = null;
if (isset($_GET['id'])) {
  $movieId = (int) $_GET['id'];
}

if ($movieId === null || $movieId <= 0) {
  api_response_movie_ratings(400, ['error' => 'Movie id is required']);
}

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT u.username, r.score FROM ratings r INNER JOIN users u ON u.id = r.user_id WHERE r.movie_id = :movie_id ORDER BY u.username ASC');
  $stmt->execute([':movie_id' => $movieId]);
  $ratings = $stmt->fetchAll();

  api_response_movie_ratings(200, ['movie_id' => $movieId, 'ratings' => $ratings]);
} catch (Throwable $e) {
  api_response_movie_ratings(500, ['error' => 'Failed to fetch user scores for movie']);
}
