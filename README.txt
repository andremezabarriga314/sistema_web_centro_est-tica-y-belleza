BELLA IMAGEN — SISTEMA DE GESTIÓN (PHP + MYSQL)
================================================

REQUISITOS
- Windows 10/11
- XAMPP (Apache + MySQL/MariaDB)
- PHP 8.1 o superior
- MySQL 5.7+ o MariaDB 10.4+

INSTALACIÓN DESDE CERO
1. Descomprime la carpeta bella_imagen en C:\xampp2\htdocs\
   (si tu XAMPP está en C:\xampp, usa C:\xampp\htdocs\).
2. Abre MySQL Workbench, conecta a tu servidor y ejecuta TODO el archivo
   bella_imagen\database.sql. El script crea bella_imagen_db y sus tablas.
   Si ya instalaste la versión anterior y quieres conservar sus datos, ejecuta
   database_update.sql en lugar de volver a ejecutar database.sql.
3. Abre config\database.php y configura host, puerto, base, usuario y clave
   según tu instalación de MySQL. Por defecto: 127.0.0.1:3306, root, sin clave.
4. Inicia Apache y MySQL desde el panel de XAMPP.
5. Abre http://localhost/bella_imagen/crear_admin.php UNA SOLA VEZ.
6. Cuando indique que el administrador fue creado, elimina crear_admin.php.
7. Entra en http://localhost/bella_imagen/login.php.

ACCESO INICIAL
Usuario: admin
Contraseña: BellaImagen2026!
Cambia esta contraseña después del primer ingreso.

MÓDULOS
- Panel de inicio con indicadores y próximas citas.
- Catálogo visual de servicios con ilustraciones, precios y duración.
- Clientes: alta, edición, consulta y eliminación.
- Especialistas y especialidades: mantenimiento de registros.
- Servicios: precio, duración y estado.
- Asignación de especialidades y servicios por especialista (solo administrador).
- Horarios semanales por especialista. Si hay horarios configurados, la agenda
  exige que la cita quede dentro de un horario válido. Si no hay ninguno, se
  usa el horario general de 07:00 a 21:00.
- Agenda: alta, edición, cancelación, estados y prevención de cruces de citas
  para un mismo especialista mediante transacción y bloqueo de fila.
- Atenciones: registra descripción y observaciones; permite asociar productos,
  descuenta existencias y genera movimientos de salida en una transacción.
- Productos e inventario: catálogo, entradas, salidas, ajustes y kardex.
- Tratamientos: catálogo, asignación a clientes y registro de sesiones; el plan
  se finaliza al completar las sesiones contratadas.
- Pagos: pagos asociados a citas o tratamientos; efectivo, crédito, débito y Yape.
- Reportes: ingresos pagados, citas por estado, servicios solicitados e ingresos
  agrupados por día para un rango de fechas.
- Usuarios y roles: creación, edición y desactivación de usuarios desde el
  módulo Usuarios (solo administrador). Roles iniciales: Administrador,
  Recepcionista y Especialista.
- Permisos por rol: administrador (todos los módulos), recepcionista (clientes,
  agenda, asignación de tratamientos, pagos y reportes), especialista (agenda y atenciones de su propio perfil, y registro de sesiones
  de tratamiento asociado a su cuenta). Para crear ese acceso, registra primero
  al especialista y luego vincula su perfil al usuario en Usuarios y roles.

NOTAS Y ALCANCE
- La aplicación usa PDO y consultas preparadas, validación de formularios,
  protección CSRF en formularios internos y contraseñas con password_hash.
- El administrador puede asignar servicios y especialidades a especialistas.
  Cuando un especialista ya tiene servicios asignados, la agenda solo permite
  reservar esos servicios.
- La aplicación se entrega como sistema funcional para entorno local/académico.
  Antes de usarla en producción deben revisarse los permisos específicos de
  cada rol, recuperación de contraseñas, auditoría, copias de seguridad,
  HTTPS, privacidad, comprobantes tributarios y reglas contables.
- La disponibilidad real depende de que se registren horarios y asignaciones.
  Los pagos permiten registrar abonos; la conciliación y cierre de caja diario
  requieren reglas adicionales del negocio.
- No publiques config/database.php ni credenciales reales en GitHub.

ESTRUCTURA
config/       conexión PDO y protección de acceso directo
includes/     funciones, encabezado y pie
assets/       estilos y JavaScript
*.php         módulos funcionales
 database.sql estructura de base de datos
