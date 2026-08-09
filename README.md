# Sistema de Control de Activos Físicos - Edificio de Informática, CEDES Don Bosco

Sistema web para el control y gestión de activos físicos (laptops, proyectores, mobiliario, etc.) del edificio de Informática del Colegio Técnico Profesional Don Bosco (CEDES Don Bosco), incluyendo préstamos a profesores, registro de incidencias y control de usuarios por rol.

## Descripción

Actualmente el control de activos del departamento de Informática se realiza mediante una hoja de cálculo de Excel, de forma manual. Este proyecto busca automatizar ese proceso mediante un sistema digital que permita:

- Gestionar activos por laboratorio y especialidad.
- Registrar préstamos de activos a profesores, con fecha límite y devolución.
- Reportar incidencias o daños sobre los activos.
- Controlar el acceso mediante usuarios con roles (`admin` y `lector`).
- (En desarrollo) Identificación y consulta de activos mediante escaneo de código QR / placa.

Este proyecto es parte de un trabajo de investigación e implementación desarrollado para el departamento de TIC's de CEDES Don Bosco.

## Tecnologías utilizadas

- **Backend:** PHP
- **Base de datos:** Microsoft SQL Server 2025 Express Edition
- **Administración de BD:** SQL Server Management Studio (SSMS)
- **Frontend:** HTML, CSS
- **Entorno local:** XAMPP
- **IDE:** Visual Studio Code
- **Control de versiones:** Git / GitHub

## Estructura del proyecto

- **img/**: carpeta con las imágenes usadas en la interfaz (login.webp, module_table_bottom.png, module_table_top.png).
- **activos_logica.php**: contiene la lógica de negocio para el manejo de activos.
- **conexion.php**: archivo de conexión a la base de datos en SQL Server.
- **database.sql**: script para crear la base de datos y sus tablas.
- **index.php**: punto de entrada del sistema, donde se realiza el login.
- **style.css**: hoja de estilos general del proyecto.
- **vista_activos.php**: vista principal para la gestión de activos.

## Requisitos previos

- [PHP](https://www.php.net/downloads) (con extensión `pdo_odbc` habilitada)
- [SQL Server Express](https://www.microsoft.com/es-es/sql-server/sql-server-downloads) o una instancia de SQL Server
- Driver ODBC de SQL Server instalado
- Un servidor local, por ejemplo [XAMPP](https://www.apachefriends.org/es/index.html) o el servidor embebido de PHP

## Instalación

1. **Clona o descarga este repositorio**
   ```bash
   git clone https://github.com/esteban191520/expophp.git
   ```

2. **Crea la base de datos**

   Abre SQL Server Management Studio (o Azure Data Studio), conéctate a tu instancia y ejecuta el script `database.sql` incluido en este repositorio. Esto creará la base de datos `ControlActivosColegio` con todas sus tablas y algunos datos de prueba.

3. **Configura la conexión**

   Revisa el archivo `conexion.php` y ajusta el nombre del servidor según tu instalación local:
   ```php
   $server   = "TU_SERVIDOR\\SQLEXPRESS";
   $database = "ControlActivosColegio";
   ```

4. **Levanta el proyecto**

   Coloca la carpeta del proyecto en el directorio de tu servidor local (por ejemplo, `htdocs` en XAMPP) y accede desde el navegador:
   ```
   http://localhost/expophp/index.php
   ```

## Base de datos

El script `database.sql` incluye:
- Tablas: `especialidad`, `laboratorio`, `profesor`, `usuario`, `activos`, `prestamos`, `incidencias`
- Relaciones (llaves foráneas) entre todas las tablas
- Datos de prueba iniciales para poder probar el sistema de inmediato

## Estado actual del desarrollo

El proyecto se encuentra en fase de desarrollo. Ya se cuenta con una estructura funcional y los módulos principales (login, gestión de activos, laboratorios). Actualmente en desarrollo:

- Módulo de gestión de laboratorios (interfaz).
- Módulo de préstamos.
- Registro de reportes de incidencias/fallas.
- Escaneo de código QR para identificación y consulta rápida de activos.

## Equipo de desarrollo

- Samir Alvarado Contreras
- Marcelo Arguedas Monge
- Mathias Gonzalez Quiros
- Pablo Leyva Aguilar
- Yeicob Sanchez Rojas

## Proyecto académico

Este proyecto fue desarrollado para el departamento de TIC's de CEDES Don Bosco, Alajuelita, Costa Rica, como parte del desarrollo del sistema de control de activos físicos del edificio de Informática del Colegio Técnico Profesional Don Bosco.
