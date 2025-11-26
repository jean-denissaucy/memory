<?php
class Game
{
    private array $cards = [];
    private int $pairs;

    public function __construct(int $pairs)
    {
        $this->pairs = $pairs;
        $this->generateCards();
        shuffle($this->cards);
    }

    private function generateCards()
    {
        $images = glob("public/asset/images/*.png"); // ← tes 12 images

        $selected = array_slice($images, 0, $this->pairs);

        foreach ($selected as $img) {
            $this->cards[] = new Card($img);
            $this->cards[] = new Card($img); // la paire
        }
    }

    public function getCards()
    {
        return $this->cards;
    }
}
