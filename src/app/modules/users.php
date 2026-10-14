<?php
/** modules/users/users.php **/
?>
<div class="w-full h-full p-4 flex flex-col gap-4">
    <!-- Tarjetas Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Total Usuarios -->
        <div class="bg-slate-700 border-l-4 border-blue-500 rounded-2xl p-4 shadow-md border-t border-r border-b border-slate-700 flex items-center justify-between">
            <h6 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Usuarios</h6>
            <div class="text-2xl font-extrabold text-white" id="users-count-total">0</div>
        </div>

        <!-- Activos -->
        <div class="bg-slate-700 border-l-4 border-emerald-500 rounded-2xl p-4 shadow-md border-t border-r border-b border-slate-700 flex items-center justify-between">
            <h6 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Activos</h6>
            <div class="text-2xl font-extrabold text-white" id="users-count-active">0</div>
        </div>

        <!-- Sin Huella Digital -->
        <div class="bg-slate-700 border-l-4 border-amber-500 rounded-2xl p-4 shadow-md border-t border-r border-b border-slate-700 flex items-center justify-between">
            <h6 class="text-xs font-bold text-amber-400 uppercase tracking-wider">Sin Huella</h6>
            <div class="text-2xl font-extrabold text-white" id="users-count-fingerprint">0</div>
        </div>
    </div>

    <!-- Contenedor Principal / Tabla -->
    <div class="flex-1 bg-slate-700 border border-slate-700 rounded-2xl shadow-lg flex flex-col overflow-hidden">
        <!-- Header de la sección -->
        <div class="p-4 border-b border-slate-700 bg-slate-700/80 flex flex-col sm:flex-row justify-between items-center gap-3">
            <h5 class="m-0 font-bold text-white text-lg flex items-center gap-2">
                <i class="bi bi-people text-blue-400"></i>Gestión de Personal
            </h5>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <input type="text" 
                       id="users-input-search"
                       class="w-full sm:w-64 bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-blue-500" 
                       placeholder="Buscar usuario..." 
                       onkeyup="UsersApp.filterUsers(this.value)">
                <button class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2 shrink-0 shadow-md" 
                        id="users-button-create"
                        onclick="UsersApp.openCreateUser()">
                    <i class="bi bi-person-plus-fill"></i> Nuevo
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto flex-1">
            <table id="users-table" class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="py-3 px-4">Nombre / Identificador</th>
                        <th class="py-3 px-4 text-center">Rol / Puesto</th>
                        <th class="py-3 px-4 text-center">Estatus</th>
                        <th class="py-3 px-4 text-center">Biometría</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50" id="users-table-body">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tag para el modal -->
    <div id="users-modal"></div>
</div>

<!-- ==================== TEMPLATES MODALES ==================== -->

<!-- Modal: Crear / Editar Usuario (uso: form/edit) -->
<template id="users-template-form">
    <div class="flex flex-col w-full h-full max-h-[85vh] p-4 text-white overflow-y-auto">
        <div class="flex justify-between items-center pb-3 border-b border-slate-700 mb-4">
            <h3 class="font-bold text-lg text-blue-400 flex items-center gap-2" id="users-form-title">
                <i class="bi bi-person-gear"></i> Usuario
            </h3>
            <button class="text-slate-400 hover:text-white transition" onclick="IxeaComponents.closeModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="users-form" autocomplete="off" class="space-y-4">
            <input type="hidden" name="user_id" id="users-field-id" >
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nombre(s) *</label>
                    <input type="text" name="first_name" id="users-field-firstname" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Apellidos</label>
                    <input type="text" name="last_name" id="users-field-lastname" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-slate-300 mb-1">RFC / ID</label>
                    <input type="text" name="tax_id" id="users-field-taxid" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500" maxlength="20">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-300 mb-1">Rol *</label>
                    <select id="users-field-role" name="role_id" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-2.5 text-xs focus:outline-none focus:border-blue-500" required></select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-300 mb-1">PIN</label>
                    <input type="text" name="auth_pin" id="users-field-pin" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-center text-white focus:outline-none focus:border-blue-500" maxlength="4" placeholder="0000">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-7">
                    <label class="block text-xs font-bold text-slate-300 mb-1">Email *</label>
                    <input type="email" name="email" id="users-field-email" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500" required>
                </div>
                <div class="md:col-span-5">
                    <label class="block text-xs font-bold text-slate-300 mb-1">Contraseña</label>
                    <input type="password" name="password" id="users-field-password" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-blue-500" placeholder="********">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Estado del Usuario</label>
                <select name="status" id="users-field-status" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl p-2.5 text-xs focus:outline-none focus:border-blue-500">
                    <option value="active">Activo</option>
                    <option value="suspended">Suspendido</option>
                    <option value="archived">Baja</option>
                </select>
            </div>
        </form>

        <div class="flex space-x-2 pt-4 mt-auto border-t border-slate-700">
            <button type="button" onclick="IxeaComponents.closeModal()" class="w-1/3 bg-slate-600 hover:bg-slate-600 text-white font-bold py-2.5 rounded-xl text-xs transition">
                Cancelar
            </button>
            <button type="button" onclick="UsersApp.saveUser()" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded-xl text-xs shadow-lg transition flex items-center justify-center gap-2">
                <i class="bi bi-check2-circle text-base"></i>Guardar Usuario
            </button>
        </div>
    </div>
</template>