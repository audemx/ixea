#!/usr/bin/env bash

# ==============================================================================
# Script para generar la estructura del proyecto en 'structure.txt'
# Excluye directorios pesados, archivos temporales y basura de IDEs/SO.
# ==============================================================================

# Lista de elementos a ignorar separados por el carácter '|'
EXCLUDE_PATTERN="vendor|.git|.DS_Store|eros_old|node_modules|.idea|.vscode|*.log|storage/logs|tmp|temp|Thumbs.db"

# Ejecutar el comando tree
tree -a -I "$EXCLUDE_PATTERN" > structure.txt

echo "✅ Estructura del proyecto generada exitosamente en 'structure.txt'"