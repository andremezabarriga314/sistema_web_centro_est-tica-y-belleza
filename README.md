# ✨ BELLA IMAGEN
### Sistema Web de Gestión para Centros de Estética y Belleza

<p align="center">
  <strong>Una plataforma integral para administrar, organizar y optimizar las operaciones de un centro de belleza.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap">
  <img src="https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
</p>

---

## 💎 Sobre el proyecto

**Bella Imagen** es un sistema web desarrollado para mejorar la gestión administrativa y operativa de un centro de estética y belleza.

Su propósito es centralizar los procesos del negocio en una plataforma intuitiva, facilitando el control de clientes, especialistas, servicios, citas, tratamientos, pagos e inventario.

El sistema busca reducir tareas manuales, organizar la información y proporcionar una mejor experiencia de administración.

---

## 🚀 Módulos del sistema

| Módulo | Descripción |
|:---|:---|
| 🔐 Autenticación | Acceso mediante usuario y contraseña. |
| 📊 Dashboard | Panel principal con información general. |
| 👥 Clientes | Registro y administración de clientes. |
| 💆 Especialistas | Gestión de profesionales. |
| ✨ Especialidades | Organización de especialidades. |
| 🧴 Servicios | Administración del catálogo de servicios. |
| 🌸 Tratamientos | Registro y seguimiento de tratamientos. |
| 📅 Agenda | Organización de horarios y citas. |
| 📝 Atenciones | Control de servicios realizados. |
| 💳 Pagos | Registro y control de pagos. |
| 📦 Inventario | Administración de productos y existencias. |
| 📈 Reportes | Consulta de información administrativa. |

---

## 🛠️ Tecnologías utilizadas

<p align="center">
  <img src="https://skillicons.dev/icons?i=php,mysql,html,css,js,bootstrap,git,github" alt="Tecnologías">
</p>

| Tecnología | Utilidad |
|:---|:---|
| PHP | Lógica del servidor. |
| MySQL | Gestión de base de datos. |
| HTML5 | Estructura de las interfaces. |
| CSS3 | Diseño y estilos visuales. |
| JavaScript | Interactividad. |
| Bootstrap 5 | Diseño adaptable. |
| PDO | Conexión y consultas a la base de datos. |
| Git / GitHub | Control de versiones. |

---

## ⚙️ Instalación y ejecución

### 1. Requisitos

- XAMPP.
- PHP 8 o superior.
- MySQL o MariaDB.
- Navegador web actualizado.
- Git (opcional).

### 2. Clonar el repositorio

```bash
git clone https://github.com/andremezabarriga314/sistema_web_centro_est-tica-y-belleza.git
```

### 3. Ubicar el proyecto

Copia la carpeta del proyecto dentro de:

```text
C:\xampp2\htdocs\
```

### 4. Configurar la base de datos

1. Inicia Apache y MySQL desde XAMPP.
2. Ingresa a `http://localhost/phpmyadmin`.
3. Crea la base de datos correspondiente.
4. Importa el archivo `database.sql`.
5. Configura la conexión en `config/database.php`.

### 5. Ejecutar el sistema

Abre el navegador y accede a:

```text
http://localhost/bella_imagen
```

---

## 📁 Estructura general

```text
bella_imagen/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
│
├── config/
│   └── database.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
│
├── agenda.php
├── atenciones.php
├── dashboard.php
├── inventario.php
├── login.php
├── logout.php
├── modulo.php
├── pagos.php
├── reportes.php
├── reservar.php
│
├── database.sql
├── database_update.sql
└── README.md
```

---

## 🔒 Seguridad

El proyecto contempla mecanismos para proteger la información y controlar el acceso:

- Autenticación de usuarios.
- Consultas preparadas mediante PDO.
- Validación de información.
- Protección de acceso a módulos.
- Manejo seguro de contraseñas.

---

## 🎯 Objetivos del proyecto

- Digitalizar los procesos administrativos.
- Centralizar la información del negocio.
- Mejorar la organización de citas y atenciones.
- Facilitar el control de pagos e inventario.
- Optimizar la gestión de clientes y especialistas.

---

## 👨‍💻 Desarrollador

<p align="center">
  <strong>ANDRÉ MEZA BARRIGA</strong>
  <br>
  Estudiante de Ingeniería de Software con Inteligencia Artificial
  <br><br>
  <a href="https://github.com/andremezabarriga314">
    <img src="https://img.shields.io/badge/GitHub-André%20Meza-181717?style=for-the-badge&logo=github" alt="GitHub">
  </a>
</p>

---

<p align="center">
  <strong>✨ BELLA IMAGEN</strong>
  <br>
  <em>La belleza también se gestiona con tecnología.</em>
  <br><br>
  © 2026 Bella Imagen · Proyecto académico
</p>
