<?php

namespace App\Models;

class ClassePlayer implements \JsonSerializable
{
    private ?int $id = null;
    private string $name = '';

    public function __construct(?int $id = null, string $name = '')
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function fromArray(array $data): self
    {
        $inst = new self(
            isset($data['id']) ? (int)$data['id'] : (isset($data['player_id']) ? (int)$data['player_id'] : null),
            isset($data['name']) ? $data['name'] : (isset($data['username']) ? $data['username'] : ''),
        );
        return $inst;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
