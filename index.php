<?php
session_start();

// 1. Cargar la conexión SQL Server
require_once 'conexion.php';

// Manejo del Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_SESSION['idUsuario'])) {
        $stmtLimpiar = $conn->prepare("UPDATE usuario SET remember_token = NULL WHERE id = :id");
        $stmtLimpiar->execute(['id' => $_SESSION['idUsuario']]);
    }
    if (isset($_COOKIE['recordarme'])) {
        setcookie('recordarme', '', time() - 3600, '/');
    }
    session_destroy();
    header('Location: index.php?vista=login');
    exit;
}

// Auto-login: si no hay sesión activa pero sí una cookie "recordarme" válida, iniciamos sesión sin pedir credenciales
if (empty($_SESSION['autenticado']) && isset($_COOKIE['recordarme'])) {
    $tokenHashCookie = hash('sha256', $_COOKIE['recordarme']);

    $stmtCookie = $conn->prepare("SELECT * FROM usuario WHERE remember_token = :token");
    $stmtCookie->execute(['token' => $tokenHashCookie]);
    $usuarioCookie = $stmtCookie->fetch();

    if ($usuarioCookie) {
        $_SESSION['autenticado'] = true;
        $_SESSION['rolActual']   = $usuarioCookie['tipo_usuario'] ?? 'Usuario General';
        $_SESSION['idUsuario']   = $usuarioCookie['id'];
    }
}

// 2. Obtener métricas rápidas directamente desde tus tablas de SQL Server
$totalActivos   = $conn->query("SELECT COUNT(*) FROM activos")->fetchColumn() ?: 0;
$totalPrestamos = $conn->query("SELECT COUNT(*) FROM prestamos")->fetchColumn() ?: 0;
$totalLabs      = $conn->query("SELECT COUNT(*) FROM laboratorio")->fetchColumn() ?: 0;
$totalFallas    = $conn->query("SELECT COUNT(*) FROM incidencias")->fetchColumn() ?: 0;
// Control de variables de estado
$estaAutenticado = $_SESSION['autenticado'] ?? false;
$rolActual       = $_SESSION['rolActual'] ?? 'Invitado';


// 3. Control de Inicio de Sesión
$errorLogin = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'login') {
    $email = $_POST['email'] ?? '';
    $clave = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM usuario WHERE correo = :correo AND contrasena = :contrasena");
    $stmt->execute([
        'correo'     => $email,
        'contrasena' => $clave
    ]);
    
    $userBD = $stmt->fetch();

    if ($userBD) {
        $_SESSION['autenticado'] = true;
        $_SESSION['rolActual']   = $userBD['tipo_usuario'] ?? 'Usuario General';
        $_SESSION['idUsuario']   = $userBD['id'];

        if (!empty($_POST['recordarme'])) {
            $tokenRecordar     = bin2hex(random_bytes(32));
            $tokenHashRecordar = hash('sha256', $tokenRecordar);

            $stmtRecordar = $conn->prepare("UPDATE usuario SET remember_token = :token WHERE id = :id");
            $stmtRecordar->execute([
                'token' => $tokenHashRecordar,
                'id'    => $userBD['id']
            ]);

            setcookie('recordarme', $tokenRecordar, [
                'expires'  => time() + 60 * 60 * 24 * 30, // 30 días
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        header('Location: index.php?modulo=inicio');
        exit;
    } else {
        // Marcamos el error para disparar la alerta
        $errorLogin = "Correo o contraseña incorrectos.";
    }
}


// Mapeo de módulos
$titulosModulos = [
    'inicio'       => 'Panel Principal',
    'activos'      => 'Activos',
    'laboratorios' => 'Laboratorios',
    'prestamos'    => 'Préstamos',
    'fallas'       => 'Reportes de Fallas',
    'escaner'      => 'Escáner'
];

$iconosModulos = [
    'inicio'       => 'bi-speedometer2',
    'activos'      => 'bi-box-seam',
    'laboratorios' => 'bi-building',
    'prestamos'    => 'bi-arrow-left-right',
    'fallas'       => 'bi-exclamation-triangle',
    'escaner'      => 'bi-qr-code-scan'
];

$vista = $_GET['vista'] ?? 'login';
$moduloId = $_GET['modulo'] ?? 'inicio';
$txtModuloActual = $titulosModulos[$moduloId] ?? 'Panel Principal';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $estaAutenticado ? $txtModuloActual : 'Autenticación'; ?></title>
    
    <!-- Bootstrap 5 CSS e Iconos -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php if (!$estaAutenticado): ?>
    <!-- ========================================== -->
    <!-- MÓDULO DE AUTENTICACIÓN (LOGIN / BIENVENIDA) -->
    <!-- ========================================== -->

    <?php if ($vista === 'registro'): ?>
        <div class="auth-page">
            <div class="auth-bg-sharp"></div>
            <div class="auth-topbar">
                <div class="auth-brand"><i class="bi bi-cpu"></i> Control IT</div>
            </div>
            <div class="auth-content">
                <div class="auth-content-inner">
                    <span class="auth-label">Solicitar acceso</span>
                    <h2 class="auth-title">Crear cuenta<span class="text-accent"></span></h2>
                    <p class="auth-subtitle">Tu cuenta quedará pendiente de aprobación</p>

                    <div class="auth-glass">
                        <form action="index.php" method="POST">
                            <div class="mb-4">
                                <label class="field-label">Nombre completo</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-person"></i>
                                    <input type="text" class="form-control form-control-custom" placeholder="Tu nombre completo" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="field-label">Correo electrónico</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" class="form-control form-control-custom" placeholder="admin@colegio.edu" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="field-label">Contraseña</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-lock"></i>
                                    <input type="password" class="form-control form-control-custom" placeholder="••••••••" required>
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-4">
                                <a href="index.php?vista=login" class="btn-outline-auth text-center text-decoration-none">Iniciar sesión</a>
                                <button type="button" onclick="alert('Registro enviado a aprobación')" class="btn-principal">Registrarme</button>
                            </div>
                        </form>
                    </div>
                    <p class="auth-footer">© 2026 Control IT - Colegio Técnico Don Bosco</p>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="auth-page">
            <div class="auth-bg-sharp"></div>
            <div class="auth-topbar">
                <div class="auth-brand"><i class="bi bi-cpu"></i> Control IT</div>
            </div>
            <div class="auth-content">
                <div class="auth-content-inner">
                    <span class="auth-label">Acceso al sistema</span>
                    <h2 class="auth-title">Iniciar sesión<span class="text-accent"></span></h2>
                    <p class="auth-subtitle">Ingresa con tu correo institucional</p>

                    <div class="auth-glass">
                        <form action="index.php" method="POST" autocomplete="off">
                            <input type="hidden" name="accion" value="login">

                            <div class="mb-4">
                                <label class="field-label">Correo electrónico</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-envelope"></i>
                                    <input type="email" name="email" class="form-control form-control-custom" placeholder="admin@colegio.edu" autocomplete="off" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="field-label">Contraseña</label>
                                <div class="input-group-custom">
                                    <i class="bi bi-key"></i>
                                    <input type="password" name="password" class="form-control form-control-custom" placeholder="••••••••" autocomplete="new-password" required>
                                </div>
                            </div>

                            <div class="auth-extra-row">
                                <label class="auth-checkbox">
                                    <input type="checkbox" name="recordarme">
                                    Recordarme
                                </label>
                            </div>

                            <div class="d-flex gap-3 mt-2">
                                <a href="index.php?vista=registro" class="btn-outline-auth text-center text-decoration-none">Crear cuenta</a>
                                <button type="submit" class="btn-principal">Ingresar</button>
                            </div>
                        </form>
                    </div>
                    <p class="auth-footer">© 2026 Control IT - Colegio Técnico Don Bosco</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- ========================================== -->
    <!-- PANEL PRINCIPAL ESTILO OPENBOM UI/UX -->
    <!-- ========================================== -->
    <div class="app-container">
        
        <!-- Sidebar Lateral -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-cpu fs-4"></i>
                <span>Control IT</span>
            </div>

            <nav class="nav-menu">
                <?php foreach ($titulosModulos as $id => $titulo): ?>
                    <a href="index.php?modulo=<?php echo $id; ?>" 
                       class="nav-item-link <?php echo ($moduloId === $id) ? 'active' : ''; ?>">
                        <i class="bi <?php echo $iconosModulos[$id]; ?>"></i>
                        <span><?php echo $titulo; ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="d-block text-secondary">Usuario</small>
                        <strong class="text-white small"><?php echo htmlspecialchars($rolActual); ?></strong>
                    </div>
                    <a href="index.php?action=logout" class="btn btn-sm btn-outline-danger" title="Cerrar Sesión">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Contenido Central -->
        <main class="main-content">
            
            <!-- Top Navbar -->
            <header class="top-navbar">
                <h5 class="m-0 fw-bold text-dark"><?php echo htmlspecialchars($txtModuloActual); ?></h5>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-light text-dark border"><i class="bi bi-circle-fill text-success me-1"></i> Servidor SQL Server Activo</span>
                    <i class="bi bi-bell text-secondary fs-5"></i>
                </div>
            </header>

            <!-- Área de Trabajo de Módulos -->
            <div class="p-4">
                
                <?php switch ($moduloId):
                    case 'inicio': ?>
                        <!-- Cards de Métricas Dinámicas desde SQL Server -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div>
                                        <small class="d-block fw-bold">TOTAL ACTIVOS</small>
                                        <h3 class="m-0 fw-bold"><?php echo number_format($totalActivos); ?></h3>
                                    </div>
                                    <div class="metric-icon-box bg-primary-subtle text-primary">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div>
                                        <small class="d-block fw-bold">EN PRÉSTAMO</small>
                                        <h3 class="m-0 fw-bold"><?php echo number_format($totalPrestamos); ?></h3>
                                    </div>
                                    <div class="metric-icon-box bg-warning-subtle text-warning">
                                        <i class="bi bi-arrow-left-right"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div>
                                        <small class="d-block fw-bold">LABORATORIOS</small>
                                        <h3 class="m-0 fw-bold"><?php echo number_format($totalLabs); ?></h3>
                                    </div>
                                    <div class="metric-icon-box bg-info-subtle text-info">
                                        <i class="bi bi-building"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="metric-card">
                                    <div>
                                        <small class="d-block fw-bold">FALLAS REPORTADAS</small>
                                        <h3 class="m-0 fw-bold"><?php echo number_format($totalFallas); ?></h3>
                                    </div>
                                    <div class="metric-icon-box bg-danger-subtle text-danger">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla con Datos Dinámicos de la BD -->
                        <div class="custom-card-panel">
                            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Últimos Movimientos de Inventario</h6>
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>CÓDIGO / ID</th>
                                            <th>DESCRIPCIÓN DEL ACTIVO</th>
                                            <th>ESTADO</th>
                                            <th>ACCIONES</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Consulta a la tabla 'activos' en SQL Server (sintaxis TOP en lugar de LIMIT)
                                        $stmt = $conn->query("SELECT TOP 10 * FROM activos");
                                        $listaActivos = $stmt->fetchAll();

                                        if (count($listaActivos) > 0):
                                            foreach ($listaActivos as $item):
                                                $estado = $item['estado'] ?? 'Disponible';
                                                
                                                // Mapeo de estilos según estado
                                                $claseEstado = match(mb_strtolower($estado)) {
                                                    'disponible' => 'status-disponible',
                                                    'en prestamo', 'prestado' => 'status-enuso',
                                                    default => 'status-danado'
                                                };
                                        ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($item['id_activo'] ?? $item['id'] ?? 'ACT-00'); ?></code></td>
                                                <td><?php echo htmlspecialchars($item['nombre'] ?? $item['descripcion'] ?? 'Sin descripción'); ?></td>
                                                <td><span class="badge-status <?php echo $claseEstado; ?>"><?php echo htmlspecialchars($estado); ?></span></td>
                                                <td><button class="btn btn-sm btn-light border">Ver detalle</button></td>
                                            </tr>
                                        <?php 
                                            endforeach;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No hay activos registrados todavía.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case 'escaner': ?>
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="custom-card-panel text-center">
                                    <h5 class="fw-bold mb-3">Lector de Código Inteligente IA</h5>
                                    
                                    <div class="scanner-viewport my-3">
                                        <div class="scanner-laser"></div>
                                        <i class="bi bi-qr-code text-white opacity-50 display-1"></i>
                                        <small class="text-white-50 mt-2 d-block">Alinee el código QR / Barras en el marco</small>
                                    </div>

                                    <button class="btn btn-principal fw-bold">
                                        <i class="bi bi-camera me-2"></i> Activar Cámara
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php default: ?>
                        <div class="custom-card-panel text-center py-5">
                            <i class="bi <?php echo $iconosModulos[$moduloId] ?? 'bi-box'; ?> display-3 text-secondary mb-3"></i>
                            <h4>Módulo de <?php echo htmlspecialchars($txtModuloActual); ?></h4>
                            <p class="text-secondary">Gestión de datos y registros en tiempo real.</p>
                        </div>
                        <?php break; ?>
                <?php endswitch; ?>

            </div>
        </main>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Alerta de Error con SweetAlert2 -->
<?php if ($errorLogin): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: '¡Error de acceso!',
        text: '<?php echo $errorLogin; ?>',
        confirmButtonColor: '#10B981',
        confirmButtonText: 'Intentar de nuevo'
    });
</script>
<?php endif; ?>
</body>
</html>