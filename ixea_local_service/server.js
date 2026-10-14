// =========================================================================
// MICROSERVICIO LOCAL DE IMPRESIÓN - IXEA BISTRO
// Servidor HTTPS Express + Envio RAW a Cola de Impresión Windows
// =========================================================================

const express = require('express');
const fs = require('fs');
const https = require('https');
const path = require('path');
const { exec } = require('child_process');

// Importar utilidades organizadas en módulos externos
const { printBillTicket, printOrderTicket } = require('./utils/PosTicket');

const app = express();

// =========================================================================
// CONFIGURACIÓN GLOBAL Y VARIABLES DE ENTORNO
// =========================================================================
const LOCAL_AUTH_TOKEN = 'IxeaDevLocal2026.';
const PRINTER_SHARE_NAME = 'IXEAPOS80'; // Nombre en "Uso Compartido" de Windows
const PORT = 9100;

const allowedOrigins = [
    'https://eros.ixea.mx',
    'http://eros.localhost:8000',
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    'http://localhost'
];

// =========================================================================
// 1. CONFIGURACIÓN UNIFICADA DE CORS Y PRIVATE NETWORK ACCESS (PNA)
// =========================================================================
app.use((req, res, next) => {
    const origin = req.headers.origin;

    if (!origin || allowedOrigins.includes(origin) || origin.endsWith('.localhost:8000') || origin.includes('ixea.mx')) {
        res.setHeader('Access-Control-Allow-Origin', origin || '*');
    } else {
        res.setHeader('Access-Control-Allow-Origin', 'https://eros.ixea.mx');
    }

    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Local-Token, x-local-token, Access-Control-Allow-Private-Network');
    res.setHeader('Access-Control-Allow-Credentials', 'true');
    res.setHeader('Access-Control-Allow-Private-Network', 'true');

    if (req.method === 'OPTIONS') {
        return res.sendStatus(204);
    }

    next();
});

app.use(express.json());

// =========================================================================
// 2. MIDDLEWARE DE SEGURIDAD LOCAL
// =========================================================================
app.use((req, res, next) => {
    if (LOCAL_AUTH_TOKEN) {
        const clientToken = req.headers['x-local-token'];

        if (!clientToken || clientToken !== LOCAL_AUTH_TOKEN) {
            console.warn(`[Security] Intento de acceso no autorizado desde IP: ${req.ip}`);
            return res.status(401).json({
                success: false,
                message: 'Acceso no autorizado al microservicio local de impresión.'
            });
        }
    }
    next();
});

// =========================================================================
// 3. ENDPOINTS DE LA API REST
// =========================================================================

app.post('/bill', async (req, res) => {
    const data = req.body.ticket;

    if (!data || (!data.folio && !data.order_id)) {
        return res.status(400).json({
            success: false,
            message: 'Estructura de ticket inválida. Se requiere folio u order_id.'
        });
    }

    try {
        await printBillTicket(data);
        console.log(`[PrintService] Ticket impreso con éxito. Folio: ${data.folio || data.order_id}`);
        return res.json({ success: true, message: 'Ticket de cuenta impreso correctamente.' });
    } catch (error) {
        console.error('[PrintService Error]:', error.message);
        return res.status(500).json({ success: false, message: error.message });
    }
});

app.post('/order', async (req, res) => {
    const data = req.body.order;

    if (!data || !data.stations || !Array.isArray(data.stations)) {
        return res.status(400).json({
            success: false,
            message: 'Estructura de comanda inválida. Se requiere el objeto order con stations.'
        });
    }

    try {
        await printOrderTicket(data);
        console.log(`[PrintService] Comanda impresa con éxito.`);
        return res.json({ success: true, message: 'Comanda impresa correctamente.' });
    } catch (error) {
        console.error('[PrintService Order Error]:', error.message);
        return res.status(500).json({ success: false, message: error.message });
    }
});

// =========================================================================
// 4. CARGA DE CERTIFICADOS SSL Y ARRANQUE DEL SERVIDOR
// =========================================================================

const files = fs.readdirSync(__dirname);
const keyFile = files.find(f => f.endsWith('-key.pem'));
const certFile = files.find(f => f.endsWith('.pem') && !f.endsWith('-key.pem'));

if (!keyFile || !certFile) {
    console.error('[Error] No se encontraron los certificados SSL (mkcert) en el directorio raíz.');
    process.exit(1);
}

const options = {
    key: fs.readFileSync(path.join(__dirname, keyFile)),
    cert: fs.readFileSync(path.join(__dirname, certFile))
};

https.createServer(options, app).listen(PORT, '0.0.0.0', () => {
    console.log(`=======================================================`);
    console.log(` Servidor seguro de impresión activo en puerto ${PORT}`);
    console.log(` https://192.168.1.200:${PORT} / https://local.ixea.mx:${PORT}`);
    console.log(` Recursos compartidos Windows target: \\\\127.0.0.1\\${PRINTER_SHARE_NAME}`);
    console.log(`=======================================================`);
});