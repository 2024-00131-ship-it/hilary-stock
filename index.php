<?php
require_once 'conexion.php';

$mensaje = '';
$tipo_alerta = '';
$tab_activa = isset($_GET['tab']) ? $_GET['tab'] : 'inventario';

// 1. ELIMINAR PRODUCTO
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=eliminado&tab=inventario");
    exit();
}

// 2. AGREGAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $precio = (float)$_POST['precio'];
    $stock  = (int)$_POST['stock'];

    if (!empty($nombre) && $precio >= 0 && $stock >= 0) {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, precio, stock) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $precio, $stock]);
        header("Location: index.php?msg=agregado&tab=inventario");
        exit();
    }
}

// 3. EDITAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id     = (int)$_POST['id'];
    $nombre = trim($_POST['nombre']);
    $precio = (float)$_POST['precio'];
    $stock  = (int)$_POST['stock'];

    if ($id > 0 && !empty($nombre) && $precio >= 0 && $stock >= 0) {
        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, precio = ?, stock = ? WHERE id = ?");
        $stmt->execute([$nombre, $precio, $stock, $id]);
        header("Location: index.php?msg=editado&tab=inventario");
        exit();
    }
}

// 4. BÚSQUEDA Y LISTADO COMPLETO
$stmt_todos = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
$productos = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);

// Búsqueda específica para la pestaña 3
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';

$sql_buscar = "SELECT * FROM productos WHERE 1=1";
$params = [];

if (!empty($busqueda)) {
    $sql_buscar .= " AND nombre LIKE ?";
    $params[] = "%$busqueda%";
}

if ($filtro_estado === 'agotado') {
    $sql_buscar .= " AND stock = 0";
} elseif ($filtro_estado === 'bajo') {
    $sql_buscar .= " AND stock > 0 AND stock <= 5";
}

$sql_buscar .= " ORDER BY id DESC";
$stmt_buscar = $pdo->prepare($sql_buscar);
$stmt_buscar->execute($params);
$resultados_busqueda = $stmt_buscar->fetchAll(PDO::FETCH_ASSOC);

// 5. CÁLCULO DE ESTADÍSTICAS
$total_productos = count($productos);
$valor_inventario = 0;
$bajo_stock = 0;

foreach ($productos as $p) {
    $valor_inventario += ($p['precio'] * $p['stock']);
    if ($p['stock'] <= 5) {
        $bajo_stock++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HilaryStock | Sistema Tabular</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-purple: #2e1065;
            --main-purple: #6d28d9;
            --soft-purple: #8b5cf6;
            --bg-purple: #f3e8ff;
            --card-bg: #ffffff;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-purple);
            color: #1e1b4b;
            min-height: 100vh;
        }

        .layout-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--sidebar-purple) 0%, #1e1b4b 100%);
            color: white;
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 2.5rem;
        }

        /* ESTILOS DE NAVEGACIÓN EN PESTAÑAS (SIDEBAR) */
        .nav-pills .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-align: left;
            width: 100%;
        }

        .nav-pills .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-pills .nav-link.active {
            color: white;
            background: linear-gradient(135deg, var(--main-purple), var(--soft-purple));
            box-shadow: 0 4px 15px rgba(109, 40, 217, 0.3);
        }

        /* BOTONES Y TARJETAS */
        .btn-violet {
            background: linear-gradient(135deg, var(--main-purple), var(--soft-purple));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(109, 40, 217, 0.3);
        }

        .btn-violet:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(109, 40, 217, 0.45);
            color: white;
        }

        .btn-action-edit {
            background-color: #f3e8ff;
            color: var(--main-purple);
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            transition: all 0.2s;
        }

        .btn-action-edit:hover { background-color: var(--main-purple); color: white; }

        .btn-action-delete {
            background-color: #ffe4e6;
            color: #e11d48;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            transition: all 0.2s;
        }

        .btn-action-delete:hover { background-color: #e11d48; color: white; }

        .card-custom {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid rgba(139, 92, 246, 0.15);
            box-shadow: 0 10px 30px rgba(46, 16, 101, 0.04);
        }

        .stat-card-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: var(--bg-purple);
            color: var(--main-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        /* TABLAS */
        .table-violet thead {
            background-color: var(--bg-purple);
            color: var(--sidebar-purple);
        }

        .table-violet th {
            border: none;
            padding: 14px 18px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-violet td {
            padding: 16px 18px;
            border-bottom: 1px solid #f3e8ff;
        }

        .badge-soft-success { background-color: #dcfce7; color: #15803d; font-weight: 700; }
        .badge-soft-warning { background-color: #fef9c3; color: #a16207; font-weight: 700; }
        .badge-soft-danger  { background-color: #ffe4e6; color: #be123c; font-weight: 700; }

        .modal-header-violet {
            background: linear-gradient(135deg, var(--sidebar-purple), var(--main-purple));
            color: white;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
        }

        @media (max-width: 992px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .layout-wrapper { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="layout-wrapper">

    <!-- SIDEBAR DE NAVEGACIÓN -->
    <aside class="sidebar">
        <div>
            <div class="d-flex align-items-center gap-3 mb-4">
                <i class="fa-solid fa-layer-group fs-2 text-warning"></i>
                <div>
                    <h4 class="fw-bold mb-0 text-white">HilaryStock</h4>
                    <small class="text-light opacity-75">Control Modular</small>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <!-- CONTROL DE PESTAÑAS Y SECCIONES -->
            <div class="nav flex-column nav-pills gap-2" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <button class="nav-link <?= ($tab_activa === 'inventario') ? 'active' : '' ?>" id="tab-inventario-btn" data-bs-toggle="pill" data-bs-target="#tab-inventario" type="button" role="tab">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Inventario General</span>
                </button>
                
                <button class="nav-link <?= ($tab_activa === 'nuevo') ? 'active' : '' ?>" id="tab-nuevo-btn" data-bs-toggle="pill" data-bs-target="#tab-nuevo" type="button" role="tab">
                    <i class="fa-solid fa-square-plus"></i>
                    <span>Ingresar Producto</span>
                </button>

                <button class="nav-link <?= ($tab_activa === 'buscar') ? 'active' : '' ?>" id="tab-buscar-btn" data-bs-toggle="pill" data-bs-target="#tab-buscar" type="button" role="tab">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Consultar / Buscar</span>
                </button>
            </div>
        </div>

        <div>
            <div class="text-center text-light opacity-50 small">
                &copy; <?= date('Y') ?> HilaryStock Pro
            </div>
        </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL EN PESTAÑAS -->
    <main class="main-content">

        <!-- MENSAJES DE CONFIRMACIÓN -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-dismissible fade show bg-white shadow-sm border-start border-4 border-primary mb-4" role="alert">
                <?php 
                    if($_GET['msg'] == 'agregado') echo '<i class="fa-solid fa-circle-check text-success me-2"></i> Producto registrado correctamente en el inventario.';
                    if($_GET['msg'] == 'editado') echo '<i class="fa-solid fa-pen-to-square text-primary me-2"></i> Cambios guardados con éxito.';
                    if($_GET['msg'] == 'eliminado') echo '<i class="fa-solid fa-trash text-danger me-2"></i> Producto eliminado del sistema.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="tab-content" id="v-pills-tabContent">

            <!-- ================= PESTAÑA 1: INVENTARIO GENERAL ================= -->
            <div class="tab-pane fade <?= ($tab_activa === 'inventario') ? 'show active' : '' ?>" id="tab-inventario" role="tabpanel">
                
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold text-dark mb-1">Inventario General</h2>
                        <p class="text-muted mb-0">Resumen y monitoreo del inventario de productos.</p>
                    </div>
                </div>

                <!-- KPIs ESTADÍSTICOS -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-custom p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-card-icon">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-bold text-uppercase">Total Productos</span>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $total_productos ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-custom p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-card-icon" style="background: #e0e7ff; color: #4338ca;">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-bold text-uppercase">Valoración (GTQ)</span>
                                    <h3 class="fw-bold mb-0 text-dark">Q<?= number_format($valor_inventario, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-custom p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-card-icon" style="background: #ffe4e6; color: #e11d48;">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-bold text-uppercase">Stock Bajo (≤5)</span>
                                    <h3 class="fw-bold mb-0 text-danger"><?= $bajo_stock ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLA DE LISTADO GENERAL -->
                <div class="card card-custom p-4">
                    <div class="table-responsive">
                        <table class="table table-violet align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Producto</th>
                                    <th>Precio Unitario</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($productos) > 0): ?>
                                    <?php foreach ($productos as $p): ?>
                                        <tr>
                                            <td><span class="fw-bold text-muted">#<?= $p['id'] ?></span></td>
                                            <td><strong class="text-dark fs-6"><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                            <td><span class="fw-bold text-dark">Q<?= number_format($p['precio'], 2) ?></span></td>
                                            <td><span class="fw-bold"><?= $p['stock'] ?></span> <small class="text-muted">uds.</small></td>
                                            <td>
                                                <?php if ($p['stock'] == 0): ?>
                                                    <span class="badge badge-soft-danger px-3 py-2 rounded-pill">Agotado</span>
                                                <?php elseif ($p['stock'] <= 5): ?>
                                                    <span class="badge badge-soft-warning px-3 py-2 rounded-pill">Pocas Unidades</span>
                                                <?php else: ?>
                                                    <span class="badge badge-soft-success px-3 py-2 rounded-pill">En Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $p['id'] ?>" title="Editar">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <a href="index.php?eliminar=<?= $p['id'] ?>" class="btn btn-action-delete" onclick="return confirm('¿Eliminar este producto?');" title="Eliminar">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- MODAL EDITAR -->
                                        <div class="modal fade" id="modalEditar<?= $p['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 rounded-4 shadow">
                                                    <div class="modal-header modal-header-violet">
                                                        <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Editar Producto #<?= $p['id'] ?></h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="index.php">
                                                        <div class="modal-body p-4">
                                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Nombre del Producto</label>
                                                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($p['nombre']) ?>" required>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-bold">Precio (Q)</label>
                                                                    <input type="number" step="0.01" name="precio" class="form-control" value="<?= $p['precio'] ?>" required>
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label fw-bold">Stock</label>
                                                                    <input type="number" name="stock" class="form-control" value="<?= $p['stock'] ?>" required>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light rounded-bottom-4">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" name="editar" class="btn btn-violet">Guardar Cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-box-open fs-1 d-block mb-3 opacity-50"></i>
                                            No hay productos registrados actualmente.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ================= PESTAÑA 2: INGRESAR PRODUCTO ================= -->
            <div class="tab-pane fade <?= ($tab_activa === 'nuevo') ? 'show active' : '' ?>" id="tab-nuevo" role="tabpanel">
                
                <div class="mb-4">
                    <h2 class="fw-bold text-dark mb-1">Registrar Nuevo Producto</h2>
                    <p class="text-muted mb-0">Completa el formulario para ingresar un nuevo artículo al catálogo.</p>
                </div>

                <div class="row justify-content-start">
                    <div class="col-lg-8">
                        <div class="card card-custom p-4">
                            <form method="POST" action="index.php">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Nombre del Producto</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-tag text-muted"></i></span>
                                        <input type="text" name="nombre" class="form-control bg-light border-start-0 py-2" placeholder="Ej. Teclado Mecánico RGB" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Precio Unitario (Q)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-coins text-muted"></i></span>
                                            <input type="number" step="0.01" name="precio" class="form-control bg-light border-start-0 py-2" placeholder="250.00" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Cantidad de Stock Inicial</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-cubes text-muted"></i></span>
                                            <input type="number" name="stock" class="form-control bg-light border-start-0 py-2" placeholder="15" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 pt-2">
                                    <button type="submit" name="agregar" class="btn btn-violet px-4 py-2">
                                        <i class="fa-solid fa-plus-circle me-2"></i> Guardar Producto
                                    </button>
                                    <button type="reset" class="btn btn-light border px-4 py-2">Limpiar Formulario</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================= PESTAÑA 3: CONSULTAR Y BUSCAR ================= -->
            <div class="tab-pane fade <?= ($tab_activa === 'buscar') ? 'show active' : '' ?>" id="tab-buscar" role="tabpanel">
                
                <div class="mb-4">
                    <h2 class="fw-bold text-dark mb-1">Módulo de Búsqueda y Filtros</h2>
                    <p class="text-muted mb-0">Localiza productos específicos en tiempo real con filtros avanzados.</p>
                </div>

                <div class="card card-custom p-4 mb-4">
                    <form method="GET" action="index.php" class="row g-3">
                        <input type="hidden" name="tab" value="buscar">
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">BUSCAR POR NOMBRE</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                <input type="text" name="buscar" class="form-control bg-light border-start-0" placeholder="Escribe el nombre del producto..." value="<?= htmlspecialchars($busqueda) ?>">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">FILTRAR POR ESTADO</label>
                            <select name="estado" class="form-select bg-light">
                                <option value="todos" <?= ($filtro_estado === 'todos') ? 'selected' : '' ?>>Todos los estados</option>
                                <option value="bajo" <?= ($filtro_estado === 'bajo') ? 'selected' : '' ?>>Stock Bajo (≤ 5 unidades)</option>
                                <option value="agotado" <?= ($filtro_estado === 'agotado') ? 'selected' : '' ?>>Agotados (0 unidades)</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-violet w-100 py-2"><i class="fa-solid fa-filter me-1"></i> Filtrar</button>
                            <?php if(!empty($busqueda) || $filtro_estado !== 'todos'): ?>
                                <a href="index.php?tab=buscar" class="btn btn-light border py-2" title="Limpiar"><i class="fa-solid fa-xmark"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- RESULTADOS DE LA BÚSQUEDA -->
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-3 text-muted">Resultados encontrados: <?= count($resultados_busqueda) ?></h5>
                    
                    <div class="table-responsive">
                        <table class="table table-violet align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($resultados_busqueda) > 0): ?>
                                    <?php foreach ($resultados_busqueda as $p): ?>
                                        <tr>
                                            <td><span class="fw-bold text-muted">#<?= $p['id'] ?></span></td>
                                            <td><strong class="text-dark fs-6"><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                            <td><span class="fw-bold text-dark">Q<?= number_format($p['precio'], 2) ?></span></td>
                                            <td><span class="fw-bold"><?= $p['stock'] ?></span> <small class="text-muted">uds.</small></td>
                                            <td>
                                                <?php if ($p['stock'] == 0): ?>
                                                    <span class="badge badge-soft-danger px-3 py-2 rounded-pill">Agotado</span>
                                                <?php elseif ($p['stock'] <= 5): ?>
                                                    <span class="badge badge-soft-warning px-3 py-2 rounded-pill">Pocas Unidades</span>
                                                <?php else: ?>
                                                    <span class="badge badge-soft-success px-3 py-2 rounded-pill">En Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $p['id'] ?>" title="Editar">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <a href="index.php?eliminar=<?= $p['id'] ?>" class="btn btn-action-delete" onclick="return confirm('¿Eliminar este producto?');" title="Eliminar">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-magnifying-glass fs-1 d-block mb-3 opacity-50"></i>
                                            No se encontraron coincidencias para tu búsqueda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

    </main>
</div>

<!-- JS BOOTSTRAP -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>