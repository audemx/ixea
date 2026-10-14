# ==========================================
# IXEA Local Service - Instalador Local (Sin descargas externas)
# Debe ejecutarse como Administrador
# ==========================================

$InstallDir = "C:\ixea_local_service"
$TargetIP = "192.168.100.200"
$Domain = "local.ixea.mx"
$ServiceName = "ixea-local-service"
$FirewallRuleName = "IXEA Local Service"

Write-Host "=== INICIANDO INSTALACIÓN DE IXEA LOCAL SERVICE ===" -ForegroundColor Cyan
Write-Host "[ATENCIÓN] Asegúrate de que esta PC tenga asignada o amarrada la IP estática: $TargetIP" -ForegroundColor Yellow

# 1. Verificar si Node.js está instalado
if (!(Get-Command "node" -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] Node.js no está instalado en este equipo. Por favor instálalo desde https://nodejs.org/" -ForegroundColor Red
    Read-Host "Presiona Enter para salir"
    exit
}

# 2. Localizar el paquete local ixea_local_service.zip en el directorio actual
$SourceZipPath = Join-Path $PSScriptRoot "ixea_local_service.zip"

if (!(Test-Path $SourceZipPath)) {
    Write-Host "[ERROR] No se encontró el archivo 'ixea_local_service.zip' en la carpeta actual ($PSScriptRoot)." -ForegroundColor Red
    Write-Host "Asegúrate de haber descargado el archivo .zip en la misma carpeta que este script." -ForegroundColor Yellow
    Read-Host "Presiona Enter para salir"
    exit
}

# 3. Detener el servicio previo si existe en PM2 para liberar archivos bloqueados
if (Get-Command "pm2" -ErrorAction SilentlyContinue) {
    Write-Host "[INFO] Deteniendo el servicio '$ServiceName' si está activo..." -ForegroundColor Yellow
    pm2 stop $ServiceName 2>$null | Out-Null
}

# 4. Crear directorio de instalación y descomprimir archivos
if (!(Test-Path $InstallDir)) {
    New-Item -ItemType Directory -Path $InstallDir | Out-Null
    Write-Host "[OK] Carpeta creada en $InstallDir" -ForegroundColor Green
}

Write-Host "[INFO] Extrayendo archivos desde el ZIP local hacia $InstallDir..." -ForegroundColor Yellow
try {
    Expand-Archive -Path $SourceZipPath -DestinationPath $InstallDir -Force
    Write-Host "[OK] Archivos extraídos correctamente." -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Ocurrió un fallo al descomprimir el archivo ZIP local." -ForegroundColor Red
    Write-Error $_.Exception.Message
    Read-Host "Presiona Enter para salir"
    exit
}

# Cambiar la ruta de trabajo al directorio de instalación
Set-Location $InstallDir

# 5. Renombrar / Preparar mkcert.exe
$MkcertExecutable = "$InstallDir\mkcert-v1.4.4-windows-amd64.exe"
$MkcertPath = "$InstallDir\mkcert.exe"

# Si viene con el nombre original del binario descargado de GitHub, lo renombramos a mkcert.exe
if ((Test-Path $MkcertExecutable) -and !(Test-Path $MkcertPath)) {
    Rename-Item -Path $MkcertExecutable -NewName "mkcert.exe" -Force
}

if (!(Test-Path $MkcertPath)) {
    Write-Host "[ERROR] No se encontró el ejecutable 'mkcert.exe' ni 'mkcert-v1.4.4-windows-amd64.exe' en la instalación." -ForegroundColor Red
    Read-Host "Presiona Enter para salir"
    exit
}

# 6. Generar / Reinstalar CA raíz y Certificados SSL locales
Write-Host "[INFO] Reinstalando la CA raíz e instalando certificados SSL..." -ForegroundColor Yellow

# Forzar la reinstalación del Certificate Authority local
& $MkcertPath -install

# Generar / sobrescribir los certificados SSL
& $MkcertPath -key-file "$InstallDir\localhost-key.pem" -cert-file "$InstallDir\localhost.pem" localhost 127.0.0.1 $TargetIP $Domain

# Copiar la CA Raíz (rootCA.pem) al Escritorio del usuario como IxeaCA.pem
try {
    $RootCARoot = (& $MkcertPath -CAROOT).Trim()
    $RootCAPath = Join-Path $RootCARoot "rootCA.pem"
    
    # Ruta dinámica del escritorio del usuario actual
    $DesktopPath = [System.Environment]::GetFolderPath([System.Environment+SpecialFolder]::Desktop)
    $DestinationCAPath = Join-Path $DesktopPath "IxeaCA.pem"

    if (Test-Path $RootCAPath) {
        Copy-Item -Path $RootCAPath -Destination $DestinationCAPath -Force
        Write-Host "[OK] Se copió el certificado raíz a: $DestinationCAPath" -ForegroundColor Green
    } else {
        Write-Host "[ADVERTENCIA] No se encontró el archivo rootCA.pem en $RootCARoot" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[ADVERTENCIA] Ocurrió un inconveniente al copiar IxeaCA.pem al Escritorio: $_" -ForegroundColor Yellow
}

# 7. Instalar dependencias de Node.js
Write-Host "[INFO] Instalando dependencias de Node.js (npm install)..." -ForegroundColor Yellow
npm install

# 8. Configurar Firewall para el puerto 9100
Write-Host "[INFO] Configurando regla en el Firewall de Windows para $FirewallRuleName (Puerto 9100)..." -ForegroundColor Yellow

# Eliminar regla previa si existe para evitar duplicados y reasignarla limpia
Remove-NetFirewallRule -DisplayName $FirewallRuleName -ErrorAction SilentlyContinue
New-NetFirewallRule -DisplayName $FirewallRuleName -Direction Inbound -Protocol TCP -LocalPort 9100 -Action Allow -ErrorAction SilentlyContinue | Out-Null

# 9. Configurar PM2 para arranque automático
Write-Host "[INFO] Configurando inicio automático con PM2..." -ForegroundColor Yellow

# Instalar módulos globales silenciosamente
npm install -g pm2 pm2-windows-startup | Out-Null
pm2-startup install | Out-Null

# Eliminar instancia previa de este servicio para iniciar limpio
pm2 delete $ServiceName 2>$null | Out-Null

# Iniciar servicio actualizado
pm2 start server.js --name $ServiceName
pm2 save

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "¡INSTALACIÓN Y CONFIGURACIÓN COMPLETADA!" -ForegroundColor Green
Write-Host "El servicio corre de forma segura en:" -ForegroundColor Cyan
Write-Host "  - https://${TargetIP}:9100" -ForegroundColor Cyan
Write-Host "  - https://${Domain}:9100" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Read-Host "Presiona Enter para finalizar"