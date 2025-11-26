<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\BaseController;
use App\Models\ClassePlayer;
use PDO;
use Exception;

class PlayerController extends BaseController
{
    // Propriété de cache 
    protected ?PDO $db = null;

    protected function getDb(): PDO
    {
        // Réutilise la connexion
        if (property_exists($this, 'db') && $this->db instanceof PDO) {
            return $this->db;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $db   = getenv('DB_NAME') ?: 'memory_game';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            // Met en cache la connexion pour les appels suivants
            $this->db = $pdo;
            return $pdo;
        } catch (\Throwable $e) {
            throw new \Exception('Impossible de se connecter à la base de données : ' . $e->getMessage());
        }
    }

    // Affiche la page listant les joueurs
    public function index(): void
    {
        $players = [];
        try {
            $pdo = $this->getDb();
            $stmt = $pdo->query("SELECT id, username as name FROM users ORDER BY username ASC");
            $rows = $stmt->fetchAll();
            foreach ($rows as $r) {
                $players[] = ClassePlayer::fromArray($r);
            }
        } catch (Exception $e) {
            // silence: la vue peut afficher une erreur si besoin
        }

        $data = [
            'title' => 'Joueurs',
            'players' => $players,
        ];

        $this->render('player/index', $data);
    }

    // API: GET
    public function apiIndex(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = $this->getDb();
            $stmt = $pdo->query("SELECT id, username as name FROM users ORDER BY username ASC");
            $rows = $stmt->fetchAll();
            $out = array_map(fn($r) => ClassePlayer::fromArray($r)->toArray(), $rows);
            echo json_encode(['success' => true, 'data' => $out], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // API: GET 
    public function show($id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = $this->getDb();
            $stmt = $pdo->prepare("SELECT id, username as name FROM users WHERE id = :id");
            $stmt->execute([':id' => (int)$id]);
            $row = $stmt->fetch();
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Joueur non trouvé']);
                return;
            }
            $player = ClassePlayer::fromArray($row);
            echo json_encode(['success' => true, 'data' => $player->toArray()], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // API
    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $input = $_POST;
        $raw = file_get_contents('php://input');
        if ($raw && empty($_POST)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $input = $decoded;
        }

        $name = trim((string)($input['name'] ?? $input['username'] ?? ''));
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Le nom du joueur est requis']);
            return;
        }

        try {
            $pdo = $this->getDb();
            $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (:username)");
            $stmt->execute([':username' => $name]);
            $id = (int)$pdo->lastInsertId();
            $stmt2 = $pdo->prepare("SELECT id, username as name FROM users WHERE id = :id");
            $stmt2->execute([':id' => $id]);
            $row = $stmt2->fetch();
            $player = $row ? ClassePlayer::fromArray($row) : null;

            http_response_code(201); // Created
            echo json_encode(['success' => true, 'data' => $player ? $player->toArray() : null], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            // Gestion basique des doublons (unique)
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // API: PUT/PATCH
    public function update($id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $input = [];
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $input = $decoded;
        }
        $name = isset($input['name']) ? trim((string)$input['name']) : null;
        if ($name === null || $name === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Le nom du joueur est requis']);
            return;
        }

        try {
            $pdo = $this->getDb();
            $stmt = $pdo->prepare("UPDATE users SET username = :username WHERE id = :id");
            $stmt->execute([':username' => $name, ':id' => (int)$id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Joueur non trouvé ou aucune modification']);
                return;
            }

            // Retourne le joueur 
            $this->show($id);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // API: DELETE /players
    public function delete($id): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $pdo = $this->getDb();
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => (int)$id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Joueur non trouvé']);
                return;
            }

            echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
