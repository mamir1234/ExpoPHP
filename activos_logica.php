<?php
// ============================================================
// LÓGICA DEL MÓDULO DE ACTIVOS
// Se incluye desde index.php solo cuando hace falta (ver activos
// o procesar una de sus acciones), nunca en las demás pantallas.
// ============================================================

// --- Familias de activos y cuáles llevan número de placa ---
$familiasActivo = [
    'Mobiliario',
    'Equipo',
    'Perifericos y Cargadores',
    'Redes y Ciberseguridad',
    'Kits',
    'Audiovisual',
    'Energia'
];
$familiasConPlaca = ['Mobiliario', 'Equipo', 'Redes y Ciberseguridad', 'Audiovisual'];

$mensajeActivos = $_GET['ok'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    // --- Registrar un nuevo activo ---
    if ($_POST['accion'] === 'crear_activo') {
        $nombre           = trim($_POST['nombre'] ?? '');
        $tipo             = $_POST['tipo'] ?? '';
        $descripcion      = trim($_POST['descripcion'] ?? '');
        $fechaAdquisicion = $_POST['fecha_adquisicion'] ?? null;
        $estadoNuevo      = $_POST['estado'] ?? 'Disponible';
        $idLaboratorio    = $_POST['id_laboratorio'] !== '' ? $_POST['id_laboratorio'] : null;
        $imagenBase64     = null;

        $esConPlaca   = in_array($tipo, $familiasConPlaca);
        $subcategoria = $esConPlaca ? 'Con placa' : 'Sin placa';
        $numeroPlaca  = $esConPlaca ? trim($_POST['numero_placa'] ?? '') : null;
        $cantidad     = $esConPlaca ? null : (int)($_POST['cantidad'] ?? 1);

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $contenidoImagen = file_get_contents($_FILES['imagen']['tmp_name']);
            $tipoMime        = mime_content_type($_FILES['imagen']['tmp_name']);
            $imagenBase64    = 'data:' . $tipoMime . ';base64,' . base64_encode($contenidoImagen);
        }

        $stmt = $conn->prepare("INSERT INTO activos (nombre, tipo, subcategoria, numero_placa, descripcion, cantidad, imagen_base64, fecha_adquisicion, estado, id_laboratorio)
                                 VALUES (:nombre, :tipo, :subcategoria, :numero_placa, :descripcion, :cantidad, :imagen, :fecha, :estado, :laboratorio)");
        $stmt->execute([
            'nombre'       => $nombre,
            'tipo'         => $tipo,
            'subcategoria' => $subcategoria,
            'numero_placa' => $numeroPlaca,
            'descripcion'  => $descripcion,
            'cantidad'     => $cantidad,
            'imagen'       => $imagenBase64,
            'fecha'        => $fechaAdquisicion,
            'estado'       => $estadoNuevo,
            'laboratorio'  => $idLaboratorio
        ]);

        header('Location: index.php?modulo=activos&ok=creado');
        exit;
    }

    // --- Editar un activo existente ---
    if ($_POST['accion'] === 'editar_activo') {
        $idActivo         = $_POST['id'] ?? 0;
        $nombre           = trim($_POST['nombre'] ?? '');
        $tipo             = $_POST['tipo'] ?? '';
        $descripcion      = trim($_POST['descripcion'] ?? '');
        $fechaAdquisicion = $_POST['fecha_adquisicion'] ?? null;
        $estadoNuevo      = $_POST['estado'] ?? 'Disponible';
        $idLaboratorio    = $_POST['id_laboratorio'] !== '' ? $_POST['id_laboratorio'] : null;

        $esConPlaca   = in_array($tipo, $familiasConPlaca);
        $subcategoria = $esConPlaca ? 'Con placa' : 'Sin placa';
        $numeroPlaca  = $esConPlaca ? trim($_POST['numero_placa'] ?? '') : null;
        $cantidad     = $esConPlaca ? null : (int)($_POST['cantidad'] ?? 1);

        $camposComunes = [
            'nombre' => $nombre, 'tipo' => $tipo, 'subcategoria' => $subcategoria,
            'numero_placa' => $numeroPlaca, 'descripcion' => $descripcion, 'cantidad' => $cantidad,
            'fecha' => $fechaAdquisicion, 'estado' => $estadoNuevo, 'laboratorio' => $idLaboratorio, 'id' => $idActivo
        ];

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $contenidoImagen = file_get_contents($_FILES['imagen']['tmp_name']);
            $tipoMime        = mime_content_type($_FILES['imagen']['tmp_name']);
            $camposComunes['imagen'] = 'data:' . $tipoMime . ';base64,' . base64_encode($contenidoImagen);

            $stmt = $conn->prepare("UPDATE activos SET nombre=:nombre, tipo=:tipo, subcategoria=:subcategoria, numero_placa=:numero_placa,
                                     descripcion=:descripcion, cantidad=:cantidad, imagen_base64=:imagen,
                                     fecha_adquisicion=:fecha, estado=:estado, id_laboratorio=:laboratorio WHERE id=:id");
        } else {
            $stmt = $conn->prepare("UPDATE activos SET nombre=:nombre, tipo=:tipo, subcategoria=:subcategoria, numero_placa=:numero_placa,
                                     descripcion=:descripcion, cantidad=:cantidad,
                                     fecha_adquisicion=:fecha, estado=:estado, id_laboratorio=:laboratorio WHERE id=:id");
        }
        $stmt->execute($camposComunes);

        header('Location: index.php?modulo=activos&ok=editado');
        exit;
    }

    // --- Dar de baja un activo (no se elimina: queda el historial completo) ---
    if ($_POST['accion'] === 'dar_de_baja') {
        $idActivo   = $_POST['id'] ?? 0;
        $motivoBaja = trim($_POST['motivo_baja'] ?? '');

        $stmt = $conn->prepare("UPDATE activos SET estado = 'Baja', fecha_baja = :fecha, motivo_baja = :motivo WHERE id = :id");
        $stmt->execute([
            'fecha'  => date('Y-m-d H:i:s'),
            'motivo' => $motivoBaja,
            'id'     => $idActivo
        ]);

        header('Location: index.php?modulo=activos&ok=baja');
        exit;
    }
}

// --- Laboratorios disponibles, para los <select> de los formularios ---
$listaLaboratorios = $conn->query("SELECT id, codigo_laboratorio FROM laboratorio ORDER BY codigo_laboratorio")->fetchAll();

// --- Filtros de búsqueda (todos opcionales) ---
$filtroTipo        = $_GET['filtro_tipo'] ?? '';
$filtroEstado      = $_GET['filtro_estado'] ?? '';
$filtroLaboratorio = $_GET['filtro_laboratorio'] ?? '';
$filtroBuscar      = trim($_GET['buscar'] ?? '');

$condicionesActivos = [];
$parametrosActivos  = [];

if ($filtroTipo !== '') {
    $condicionesActivos[] = "a.tipo = :tipo";
    $parametrosActivos['tipo'] = $filtroTipo;
}
if ($filtroEstado !== '') {
    $condicionesActivos[] = "a.estado = :estado";
    $parametrosActivos['estado'] = $filtroEstado;
} else {
    // Por defecto no mostramos los dados de baja, a menos que se pida explícitamente en el filtro
    $condicionesActivos[] = "a.estado <> 'Baja'";
}
if ($filtroLaboratorio !== '') {
    $condicionesActivos[] = "a.id_laboratorio = :laboratorio";
    $parametrosActivos['laboratorio'] = $filtroLaboratorio;
}
if ($filtroBuscar !== '') {
    $condicionesActivos[] = "(a.numero_placa LIKE :buscar OR a.nombre LIKE :buscarNombre)";
    $parametrosActivos['buscar'] = '%' . $filtroBuscar . '%';
    $parametrosActivos['buscarNombre'] = '%' . $filtroBuscar . '%';
}

$sqlActivos = "SELECT a.*, l.codigo_laboratorio
               FROM activos a
               LEFT JOIN laboratorio l ON l.id = a.id_laboratorio";

if (count($condicionesActivos) > 0) {
    $sqlActivos .= " WHERE " . implode(' AND ', $condicionesActivos);
}
$sqlActivos .= " ORDER BY a.id DESC";

$stmtActivos = $conn->prepare($sqlActivos);
$stmtActivos->execute($parametrosActivos);
$listaActivosModulo = $stmtActivos->fetchAll();