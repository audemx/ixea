/**
 *  Funciones de utilidad para la impresión de tickets
 */

/**
 * Convierte una cadena de texto a una cadena de texto codificada en CP850
 * @param {string} str - El número a formatear
 * @returns {Buffer} - El número formateado a moneda mexicana
 */
function stringToCP850(str) {
    const cp850Map = {
        'á': 0xA0, 'é': 0x82, 'í': 0xA1, 'ó': 0xA2, 'ú': 0xA3,
        'Á': 0xB5, 'É': 0x90, 'Í': 0xD6, 'Ó': 0xE0, 'Ú': 0xE9,
        'ñ': 0xA4, 'Ñ': 0xA5, 'ü': 0x81, 'Ü': 0x9A, '¿': 0xA8, '¡': 0xAD
    };

    const buf = Buffer.alloc(str.length);
    for (let i = 0; i < str.length; i++) {
        const char = str[i];
        const code = str.charCodeAt(i);
        if (cp850Map[char] !== undefined) {
            buf[i] = cp850Map[char];
        } else if (code < 128) {
            buf[i] = code;
        } else {
            buf[i] = 0x3F;
        }
    }
    return buf;
}

/**
 * Envía el Buffer binario directamente al recurso compartido local de Windows
 * @param {Buffer} buffer - El buffer binario a enviar
 * @returns {Promise<boolean>} - Una promesa que se resuelve cuando el buffer ha sido enviado
 */
function sendRawToWindowsShare(buffer) {
    return new Promise((resolve, reject) => {
        if (!buffer || buffer.length === 0) {
            return reject(new Error('El buffer binario de impresión está vacío (0 bytes).'));
        }

        const tempFilePath = path.join(__dirname, `temp_print_${Date.now()}.bin`);

        fs.writeFile(tempFilePath, buffer, (err) => {
            if (err) return reject(new Error(`Error al escribir archivo binario temporal: ${err.message}`));

            const cmd = `cmd.exe /c copy /b "${tempFilePath}" "\\\\127.0.0.1\\${PRINTER_SHARE_NAME}"`;

            exec(cmd, (error, stdout, stderr) => {
                if (fs.existsSync(tempFilePath)) fs.unlinkSync(tempFilePath);

                if (error) {
                    return reject(new Error(`Error en el spooler de Windows: ${error.message}`));
                }
                resolve(true);
            });
        });
    });
}

/**
 * Divide un texto largo en varias líneas respetando el ancho máximo de la impresora
 * y centra cada línea resultante.
 */
function wrapAndCenterText(text, maxWidth = 48) {
    if (!text) return [];

    const words = text.trim().split(/\s+/);
    const lines = [];
    let currentLine = '';

    for (const word of words) {
        const testLine = currentLine ? `${currentLine} ${word}` : word;
        if (testLine.length <= maxWidth) {
            currentLine = testLine;
        } else {
            if (currentLine) {
                const padding = Math.floor((maxWidth - currentLine.length) / 2);
                lines.push(' '.repeat(Math.max(0, padding)) + currentLine);
            }
            currentLine = word;
        }
    }

    if (currentLine) {
        const padding = Math.floor((maxWidth - currentLine.length) / 2);
        lines.push(' '.repeat(Math.max(0, padding)) + currentLine);
    }

    return lines;
}

/**
 * 
 * @param {*} text 
 * @param {*} maxWidth 
 * @param {*} indent 
 * @returns 
 */
function wrapText(text, maxWidth = 48, indent = 6) {
    if (!text) return [''];
    const words = text.trim().split(/\s+/);
    const lines = [];
    let currentLine = '';

    for (const word of words) {
        // La primera línea usa todo el ancho; las siguientes restan el espacio de la sangría
        const limit = lines.length === 0 ? maxWidth : (maxWidth - indent);
        const testLine = currentLine ? `${currentLine} ${word}` : word;

        if (testLine.length <= limit) {
            currentLine = testLine;
        } else {
            if (currentLine) {
                lines.push(lines.length === 0 ? currentLine : ' '.repeat(indent) + currentLine);
            }
            currentLine = word;
        }
    }

    if (currentLine) {
        lines.push(lines.length === 0 ? currentLine : ' '.repeat(indent) + currentLine);
    }

    return lines;
}

/**
 *  Formatea un número a formato de moneda mexicana
 * @param {number} amount - El número a formatear
 * @returns {string} - El número formateado a moneda mexicana
 */
function toCurrency(amount) {
    return Number(amount || 0).toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Convierte un número a letras
 * @param {number} num - El número a convertir
 * @returns {string} - El número convertido a letras
 */
function _helperUnidades(num) {
    const u = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    return u[num] || '';
}

/**
 * Convierte un número a letras
 * @param {number} num - El número a convertir
 * @returns {string} - El número convertido a letras
 */
function _helperDecenas(num) {
    let decena = Math.floor(num / 10);
    let unidad = num - (decena * 10);

    switch (decena) {
        case 1:
            const especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE'];
            return unidad <= 5 ? especiales[unidad] : 'DIECI' + _helperUnidades(unidad);
        case 2: return unidad === 0 ? 'VEINTE' : 'VEINTI' + _helperUnidades(unidad);
        case 3: return unidad === 0 ? 'TREINTA' : 'TREINTA Y ' + _helperUnidades(unidad);
        case 4: return unidad === 0 ? 'CUARENTA' : 'CUARENTA Y ' + _helperUnidades(unidad);
        case 5: return unidad === 0 ? 'CINCUENTA' : 'CINCUENTA Y ' + _helperUnidades(unidad);
        case 6: return unidad === 0 ? 'SESENTA' : 'SESENTA Y ' + _helperUnidades(unidad);
        case 7: return unidad === 0 ? 'SETENTA' : 'SETENTA Y ' + _helperUnidades(unidad);
        case 8: return unidad === 0 ? 'OCHENTA' : 'OCHENTA Y ' + _helperUnidades(unidad);
        case 9: return unidad === 0 ? 'NOVENTA' : 'NOVENTA Y ' + _helperUnidades(unidad);
        default: return _helperUnidades(unidad);
    }
}

/**
 * Convierte un número a letras
 * @param {number} num - El número a convertir
 * @returns {string} - El número convertido a letras
 */
function _helperCentenas(num) {
    let centena = Math.floor(num / 100);
    let resto = num - (centena * 100);

    switch (centena) {
        case 1: return resto > 0 ? 'CIENTO ' + _helperDecenas(resto) : 'CIEN';
        case 2: return 'DOSCIENTOS ' + _helperDecenas(resto);
        case 3: return 'TRESCIENTOS ' + _helperDecenas(resto);
        case 4: return 'CUATROCIENTOS ' + _helperDecenas(resto);
        case 5: return 'QUINIENTOS ' + _helperDecenas(resto);
        case 6: return 'SEISCIENTOS ' + _helperDecenas(resto);
        case 7: return 'SETECIENTOS ' + _helperDecenas(resto);
        case 8: return 'OCHOCIENTOS ' + _helperDecenas(resto);
        case 9: return 'NOVECIENTOS ' + _helperDecenas(resto);
        default: return _helperDecenas(resto);
    }
}

/**
 * Convierte un número a letras
 * @param {number} num - El número a convertir
 * @returns {string} - El número convertido a letras
 */
function _helperMiles(num) {
    let divisor = 1000;
    let cientos = Math.floor(num / divisor);
    let resto = num - (cientos * divisor);

    let strMiles = '';
    if (cientos === 1) strMiles = 'UN MIL';
    if (cientos > 1) strMiles = _helperCentenas(cientos) + ' MIL';

    return `${strMiles} ${_helperCentenas(resto)}`.trim();
}

/**
 * Convierte un número a letras
 * @param {number} num - El número a convertir
 * @returns {string} - El número convertido a letras
 */
function _helperMillones(num) {
    let divisor = 1000000;
    let millones = Math.floor(num / divisor);
    let resto = num - (millones * divisor);

    let strMillones = '';
    if (millones === 1) strMillones = 'UN MILLON DE';
    if (millones > 1) strMillones = _helperCentenas(millones) + ' MILLONES DE';

    return `${strMillones} ${_helperMiles(resto)}`.trim();
}

/**
 * Convierte un número a letras
 * @param {number} num - El número a convertir
 * @returns {string} - El número convertido a letras
 */
function numberToLetters(num) {
    const data = {
        numero: num,
        enteros: Math.floor(num),
        centavos: Math.round(num * 100) - Math.floor(num) * 100,
        letrasMonedaPlural: 'PESOS',
        letrasMonedaSingular: 'PESO',
        letrasMonedaCentavo: '/100 M.N.'
    };

    let centavosStr = (data.centavos < 10 ? '0' : '') + data.centavos + data.letrasMonedaCentavo;

    if (data.enteros === 0) return `CERO ${data.letrasMonedaPlural} ${centavosStr}`;

    const nombreMoneda = (data.enteros === 1) ? data.letrasMonedaSingular : data.letrasMonedaPlural;
    return `${_helperMillones(data.enteros)} ${nombreMoneda} ${centavosStr}`.toUpperCase().trim();
}

module.exports = {
    stringToCP850,
    sendRawToWindowsShare,
    wrapAndCenterText,
    wrapText,
    toCurrency,
    numberToLetters
};