<?php
require_once __DIR__ . '/../../db.php';

function api_response_ratings(int $statusCode, array $payload): void {
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
  api_response_ratings(405, ['error' => 'Method not allowed']);
}

if (empty($_SESSION['user_id'])) {
  api_response_ratings(401, ['error' => 'Authentication required']);
}

$userId = (int) $_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = $raw !== false && trim($raw) !== '' ? json_decode($raw, true) : [];
if (!is_array($data)) {
  api_response_ratings(400, ['error' => 'Invalid JSON body']);
}

try {
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'GET'){
  $stmt = $pdo->prepare('SELECT m.id AS movie_id, m.movieName AS movie_name, r.score FROM ratings r INNER JOIN movies m ON m.id = r.movie_id WHERE r.user_id = :user_id ORDER BY m.movieName ASC');
  $stmt->execute([':user_id' => $userId]);
  $ratings = $stmt->fetchAll();

  api_response_ratings(200, ['ratings' => $ratings]);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieId = isset($data['movie_id']) ? (int) $data['movie_id'] : null;
    $score = isset($data['score']) ? (int) $data['score'] : null;

    if ($movieId === null || $movieId <= 0 || $score === null) {
      api_response_ratings(400, ['error' => 'movie_id and score are required']);
    }

    if ($score < 1 || $score > 5) {
      api_response_ratings(400, ['error' => 'Score must be between 1 and 5']);
    }

    $movieCheck = $pdo->prepare('SELECT id FROM movies WHERE id = :id LIMIT 1');
    $movieCheck->execute([':id' => $movieId]);
    if ($movieCheck->fetch() === false) {
      api_response_ratings(404, ['error' => 'Movie not found']);
    }

    $check = $pdo->prepare('SELECT score FROM ratings WHERE user_id = :user_id AND movie_id = :movie_id LIMIT 1');
    $check->execute([':user_id' => $userId, ':movie_id' => $movieId]);
    $existing = $check->fetch();

    if ($existing !== false) {
      api_response_ratings(409, ['error' => 'You have already rated this movie']);
    }

    $insert = $pdo->prepare('INSERT INTO ratings (user_id, movie_id, score) VALUES (:user_id, :movie_id, :score)');
    $insert->execute([
      ':user_id' => $userId,
      ':movie_id' => $movieId,
      ':score' => $score,
    ]);

    api_response_ratings(201, ['message' => 'Rating added successfully']);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $movieId = isset($data['movie_id']) ? (int) $data['movie_id'] : null;
    $score = isset($data['score']) ? (int) $data['score'] : null;

    if ($movieId === null || $movieId <= 0 || $score === null) {
      api_response_ratings(400, ['error' => 'movie_id and score are required']);
    }

    if ($score < 1 || $score > 5) {
      api_response_ratings(400, ['error' => 'Score must be between 1 and 5']);
    }

    $existing = $pdo->prepare('SELECT score FROM ratings WHERE user_id = :user_id AND movie_id = :movie_id LIMIT 1');
    $existing->execute([':user_id' => $userId, ':movie_id' => $movieId]);
    $rating = $existing->fetch();

    if ($rating === false) {
      api_response_ratings(404, ['error' => 'Rating not found']);
    }

    $update = $pdo->prepare('UPDATE ratings SET score = :score WHERE user_id = :user_id AND movie_id = :movie_id');
    $update->execute([
      ':score' => $score,
      ':user_id' => $userId,
      ':movie_id' => $movieId,
    ]);

    api_response_ratings(200, ['message' => 'Rating updated successfully']);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $movieId = isset($data['movie_id']) ? (int) $data['movie_id'] : null;
    if ($movieId === null || $movieId <= 0) {
      api_response_ratings(400, ['error' => 'movie_id is required']);
    }

    $delete = $pdo->prepare('DELETE FROM ratings WHERE user_id = :user_id AND movie_id = :movie_id');
    $delete->execute([
      ':user_id' => $userId,
      ':movie_id' => $movieId,
    ]);

    if ($delete->rowCount() === 0) {
      api_response_ratings(404, ['error' => 'Rating not found']);
    }

    api_response_ratings(200, ['message' => 'Rating removed successfully']);
  }
} catch (Throwable $e) {
  api_response_ratings(500, ['error' => 'Failed to process rating request']);
}