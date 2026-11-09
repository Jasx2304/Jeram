// =========================================================
// comentarios.js: sección de comentarios de cada juego.
// Carga la lista inicial, permite publicar (API POST /api/comment/add)
// y recarga la lista después de enviar o eliminar.
// =========================================================
async function initComments(gameSlug) {
    const form = document.getElementById('submitComment');
    const textarea = document.getElementById('comment-text');
    const charCount = document.getElementById('charCount');

    if (form && textarea) {
        if (charCount) {
            textarea.addEventListener('input', () => {
                charCount.textContent = `${textarea.value.length}/2000`;
            });
        }

        form.addEventListener('click', async () => {
            const contenido = textarea.value.trim();
            if (!contenido) return;

            form.disabled = true;
            form.textContent = 'Enviando...';

            try {
                const data = await API.post('/api/comment/add', {
                    game_slug: gameSlug,
                    contenido: contenido,
                });

                if (data.success) {
                    textarea.value = '';
                    if (charCount) charCount.textContent = '0/2000';
                    prependComment(data.comment);
                }
            } catch (err) {
                console.error('Error al comentar:', err);
                alert('Error al enviar comentario');
            } finally {
                form.disabled = false;
                form.textContent = 'Enviar comentario';
            }
        });
    }

    await loadComments(gameSlug);
}

async function loadComments(gameSlug, page = 1) {
    const container = document.getElementById('commentsList');
    if (!container) return;

    try {
        container.innerHTML = '<p class="loading-text">Cargando comentarios...</p>';
        const data = await API.get('/api/comment/list', {
            game_slug: gameSlug,
            page: page,
            limit: 20,
        });

        if (!data.comments || data.comments.length === 0) {
            container.innerHTML = '<p class="empty-state">No hay comentarios todavía. ¡Sé el primero en comentar!</p>';
            return;
        }

        container.innerHTML = data.html;

        if (data.has_more) {
            const loadMore = document.createElement('button');
            loadMore.className = 'btn load-more';
            loadMore.textContent = 'Cargar más comentarios';
            loadMore.addEventListener('click', () => loadComments(gameSlug, page + 1));
            container.appendChild(loadMore);
        }
    } catch (err) {
        console.error('Error al cargar comentarios:', err);
        container.innerHTML = '<p class="error-text">Error al cargar comentarios</p>';
    }
}

function prependComment(comment) {
    const container = document.getElementById('commentsList');
    if (!container) return;

    const emptyState = container.querySelector('.empty-state');
    if (emptyState) emptyState.remove();

    const avatar = comment.avatar || `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(comment.nombre)}&size=32`;
    const fecha = new Date(comment.created_at).toLocaleDateString('es-CO', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });

    const div = document.createElement('div');
    div.className = 'comment-item';
    div.innerHTML = `
        <div class="comment-header">
            <img src="${avatar}" alt="" class="comment-avatar">
            <span class="comment-author">${escapeHtml(comment.nombre)}</span>
            <span class="comment-date">${fecha}</span>
        </div>
        <p class="comment-text">${escapeHtml(comment.contenido)}</p>
    `;

    container.prepend(div);
}