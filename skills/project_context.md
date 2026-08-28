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

Toda la arquitectura puede encontrarse en `structure.txt`.