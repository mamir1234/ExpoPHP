<?php
// ============================================================
// VISTA DEL MÓDULO DE ACTIVOS
// Se incluye desde index.php dentro del case 'activos'.
// Usa $listaActivosModulo, $listaLaboratorios, $familiasActivo,
// $familiasConPlaca y los filtros calculados en activos_logica.php
// ============================================================
$estadosActivo = ['Disponible', 'Prestado', 'Dañado'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold m-0"><i class="bi bi-box-seam me-2"></i>Activos registrados</h6>
    <button type="button" class="btn-principal-sm" data-bs-toggle="modal" data-bs-target="#modalCrearActivo">
        <i class="bi bi-plus-lg me-1"></i> Registrar activo
    </button>
</div>

<!-- Filtros -->
<div class="custom-card-panel panel-filtros mb-3">
    <form action="index.php" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="modulo" value="activos">

        <div class="col-md-3">
            <label class="filtro-label">Tipo</label>
            <select name="filtro_tipo" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($familiasActivo as $tipoOpcion): ?>
                    <option value="<?php echo htmlspecialchars($tipoOpcion); ?>" <?php echo ($filtroTipo === $tipoOpcion) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipoOpcion); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="filtro-label">Estado</label>
            <select name="filtro_estado" class="form-select">
                <option value="">Todos (sin bajas)</option>
                <?php foreach (array_merge($estadosActivo, ['Baja']) as $estadoOpcion): ?>
                    <option value="<?php echo htmlspecialchars($estadoOpcion); ?>" <?php echo ($filtroEstado === $estadoOpcion) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($estadoOpcion); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="filtro-label">Laboratorio</label>
            <select name="filtro_laboratorio" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($listaLaboratorios as $lab): ?>
                    <option value="<?php echo $lab['id']; ?>" <?php echo ($filtroLaboratorio == $lab['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lab['codigo_laboratorio']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="filtro-label">Buscar por nombre o N° de placa</label>
            <div class="d-flex gap-2">
                <input type="text" name="buscar" class="form-control" placeholder="Ej: Laptop / PL-00123" value="<?php echo htmlspecialchars($filtroBuscar); ?>">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de activos -->
<div class="custom-card-panel">
    <div class="table-responsive">
        <table class="table table-custom mb-0 align-middle">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Placa / Cant.</th>
                    <th>Laboratorio</th>
                    <th>Adquisición</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($listaActivosModulo) === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No se encontraron activos con esos filtros.</td>
                    </tr>
                <?php else: foreach ($listaActivosModulo as $activo):
                    $claseEstadoActivo = match(mb_strtolower($activo['estado'] ?? '')) {
                        'disponible' => 'status-disponible',
                        'prestado'   => 'status-enuso',
                        'baja'       => 'status-baja',
                        default      => 'status-danado'
                    };
                    $esConPlacaFila = in_array($activo['tipo'], $familiasConPlaca);
                ?>
                    <tr>
                        <td>
                            <?php if (!empty($activo['imagen_base64'])): ?>
                                <img src="<?php echo htmlspecialchars($activo['imagen_base64']); ?>" class="activo-thumb" alt="Foto del activo">
                            <?php else: ?>
                                <div class="activo-thumb activo-thumb-vacio"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($activo['nombre'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($activo['tipo'] ?? '-'); ?></td>
                        <td>
                            <?php if ($esConPlacaFila): ?>
                                <code><?php echo htmlspecialchars($activo['numero_placa'] ?? '-'); ?></code>
                            <?php else: ?>
                                <?php echo (int)($activo['cantidad'] ?? 0); ?> uds.
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($activo['codigo_laboratorio'] ?? 'Sin asignar'); ?></td>
                        <td><?php echo htmlspecialchars($activo['fecha_adquisicion'] ?? '-'); ?></td>
                        <td><span class="badge-status <?php echo $claseEstadoActivo; ?>"><?php echo htmlspecialchars($activo['estado'] ?? '-'); ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#verActivo<?php echo $activo['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (mb_strtolower($activo['estado']) !== 'baja'): ?>
                                    <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editarActivo<?php echo $activo['id']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#bajaActivo<?php echo $activo['id']; ?>">
                                        <i class="bi bi-box-arrow-down"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===================== MODAL: Registrar activo ===================== -->
<div class="modal fade" id="modalCrearActivo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="crear_activo">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar activo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del activo</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Laptop Dell Latitude" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select selector-tipo-activo" required>
                            <?php foreach ($familiasActivo as $tipoOpcion): ?>
                                <option value="<?php echo htmlspecialchars($tipoOpcion); ?>"><?php echo htmlspecialchars($tipoOpcion); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" data-campo="placa">
                        <label class="form-label">Número de placa</label>
                        <input type="text" name="numero_placa" class="form-control">
                    </div>
                    <div class="mb-3" data-campo="cantidad">
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="cantidad" class="form-control" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Laboratorio</label>
                        <select name="id_laboratorio" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php foreach ($listaLaboratorios as $lab): ?>
                                <option value="<?php echo $lab['id']; ?>"><?php echo htmlspecialchars($lab['codigo_laboratorio']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" required>
                            <?php foreach ($estadosActivo as $estadoOpcion): ?>
                                <option value="<?php echo htmlspecialchars($estadoOpcion); ?>"><?php echo htmlspecialchars($estadoOpcion); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de adquisición</label>
                        <input type="date" name="fecha_adquisicion" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Foto del activo (opcional)</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-principal-sm">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODALES POR ACTIVO: Ver / Editar / Dar de baja ===================== -->
<?php foreach ($listaActivosModulo as $activo):
    $esConPlacaModal = in_array($activo['tipo'], $familiasConPlaca);
?>

    <!-- Ver detalle -->
    <div class="modal fade" id="verActivo<?php echo $activo['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del activo #<?php echo $activo['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($activo['imagen_base64'])): ?>
                        <img src="<?php echo htmlspecialchars($activo['imagen_base64']); ?>" class="activo-detalle-img mb-3" alt="Foto del activo">
                    <?php endif; ?>
                    <p class="mb-1"><strong>Nombre:</strong> <?php echo htmlspecialchars($activo['nombre'] ?? '-'); ?></p>
                    <p class="mb-1"><strong>Tipo:</strong> <?php echo htmlspecialchars($activo['tipo'] ?? '-'); ?></p>
                    <?php if ($esConPlacaModal): ?>
                        <p class="mb-1"><strong>N° de placa:</strong> <?php echo htmlspecialchars($activo['numero_placa'] ?? '-'); ?></p>
                    <?php else: ?>
                        <p class="mb-1"><strong>Cantidad:</strong> <?php echo (int)($activo['cantidad'] ?? 0); ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($activo['descripcion'] ?? '-')); ?></p>
                    <p class="mb-1"><strong>Laboratorio:</strong> <?php echo htmlspecialchars($activo['codigo_laboratorio'] ?? 'Sin asignar'); ?></p>
                    <p class="mb-1"><strong>Fecha de adquisición:</strong> <?php echo htmlspecialchars($activo['fecha_adquisicion'] ?? '-'); ?></p>
                    <p class="mb-1"><strong>Estado:</strong> <?php echo htmlspecialchars($activo['estado'] ?? '-'); ?></p>
                    <?php if (mb_strtolower($activo['estado']) === 'baja'): ?>
                        <hr>
                        <p class="mb-1"><strong>Fecha de baja:</strong> <?php echo htmlspecialchars($activo['fecha_baja'] ?? '-'); ?></p>
                        <p class="mb-0"><strong>Motivo:</strong> <?php echo htmlspecialchars($activo['motivo_baja'] ?? '-'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (mb_strtolower($activo['estado']) !== 'baja'): ?>
        <!-- Editar -->
        <div class="modal fade" id="editarActivo<?php echo $activo['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="accion" value="editar_activo">
                        <input type="hidden" name="id" value="<?php echo $activo['id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar activo #<?php echo $activo['id']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nombre del activo</label>
                                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($activo['nombre'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select name="tipo" class="form-select selector-tipo-activo" required>
                                    <?php foreach ($familiasActivo as $tipoOpcion): ?>
                                        <option value="<?php echo htmlspecialchars($tipoOpcion); ?>" <?php echo ($activo['tipo'] === $tipoOpcion) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($tipoOpcion); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3" data-campo="placa">
                                <label class="form-label">Número de placa</label>
                                <input type="text" name="numero_placa" class="form-control" value="<?php echo htmlspecialchars($activo['numero_placa'] ?? ''); ?>">
                            </div>
                            <div class="mb-3" data-campo="cantidad">
                                <label class="form-label">Cantidad</label>
                                <input type="number" name="cantidad" class="form-control" min="1" value="<?php echo (int)($activo['cantidad'] ?? 1); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($activo['descripcion'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Laboratorio</label>
                                <select name="id_laboratorio" class="form-select">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($listaLaboratorios as $lab): ?>
                                        <option value="<?php echo $lab['id']; ?>" <?php echo ($activo['id_laboratorio'] == $lab['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lab['codigo_laboratorio']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select" required>
                                    <?php foreach ($estadosActivo as $estadoOpcion): ?>
                                        <option value="<?php echo htmlspecialchars($estadoOpcion); ?>" <?php echo ($activo['estado'] === $estadoOpcion) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($estadoOpcion); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fecha de adquisición</label>
                                <input type="date" name="fecha_adquisicion" class="form-control" value="<?php echo htmlspecialchars($activo['fecha_adquisicion'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Reemplazar foto (opcional)</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn-principal-sm">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Dar de baja -->
        <div class="modal fade" id="bajaActivo<?php echo $activo['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="index.php" method="POST">
                        <input type="hidden" name="accion" value="dar_de_baja">
                        <input type="hidden" name="id" value="<?php echo $activo['id']; ?>">
                        <div class="modal-header modal-header-danger">
                            <h5 class="modal-title">Dar de baja activo #<?php echo $activo['id']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary small">Esta acción no elimina el activo: queda marcado como "Baja" junto con la fecha y el motivo, para mantener el historial.</p>
                            <div class="mb-2">
                                <label class="form-label">Motivo de la baja</label>
                                <textarea name="motivo_baja" class="form-control" rows="3" required placeholder="Ej: Equipo dañado sin reparación posible"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Confirmar baja</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endforeach; ?>

<script>
    // Muestra "Número de placa" o "Cantidad" según la familia elegida en cada formulario
    const familiasConPlaca = <?php echo json_encode($familiasConPlaca); ?>;

    function actualizarCamposActivo(select) {
        const contenedor = select.closest('.modal-body');
        if (!contenedor) return;

        const esConPlaca = familiasConPlaca.includes(select.value);
        const campoPlaca = contenedor.querySelector('[data-campo="placa"]');
        const campoCantidad = contenedor.querySelector('[data-campo="cantidad"]');

        if (campoPlaca) {
            campoPlaca.style.display = esConPlaca ? '' : 'none';
            campoPlaca.querySelector('input').required = esConPlaca;
        }
        if (campoCantidad) {
            campoCantidad.style.display = esConPlaca ? 'none' : '';
            campoCantidad.querySelector('input').required = !esConPlaca;
        }
    }

    document.querySelectorAll('.selector-tipo-activo').forEach(function (select) {
        actualizarCamposActivo(select);
        select.addEventListener('change', function () { actualizarCamposActivo(select); });
    });
</script>

<?php if (isset($_GET['ver'])): ?>
<script>
    // Llegamos desde "Ver detalle" del Panel Principal: abrimos ese modal directamente
    document.addEventListener('DOMContentLoaded', function () {
        var modalDetalle = document.getElementById('verActivo<?php echo (int) $_GET['ver']; ?>');
        if (modalDetalle) {
            new bootstrap.Modal(modalDetalle).show();
        }
    });
</script>
<?php endif; ?>

<?php if ($mensajeActivos): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: <?php echo json_encode(match($mensajeActivos) {
            'creado'  => 'Activo registrado correctamente',
            'editado' => 'Activo actualizado correctamente',
            'baja'    => 'Activo dado de baja correctamente',
            default   => 'Listo'
        }); ?>,
        confirmButtonColor: '#10B981'
    });
</script>
<?php endif; ?>