/**
 * Controlador de ticket POS
 */

const { stringToCP850, sendRawToWindowsShare, wrapAndCenterText, wrapText, toCurrency, numberToLetters } = require('./printerUtils');

/**
 * Imprime el ticket de cuenta
 * @param {*} data 
 * @returns 
 */
async function printBillTicket(data) {
    const printerType = data.printerType || 'console';

    if (printerType === 'console') {
        consoleBillTicket(data);
        return;
    }

    if (printerType === 'usb') {
        const rawBuffer = await generateBillBuffer(data);
        await sendRawToWindowsShare(rawBuffer);
    }
}

/**
 * Imprime la comanda
 * @param {*} data 
 * @returns 
 */
async function printOrderTicket(data) {
    const printerType = data.printerType || 'console';

    if (printerType === 'console') {
        consoleOrderTicket(data);
        return;
    }

    if (printerType === 'usb') {
        const rawBuffer = await generateOrderBuffer(data);
        await sendRawToWindowsShare(rawBuffer);
    }
}

/**
 * Genera una cuenta para imprimir en consola
 * @param {*} data 
 * @returns 
 */
function consoleBillTicket(data) {
    const LINE_WIDTH = 48;
    const lines = [];

    // 1. Encabezado del Negocio
    lines.push('='.repeat(LINE_WIDTH));
    lines.push((data.store_name || 'IXEA BISTRO').padStart((LINE_WIDTH + (data.store_name || 'IXEA BISTRO').length) / 2));
    lines.push((data.store_slogan || '').padStart((LINE_WIDTH + (data.store_slogan || '').length) / 2));
    lines.push(`RFC: ${data.store_rfc || 'N/A'}`.padStart((LINE_WIDTH + 15) / 2));
    if (data.store_address) {
        const addressLines = wrapAndCenterText(data.store_address, LINE_WIDTH);
        addressLines.forEach(line => lines.push(line));
    }
    lines.push('='.repeat(LINE_WIDTH));

    // 2. Encabezado de la Orden
    lines.push(`Folio:    ${data.folio || 'N/A'}`);
    lines.push(`Fecha:    ${data.date || new Date().toLocaleString()}`);
    lines.push(`Mesa:     ${data.table_name || 'N/A'}`);
    lines.push(`Atendio:  ${data.user_name || 'Caja'}`);
    lines.push(`Cliente:  ${data.customer_name || 'Público General'}`);
    lines.push(`Cuenta:   ${data.type === 'global' ? 'Global' : data.type === 'split-equal' ? 'Partes Iguales' : data.type === 'split-person' ? 'Por Persona' : ''}`);
    lines.push('='.repeat(LINE_WIDTH));

    const isGlobal = (data.type === 'global');
    const bills = data.bills || [];

    if (isGlobal) {
        // --- MODO CUENTA GLOBAL ---
        lines.push('PRODUCTO'.padEnd(28) + 'CANT'.padStart(6) + 'SUBTOTAL'.padStart(14));
        lines.push('-'.repeat(LINE_WIDTH));

        const globalItems = (bills[0] && bills[0].items) ? bills[0].items : [];

        globalItems.forEach(item => {
            const name = (item.name || '').substring(0, 27);
            const qty = String(item.qty || 1);
            const subtotal = `$${toCurrency(item.subtotal)
                } `;

            lines.push(name.padEnd(28) + qty.padStart(6) + subtotal.padStart(14));
            lines.push(`  P.U: $${toCurrency(item.price)} `);
        });

        lines.push('-'.repeat(LINE_WIDTH));

    } else {
        // --- MODO CUENTAS DIVIDIDAS (split-person / split-equal) ---
        bills.forEach((bill) => {
            const label = `--- SUBCUENTA: ${(bill.bill_index == 0 ? 'Centro' : bill.target) || 'P' + bill.bill_index} --- `;
            lines.push(label.padStart((LINE_WIDTH + label.length) / 2));

            if (bill.items && bill.items.length > 0) {
                lines.push('PRODUCTO'.padEnd(28) + 'CANT'.padStart(6) + 'SUBTOTAL'.padStart(14));
                lines.push('-'.repeat(LINE_WIDTH));

                bill.items.forEach(item => {
                    const name = (item.name || '').substring(0, 27);
                    const qty = String(item.qty || 1);
                    const subtotal = `$${toCurrency(item.subtotal)} `;

                    lines.push(name.padEnd(28) + qty.padStart(6) + subtotal.padStart(14));
                    lines.push(`  P.U: $${toCurrency(item.price)} `);
                });
                lines.push('-'.repeat(LINE_WIDTH));
            }

            // Totales por Sub-cuenta
            lines.push(`Subtotal: $${toCurrency(bill.subtotal)} `.padStart(LINE_WIDTH));
            if (bill.discount > 0) {
                lines.push(`Descuento: $${toCurrency(bill.discount)} `.padStart(LINE_WIDTH));
            }
            lines.push(`Total: $${toCurrency(bill.total)} `.padStart(LINE_WIDTH));
            lines.push('-'.repeat(LINE_WIDTH));
        });
    }

    // 3. Totales Generales de la Orden
    lines.push('='.repeat(LINE_WIDTH));
    lines.push('RESUMEN GENERAL DE LA ORDEN'.padStart((LINE_WIDTH + 27) / 2));
    lines.push('='.repeat(LINE_WIDTH));

    lines.push(`Subtotal: $${toCurrency(data.subtotal)} `.padStart(LINE_WIDTH));
    if (data.discount > 0) {
        lines.push(`DESCUENTO: $${toCurrency(data.discount)} `.padStart(LINE_WIDTH));
    }
    lines.push(`IVA: $${toCurrency(data.iva)} `.padStart(LINE_WIDTH));
    lines.push(`TOTAL: $${toCurrency(data.total)} `.padStart(LINE_WIDTH));
    lines.push('='.repeat(LINE_WIDTH));
    lines.push('         ¡DOCUMENTO DE PRUEBA EN CONSOLA!        ');
    lines.push('='.repeat(LINE_WIDTH));

    const ticketOutput = lines.join('\n');

    console.log('\n\x1b[32m%s\x1b[0m', '>>> [TICKET GENERADO CON ÉXITO] <<<');
    console.log('\x1b[36m%s\x1b[0m', ticketOutput);
}

/**
 * Compila y genera un Buffer binario ESC/POS nativo con soporte completo para acentos y Ñ (CP850)
 */
async function generateBillBuffer(data) {
    const LINE_WIDTH = 48;
    const chunks = [];

    // Comandos binarios ESC/POS estándar
    const ESC = 0x1B;
    const GS = 0x1D;

    const INIT = Buffer.from([ESC, 0x40]);                         // Inicializar impresora
    const SELECT_CP850 = Buffer.from([ESC, 0x74, 0x02]);           // ESC t 2 -> Seleccionar Code Page CP850
    const ALIGN_CENTER = Buffer.from([ESC, 0x61, 0x01]);           // Centrar
    const ALIGN_LEFT = Buffer.from([ESC, 0x61, 0x00]);             // Alineación izquierda
    const ALIGN_RIGHT = Buffer.from([ESC, 0x61, 0x02]);            // Alineación derecha
    const TEXT_DOUBLE = Buffer.from([GS, 0x21, 0x11]);            // Texto grande (Doble alto/ancho)
    const TEXT_NORMAL = Buffer.from([GS, 0x21, 0x00]);            // Texto normal
    const CUT_PAPER = Buffer.from([GS, 0x56, 0x41, 0x03]);        // Corte de papel parcial/total

    // Helper para agregar texto convertido a CP850 con salto de línea
    const appendText = (text) => chunks.push(Buffer.concat([stringToCP850(String(text)), Buffer.from('\n')]));

    // 1. Inicialización y Selección de Tabla de Caracteres
    chunks.push(INIT);
    chunks.push(SELECT_CP850);

    // 2. Encabezado del Ticket
    chunks.push(ALIGN_CENTER);
    chunks.push(TEXT_DOUBLE);
    appendText(data.store_name || 'IXEA BISTRO');
    chunks.push(TEXT_NORMAL);
    appendText('');

    if (data.store_slogan) appendText(data.store_slogan);
    appendText(`RFC: ${data.store_rfc || 'N/A'}`);
    if (data.store_address) {
        const addressLines = wrapAndCenterText(data.store_address, LINE_WIDTH);
        addressLines.forEach(line => appendText(line));
    }
    appendText('='.repeat(LINE_WIDTH));

    // 3. Metadatos de la Orden
    chunks.push(ALIGN_LEFT);
    appendText(`Folio:    ${data.folio || 'N/A'}`);
    appendText(`Fecha:    ${data.date || new Date().toLocaleString()}`);
    appendText(`Mesa:     ${data.table_name || 'N/A'}`);
    appendText(`Atendió:  ${data.user_name || 'Caja'}`);
    appendText(`Cliente:  ${data.customer_name || 'Público General'}`);
    appendText(`Cuenta:   ${(data.type || 'global').toUpperCase()}`);
    appendText('='.repeat(LINE_WIDTH));

    const isGlobal = (data.type === 'global');
    const bills = Array.isArray(data.bills) ? data.bills : [];

    // 4. Detalle de Ítems / Productos

    if (isGlobal) {
        appendText('PRODUCTO'.padEnd(28) + 'CANT'.padStart(6) + 'SUBTOTAL'.padStart(14));
        appendText('-'.repeat(LINE_WIDTH));

        const globalItems = (bills[0] && Array.isArray(bills[0].items)) ? bills[0].items : (Array.isArray(data.items) ? data.items : []);
        globalItems.forEach(item => {
            const name = String(item.name || item.title || '').substring(0, 27);
            const qty = String(item.qty || item.quantity || 1);
            const subtotal = `$${toCurrency(item.subtotal || ((item.price || 0) * qty))}`;
            appendText(name.padEnd(28) + qty.padStart(6) + subtotal.padStart(14));
        });
        appendText('-'.repeat(LINE_WIDTH));
    } else {
        bills.forEach(bill => {
            chunks.push(ALIGN_CENTER);
            appendText(`--- SUB-CUENTA: ${bill.target || 'P' + (bill.bill_index || 0)} ---`);
            chunks.push(ALIGN_LEFT);

            const items = Array.isArray(bill.items) ? bill.items : [];
            if (items.length > 0) {
                appendText('PRODUCTO'.padEnd(28) + 'CANT'.padStart(6) + 'SUBTOTAL'.padStart(14));
                appendText('-'.repeat(LINE_WIDTH));
                items.forEach(item => {
                    const name = String(item.name || item.title || '').substring(0, 27);
                    const qty = String(item.qty || item.quantity || 1);
                    const subtotal = `$${toCurrency(item.subtotal || ((item.price || 0) * qty))}`;
                    appendText(name.padEnd(28) + qty.padStart(6) + subtotal.padStart(14));
                });
                appendText('-'.repeat(LINE_WIDTH));
            }

            chunks.push(ALIGN_RIGHT);
            appendText(`Subtotal: $${toCurrency(bill.subtotal)}`);
            appendText(`IVA:      $${toCurrency(bill.iva)}`);
            appendText(`TOTAL ${bill.target || ''}: $${toCurrency(bill.total)}`);
            chunks.push(ALIGN_LEFT);
            appendText('-'.repeat(LINE_WIDTH));
        });
    }

    // 5. Totales y Pie de Página
    chunks.push(ALIGN_CENTER);
    appendText('='.repeat(LINE_WIDTH));
    appendText('RESUMEN GENERAL DE LA ORDEN');
    appendText('='.repeat(LINE_WIDTH));

    chunks.push(ALIGN_RIGHT);
    appendText(`Subtotal:   $${toCurrency(data.subtotal)}`);
    appendText(`IVA:        $${toCurrency(data.iva)}`);
    chunks.push(TEXT_DOUBLE);
    appendText(`TOTAL: $${toCurrency(data.total)}`);
    chunks.push(TEXT_NORMAL);
    appendText('='.repeat(LINE_WIDTH));

    chunks.push(ALIGN_CENTER);
    appendText('Documento sin valor fiscal');
    appendText('¡Gracias por su visita!\n\n\n');
    chunks.push(CUT_PAPER);


    return Buffer.concat(chunks);
}

/**
 * Genera una comanda de prueba para imprimir en consola
 */
async function consoleOrderTicket(data) {
    const LINE_WIDTH = 48;

    if (!data || !Array.isArray(data.stations)) return;

    for (const stationGroup of data.stations) {
        const lines = [];

        lines.push('='.repeat(LINE_WIDTH));
        lines.push(`*** COMANDA: ${stationGroup.station.toUpperCase()} ***`.padStart((LINE_WIDTH + stationGroup.station.length + 15) / 2));
        lines.push('='.repeat(LINE_WIDTH));
        lines.push(`Fecha:    ${new Date().toLocaleString()}`);
        lines.push(`Mesa:     ${data.table || 'N/A'}`);
        lines.push(`Mesero:   ${data.waiter || 'N/A'}`);
        lines.push('='.repeat(LINE_WIDTH));
        lines.push('CANT        PRODUCTO           [TARGET]');
        lines.push('-'.repeat(LINE_WIDTH));

        stationGroup.items.forEach(item => {
            const targetLabel = item.target > 0 ? ` [P${item.target}]` : ' [C]';

            // Construimos la línea completa inicial
            const rawLine = `${String(item.qty).padStart(4)}  ${item.name}${targetLabel}`;

            // Obtenemos un array de líneas (si excede 48 caracteres, se divide con 6 espacios de sangría)
            const formattedLines = wrapText(rawLine, LINE_WIDTH, 6);

            formattedLines.forEach(line => {
                lines.push(line); // O appendText(line) si es en generateOrderBuffer
            });

            if (item.notes) {
                lines.push(`      NOTA: ${item.notes}`);
            }
            if (Array.isArray(item.modifiers)) {
                item.modifiers.forEach(mod => {
                    lines.push(`       -> ${mod}`);
                });
            }
        });

        lines.push('='.repeat(LINE_WIDTH));
        lines.push('\n\n');

        const ticketOutput = lines.join('\n');
        console.log(`\n\x1b[33m%s\x1b[0m`, `>>> [COMANDA ESTACIÓN: ${stationGroup.station.toUpperCase()}] <<<`);
        console.log('\x1b[36m%s\x1b[0m', ticketOutput);
    }
}

/**
 * Genera el buffer de la comanda para imprimir en hardware ESC/POS
 */
async function generateOrderBuffer(data) {
    const LINE_WIDTH = 48;
    const chunks = [];

    const ESC = 0x1B;
    const GS = 0x1D;

    const INIT = Buffer.from([ESC, 0x40]);
    const SELECT_CP850 = Buffer.from([ESC, 0x74, 0x02]);
    const ALIGN_CENTER = Buffer.from([ESC, 0x61, 0x01]);
    const ALIGN_LEFT = Buffer.from([ESC, 0x61, 0x00]);
    const TEXT_DOUBLE = Buffer.from([GS, 0x21, 0x11]);
    const TEXT_NORMAL = Buffer.from([GS, 0x21, 0x00]);
    const CUT_PAPER = Buffer.from([GS, 0x56, 0x41, 0x03]);

    const appendText = (text) => chunks.push(Buffer.concat([stringToCP850(String(text)), Buffer.from('\n')]));

    for (const stationGroup of data.stations) {
        chunks.push(INIT);
        chunks.push(SELECT_CP850);

        chunks.push(ALIGN_CENTER);
        chunks.push(TEXT_DOUBLE);
        appendText(`*** COMANDA: ${stationGroup.station.toUpperCase()} ***`);
        chunks.push(TEXT_NORMAL);
        appendText('');
        appendText('='.repeat(LINE_WIDTH));

        chunks.push(ALIGN_LEFT);
        appendText(`Fecha:    ${new Date().toLocaleString()}`);
        appendText(`Mesa:     ${data.table || 'N/A'}`);
        appendText(`Mesero:   ${data.waiter || 'N/A'}`);
        appendText('='.repeat(LINE_WIDTH));

        appendText('CANT  PRODUCTO / MODIFICADORES');
        appendText('-'.repeat(LINE_WIDTH));

        stationGroup.items.forEach(item => {
            const targetLabel = item.target > 0 ? ` [P${item.target}]` : ' [C]';

            // Construimos la línea completa inicial
            const rawLine = `${String(item.qty).padStart(4)}  ${item.name}${targetLabel}`;

            // Obtenemos un array de líneas (si excede 48 caracteres, se divide con 6 espacios de sangría)
            const formattedLines = wrapText(rawLine, LINE_WIDTH, 6);

            formattedLines.forEach(line => {
                lines.push(line); // O appendText(line) si es en generateOrderBuffer
            });

            if (item.notes) {
                lines.push(`      NOTA: ${item.notes}`);
            }
            if (Array.isArray(item.modifiers)) {
                item.modifiers.forEach(mod => {
                    lines.push(`       -> ${mod}`);
                });
            }
        });

        appendText('\n\n\n');
        chunks.push(CUT_PAPER);
    }

    return Buffer.concat(chunks);
}

module.exports = {
    printBillTicket,
    printOrderTicket
};