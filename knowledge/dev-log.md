# Bitácora de Desarrollo — IXEA EROS

---

## 2026-08-21

### [2026-08-21 12:45] `build(infra)`: Inicialización de arquitectura de carpetas, entorno Docker y aislamiento HTTP

* **feat(arch):** Se definió la estructura base del repositorio separando la capa pública accesible por HTTP (`src/public/`) de la capa de lógica sensible y negocio (`src/app/`).
* **feat(docker):** Se integraron las definiciones de `Dockerfile` y `docker-compose.yml` para aislar los entornos de servidor Web (Apache / PHP 8.2) y Base de Datos (MariaDB).
* **feat(zsh):** Se configuraron alias locales en la terminal para optimizar la orquestación de los contenedores:
  ```bash
  alias ixea-build='docker compose up -d --build'
  alias ixea-up='docker compose up -d'
  alias ixea-down='docker compose down'
  alias ixea-restart='docker compose restart'
  alias ixea-logs='docker compose logs -f'
  ```
* **infra(docker):** Se estableció el mapeo de volúmenes de Docker hacia el contenedor sin alterar los permisos o la estructura local:
  ```txt
  Local Host                                         CONTENEDOR (Linux)
  ─────────────────                                  ────────────────────
  mi-proyecto/
  ├── docker-compose.yml
  ├── src/                       ───[PUENTE]───>    /var/www/html (Apache)
  └── db_data (Volumen oculto)   ───[PUENTE]───>    /var/lib/mysql (MariaDB)
  ```
* **docs(network):** Se definieron los accesos web locales:
  * Apache Web: `http://localhost:8000`
  * phpMyAdmin: `http://localhost:8080`
* **ref(adr):** Implementación alineada con **ADR-004** (Aislamiento del Entorno de Desarrollo y Separación de Capas HTTP/App mediante Docker).

---

## 2026-08-26

### [2026-08-26 20:55] `feat(db)`: Definición del esquema inicial de base de datos y modelado ER

* **test(infra):** Se verificó la conectividad exitosa a los puertos expuestos `8000` (HTTP), `8080` (PMA) y `3306` (MariaDB).
* **feat(db):** Se creó el esquema inicial SQL en `src/app/db/schema.sql`.
* **refactor(db):** Se optimizó la estructura de tablas orientándola al dominio operativo de restaurantes (gestión de comanda, caja y mesas).
* **build(deps):** Se instaló la herramienta global `@dbml/cli` vía `npm` para soporte de modelado relacional.
* **docs(db):** Se generó y visualizó el diagrama Entidad-Relación (ER) a través de `dbdiagram.io`.

---

## 2026-08-27

### [2026-08-27 13:20] `build(deps)`: Integración de Composer y Eloquent ORM en entorno desacoplado

* **build(deps):** Se instaló Composer localmente vía Homebrew (`brew install composer`).
* **build(deps):** Se ejecutó la instalación de dependencias de Composer dentro del contenedor ejecutor (`docker exec -it dev-web-1 composer install --working-dir=/var/www/app`).
* **feat(orm):** Se integró `illuminate/database` (Eloquent ORM) fuera de un marco de trabajo completo (`docker exec -it dev-web-1 composer require illuminate/database --working-dir=/var/www/app`).
* **build(deps):** Se actualizó `composer.json` y se regeneró el mapa de autocarga de clases PSR-4 mediante `composer dump-autoload`.
* **feat(db):** Se creó el script de arranque global de conexión de Eloquent en `src/app/db/conexion.php`.
* **ref(adr):** Implementación alineada con **ADR-002** (Uso de Eloquent ORM fuera de Laravel).

---

### [2026-08-27 15:30] `feat(tooling)`: Automatización de aprovisionamiento de DB y generadores de código

* **fix(db):** Se ejecutó la secuencia manual de purga y reinyección del esquema SQL en MariaDB:
  ```bash
  # Purga e inicialización de la base de datos
  docker exec -i dev-db-1 mariadb -u root -pixea_1234. -e "DROP DATABASE IF EXISTS ixea_db; CREATE DATABASE ixea_db;"

  # Carga de la estructura de tablas
  docker exec -i dev-db-1 mariadb -u root -pixea_1234. ixea_db < schema.sql
  ```
* **test(db):** Se confirmó el correcto handshake con la base de datos ejecutando `src/app/db/test-conexion.php`.
* **feat(tooling):** Se construyó el script `src/app/db/gen-models.php` para la introspección de tablas y autogeneración de clases Eloquent.
* **feat(tooling):** Se construyó el script `src/app/db/gen-schema-json.php` para la exportación del grafo de entidades a JSON.
* **ref(adr):** Implementación alineada con **ADR-003** (Estrategia de Autoconsumo de Metadatos mediante Generación Automática de Modelos y Schemas JSON).

---

### [2026-08-27 20:35] `feat(build)`: Consolidación del script maestro de compilación `build.php`

* **feat(build):** Se creó el script orquestador maestro `build.php` en la raíz de `src/`.
* **refactor(tooling):** Se adaptaron `gen-models.php` y `gen-schema-json.php` para responder a la ejecución centralizada desde `src/` conectando a `127.0.0.1:3306`.
* **refactor(db):** Se modularizó la inicialización del esquema separando la estructura DDL (`schema.sql`) de los datos iniciales del sistema DML (`sys-data.sql`).
* **test(build):** Se ejecutó satisfactoriamente la prueba de compilación de datos y modelos con `php build.php`.
* **test(db):** Se verificó la consistencia final ejecutando `docker exec -it dev-web-1 php ../app/db/test-connection.php`.

---

## 2026-08-28

### [2026-08-28 15:10] `chore(git)`: Configuración del repositorio remoto e inspección de estructura

* **chore(git):** Se creó el repositorio público/privado en GitHub (`https://github.com/audemx/ixea`).
* **chore(git):** Se configuraron las reglas de exclusión en `.gitignore` para el núcleo del proyecto.
* **chore(git):** Se inicializó el árbol de Git (`git init`), estableciendo `main` como rama principal (`git branch -M main`).
* **chore(git):** Se vinculó el origen remoto (`git remote add origin https://github.com/audemx/ixea`).
* **feat(git):** Se realizó el primer envío de cambios (`git push -u origin main`).
* **docs(arch):** Se generó el mapa visual del árbol de archivos rastreados (`git ls-files | tree --fromfile > structure.txt`).

---

### [2026-08-28 18:45] `docs(knowledge)`: Documentación de contexto y decisiones de arquitectura

* **docs(arch):** Se elaboró el archivo de contexto técnico global del proyecto `project_context.md` en el directorio `knowledge/`, traslado la bitácora de desarrollo.
* **docs(adr):** Se incorporó a knowledge/ los ADRs/ de arquitectura. Se vincularon los requerimientos de interfaz multimodo documentados en **ADR-001** (Adopción de arquitectura híbrida Stages/Stations para el Frontend), ADR-002 (Uso de Eloquent ORM fuera de Laravel), ADR-003 (Estrategia de Autoconsumo de Metadatos mediante Generación Automática de Modelos y Schemas JSON), ADR-004 (Aislamiento del Entorno de Desarrollo y Separación de Capas HTTP/App mediante Docker).
* **build(deps):** Se ejecutó `docker exec -it dev-web-1 composer dump-autoload --working-dir=/var/www/app` para actualizar el mapa de autocarga de clases PSR-4.

### [2026-09-05 01:15] `feat(infra)`: PoC de Ixea Bistro.
* Se planteo un esquema de desarrollo sobre React+Node.js.
* Se construyó un modelo prototipo del comandero.
* Debido a la incapacidad para levantar conexiones persistentes en plataformas de hosting compartida como Hostinger, se abandonó la idea de utilizar WebSockets o Server-Sent Events (SSE).
* Se planteo utitilizar polling cada 5 segundos, lo cual representa una latencia de 5 segundos en el peor de los casos, junto con una infraestructura de Edge Compute donde un único nodo dentro del negocio se encargaría de actualizar los cambios a los demás dispositivos.
* Se integrará nuevamente PHP para manejo de base de datos y lógica de negocio.

### [2026-09-09 12:00] `feat(arch)`: Diseño de arquitectura para Bistro POS y Bistro KDS.
* Introducción de Bistro POS y Bistro KDS en Launchpad
* Generación de frontend (php, js) y backend (controller) para BistroPos
* Extracción de schema para Bistro con independencia de EROS

### [2026-09-12 20:00] `feat(db)`: Generación de clases Enum.
* Generación de clases Enum a partir de tablas de sistema.
* Se modificó la tabla sys_tables para incluir el campo is_enum y column_name.
* Se incorporó al flujo de generación de modelos el script generate-enums.php.
* Actualización de composer.json para incluir el namespace App\Enums\ y actualizar el mapa de autocarga de clases PSR-4.
* Ejecución del script generate-enums.php para generar las clases Enum: `docker exec -it dev-web-1 php ../app/db/generate-enums.php`.
* Para iniciar de cero usamos:
  * `orb start` para contenerizador.
  * `ixea-up` para iniciar contenedores.
  * `php src/build.php bistro` para generar modelos, schema JSON y datos iniciales del sistema (sys-data.sql) con squema bistro.
  * `docker exec -it dev-web-1 php ../app/db/test-connection.php` para verificar la conexión a la base de datos.
  * `docker exec -it dev-web-1 php ../app/db/generate-enums.php` para generar clases Enum.
  * `docker exec -it dev-web-1 composer dump-autoload --working-dir=/var/www/app` para actualizar el mapa de autocarga de clases PSR-4.
  
### [2026-09-18 16:30] `feat(backend)`: Optimización en la lógica de seguridad y permisos.
* Se eliminó la carga de permisos en la sesión.
  * Se modificó `app/access/login.php` para que devuelva user_id y role_id.
* Se optimizó el manejo de permisos y se colocó dentro del helper Security.
  * Se incorporaron las funciones `hasPermission()` y `getInheritedRoles()` en la clase Security.
  * Se incorporó a la función `authorize()` la validación de permisos como una lista que integra cada `permission_id` y el manejo de CSRF.
  
### [2026-09-18 18:45] `feat(frontend)`: Optimización en la lógica de seguridad y permisos.
* IxeaComponents contiene openModal('auth') lanza modal de autentificación.
* Ajustar parametros de IxeaBouncer: action, permission, para que se revisen permisos específicos.
