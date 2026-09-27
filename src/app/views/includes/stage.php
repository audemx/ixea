<?php
/**
 * IXEA OS - Contenedor Principal de Escenarios (Stage Manager)
 * /src/app/views/includes/stage-manager.php
 */

$viewsPath = dirname(__DIR__);
?>

<main id="os-viewport">
    <!-- Escenario 0: Splash Screen / Introducción -->
    <div id="stage-0" class="stage active">
        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center intro-wrapper overflow-hidden">
            <div style="min-height: 120px;" class="d-flex flex-column justify-content-center">
                <p>
                    <span id="intro-welcome" class="display-2 intro-text text-dark mb-0">BIENVENIDO </span>
                    <span id="intro-a" class="display-2 intro-text text-dark mb-0" style="visibility: hidden;">A</span>
                </p>
                <p id="intro-logo" class="display-1 intro-text fw-bold text-dark" style="visibility: hidden; height: 0; margin: 0;">
                    IXEA <span class="text-xaccent">EROS</span>
                </p>
            </div>
            
            <div class="d-flex gap-4 mt-2" id="intro-slogan">
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Intuitivo.</span>
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Rápido.</span>
                <span class="text-xaccent fs-5 slogan-word" style="visibility: hidden;">Brutal.</span>
            </div>
        </div>
    </div>

    <!-- Escenario Launchpad (Cargado desde su propio include) -->
    <?php include $viewsPath . '/includes/launchpad.php'; ?>

    <!-- Contenedor para aplicaciones dinámicas activas -->
    <div id="dynamic-stages" class="p-3"></div>
</main>

<template id="bouncer-template">
    <h3 class="font-bold text-lg text-white mb-1">Autorización</h3>
    <p class="text-xs text-slate-400 mb-4">Ingresa tu PIN de 4 dígitos</p>

    <div class=" flex justify-center space-x-3 mb-6">
        <div class="pin-dot w-3.5 h-3.5 rounded-full bg-slate-500"></div>
        <div class="pin-dot w-3.5 h-3.5 rounded-full bg-slate-500"></div>
        <div class="pin-dot w-3.5 h-3.5 rounded-full bg-slate-500"></div>
        <div class="pin-dot w-3.5 h-3.5 rounded-full bg-slate-500"></div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-2">
        <button onclick="IxeaBouncer.handlePinInput('1')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">1</button>
        <button onclick="IxeaBouncer.handlePinInput('2')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">2</button>
        <button onclick="IxeaBouncer.handlePinInput('3')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">3</button>
        <button onclick="IxeaBouncer.handlePinInput('4')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">4</button>
        <button onclick="IxeaBouncer.handlePinInput('5')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">5</button>
        <button onclick="IxeaBouncer.handlePinInput('6')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">6</button>
        <button onclick="IxeaBouncer.handlePinInput('7')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">7</button>
        <button onclick="IxeaBouncer.handlePinInput('8')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">8</button>
        <button onclick="IxeaBouncer.handlePinInput('9')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">9</button>
        <button onclick="IxeaBouncer.handlePinInput('cancel')" class="bg-rose-500/20 text-rose-300 py-3 rounded-xl font-bold text-xs border border-rose-500/30">Cancel</button>
        <button onclick="IxeaBouncer.handlePinInput('0')" class="bg-slate-900 active:bg-slate-700 py-3 rounded-xl font-bold text-lg border border-slate-700">0</button>
        <button onclick="IxeaBouncer.handlePinInput('delete')" class="bg-slate-700 text-slate-300 py-3 rounded-xl font-bold text-lg border border-slate-700">⌫</button>
    </div>
</template>
    
