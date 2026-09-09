<style id="statementTicketStyle">
    /* Hereda los de saleTicketStyle, añadimos variantes */
    .st-summary { font-size: 0.8rem; margin-bottom: 10px; }
    .st-summary table { width: 100%; }
    .st-summary td { padding: 1px 0; }
    .text-red { color: #000; font-weight: bold; } /* En ticket térmico no hay color, usamos negrita */
    .bg-gray { background-color: #f0f0f0; -webkit-print-color-adjust: exact; }
</style>

<div id="statementTicketBody" hidden>
    <div class="ticket">
        <div class="p-logo">
            <p class="p-logo-up">Tlapa y Ferre</p>
            <p class="p-logo-down">Diego</p>
        </div>
        <div class="header">
            <p>"Todo para el carpintero"</p>
            <p>MIRE680611NX4</p>
            <p>Ecatepec, Edo. Mex.</p>
        </div>
        
        <div class="block-line"></div>
        <div class="ticket-title">ESTADO DE CUENTA</div>
        <div class="details">
            <p><strong>Cliente:</strong> <span class="stClient"></span></p>
            <p><strong>RFC:</strong> <span class="stTaxId"></span></p>
            <p><strong>Periodo:</strong> <span class="stPeriod"></span></p>
            <p><strong>Fecha Impresión:</strong> <span class="stPrintDate"></span></p>
        </div>
        
        <div class="block-line"></div>
        <div class="st-summary">
            <table>
                <tr><td>Saldo Anterior:</td><td style="text-align:right;">$ <span class="stPrevBalance"></span></td></tr>
                <tr><td>(+) Cargos del Periodo:</td><td style="text-align:right;">$ <span class="stPeriodCargos"></span></td></tr>
                <tr><td>(-) Abonos del Periodo:</td><td style="text-align:right;">$ <span class="stPeriodAbonos"></span></td></tr>
                <tr style="font-weight:bold; font-size: 0.9rem; border-top: 1px solid #000;">
                    <td>SALDO TOTAL ACTUAL:</td>
                    <td style="text-align:right;">$ <span class="stTotalBalance"></span></td>
                </tr>
            </table>
        </div>

        <div class="block-line"></div>
        <p style="font-size: 0.7rem; font-weight: bold; text-align: center; margin: 2px 0;">TICKETS PENDIENTES DE PAGO</p>
        <table class="stPendingTable">
            <tbody class="stPendingBody"></tbody>
        </table>
        
        <div class="block-line"></div>
        <p style="font-size: 0.7rem; font-weight: bold; text-align: center; margin: 2px 0;">HISTORIAL DE MOVIMIENTOS</p>
        
        <table>
            <thead>
                <tr>
                    <th style="text-align:left;">Fecha/Ref</th>
                    <th style="text-align:right;">Cargo</th>
                    <th style="text-align:right;">Abono</th>
                </tr>
            </thead>
            <tbody class="stHistoryBody"></tbody>
        </table>

        <div class="block-line"></div>
        <div class="greetings">
            <p>Este documento es informativo y no constituye un recibo de pago oficial por el saldo total.</p>
            <p style="font-weight: bold;">¡Gracias por su preferencia!</p>
        </div>
    </div>
</div>

<template id="stHistoryTr">
    <tr style="border-bottom: 0.5px dashed #ccc;">
        <td class="stDateRef" style="font-size: 0.7rem;"></td>
        <td class="stCargo text-right" style="text-align: right;"></td>
        <td class="stAbono text-right" style="text-align: right;"></td>
    </tr>
</template>

<template id="stPendingTr">
    <tr style="border-bottom: 0.5px dashed #ccc;">
        <td style="font-size: 0.7rem;">
            <b class="stFolioP"></b> <br>
            <span class="stDaysP"></span> días vencido
        </td>
        <td class="stAmountP" style="text-align: right; vertical-align: bottom; font-weight: bold;"></td>
    </tr>
</template>