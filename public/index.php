<?php
// Chargement manuel des fichiers nécessaires au fonctionnement de l'application
require_once __DIR__ . '/../vendor/autoload.php';

// Importation des classes avec namespaces pour éviter les conflits de noms
use Core\Router;

// Initialisation du routeur
$router = new Router();

// Définition des routes de l'application
// La route "/" pointe vers la méthode "index" du contrôleur HomeController
$router->get('/', 'App\\Controllers\\HomeController@index');

$router->get('/about', 'App\\Controllers\\HomeController@about');

$router->get('/game', 'App\\Controllers\\GameController@index');

$router->get('/score', 'App\\Controllers\\ScoreController@index');

$router->post('/scores', 'App\\Controllers\\ScoreController@store');

$router->get('/player', 'App\\Controllers\\PlayerController@index');

$router->get('/card', 'App\\Controllers\\CardController@index');

$router->get('/leaderboard', 'App\\Controllers\\LeaderboardController@index');
// endpoint JSON paginé pour le leaderboard
$router->get('/leaderboard/top', 'App\\Controllers\\LeaderboardController@topJson');





// Exécution du routeur :
// On analyse l'URI et la méthode HTTP pour appeler le contrôleur et la méthode correspondants
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
