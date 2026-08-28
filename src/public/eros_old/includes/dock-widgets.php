<?php
/**
 * Panel de Widgets y Stages (Derecha)
 * includes/dock-widgets.php
 */
?>
<div id="dock-widgets-container" class="side-dock-container d-none d-lg-flex">
    <div id="dock-right" class="dock-content">
        
        <div class="dock-item" onclick="IxeaWidgets.toggle('calc')" title="Calculadora">
            <i class="bi bi-calculator text-secondary"></i>
        </div>
        
        <div class="dock-item" onclick="IxeaWidgets.toggle('notes')" title="Notas">
            <i class="bi bi-journal-text text-secondary"></i>
        </div>

        <div class="dock-item" onclick="IxeaWidgets.toggle('calendar')" title="Calendario">
            <i class="bi bi-calendar3 text-secondary"></i>
        </div>

    </div>
</div>