<?php
// $top doit être fourni par le contrôleur (array de lignes)
$top = $top ?? [];
?>
<link rel="stylesheet" href="/miraculous.css" />
<div class="container">
    <div class="panel">
        <h1 class="center"><?= htmlspecialchars($title ?? 'Classement', ENT_QUOTES, 'UTF-8') ?></h1>

        <div style="overflow-x:auto;margin-top:12px;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid rgba(255,255,255,0.06)">
                        <th>#</th>
                        <th>Joueur</th>
                        <th>Score</th>
                        <th>Coups</th>
                        <th>Temps (s)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top)): ?>
                        <tr>
                            <td colspan="6" style="padding:12px">Aucun score disponible.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($top as $i => $row): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                                <td style="padding:8px;vertical-align:middle;"><?= $i + 1 ?></td>
                                <td style="padding:8px;vertical-align:middle;"><?= htmlspecialchars($row['player'] ?? ($row['username'] ?? 'Anonyme'), ENT_QUOTES) ?></td>
                                <td style="padding:8px;vertical-align:middle;"><?= htmlspecialchars((string)($row['score'] ?? 0), ENT_QUOTES) ?></td>
                                <td style="padding:8px;vertical-align:middle;"><?= htmlspecialchars((string)($row['moves'] ?? 0), ENT_QUOTES) ?></td>
                                <td style="padding:8px;vertical-align:middle;"><?= htmlspecialchars((string)($row['time_seconds'] ?? 0), ENT_QUOTES) ?></td>
                                <td style="padding:8px;vertical-align:middle;"><?= htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px;text-align:center;">
            <a href="/game" class="btn">Rejouer</a>
            <a href="/" class="btn ghost">Accueil</a>
        </div>
    </div>
</div>