<?php
class Card
{
    private string $imagePath;
    private bool $isMatched = false;

    public function __construct($imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public function getImage()
    {
        return $this->imagePath;
    }
    public function isMatched()
    {
        return $this->isMatched;
    }
    public function setMatched(bool $state)
    {
        $this->isMatched = $state;
    }
}
