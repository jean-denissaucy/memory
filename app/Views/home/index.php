<?php

/**
 * Vue : Page d'accueil
 * ---------------------
 * Cette vue reçoit une variable $title optionnelle
 * transmise par le HomeController.
 */
?>
<!-- Intégration du thème Miraculous (si le fichier est à /public/miraculous.css) -->
<link rel="stylesheet" href="/miraculous.css" />

<?php
// recherche des fichiers côté serveur (priorité public/asset/image puis autres dossiers)
$projectRoot = realpath(dirname(__DIR__, 3));
$searchDirs = [
  $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'asset' . DIRECTORY_SEPARATOR . 'image',
  $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img',
  $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'cards',
];

function fileToDataUri(string $path): ?string
{
  if (!is_file($path) || !is_readable($path)) return null;
  $finfo = @finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? finfo_file($finfo, $path) : mime_content_type($path);
  if ($finfo) finfo_close($finfo);
  $data = base64_encode(file_get_contents($path));
  return 'data:' . ($mime ?: 'image/png') . ';base64,' . $data;
}

function findAndDataUri(array $dirs, string $basename): ?string
{
  foreach ($dirs as $d) {
    $full = rtrim($d, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename;
    if (is_file($full) && is_readable($full)) {
      return fileToDataUri($full);
    }
  }
  return null;
}

// Liste des héros (noms propres, pas d'espaces superflus)
$heroes = [
  ['slug' => 'marinet3', 'label' => 'Marinette'],
  ['slug' => 'tiki1',    'label' => 'Tiki'],
  ['slug' => 'chatnoir1', 'label' => 'Chat Noir'],
  ['slug' => 'rena',     'label' => 'Rena'],
  ['slug' => 'zoe',      'label' => 'Zoe'],
  ['slug' => 'nathalie', 'label' => 'Nathalie'],
  ['slug' => 'purple',   'label' => 'Purple'],
  ['slug' => 'kagami1',   'label' => 'Kagami'],
  ['slug' => 'jenny',   'label' => 'jenny'],
];

// Prépare tableau des images pour la boucle d'affichage (web src + optional data-uri fallback)
$heroImages = [];
foreach ($heroes as $h) {
  $basename = $h['slug'] . '.png';
  $data = findAndDataUri($searchDirs, $basename);             // data-uri si fichier trouvé côté serveur
  $web  = '/asset/image/' . $basename;                       // chemin web à tenter d'abord
  $heroImages[] = ['label' => $h['label'], 'web' => $web, 'data' => $data];
}
?>
<!-- debug: chatFile=<?= $chatFile ? htmlspecialchars($chatFile, ENT_QUOTES) : 'not_found' ?> chatWeb=<?= htmlspecialchars($chatWeb, ENT_QUOTES) ?> chatData=<?= $chatData ? 'yes' : 'no' ?> -->
<!-- debug: marinetteFile=<?= $marinetteFile ? htmlspecialchars($marinetteFile, ENT_QUOTES) : 'not_found' ?> marinetteWeb=<?= htmlspecialchars($marinetteWeb, ENT_QUOTES) ?> marinetteData=<?= $marinetteData ? 'yes' : 'no' ?> -->
<!-- debug: chat1File=<?= $chat1File ? htmlspecialchars($chat1File, ENT_QUOTES) : 'not_found' ?> chat1Web=<?= htmlspecialchars($chat1Web, ENT_QUOTES) ?> chat1Data=<?= $chat1Data ? 'yes' : 'no' ?> -->

<div class="container">
  <div class="app-header panel">
    <div class="app-title">
      <div class="logo">🐞</div>
      <h1 class="mt-12">
        <?= htmlspecialchars($title ?? 'Accueil', ENT_QUOTES, 'UTF-8') ?>
      </h1>
    </div>
    <nav class="nav">
      <a href="/" class="badge">Accueil</a>
      <a href="/game" class="btn play">Jouer</a>
      <a href="/score" class="btn">Classement</a>
    </nav>
  </div>

  <div class="panel mt-12">
    <div class="players mt-12" aria-label="Héros">
      <?php foreach ($heroImages as $img): ?>
        <div class="player" style="text-align:center;">
          <img
            src="<?= htmlspecialchars($img['web'], ENT_QUOTES) ?>"
            <?= $img['data'] ? 'data-fallback="' . htmlspecialchars($img['data'], ENT_QUOTES) . '"' : '' ?>
            alt="<?= htmlspecialchars($img['label'], ENT_QUOTES) ?>"
            style="width:120px;height:auto;border-radius:10px;display:block;margin:0 auto 8px;"
            onerror="if(this.dataset.fallback && this.src!==this.dataset.fallback){this.src=this.dataset.fallback; this.onerror=null;} else this.style.display='none'">
          <div class="name"><?= htmlspecialchars($img['label'], ENT_QUOTES) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <p>Sauras-tu retrouver toutes les paires avant que l’akumatisation ne frappe ?</p>
    <p>stratégie, rapidité et passion, ce memory te mettra au défi comme jamais. Que tu sois un super-fan ou un nouveau héros en devenir, une seule règle : reste concentré, et montre de quoi tu es capable ! </p>
    <p>✨ Le Miraculous n’attend que toi… Es-tu prêt à relever le défi ? 🐞⚡.</p>

  </div>
</div>