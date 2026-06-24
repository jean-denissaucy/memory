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
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(200, (int)$_GET['limit'])) : 50;
        $offset = ($page - 1) * $limit;

        $total = 0;
        try {
            $total = $this->leaderboard->countAll();
            $top = $this->leaderboard->getTop($limit, $offset);
        } catch (\Exception $e) {
            $top = [];
        }

        $pages = $limit > 0 ? (int)ceil($total / $limit) : 1;
        include __DIR__ . '/../Views/score/index.php';
    }

    /**
     * Retourne le top N en JSON (ex: GET /leaderboard/top?limit=20)
     */
    public function topJson(): void
    {
        $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 50;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $total = $this->leaderboard->countAll();
        $top = $this->leaderboard->getTop($limit, $offset);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $top, 'meta' => ['total' => $total, 'page' => $page, 'pages' => $limit ? (int)ceil($total / $limit) : 1]], JSON_UNESCAPED_UNICODE);
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
