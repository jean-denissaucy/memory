<?php

namespace App\Models;

class ClasseScore implements \JsonSerializable
{
    // Identifiant
    private ?int $id = null;

    // Référence joueur
    private ?int $playerId = null;

    // Référence de la partie
    private ?int $gameId = null;

    // score
    private int $score = 0;

    // mouvements
    private int $moves = 0;

    // Temps en secondes
    private int $timeSeconds = 0;

    // création
    private string $createdAt;

    public function __construct(int $playerId = 0, int $score = 0, int $moves = 0, int $timeSeconds = 0, ?int $gameId = null)
    {
        $this->playerId = $playerId ?: null;
        $this->score = $score;
        $this->moves = $moves;
        $this->timeSeconds = $timeSeconds;
        $this->gameId = $gameId;
        $this->createdAt = date("Y-m-d H:i:s");
    }

    public static function fromArray(array $data): self
    {
        $inst = new self(
            isset($data['player_id']) ? (int)$data['player_id'] : (isset($data['playerId']) ? (int)$data['playerId'] : 0),
            isset($data['score']) ? (int)$data['score'] : 0,
            isset($data['moves']) ? (int)$data['moves'] : 0,
            isset($data['time_seconds']) ? (int)$data['time_seconds'] : (isset($data['timeSeconds']) ? (int)$data['timeSeconds'] : 0),
            isset($data['game_id']) ? (int)$data['game_id'] : (isset($data['gameId']) ? (int)$data['gameId'] : null)
        );
        if (isset($data['id'])) $inst->setId((int)$data['id']);
        if (isset($data['created_at'])) $inst->setCreatedAt($data['created_at']);
        return $inst;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'player_id' => $this->playerId,
            'game_id' => $this->gameId,
            'score' => $this->score,
            'moves' => $this->moves,
            'time_seconds' => $this->timeSeconds,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // Getters / Setters
    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPlayerId(): ?int
    {
        return $this->playerId;
    }
    public function setPlayerId(?int $id): void
    {
        $this->playerId = $id;
    }

    public function getGameId(): ?int
    {
        return $this->gameId;
    }
    public function setGameId(?int $gid): void
    {
        $this->gameId = $gid;
    }

    public function getScore(): int
    {
        return $this->score;
    }
    public function setScore(int $s): void
    {
        $this->score = $s;
    }

    public function getMoves(): int
    {
        return $this->moves;
    }
    public function setMoves(int $m): void
    {
        $this->moves = $m;
    }

    public function getTimeSeconds(): int
    {
        return $this->timeSeconds;
    }
    public function setTimeSeconds(int $t): void
    {
        $this->timeSeconds = $t;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    public function setCreatedAt(string $dt): void
    {
        $this->createdAt = $dt;
    }
}
