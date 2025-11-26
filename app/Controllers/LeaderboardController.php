<?php

namespace App\Controllers;

use App\Models\ClasseLeaderboard;
use PDO;

class LeaderboardController
{
    /** @var ClasseLeaderboard */
    protected $leaderboard;

    /** @param PDO $pdo */
    public function __construct(PDO $pdo)
    {
        $this->leaderboard = new ClasseLeaderboard($pdo);
    }

    /**
     * Affiche la page du leaderboard (HTML)
     * Vous pouvez adapter le chemin de la vue selon votre projet.
     */
    public function index(): void
    {
        $title = 'Classement';
        $top = $this->leaderboard->getTop(50);
        include __DIR__ . '/../Views/score/index.php';
    }

    /**
     * Retourne le top N en JSON (ex: GET /leaderboard/top?limit=20)
     */
    public function topJson(): void
    {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $limit = max(1, min(500, $limit));
        $top = $this->leaderboard->getTop($limit);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $top], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Enregistre un score via formulaire/API (facultatif si déjà géré ailleurs)
     * Méthode d'exemple réutilisant le modèle ; attend POST JSON.
     */
    public function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $data = [
            'player_name'  => $input['player_name'] ?? null,
            'score'        => isset($input['score']) ? (int)$input['score'] : 0,
            'moves'        => isset($input['moves']) ? (int)$input['moves'] : 0,
            'time_seconds' => isset($input['time_seconds']) ? (int)$input['time_seconds'] : 0,
            'user_id'      => isset($input['user_id']) ? (int)$input['user_id'] : null,
            'game_id'      => isset($input['game_id']) ? (int)$input['game_id'] : null,
        ];

        $id = $this->leaderboard->save($data);
        header('Content-Type: application/json; charset=utf-8');
        if ($id) {
            echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Impossible d\'enregistrer le score'], JSON_UNESCAPED_UNICODE);
        }
    }
}
