<?php
/**
 * Barra superior de menús - IXEA OS
 * includes/menu-bar.php
 */
?>
<div id="menu-bar">
    <div class="menu-left">
        <div class="dropdown">
            <div class="menu-item ixea-logo d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <?php echo file_get_contents($root_path . '/includes/isotipo.php'); ?>
            </div>
            <ul class="dropdown-menu shadow border-0 mt-2">
                <li><a class="dropdown-item small" href="#"><i class="bi bi-info-circle me-2"></i>Acerca de IXEA OS</a></li>
                <li><a class="dropdown-item small" href="#" onclick="alert('Configuración')"><i class="bi bi-gear-fill me-2"></i>Ajustes del Sistema</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item small text-sfred" href="/access/logout.php">
                        <i class="bi bi-power me-2"></i>Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="dropdown ms-2">
            <div class="menu-item d-flex align-items-center cursor-pointer" data-bs-toggle="dropdown" id="active-app-display" style="lette--spacing: 1px;">IXEA</div>
            <ul class="dropdown-menu shadow border-0 mt-2" id="dynamic-app-menu">
                <li><a class="dropdown-item small" href="#" target="_blank"><i class="bi bi-lightbulb-fill me-2"></i>Prueba tu suerte</a></li>
            </ul>
        </div>
        
        <div id="menu-app-actions" class="ms-2"></div>
        <div id="menu-app-filters" class="ms-2"></div>
    </div>

    <div class="menu-right">
        <div class="dropdown">
            <div class="menu-item" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                <i class="bi bi-search"></i>
            </div>
            <div class="dropdown-menu dropdown-menu-end p-2 shadow border-0 mt-2" style="width: 300px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);">
                <input type="text" class="form-control form-control-sm border-0 bg-light" placeholder="Buscar en IXEA OS..." id="global-search">
            </div>
        </div>

        <div class="dropdown">
            <div class="menu-item" data-bs-toggle="dropdown">
                <i id="status-indicator" class="bi bi-circle-fill text-sfgreen" style="font-size: 0.6rem;"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end p-2 shadow border-0 mt-2" style="min-width: 200px;">
                <h6 class="dropdown-header ps-0 text-white">Estado del Sistema</h6>
                <li class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">PHP Engine:</span>
                    <span class="text-sfgreen">Online</span>
                </li>
                <li class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">Latencia:</span>
                    <span id="health-ms" class="text-muted small">-- ms</span>
                </li>
            </ul>
        </div>
        
        <div class="dropdown">
            <div class="menu-item" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="dropdown-menu dropdown-menu-end p-2 shadow border-0 mt-2 text-center" style="min-width: 180px;">
                <div>
                    <i class="bi bi-person-vcard fs-3 text-sfblue"></i>
                </div>
                <div class="fw-bold"><?php echo $_SESSION['user_name']; ?></div>
                <div class="badge text-white small"><?php echo $_SESSION['user_email']; ?></div>
                <?php if ($_SESSION['gmail_connected']): ?>
                    <i class="bi bi-circle-fill text-sfgreen" style="font-size: 0.5rem;" title="Gmail Conectado" id="gmail-indicator" data-connected="true"></i>
                <?php else: ?>
                    <i class="bi bi-circle-fill text-sfred text-blink" style="font-size: 0.5rem;" 
                       title="Gmail Desconectado" id="gmail-indicator" data-connected="false"></i>
                <?php endif; ?>
                <div class="badge text-sfblue mb-3"><?php echo $_SESSION['role_name']; ?></div>
                <?php if (!$_SESSION['gmail_connected']): ?>
                    <button class="btn btn-sm btn-light w-100 text-sfred fw-bold" style="font-size: 0.7rem;" onclick="GmailApi.gmailConfirmation()" id="gmail-confirmation">
                        VINCULAR GMAIL
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="dropdown">
            <div class="menu-item fw-medium" id="menu-time" data-bs-toggle="dropdown">
                <i class="bi bi-clock"></i>
            </div>
            <div class="dropdown-menu dropdown-menu-end p-2 text-center shadow border-0 mt-2">
                <div class="text-uppercase fw-bold small"><?= date('l') ?></div>
                <div class="display-6 fw-bold"><?= date('d') ?></div>
                <div class="text-uppercase small"><?= date('F Y') ?></div>
                <hr class="my-2">
                <div class="small text-sfblue fw-bold" id="full-clock-detail"><?= date('H:i:s') ?></div>
            </div>
        </div>
    </div>
</div>