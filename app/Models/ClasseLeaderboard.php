<?php

namespace App\Models;

use PDO;

/**
 * ClasseLeaderboard - accès simple aux scores / leaderboard
 */
class ClasseLeaderboard
{
    /** @var PDO */
    protected $pdo;

    /** @param PDO $pdo connexion à la base */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Récupère le classement (top N)
     * @param int $limit
     * @return array
     */
    public function getTop(int $limit = 50): array
    {
        $sql = "SELECT s.id, COALESCE(s.player_name,u.username) AS player, s.score, s.moves, s.time_seconds, s.created_at
		        FROM scores s
		        LEFT JOIN users u ON u.id = s.user_id
		        ORDER BY s.score DESC, s.time_seconds ASC, s.moves ASC
		        LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Sauvegarde un score
     * @param array $data keys: player_name, score, moves, time_seconds, user_id (opt), game_id (opt)
     * @return int id inséré
     */
    public function save(array $data): int
    {
        $sql = "INSERT INTO scores (user_id, game_id, player_name, score, moves, time_seconds, created_at)
		        VALUES (:user_id, :game_id, :player_name, :score, :moves, :time_seconds, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $data['user_id'] ?? null, is_null($data['user_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':game_id', $data['game_id'] ?? null, is_null($data['game_id']) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':player_name', $data['player_name'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':score', (int)($data['score'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':moves', (int)($data['moves'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':time_seconds', (int)($data['time_seconds'] ?? 0), PDO::PARAM_INT);

        // laisser l'exception remonter pour que le contrôleur puisse la gérer/logguer
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Récupère les scores d'un utilisateur
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        $sql = "SELECT id, player_name, score, moves, time_seconds, created_at
		        FROM scores
		        WHERE user_id = :user_id
		        ORDER BY created_at DESC
		        LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Supprime un score par id
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM scores WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
