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

/**
 * Recherche robuste : accepte variantes (png/jpg/jpeg/gif/webp), insensible à la casse,
 * et fichiers dont le nom commence par le slug (ex : slug.png, slug-v2.jpg, slug_01.webp).
 * Retourne data-uri si trouvé, sinon null.
 */
function findAndDataUri(array $dirs, string $slug): ?string
{
  $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
  $base = preg_replace('/[^a-z0-9]+/i', '', pathinfo($slug, PATHINFO_FILENAME));
  foreach ($dirs as $d) {
    if (!is_dir($d)) continue;
    // premier : tester nom exact avec extensions
    foreach ($allowed as $ext) {
      $full = rtrim($d, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $base . '.' . $ext;
      if (is_file($full) && is_readable($full)) return fileToDataUri($full);
    }
    // ensuite : parcourir tous les fichiers et comparer normalisé
    foreach (scandir($d) as $f) {
      if (in_array($f, ['.', '..'])) continue;
      $full = rtrim($d, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f;
      if (!is_file($full)) continue;
      $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed)) continue;
      $nameNorm = preg_replace('/[^a-z0-9]+/i', '', pathinfo($full, PATHINFO_FILENAME));
      // correspondance si commence par le slug normalisé
      if (stripos($nameNorm, $base) === 0) return fileToDataUri($full);
    }
  }
  return null;
}

// Liste des héros
$heroes = [
  ['slug' => 'marinet2', 'label' => 'Marinette'],
  ['slug' => 'tiki1',    'label' => 'Tiki'],
  ['slug' => 'chatnoir1', 'label' => 'Chat Noir'],
  ['slug' => 'rena',     'label' => 'Rena'],
  ['slug' => 'zoe',      'label' => 'Zoe'],
  ['slug' => 'nathalie', 'label' => 'Nathalie'],
  ['slug' => 'purple',   'label' => 'Purple'],
  ['slug' => 'kagami',  'label' => 'Kagami'],
  ['slug' => 'jenny',    'label' => 'Jenny'],
  ['slug' => 'wiki',    'label' => 'wiki'],
  ['slug' => 'carapace1',    'label' => 'carapace'],
];

// Prépare tableau des images : src = data-uri si disponible, sinon chemin web fallback
$heroImages = [];
foreach ($heroes as $h) {
  $basename = $h['slug'] . '.png';
  $data = findAndDataUri($searchDirs, $h['slug']);
  $web  = '/asset/image/' . $basename;
  $src  = $data ?? $web;
  // Ajout du slug pour lier l'image à son histoire
  $heroImages[] = ['slug' => $h['slug'], 'label' => $h['label'], 'src' => $src, 'hasData' => (bool)$data];
}
?>
<!-- debug: chatFile=<?= !empty($chatFile) ? htmlspecialchars($chatFile, ENT_QUOTES, 'UTF-8') : 'not_found' ?> chatWeb=<?= htmlspecialchars($chatWeb ?? '', ENT_QUOTES, 'UTF-8') ?> chatData=<?= !empty($chatData) ? 'yes' : 'no' ?> -->
<!-- debug: marinetteFile=<?= !empty($marinetteFile) ? htmlspecialchars($marinetteFile, ENT_QUOTES, 'UTF-8') : 'not_found' ?> marinetteWeb=<?= htmlspecialchars($marinetteWeb ?? '', ENT_QUOTES, 'UTF-8') ?> marinetteData=<?= !empty($marinetteData) ? 'yes' : 'no' ?> -->
<!-- debug: chat1File=<?= !empty($chat1File) ? htmlspecialchars($chat1File, ENT_QUOTES, 'UTF-8') : 'not_found' ?> chat1Web=<?= htmlspecialchars($chat1Web ?? '', ENT_QUOTES, 'UTF-8') ?> chat1Data=<?= !empty($chat1Data) ? 'yes' : 'no' ?> -->

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
          <!-- Image cliquable : data-hero = slug -->
          <button type="button" class="hero-btn" data-hero="<?= htmlspecialchars($img['slug'], ENT_QUOTES) ?>" aria-haspopup="dialog" style="background:none;border:0;padding:0;cursor:pointer;">
            <img src="<?= htmlspecialchars($img['src'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($img['label'], ENT_QUOTES) ?>" class="hero-img" onerror="this.style.display='none'">
          </button>
          <div class="name"><?= htmlspecialchars($img['label'], ENT_QUOTES) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Modal histoire (hidden) -->
    <div id="heroModal" role="dialog" aria-modal="true" aria-hidden="true" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:10000;">
      <div style="position:absolute;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(2px)"></div>
      <div style="position:relative;z-index:10001;max-width:720px;width:min(92%,720px);background:var(--panel);border-radius:12px;padding:18px;border:1px solid rgba(255,255,255,0.04);box-shadow:0 20px 50px rgba(0,0,0,0.6);color:var(--white)">
        <button id="heroModalClose" style="position:absolute;top:10px;right:12px;background:transparent;border:0;color:var(--white);font-size:18px;cursor:pointer" aria-label="Fermer">✕</button>
        <h2 id="heroModalTitle" style="margin-top:0">Titre</h2>
        <div id="heroModalBody" style="margin-top:8px;line-height:1.45;color:var(--muted)"></div>
      </div>
    </div>

    <p>Sauras-tu retrouver toutes les paires avant que l’akumatisation ne frappe ?</p>
    <p>stratégie, rapidité et passion, ce memory te mettra au défi comme jamais. Que tu sois un super-fan ou un nouveau héros en devenir, une seule règle : reste concentré, et montre de quoi tu es capable ! </p>
    <p>✨ Le Miraculous n’attend que toi… Es-tu prêt à relever le défi ? 🐞⚡.</p>

  </div>
</div>

<script>
  // Histoires par slug
  const HERO_STORIES = {
    marinet2: {
      title: 'Marinette — Histoire',
      body: `<p>Marinette Dupain-Cheng est une jeune fille créative et attentionnée. Elle apprend à concilier sa vie d'élève avec son rôle de protectrice secrète de Paris.</p>`
    },
    tiki1: {
      title: 'Tiki — Histoire',
      body: `<p>Tiki est le kwami de Marinette : elle la guide, la conseille et lui donne les pouvoirs nécessaires pour devenir Ladybug.</p>`
    },
    chatnoir1: {
      title: 'Chat Noir — Histoire',
      body: `<p>Chat Noir est l'alter ego courageux et parfois insolent d'un héros mystérieux. Il travaille souvent aux côtés de Ladybug pour protéger la ville.</p>`
    },
    rena: {
      title: 'Rena — Histoire',
      body: `<p>Rena est une amie loyale, connue pour sa détermination. Elle soutient toujours ses proches dans les moments difficiles.</p>`
    },
    zoe: {
      title: 'Zoe — Histoire',
      body: `<p>Zoe est vive et débrouillarde. Elle apporte de la joie autour d'elle et a un grand sens de l'humour.</p>`
    },
    nathalie: {
      title: 'Nathalie — Histoire',
      body: `<p>Nathalie est réfléchie et fiable ; elle aide souvent à organiser les choses et à veiller au bon déroulement des plans.</p>`
    },
    purple: {
      title: 'Purple — Histoire',
      body: `<p>Purple est un personnage énigmatique avec un style unique. Son passé est plein de mystères qui intriguent les habitants.</p>`
    },
    kagami: {
      title: 'Kagami — Histoire',
      body: `<p>Kagami est digne et gracieuse, avec une grande force intérieure. Elle incarne la discipline et la loyauté.</p>`
    },
    jenny: {
      title: 'Jenny — Histoire',
      body: `<p>Jenny est créative et curieuse. Elle aime découvrir de nouvelles choses et partager ses trouvailles.</p>`
    },
    wiki: {
      title: 'Wiki — Histoire',
      body: `<p>Wiki est le petit génie du groupe : toujours en recherche d'information et prêt à expliquer le monde.</p>`
    },
    carapace1: {
      title: 'Carapace — Histoire',
      body: `<p>Carapace est solide et protecteur : un pilier sur lequel on peut compter pour défendre les autres.</p>`
    }
  };

  // Ouvrir modal pour chaque bouton hero-btn
  document.querySelectorAll('.hero-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const slug = btn.dataset.hero;
      const story = HERO_STORIES[slug] || {
        title: 'Histoire',
        body: '<p>Histoire indisponible.</p>'
      };
      const modal = document.getElementById('heroModal');
      document.getElementById('heroModalTitle').textContent = story.title;
      document.getElementById('heroModalBody').innerHTML = story.body;
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
      document.getElementById('heroModalClose').focus();
    });
  });

  // Fermer modal
  const closeBtn = document.getElementById('heroModalClose');
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      const modal = document.getElementById('heroModal');
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    });
    document.getElementById('heroModal').addEventListener('click', (e) => {
      if (e.target.id === 'heroModal') {
        e.currentTarget.style.display = 'none';
        e.currentTarget.setAttribute('aria-hidden', 'true');
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const modal = document.getElementById('heroModal');
        if (modal && modal.style.display === 'flex') {
          modal.style.display = 'none';
          modal.setAttribute('aria-hidden', 'true');
        }
      }
    });
  }
</script>