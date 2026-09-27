<?php
/** modules/catalogs.php **/

use App\Core\Security;
use App\Models\SysTable;
use App\Enums\Status;

$sysTables = SysTable::where('is_catalog', 1)
    ->get();

?>
<div class="container-fluid h-100 py-2 px-4 overflow-auto">
    

    <!-- Modal -->
    <div id="catalogs-modal"></div>
</div>

<!-- Modal de selección y confirmación -->
<template id="catalogs-template-export">
    <h3 id="catalog-export-title" class="font-bold text-lg text-white mb-1">Exportar catálogo</h3>
    <p class="mb-4">Exportación de catálogos en formato CSV</p>

    <div class="w-full space-y-2.5 mb-5">
        <select id="catalog-export-select" class="form-select">
            <option value="">Seleccionar catálogo</option>
            <?php foreach ($sysTables as $sysTable): ?>
                <option value="<?= $sysTable->id ?>"><?= $sysTable->name ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="CatalogsApp.doExport()" class="btn btn-primary w-full">Exportar</button>
    </div>

    <button onclick="IxeaComponents.closeModal()" class="text-xs text-slate-400 hover:text-white font-semibold underline">
        Cancelar
    </button>
</template>