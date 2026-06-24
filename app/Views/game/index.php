<?php
// Vue du jeu simplifiée : recherche d'images + rendu minimal
$root = dirname(__DIR__, 3);
$dirs = [
    $root . '/public/asset/image',
    $root . '/public/assets/img',
    $root . '/public/assets/img/cards',
];
$images = [];
foreach ($dirs as $d) {
    if (!is_dir($d)) continue;
    $files = glob($d . DIRECTORY_SEPARATOR . '*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE) ?: [];
    foreach ($files as $f) $images[] = '/' . trim(str_replace($root . '/public', '', $d), '/') . '/' . basename($f);
    if ($images) break;
}
if (empty($images)) $images = ['/assets/img/card1.png', '/assets/img/card2.png', '/assets/img/card3.png', '/assets/img/card4.png'];

// définir la difficulté par défaut (moyen)
$defaultDiff = $_GET['difficulty'] ?? 'medium';
?>
<link rel="stylesheet" href="/miraculous.css" />

<div class="container">
    <div class="panel">
        <h1 class="center"><?= htmlspecialchars($title ?? 'Memory', ENT_QUOTES, 'UTF-8') ?></h1>

        <div class="center mt-12">
            <label for="difficulty" class="badge">Difficulté</label>
            <select id="difficulty" class="btn ghost" style="padding:6px 10px;">
                <option value="easy" <?= $defaultDiff === 'easy' ? 'selected' : '' ?>>Facile</option>
                <option value="medium" <?= $defaultDiff === 'medium' ? 'selected' : '' ?>>Moyen</option>
                <option value="hard" <?= $defaultDiff === 'hard' ? 'selected' : '' ?>>Difficile</option>
                <option value="hardcore" <?= $defaultDiff === 'hardcore' ? 'selected' : '' ?>>Hard (toutes les cartes)</option>
            </select>

            <a href="/" class="btn ghost">Accueil</a>
            <button id="start" class="btn">Start</button>
            <button id="reset" class="btn">Réinitialiser</button>
        </div>

        <div class="panel mt-12" id="game-stats" style="display:flex;gap:12px;align-items:center;justify-content:center;">
            <div class="badge">Coups : <span id="movesCount">0</span></div>
            <div class="badge">Temps : <span id="timeElapsed">00:00</span></div>
            <div class="badge">Score : <span id="currentScore">—</span></div>
        </div>

        <div id="board" class="board square mt-12" aria-live="polite" style="--cols:4;"></div>

        <div id="status" class="mt-12 text-muted center">Choisissez une difficulté puis cliquez sur Start.</div>
    </div>
</div>

<!-- Modal d'enregistrement -->
<div id="scoreModal" class="panel" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;z-index:9999;background:rgba(0,0,0,0.5);">
    <div style="max-width:420px;margin:auto;background:var(--panel);padding:18px;border-radius:12px;">
        <h3 class="center">Partie terminée</h3>
        <p class="center">Votre score : <strong id="modalScore">0</strong></p>
        <form id="saveScoreForm">
            <label for="playerName">Votre nom</label>
            <input id="playerName" name="playerName" type="text" placeholder="Pseudo (optionnel)" style="width:100%;padding:8px;margin:8px 0;border-radius:8px;border:1px solid var(--glass-border);background:transparent;color:var(--white)" />
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
                <button type="button" id="cancelSave" class="btn ghost">Annuler</button>
                <button type="submit" class="btn">Enregistrer</button>
            </div>
            <div id="saveMsg" style="margin-top:10px;color:var(--gold);display:none"></div>
        </form>
    </div>
</div>

<!-- Overlay félicitations -->
<div id="congratsOverlay" aria-hidden="true" style="display:none;">
    <div class="congrats-backdrop"></div>
    <div class="congrats-panel">
        <div class="congrats-characters">
            <img class="congrats-char marinette" src="/asset/image/marinet4.png" alt="Marinette" onerror="this.style.display='none'">
            <img class="congrats-char chatnoir" src="/asset/image/chatnoir1.png" alt="Chat Noir" onerror="this.style.display='none'">
        </div>
        <div class="congrats-message">
            <h2>Bravo !</h2>
            <p>Tu as trouvé toutes les paires 🎉</p>
        </div>
    </div>
</div>

<script>
    /* données images injectées depuis PHP */
    const IMAGES = <?= json_encode(array_values($images), JSON_UNESCAPED_UNICODE) ?>;
    // paires selon difficulté (medium = 6 paires par défaut)
    const DIFF_MAP = {
        easy: 4,
        medium: 6,
        hard: 8
    }; // paires

    let first = null,
        busy = false,
        matches = 0,
        totalPairs = 0;
    let moves = 0,
        startTime = null,
        timerInterval = null;

    function shuffle(a) {
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    }

    function formatTime(s) {
        const m = Math.floor(s / 60).toString().padStart(2, '0'),
            ss = (s % 60).toString().padStart(2, '0');
        return `${m}:${ss}`;
    }

    function startTimer() {
        if (timerInterval) return;
        startTime = Date.now();
        timerInterval = setInterval(() => {
            document.getElementById('timeElapsed').textContent = formatTime(Math.floor((Date.now() - startTime) / 1000));
        }, 500);
    }

    function stopTimer() {
        if (!timerInterval) return;
        clearInterval(timerInterval);
        timerInterval = null;
    }

    function resetStats() {
        moves = 0;
        startTime = null;
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        document.getElementById('movesCount').textContent = '0';
        document.getElementById('timeElapsed').textContent = '00:00';
        document.getElementById('currentScore').textContent = '—';
    }

    function buildBoard(difficulty) {
        // mode "hardcore" : utiliser toutes les images disponibles (paires = IMAGES.length)
        const pairs = (difficulty === 'hardcore') ? Math.floor(IMAGES.length) : Math.min(DIFF_MAP[difficulty] || 6, Math.floor(IMAGES.length));
        totalPairs = pairs;
        const sel = shuffle(IMAGES.slice()).slice(0, pairs);
        let cards = [];
        sel.forEach(img => {
            const token = crypto ? ([...crypto.getRandomValues(new Uint8Array(6))].map(b => b.toString(16).padStart(2, '0')).join('')) : Math.random().toString(36).slice(2, 10);
            cards.push({
                token,
                img
            });
            cards.push({
                token,
                img
            });
        });
        shuffle(cards);
        const board = document.getElementById('board');
        board.dataset.diff = difficulty; // <-- store difficulty on board so CSS can adapt image sizes
        board.innerHTML = '';
        cards.forEach((c, idx) => {
            const div = document.createElement('div');
            div.className = 'card';
            div.setAttribute('data-token', c.token);
            // image now has class "card-img" and descriptive alt
            div.innerHTML = `<div class="card-inner">
            <div class="card-face card-front panel" aria-hidden="true">?</div>
            <div class="card-face card-back" role="img"><img class="card-img" src="${c.img}" alt="Image de la carte ${idx+1}" onerror="this.style.display='none'"></div>
        </div>`;
            board.appendChild(div);
        });
        // ajuster dynamiquement le nombre de colonnes pour le mode hardcore (ou autres)
        board.style.setProperty('--cols', Math.ceil(Math.sqrt(cards.length)));
        first = null;
        busy = false;
        matches = 0;
        // réinitialiser stats
        resetStats();
        // afficher prêt
        document.getElementById('status').textContent = `Jeu prêt — ${pairs} paires`;
        attachHandlers();
    }

    function attachHandlers() {
        const board = document.getElementById('board');
        if (!board || board.dataset.handlerAdded === '1') return;
        board.dataset.handlerAdded = '1';
        board.addEventListener('click', e => {
            const card = e.target.closest('.card');
            if (!card || !board.contains(card)) return;
            if (busy || card.dataset.matched === '1') return;
            if (!startTime) startTimer();
            if (!card.classList.contains('revealed')) card.classList.add('revealed');
            if (!first) {
                first = card;
                return;
            }
            if (first === card) return;
            moves++;
            document.getElementById('movesCount').textContent = String(moves);
            const t1 = first.dataset.token,
                t2 = card.dataset.token;
            if (t1 === t2) {
                first.classList.add('matched', 'fixed');
                card.classList.add('matched', 'fixed');
                first.dataset.matched = '1';
                card.dataset.matched = '1';
                first = null;
                matches++;
                document.getElementById('status').textContent = matches >= totalPairs ? 'Bravo ! Toutes les paires trouvées 🎉' : 'Paire trouvée !';
                if (matches >= totalPairs) {
                    stopTimer();
                    const elapsedSec = Math.floor((Date.now() - startTime) / 1000);
                    let score = Math.max(0, 1000 - (moves * 10) - (elapsedSec * 2));
                    document.getElementById('currentScore').textContent = String(score);
                    showCongratsEffect();
                    setTimeout(() => showScoreModal(score, moves, elapsedSec), 900);
                }
                return;
            }
            busy = true;
            const prev = first,
                curr = card;
            setTimeout(() => {
                if (!prev.dataset.matched) prev.classList.remove('revealed');
                if (!curr.dataset.matched) curr.classList.remove('revealed');
                first = null;
                busy = false;
                document.getElementById('status').textContent = 'Essaye encore...';
            }, 800);
        });
    }

    function showScoreModal(score, moves, timeSeconds) {
        document.getElementById('modalScore').textContent = String(score);
        document.getElementById('playerName').value = '';
        document.getElementById('saveMsg').style.display = 'none';
        const modal = document.getElementById('scoreModal');
        modal.style.display = 'flex';
        const form = document.getElementById('saveScoreForm'),
            cancel = document.getElementById('cancelSave');

        function close() {
            modal.style.display = 'none';
            form.removeEventListener('submit', onSubmit);
            cancel.removeEventListener('click', onCancel);
        }

        function onCancel(e) {
            e.preventDefault();
            close();
        }
        async function onSubmit(e) {
            e.preventDefault();
            const name = document.getElementById('playerName').value.trim() || null;
            const payload = {
                player_name: name,
                score: score,
                moves: moves,
                time_seconds: timeSeconds
            };
            try {
                const saveMsgEl = document.getElementById('saveMsg');
                saveMsgEl.style.display = 'block';
                saveMsgEl.textContent = 'Envoi...';

                const res = await fetch('/scores', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                // lire en texte puis tenter de parser JSON (prévenir erreurs de parse)
                const text = await res.text();
                let json = null;
                try {
                    json = text ? JSON.parse(text) : null;
                } catch (err) {
                    json = null;
                }

                if (res.ok) {
                    if (json && json.success !== false) {
                        // afficher message puis proposer actions : Rejouer / Accueil
                        saveMsgEl.innerHTML = 'Score enregistré. Merci !<div style="margin-top:10px;"><button id="replayAfterSave" class="btn">Rejouer</button> <a href="/" class="btn ghost" id="homeAfterSave">Accueil</a></div>';
                        // attacher listeners aux nouveaux boutons
                        const replayBtn = document.getElementById('replayAfterSave');
                        const homeBtn = document.getElementById('homeAfterSave');
                        const onReplay = () => {
                            close();
                            // relancer une partie avec la difficulté actuelle
                            buildBoard(document.getElementById('difficulty').value || 'medium');
                        };
                        if (replayBtn) replayBtn.addEventListener('click', onReplay);
                        if (homeBtn) homeBtn.addEventListener('click', () => {
                            /* allow normal navigation */ });
                    } else {
                        // réponse OK mais payload inattendu
                        saveMsgEl.textContent = 'Enregistré (réponse inattendue).';
                    }
                } else {
                    // Erreur serveur : afficher message si fourni, sinon code HTTP
                    if (json && json.error) {
                        saveMsgEl.textContent = 'Erreur : ' + String(json.error);
                    } else {
                        saveMsgEl.textContent = 'Erreur serveur : ' + res.status + ' ' + (res.statusText || '');
                    }
                }
            } catch (err) {
                document.getElementById('saveMsg').style.display = 'block';
                document.getElementById('saveMsg').textContent = 'Erreur réseau. Vérifiez que /scores existe et le serveur est démarré.';
            }
            // Ne pas fermer automatiquement : laisser l'utilisateur choisir Rejouer ou Accueil
        }
        form.addEventListener('submit', onSubmit);
        cancel.addEventListener('click', onCancel);
    }

    function showCongratsEffect() {
        const overlay = document.getElementById('congratsOverlay');
        if (!overlay) return;
        overlay.style.display = 'block';
        overlay.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => overlay.classList.add('show'));
        setTimeout(() => {
            overlay.classList.remove('show');
            overlay.style.display = 'none';
        }, 1800);
    }

    document.getElementById('start').addEventListener('click', () => buildBoard(document.getElementById('difficulty').value));
    // Le bouton "Regénérer" a été retiré du HTML ; on attache le listener seulement si l'élément existe
    const regenBtn = document.getElementById('regen');
    if (regenBtn) regenBtn.addEventListener('click', () => buildBoard(document.getElementById('difficulty').value));
    document.getElementById('reset').addEventListener('click', () => {
        buildBoard(document.getElementById('difficulty').value || 'normal');
        document.getElementById('status').textContent = 'Jeu réinitialisé.';
    });

    // build initial board selon difficulté par défaut (moyen si absent)
    buildBoard(document.getElementById('difficulty').value || 'medium');
</script>