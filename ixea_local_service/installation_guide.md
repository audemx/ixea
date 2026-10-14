# GUÍA DE INSTALACIÓN: IXEA Local Print Service (Windows)

Esta guía detalla los pasos para desplegar el microservicio local en la PC de caja del establecimiento, conectarlo a una impresora térmica USB y asegurar su ejecución automática al encender el sistema.

---

## Requisitos Previos en la PC Windows
1. **Node.js (Versión LTS recomendada: 18.x o superior):** Descargar e instalar desde [nodejs.org](https://nodejs.org/). Durante la instalación, asegúrate de marcar la opción *"Automatically install the necessary tools"*.
2. **Controlador de la impresora térmica:** Asegúrate de que la impresora térmica (ej. Epson, Xprinter, Star, etc.) esté conectada por **USB** y cuente con sus drivers oficiales instalados en Windows para que el sistema la reconozca.

---

## Paso 1: Ubicación del Servidor Local
1. Descarga y descomprime la carpeta del microservicio **`ixea_local_server/`** en una ruta fija del disco duro de la PC, por ejemplo:
   ```text
   C:\ixea_local_server\
   ```
2. La estructura dentro de la carpeta debe ser la siguiente:
   ```text
   ixea_local_server/
   ├── utils/
   │   └── numberToLetters.js
   ├── .env
   ├── package.json
   └── server.js
   ```

---

## Paso 2: Configuración del archivo `.env`
Abre el archivo `.env` dentro de la carpeta `C:\ixea_local_server\` con un editor de texto (como Notepad o VS Code) y configúralo de la siguiente manera:

```env
PORT=9100
IXEA_CLOUD_URL=[http://tu-dominio-en-nube.com/api/v1](http://tu-dominio-en-nube.com/api/v1)
LOCAL_AUTH_TOKEN=IxeaDevLocal2026.
PRINTER_TYPE=usb
```
*Nota:* Al configurar `PRINTER_TYPE=usb`, la librería utilizará el adaptador USB predeterminado para comunicarse directamente con la impresora térmica conectada al equipo.

---

## Paso 3: Instalación de Dependencias
1. Abre la terminal de comandos de Windows (**CMD** o **PowerShell**).
2. Navega hasta la carpeta del servidor local:
   ```cmd
   cd C:\ixea_local_server
   ```
3. Instala las dependencias del proyecto ejecutando:
   ```cmd
   npm install
   ```

---

## Paso 4: Configurar Inicio Automático al Encender la PC (Autostart)
Para evitar que el personal tenga que abrir una ventana negra de comandos cada vez que se encienda la caja, configuraremos **PM2** para que corra como un servicio invisible de fondo en Windows.

1. Instala PM2 y el complemento de arranque para Windows de forma global en la terminal (ejecutar como Administrador si es necesario):
   ```cmd
   npm install -g pm2 pm2-windows-startup
   ```
2. Configura el servicio de inicio para Windows:
   ```cmd
   pm2-startup install
   ```
3. Inicia y registra tu microservicio local apuntando al archivo `server.js`:
   ```cmd
   cd C:\ixea_local_server
   pm2 start server.js --name "ixea-print-service"
   ```
4. Guarda el estado actual para que se relance automáticamente si la PC se reinicia o apaga:
   ```cmd
   pm2 save
   ```

> **Verificación:** A partir de este momento, cada vez que enciendas la PC de caja, el servicio arrancará solo en segundo plano. Puedes verificar su estado en cualquier momento ejecutando `pm2 status`.

---

## Paso 5: Configuración de Red Local y Firewall (Para Tablets / POS Externos)

Si las órdenes se envían desde tablets conectadas por Wi-Fi hacia esta PC servidor:

1. **Firewall de Windows:** Asegúrate de permitir el tráfico entrante por el puerto **9100**. 
   * Abre *Firewall de Windows con seguridad avanzada* -> *Reglas de entrada* -> *Nueva regla* -> Selecciona **Puerto** -> TCP -> Puerto `9100` -> *Permitir la conexión*.
2. **IP Estática / Nombre mDNS:** 
   * Asigna una IP fija (DHCP Reservation) a esta PC en el módem/router del restaurante (ej. `192.168.1.200`).
   * O bien, asegúrate de que el nombre de la PC en Windows sea reconocible en la red local (ej. `ixea-server`).

---

## Paso 6: Impresión 100% Silenciosa (Sin Cuadros de Diálogo)

Gracias a esta arquitectura basada en microservicios locales:
* **Cero Clics de Confirmación:** Cuando el cajero o mesero presiona "Solicitar Cuenta" o "Pagar" en la tablet o POS web, la orden viaja a través de la red local directamente al puerto `9100` de este servidor.
* **Acceso Directo USB:** El microservicio procesa los comandos ESC/POS y los envía directo al controlador USB de la impresora térmica en milisegundos.
* **No se abre ninguna ventana de impresión del navegador**, garantizando una experiencia de punto de venta rápido y profesional.