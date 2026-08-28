# Bitácora de Desarrollo

## Creación de Estructura, Contenedores, y Conexiones
**[21/Ago/2026 - 12:45 pm]**
* Creamos la estrucutra del proyecto con la carpeta `src/`.
* Integramos las carpetas `public/` para contenido accesible por http y `app/` para archivos sensibles.
* Integramos `Dockerfile` y `docker-compose.yml` para la gestión del entorno de desarrollo.

* Creamos atajos en terminal para la gestión de los contenedores:
```bash
alias ixea-build='docker compose up -d --build'
alias ixea-up='docker compose up -d'
alias ixea-down='docker compose down'
alias ixea-restart='docker compose restart'
alias ixea-logs='docker compose logs -f'
```

* Docker se encarga de proyectar esos archivos dentro del servidor virtual sin alterar la estructura de archivos local.
```txt
Local Host                                         CONTENEDOR (Linux)
─────────────────                                  ────────────────────
mi-proyecto/
├── docker-compose.yml
├── src/                       ───[PUENTE]───>    /var/www/html (Apache)
└── db_data (Volumen oculto)   ───[PUENTE]───>    /var/lib/mysql (MariaDB)  
```

* Creamos atajos web para acceder a los puertos:
  * `http://localhost:8000` (Apache).
  * `http://localhost:8080` (phpMyAdmin).

## Schema de Base de Datos y Realizamos pruebas de conexión
**[26/Ago/2026 - 20:55 pm]**
* Realizamos pruebas de conexión a puertos 8000, 8080 y 3306 exitosas.
* Creamos schema de base de datos en `src/app/db/schema.sql`
* Optimizamos schema de base de datos para restaurante.
* Instalamos `npm install -g @dbml/cli` para poder generar diagramas ER de la base de datos.
* Visualizamos el gráfico de tablas y conexiones con dbdiagram.

## Instalamos Composer y Eloquent
**[27/Ago/2026 - 13:20 pm]**
* Instalamos composer `brew install composer`.
* Instalamos composer en docker `docker exec -it dev-web-1 composer install --working-dir=/var/www/app`.
* Instalamos Eloquent en PHP puro `docker exec -it dev-web-1 composer require illuminate/database --working-dir=/var/www/app`.
* Actualizamos composer.json y ejecutamos `docker exec -it dev-web-1 composer dump-autoload --working-dir=/var/www/app`.
* Actualizamos `docker exec -it dev-web-1 composer update --working-dir=/var/www/app`.
* Generamos archivo de conexión `src/app/db/conexion.php`.

## Inyectamos Schema a la base de datos y autogeneramos modelos
**[27/Ago/2026 - 15:30 pm]**
* Inyectamos schema a la base de datos con:
```bash
# 1. Borra y recrea la base de datos vacía.
docker exec -i dev-db-1 mariadb -u root -pixea_1234. -e "DROP DATABASE IF EXISTS ixea_db; CREATE DATABASE ixea_db;"

# 2. Vuelve a inyectar el schema.sql actualizado.
docker exec -i dev-db-1 mariadb -u root -pixea_1234. ixea_db < schema.sql
```
* Realizamos prueba de conexion a la base de datos con el archivo `src/app/db/test-conexion.php`.
* Creamos archivo para autogenerar modelos de la base de datos `src/app/db/gen-models.php`.
* Creamos archivo para generar schema json `src/app/db/gen-schema-json.php`.

## Creamos Script Maestro de Construcción
**[27/Ago/2026 - 20:35 pm]**
* Creamos archivo `build.php` en la raiz del proyecto `src/`.
* Actualizamos `gen-models.php` y `gen-schema-json.php` para que funcionen con `build.php` en nivel de `src/`, conectando con la base de datos local `127.0.0.1` en puerto `3306`.
* Realizamos prueba de construcción exitosa con `php build.php`.

## 
