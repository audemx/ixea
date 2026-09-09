<style id="saleTicketStyle">
    /* Configuración de la página física */
    @page {
        size: 80mm auto; /* Forzamos el ancho de 80mm */
        margin: 0;       /* Quitamos márgenes del navegador */
    }
    body { margin: 0; padding: 0; }

    .ticket { 
        width: 72mm;    /* Un poco menos de 80mm para evitar desbordes */
        margin: 0 auto;
        font-family: Arial, sans-serif; /* Fuente clásica de ticket */
    }
    .p-logo { text-align: center; }
    .p-logo-up { font-family: "Times New Roman", serif; font-size: 1.8rem; margin: 0; color: #a65f4b; }
    .p-logo-down { font-family: "Times New Roman", serif; font-size: 2.5rem; margin: 0; color: #a65f4b; line-height: 0.8; }
    .header { text-align: center; font-size: 0.8rem; margin-bottom: 10px; margin-top: 10px; }
    .header p { margin: 4px 0; }
    .ticket-title { text-align: center; font-weight: bold; font-size: 1.1rem; margin: 10px 0; padding: 2px; }
    .block-line { border-top: 1px solid #000; margin: 5px 0; }
    .details { font-size: 0.8rem; margin: 5px 0; }
    .details p { margin: 4px 0; }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 0.8rem; border-bottom: 1px solid #000; }
    td { font-size: 0.75rem; padding: 2px 0; }
    tbody tr { margin-top: 2px; }
    tbody .tr-details { border-bottom: 1px dashed #eee; }
    tbody .tr-details td { font-size: 0.65rem; color: #666; padding-bottom: 5px; text-align: justify; }
    .total { text-align: right; font-weight: bold; font-size: 1rem; margin-top: 10px; }
    .discount { text-align: right; font-size: 0.8rem; margin-top: 10px; display: none;}
    .footer { text-align: left; font-size: 0.7rem; margin-top: 5px; }
    .footer p { margin: 4px 0; }
    .greetings { text-align: center; font-size: 0.7rem; margin-top: 15px; }
    .greetings p { margin: 4px 0; }
    .currency:before { content: '$'; }
    @media print {
        body { margin: 0; padding: 0; }
        .ticket { width: 100%; max-width: 300px; margin: 0 auto; }
    }
</style>

<div id="saleTicketBody" hidden>
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
        <div class="details">
            <p><strong>Folio:</strong> <span class="folioTicket"></span></p>
            <p><strong>Fecha:</strong> <span class="dateTicket"></span></p>
            <p><strong>Atendió:</strong> <span class="userTicket"></span></p>
            <p><strong>Cliente:</strong> <span class="clientTicket"></span></p>
        </div>
        <div class="block-line"></div>
        <table>
            <thead>
                <tr>
                    <th style="text-align:left;">Producto</th>
                    <th style="text-align:right;">Cantidad</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody class="tbTicket"></tbody>
        </table>
        <p class="discount">Ahorro: $ <span class="discountTicket"></span></p>
        <p class="total">Total: $ <span class="totalTicket"></span></p>
        <div class="block-line"></div>
        <div class="promissory-note" style="font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5;"></div>
        <div class="footer">
            <p>Contactanos para cualquier duda o sugerencia:</p>
            <p><i class="bi bi-telephone pe-2"></i> 55-9442-3259</p>
            <p><i class="bi bi-whatsapp pe-2"></i> 55-1631-7630</p>
            <p><i class="bi bi-envelope pe-2"></i> hola@tlapayferrediego.com</p>
            <p><i class="bi bi-globe pe-2"></i> tlapayferrediego.com</p>
        </div>
        <div class="greetings">
            <div class="block-line"></div>
            <p>Documento sin valor fiscal</p>
            <p style="font-weight: bold;">¡Gracias por su compra!</p>
        </div>
    </div>
</div>

<template id="saleTicketTr">
    <tr>
        <td class="tdProduct" style="font-weight: bold; text-align: left;"></td>
        <td class="tdQuant" style="text-align: right;"></td>
        <td class="tdAmount" style="text-align: right;"></td>
    </tr>
    <tr class="tr-details">
        <td colspan="3">
            SKU: <span class="tdSku"></span> | 
            Unidad: <span class="tdUnit"></span> | 
            P. Unit: $<span class="tdUnitPrice"></span>
        </td>
    </tr>
</template>