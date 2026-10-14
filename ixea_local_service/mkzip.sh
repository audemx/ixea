#!/bin/bash

# Nombre del archivo ZIP resultante
ZIP_NAME="ixea_local_service.zip"

# Obtener la ruta de la carpeta actual donde está el script
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ZIP_PATH="$DIR/$ZIP_NAME"

# 1. Eliminar el ZIP anterior si existe
if [ -f "$ZIP_PATH" ]; then
    rm "$ZIP_PATH"
    echo "ZIP anterior eliminado."
fi

# 2. Comprimir únicamente los archivos y carpetas requeridos
echo "Comprimiendo archivos..."
cd "$DIR"
zip -r "$ZIP_NAME" package.json server.js mkcert-v1.4.4-windows-amd64.exe utils/ -x "*.DS_Store*"

echo "=========================================="
echo "¡ZIP CREADO EXITOSAMENTE EN MAC!"
echo "Ruta: $ZIP_PATH"
echo "=========================================="