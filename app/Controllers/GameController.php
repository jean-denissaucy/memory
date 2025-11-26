<?php

namespace App\Controllers;

use Core\BaseController;

class GameController extends BaseController

{
    public function index(): void
    {
        $data = array(
            'title' => 'Bienvenue',
            'ma_variable' => 'Les Miraculous'
        );
        // Appelle la méthode render() de BaseController
        // - Charge la vue "app/Views/home/index.php"
        // - Injecte le tableau de paramètres (ici, une variable $title utilisable dans la vue)
        // - Insère le contenu de la vue dans le layout global "base.php"
        $this->render('game/index', $data);
    }
}
