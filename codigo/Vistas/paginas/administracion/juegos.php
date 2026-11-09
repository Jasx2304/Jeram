<?php
// Gestión de juegos: tabla con miniaturas + modales para crear/editar.
// Los datos de cada fila viajan en atributos data-* y el JS de abajo
// los lee al pulsar "Editar" (delegación de eventos).
// Recibe $juegos desde ControladorAdmin::juegos().
use JERAM\Utilidades\Recursos;
$subPaginaActiva = 'games';
$mostrarEnvoltura = false;
?>
<?php include dirname(__DIR__, 2) . '/plantillas/cabecera.php'; ?>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h2><i class='bx bx-shield'></i> Admin</h2>
        <nav class="admin-nav">
            <a href="<?= Recursos::url('admin/dashboard') ?>"><i class='bx bx-home'></i> Dashboard</a>
            <a href="<?= Recursos::url('admin/games') ?>" class="active"><i class='bx bx-game'></i> Juegos</a>
            <a href="<?= Recursos::url('admin/comments') ?>"><i class='bx bx-comment'></i> Comentarios</a>
            <a href="<?= Recursos::url('admin/users') ?>"><i class='bx bx-user'></i> Usuarios</a>
        </nav>
    </aside>

    <main class="admin-content">
        <div class="admin-header">
            <h1><i class='bx bx-game'></i> Gestión de Juegos</h1>
            <button class="btn" onclick="openNewGameModal()">+ Nuevo juego</button>
        </div>

        <div class="admin-table-wrap">
        <table class="admin-table admin-games-table">
            <thead>
                <tr>
                    <th>Portada</th>
                    <th>Orden</th>
                    <th>Título</th>
                    <th>Slug</th>
                    <th>Ruta</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($juegos as $juego): ?>
                    <?php
                    // Los datos del juego viajan en atributos data-* de la fila;
                    // el JS los lee con dataset al pulsar "Editar".
                    $controlesJson = $juego['controles'] ?? '[]';
                    ?>
                    <tr
                        data-id="<?= $juego['id'] ?>"
                        data-titulo="<?= htmlspecialchars($juego['titulo']) ?>"
                        data-slug="<?= htmlspecialchars($juego['slug']) ?>"
                        data-desc-corta="<?= htmlspecialchars($juego['descripcion_corta'] ?? '') ?>"
                        data-desc-larga="<?= htmlspecialchars($juego['descripcion_larga'] ?? '') ?>"
                        data-objetivo="<?= htmlspecialchars($juego['objetivo'] ?? '') ?>"
                        data-ruta="<?= htmlspecialchars($juego['ruta_archivo'] ?? '') ?>"
                        data-orden="<?= (int)$juego['orden'] ?>"
                        data-activo="<?= (int)$juego['activo'] ?>"
                        data-portada="<?= htmlspecialchars($juego['imagen_portada'] ?? '') ?>"
                        data-miniatura="<?= htmlspecialchars($juego['imagen_miniatura'] ?? '') ?>"
                        data-controles="<?= htmlspecialchars($controlesJson) ?>"
                    >
                        <td class="td-thumb">
                            <img src="<?= Recursos::img(htmlspecialchars($juego['imagen_portada'])) ?>" alt="" class="game-thumb" loading="lazy" title="Portada de <?= htmlspecialchars($juego['titulo']) ?>">
                        </td>
                        <td><?= $juego['orden'] ?></td>
                        <td title="<?= htmlspecialchars($juego['titulo']) ?>"><strong><?= htmlspecialchars($juego['titulo']) ?></strong></td>
                        <td title="<?= htmlspecialchars($juego['slug']) ?>"><code><?= htmlspecialchars($juego['slug']) ?></code></td>
                        <td title="<?= htmlspecialchars($juego['ruta_archivo']) ?>"><small class="route-text"><?= htmlspecialchars($juego['ruta_archivo']) ?></small></td>
                        <td>
                            <span class="badge badge-<?= $juego['activo'] ? 'success' : 'secondary' ?>">
                                <?= $juego['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="actions">
                            <button type="button" class="btn-sm" data-action="edit">
                                <i class='bx bx-pencil'></i> Editar
                            </button>
                            <form method="post" action="<?= Recursos::url('admin/games/delete') ?>" style="display:inline">
                                <input type="hidden" name="id" value="<?= $juego['id'] ?>">
                                <button type="submit" class="btn-sm btn-danger" onclick="return confirm('¿Eliminar este juego?')" title="Eliminar"><i class='bx bx-trash'></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($juegos)): ?>
                    <tr><td colspan="7" class="empty-state">No hay juegos todavía</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </main>
</div>

<div class="modal" id="newGameModal">
    <div class="modal-content modal-wide">
        <span class="modal-close" onclick="closeModal('newGameModal')">&times;</span>
        <h3><i class='bx bx-plus-circle'></i> Nuevo juego</h3>
        <form method="post" action="<?= Recursos::url('admin/games') ?>" onsubmit="return serializeControls('new')">
            <div class="game-form-grid">
                <div class="game-form-fields">
                    <div class="form-group">
                        <label>Título *</label>
                        <input type="text" name="titulo" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label>Slug (identificador único, sin espacios) *</label>
                        <input type="text" name="slug" required maxlength="50" placeholder="ej: mi-nuevo-juego">
                    </div>
                    <div class="form-group">
                        <label>Descripción corta</label>
                        <input type="text" name="descripcion_corta" maxlength="300" placeholder="Aparece en la tarjeta del juego">
                    </div>
                    <div class="form-group">
                        <label>Descripción larga</label>
                        <textarea name="descripcion_larga" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Objetivo del juego</label>
                        <textarea name="objetivo" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ruta del juego</label>
                            <input type="text" name="ruta_archivo" placeholder="juego.php?game=mi-juego">
                        </div>
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" name="orden" value="0" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Controles</label>
                        <div class="controls-editor" id="controlsEditor-new"></div>
                        <button type="button" class="btn-sm" onclick="addControlRow('new')">+ Añadir control</button>
                        <input type="hidden" name="controles_json" id="controlesJson-new">
                    </div>
                    <div class="form-group form-checkbox">
                        <label><input type="checkbox" name="activo" checked> Activo</label>
                    </div>
                </div>

                <div class="game-form-image">
                    <label>Portada / Logo</label>
                    <div class="image-preview" id="preview-new">
                        <img src="<?= Recursos::img('en_construccion.png') ?>" alt="Preview" id="previewImg-new">
                    </div>
                    <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" id="file-new" onchange="previewImage('new')">
                    <button type="button" class="btn-sm" onclick="uploadImage('new')"><i class='bx bx-upload'></i> Subir imagen</button>
                    <input type="hidden" name="imagen_portada" id="portada-new" value="en_construccion.png">
                    <input type="hidden" name="imagen_miniatura" id="miniatura-new" value="en_construccion.png">
                    <p class="hint-text">JPG, PNG, GIF o WEBP. Máx. 2 MB.</p>
                </div>
            </div>
            <button type="submit" class="btn btn-block">Crear juego</button>
        </form>
    </div>
</div>

<div class="modal" id="editGameModal">
    <div class="modal-content modal-wide">
        <span class="modal-close" onclick="closeModal('editGameModal')">&times;</span>
        <h3><i class='bx bx-pencil'></i> Editar juego</h3>
        <form method="post" action="<?= Recursos::url('admin/games/update') ?>" onsubmit="return serializeControls('edit')">
            <input type="hidden" name="id" id="editId">
            <div class="game-form-grid">
                <div class="game-form-fields">
                    <div class="form-group">
                        <label>Título *</label>
                        <input type="text" name="titulo" id="editTitulo" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label>Slug</label>
                        <input type="text" id="editSlug" disabled>
                    </div>
                    <div class="form-group">
                        <label>Descripción corta</label>
                        <input type="text" name="descripcion_corta" id="editDescCorta" maxlength="300">
                    </div>
                    <div class="form-group">
                        <label>Descripción larga</label>
                        <textarea name="descripcion_larga" id="editDescLarga" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Objetivo</label>
                        <textarea name="objetivo" id="editObjetivo" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ruta del juego</label>
                            <input type="text" name="ruta_archivo" id="editRuta" placeholder="juego.php?game=...">
                        </div>
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" name="orden" id="editOrden" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Controles</label>
                        <div class="controls-editor" id="controlsEditor-edit"></div>
                        <button type="button" class="btn-sm" onclick="addControlRow('edit')">+ Añadir control</button>
                        <input type="hidden" name="controles_json" id="controlesJson-edit">
                    </div>
                    <div class="form-group form-checkbox">
                        <label><input type="checkbox" name="activo" id="editActivo"> Activo</label>
                    </div>
                </div>

                <div class="game-form-image">
                    <label>Portada / Logo</label>
                    <div class="image-preview" id="preview-edit">
                        <img src="" alt="Preview" id="previewImg-edit">
                    </div>
                    <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" id="file-edit" onchange="previewImage('edit')">
                    <button type="button" class="btn-sm" onclick="uploadImage('edit')"><i class='bx bx-upload'></i> Subir imagen</button>
                    <input type="hidden" name="imagen_portada" id="portada-edit">
                    <input type="hidden" name="imagen_miniatura" id="miniatura-edit">
                    <p class="hint-text">JPG, PNG, GIF o WEBP. Máx. 2 MB.</p>
                </div>
            </div>
            <button type="submit" class="btn btn-block">Guardar cambios</button>
        </form>
    </div>
</div>

<script>
const UPLOAD_URL = <?= json_encode(Recursos::url('admin/games/upload')) ?>;
const IMG_BASE = <?= json_encode(rtrim(Recursos::img(''), '/')) ?>;

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function openNewGameModal() {
    document.getElementById('controlsEditor-new').innerHTML = '';
    document.getElementById('portada-new').value = 'en_construccion.png';
    document.getElementById('miniatura-new').value = 'en_construccion.png';
    document.getElementById('previewImg-new').src = IMG_BASE + '/en_construccion.png';
    addControlRow('new');
    document.getElementById('newGameModal').classList.add('open');
}

function addControlRow(suffix, tecla = '', accion = '') {
    const editor = document.getElementById('controlsEditor-' + suffix);
    const row = document.createElement('div');
    row.className = 'control-row';
    row.innerHTML =
        '<input type="text" class="ctrl-tecla" placeholder="Tecla (ej: ESPACIO)" value="' + escapeAttr(tecla) + '">' +
        '<input type="text" class="ctrl-accion" placeholder="Acción (ej: Saltar)" value="' + escapeAttr(accion) + '">' +
        '<button type="button" class="ctrl-remove" title="Quitar" onclick="this.parentElement.remove()">&times;</button>';
    editor.appendChild(row);
}

function escapeAttr(s) {
    return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

function serializeControls(suffix) {
    const rows = document.querySelectorAll('#controlsEditor-' + suffix + ' .control-row');
    const data = [];
    rows.forEach(row => {
        const tecla = row.querySelector('.ctrl-tecla').value.trim();
        const accion = row.querySelector('.ctrl-accion').value.trim();
        if (tecla && accion) data.push({ tecla, accion });
    });
    document.getElementById('controlesJson-' + suffix).value = JSON.stringify(data);
    return true;
}

function editGame(row) {
    const d = row.dataset;
    document.getElementById('editId').value = d.id;
    document.getElementById('editTitulo').value = d.titulo;
    document.getElementById('editSlug').value = d.slug;
    document.getElementById('editDescCorta').value = d.descCorta || '';
    document.getElementById('editDescLarga').value = d.descLarga || '';
    document.getElementById('editObjetivo').value = d.objetivo || '';
    document.getElementById('editRuta').value = d.ruta || '';
    document.getElementById('editOrden').value = d.orden;
    document.getElementById('editActivo').checked = Number(d.activo) === 1;

    const portada = d.portada || 'en_construccion.png';
    document.getElementById('portada-edit').value = portada;
    document.getElementById('miniatura-edit').value = d.miniatura || portada;
    document.getElementById('previewImg-edit').src = IMG_BASE + '/' + portada;

    const editor = document.getElementById('controlsEditor-edit');
    editor.innerHTML = '';
    let controles = [];
    try { controles = JSON.parse(d.controles || '[]'); } catch (e) {}
    if (!Array.isArray(controles) || controles.length === 0) {
        addControlRow('edit');
    } else {
        controles.forEach(c => addControlRow('edit', c.tecla || '', c.accion || ''));
    }

    document.getElementById('editGameModal').classList.add('open');
}

// Delegación de eventos: un solo listener para todos los botones "Editar"
document.querySelector('.admin-games-table').addEventListener('click', e => {
    const btn = e.target.closest('[data-action="edit"]');
    if (btn) editGame(btn.closest('tr'));
});

function previewImage(suffix) {
    const file = document.getElementById('file-' + suffix).files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg-' + suffix).src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function uploadImage(suffix) {
    const input = document.getElementById('file-' + suffix);
    const file = input.files[0];
    if (!file) {
        alert('Primero selecciona una imagen');
        return;
    }
    const fd = new FormData();
    fd.append('imagen', file);

    const btn = input.nextElementSibling;
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Subiendo...';

    fetch(UPLOAD_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = original;
            if (data.ok) {
                document.getElementById('portada-' + suffix).value = data.path;
                document.getElementById('miniatura-' + suffix).value = data.path;
                document.getElementById('previewImg-' + suffix).src = data.url + '?t=' + Date.now();
                input.value = '';
            } else {
                alert(data.error || 'Error al subir la imagen');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = original;
            alert('Error de conexión al subir la imagen');
        });
}
</script>

<?php include dirname(__DIR__, 2) . '/plantillas/pie.php'; ?>
