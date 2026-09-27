<?php
/**
 * IXEA OS - Vista Launchpad (Cuadrícula de Aplicaciones)
 * /src/app/views/includes/launchpad.php
 */

use App\Classes\App;

// Cargar las aplicaciones mediante la arquitectura de clases
$userApps = App::getAccessibleApps();
?>

<div id="stage-launchpad" class="stage">
    <div class="launchpad-grid px-5">
        <?php foreach ($userApps as $app): ?>
            <div class="launchpad-app" onclick="IxeaStages.launch(
                '<?= htmlspecialchars($app->getTitle(), ENT_QUOTES) ?>', 
                '<?= htmlspecialchars($app->getUrl(), ENT_QUOTES) ?>', 
                '<?= htmlspecialchars($app->getIcon(), ENT_QUOTES) ?>', 
                '<?= htmlspecialchars($app->getCode(), ENT_QUOTES) ?>'
            )">
                <div class="icon-box" style="color: <?= htmlspecialchars($app->getColorHex(), ENT_QUOTES) ?>;">
                    <i class="bi <?= htmlspecialchars($app->getIcon(), ENT_QUOTES) ?>"></i>
                </div>
                <div class="text-dark fw-bold"><?= htmlspecialchars($app->getTitle(), ENT_QUOTES) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>