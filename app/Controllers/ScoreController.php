<?php

namespace App\Controllers;

use App\Models\ClasseLeaderboard;
use PDO;
use Exception;

class ScoreController
{
    protected PDO $pdo;
    protected ClasseLeaderboard $leaderboard;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } else {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $db   = getenv('DB_NAME') ?: 'memory_game';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            $dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        $this->leaderboard = new ClasseLeaderboard($this->pdo);
    }

    public function index(): void
    {
        $title = 'Classement';
        try {
            $top = $this->leaderboard->getTop(50);
        } catch (Exception $e) {
            $top = [];
        }
        include __DIR__ . '/../Views/score/index.php';
    }

    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        if (!is_array($input) || !isset($input['score'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Payload JSON invalide ou score manquant']);
            return;
        }

        $data = [
            'player_name'  => !empty($input['player_name']) ? trim($input['player_name']) : null,
            'score'        => (int)($input['score'] ?? 0),
            'moves'        => (int)($input['moves'] ?? 0),
            'time_seconds' => (int)($input['time_seconds'] ?? 0),
            'user_id'      => isset($input['user_id']) ? (int)$input['user_id'] : null,
            'game_id'      => isset($input['game_id']) ? (int)$input['game_id'] : null,
        ];

        try {
            $id = $this->leaderboard->save($data);
            echo json_encode(['success' => true, 'id' => $id]);
            return;
        } catch (Exception $e) {
            $logDir = __DIR__ . '/../../runtime/logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            @file_put_contents($logDir . '/score_errors.log', "[" . date('c') . "] " . $e->getMessage() . " payload:" . json_encode($data) . PHP_EOL, FILE_APPEND);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Impossible d\'enregistrer le score']);
            return;
        }
    }
}
