<?php
/**
 * Contenedor de aplicaciones
 * includes/stage-manager.php
 */
$apps = [
    [
        'title' => 'Caja', 
        'url' => '/modules/till/till.php',
        'js' => 'till',
        'permission' => 'app_caja',
        'icon' => 'bi-safe2-fill',
        'color' => 'var(--soft-green)'
    ],
    [
        'title' => 'Compras', 
        'url' => '/modules/purchases/purchases.php',
        'js' => 'purchases',
        'permission' => 'app_compra',
        'icon' => 'bi-bag-check-fill',
        'color' => 'var(--soft-orange)'
    ],
    [
        'title' => 'Pagos', 
        'url' => '/modules/payments/payments.php',
        'js' => 'payments',
        'permission' => 'app_pago',
        'icon' => 'bi-cash-coin',
        'color' => 'var(--soft-orange)'
    ],
    [
        'title' => 'Entradas', 
        'url' => '/modules/purchases/receipt.php',
        'js' => 'receipt',
        'permission' => 'app_mercancia',
        'icon' => 'bi-journal-check',
        'color' => 'var(--soft-orange)'
    ],
    [
        'title' => 'Crédito', 
        'url' => '/modules/credit/credit.php', 
        'js' => 'credit',
        'permission' => 'app_credito',
        'icon' => 'bi-credit-card-2-front-fill',
        'color' => 'var(--soft-green)'
    ],
    [
        'title' => 'Ventas', 
        'url' => '/modules/sales/pos.php', 
        'js' => 'pos',
        'permission' => 'app_venta',
        'icon' => 'bi-cart-fill',
        'color' => 'var(--soft-green)'
    ],
    [
        'title' => 'Entregas', 
        'url' => '/modules/sales/dispatch.php', 
        'js' => 'dispatch',
        'permission' => 'app_entrega',
        'icon' => 'bi-box2-fill',
        'color' => 'var(--soft-green)'
    ],
    [
        'title' => 'Checador', 
        'url' => '/modules/rrhh/checker.php', 
        'js' => 'checker',
        'permission' => 'app_checador',
        'icon' => 'bi-clock-history',
        'color' => 'var(--soft-magenta)'
    ],
    [
        'title' => 'Inventario', 
        'url' => '/modules/inventory/stock.php', 
        'js' => 'stock',
        'permission' => 'app_inventario',
        'icon' => 'bi-boxes',
        'color' => 'var(--soft-purple)'
    ],
    [
        'title' => 'Clientes', 
        'url' => '/modules/customers/customers.php', 
        'js' => 'customers',
        'permission' => 'app_clientes',
        'icon' => 'bi-person-badge-fill',
        'color' => 'var(--soft-blue)'
    ],
    [
        'title' => 'Usuarios', 
        'url' => '/modules/users/users.php', 
        'js' => 'users',
        'permission' => 'app_usuarios',
        'icon' => 'bi-people-fill',
        'color' => 'var(--soft-blue)'
    ],
    [
        'title' => 'Proveedores', 
        'url' => '/modules/suppliers/suppliers.php', 
        'js' => 'suppliers',
        'permission' => 'app_proveedores',
        'icon' => 'bi-truck-flatbed',
        'color' => 'var(--soft-orange)'
    ],
    [
        'title' => 'RRHH', 
        'url' => '/modules/rrhh/rrhh.php', 
        'js' => 'rrhh',
        'permission' => 'app_rrhh',
        'icon' => 'bi-person-vcard-fill',
        'color' => 'var(--soft-magenta)'
    ],
    [
        'title' => 'Catalogos', 
        'url' => '/modules/admin/catalogs.php', 
        'js' => 'catalogs',
        'permission' => 'app_catalogos',
        'icon' => 'bi-journal-bookmark-fill',
        'color' => 'grey'
    ],
    [
        'title' => 'Respaldos', 
        'url' => '/modules/tools/system.php', 
        'js' => 'system',
        'permission' => 'app_respaldos',
        'icon' => 'bi-shield-lock-fill',
        'color' => 'grey'
    ],
    [
        'title' => 'Reportes', 
        'url' => '/modules/reports/reports.php', 
        'js' => 'reports',
        'permission' => 'app_reportes',
        'icon' => 'bi-graph-up-arrow',
        'color' => 'var(--soft-cyan)'  
    ],
    [
        'title' => 'Finanzas', 
        'url' => '/modules/finance/finance.php', 
        'js' => 'finance',
        'permission' => 'app_finanzas',
        'icon' => 'bi-bank2',
        'color' => 'var(--soft-cyan)'
    ]
];

// LÓGICA DE FILTRADO
$user_apps = array_filter($apps, function($m) use ($is_super, $user_permissions) {
    return ($is_super || in_array($m['permission'], $user_permissions));
});

usort($user_apps, function($a, $b) {
    return strcmp($a['title'], $b['title']);
});
?>

<main id="os-viewport">
    <div id="stage-0" class="stage active">
        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center intro-wrapper overflow-hidden">
            <div style="min-height: 120px;" class="d-flex flex-column justify-content-center">
                <p>
                    <span id="intro-welcome" class="display-2 intro-text text-dark mb-0">BIENVENIDO </span>
                    <span id="intro-a" class="display-2 intro-text text-dark mb-0" style="visibility: hidden;">A</span>
                </p>
                <p id="intro-logo" class="display-1 intro-text fw-bold text-dark" style="visibility: hidden; height: 0; margin: 0;">
                    IXEA <span class="text-xaccent">EROS</span></p>
            </div>
            
            <div class="d-flex gap-4 mt-2" id="intro-slogan">
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Intuitivo.</span>
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Rápido.</span>
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Brutal.</span>
            </div>
        </div>
    </div>

    <div id="stage-launchpad" class="stage">
        <div class="launchpad-grid px-5">
            <?php foreach ($user_apps as $app): ?>
                <div class="launchpad-app" onclick="IxeaStages.launch('<?= $app['title'] ?>', '<?= $app['url'] ?>', '<?= $app['icon'] ?>', '<?= $app['js'] ?>')">
                    <div class="icon-box" style="color: <?= $app['color'] ?>;"><i class="bi <?= $app['icon'] ?>"></i></div>
                    <div class="text-dark fw-bold"><?= $app['title'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="dynamic-stages" class="p-3"></div>
</main>