@'
# ✨ Bella Imagen
### Sistema Web de Gestión para Centro de Estética y Belleza

Bella Imagen es un sistema web desarrollado para facilitar la administración y gestión de un centro de estética y belleza, centralizando sus operaciones en una sola plataforma.

## 📌 Características principales

- **Inicio de sesión:** acceso al sistema mediante autenticación de usuarios.
- **Panel principal:** visualización general de la información del negocio.
- **Gestión de clientes:** registro y administración de clientes.
- **Gestión de especialistas:** control de profesionales del centro.
- **Especialidades y servicios:** administración del catálogo de servicios.
- **Tratamientos:** seguimiento de tratamientos realizados.
- **Agenda y citas:** organización de horarios y reservas.
- **Atenciones:** registro de los servicios brindados.
- **Pagos:** gestión de pagos de los clientes.
- **Inventario:** control de productos y existencias.
- **Reportes:** consulta de información administrativa.

## 🛠️ Tecnologías utilizadas

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- PDO
- XAMPP

## 💻 Requisitos

- Windows, Linux o macOS.
- PHP 8 o superior.
- MySQL o MariaDB.
- Servidor Apache.
- Navegador web actualizado.

## ⚙️ Instalación

1. Instalar y ejecutar XAMPP.
2. Copiar el proyecto en la carpeta `htdocs`.
3. Iniciar Apache y MySQL.
4. Abrir phpMyAdmin desde `http://localhost/phpmyadmin`.
5. Crear la base de datos e importar el archivo `database.sql`.
6. Revisar la configuración de conexión en `config/database.php`.
7. Abrir el sistema desde:

   `http://localhost/bella_imagen`

## 📂 Estructura del proyecto

```text
bella_imagen/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── agenda.php
├── atenciones.php
├── clientes (modulo.php)
├── dashboard.php
├── inventario.php
├── login.php
├── pagos.php
├── reportes.php
├── tratamientos_cliente.php
├── database.sql
└── README.md
