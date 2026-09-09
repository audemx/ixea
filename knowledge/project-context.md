# IXEA EROS: Project Context

## 1. Overview

**IXEA EROS** es un sistema operativo web modular orientado a la gestión empresarial (ERP/POS/CRM). Diseñado con una interfaz multimodo: "Escenarios" (`Stages`) y "Estaciones" (`Stations`), suministra aplicaciones administrativas y operativas según el rol y permisos del usuario.

**Stages** permite trabajar con múltiples aplicaciones simultáneamente, con un flujo dinámico e intuitivo, bien organizado y sin perder el contexto del espacio de trabajo, la información y las operaciones de cada una.

**Stations** permite focalizar un único trabajo. Es ideal para procesos que requieren concentración total y un acceso rápido a las herramientas necesarias: caja, comandas, cocina, etc.

**IXEA EROS** está diseñado para adaptarse a diversos sectores comerciales como retail, restaurantes, etc.

---

## 2. Stack Tecnológico & Lenguajes

### Backend

* **PHP 8.x** (Programación Orientada a Objetos, Namespaces, PDO, ORM).

* **Composer** (Gestor de dependencias de PHP con autoloader PSR-4).

* **Illuminate Database (Eloquent / Capsule Manager 8.x/9.x)** (ORM para abstracción de base de datos y relaciones).

### Frontend

* **HTML5 / CSS3** (Variables CSS nativas para tematización y colores).

* **JavaScript (ES6+)** (Controlador del sistema operativo en cliente).

* **Bootstrap** (Framework CSS y componentes de interfaz mobile-first).

### Base de Datos

* **MariaDB / MySQL 8.0+** (Motor InnoDB, cotejamiento `utf8mb4_unicode_ci`, llaves foráneas y resticciones `CASCADE`/`RESTRICT`).

---

## 3. Arquitectura del Sistema

Puede encontrarse la arquitectura del sistema en la siguiente documentación:

* `structure.txt`: Describe la estructura de archivos y carpetas del proyecto.
* `docker-compose.yml`: Describe los contenedores docker y sus configuraciones.
* `src/app/db/schema-graph.json`: Describe la estructura entidades-relaciones de base de datos.
* `src/app/composer.json`: Describe las dependencias de PHP y namespaces.
    - `/vendor/autoload.php`: Autoloader de composer.
    - `App\Classes\`: Clases de utilidad
    - `App\Core\`: Clases del sistema
    - `App\Database\`: Clases de base de datos
    - `App\Models\`: Modelos Eloquent

## 4. Convenciones y Reglas de Desarrollo

* Bootstrapping Global: `Connection::boot()` debe ejecutarse siempre antes de intentar consultar modelos Eloquent en cualquier script o punto de entrada.
    * Siendo que `index.php` es el punto de entrada principal para acceder a IXEA EROS, se debe ejecutar `Connection::boot()` en `index.php`.