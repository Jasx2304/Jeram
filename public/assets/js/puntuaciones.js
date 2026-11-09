// =========================================================
// puntuaciones.js: ranking de puntuaciones en la página de juego.
// - initLeaderboard() pinta el top y lo refresca cada 30 segundos
// - submitScore() la llama cada juego al terminar una partida
//   (ver submitScore en los juegos) para guardar el puntaje vía API.
// =========================================================
let leaderboardInterval = null;

async function initLeaderboard(gameSlug) {
    await loadLeaderboard(gameSlug);
    leaderboardInterval = setInterval(() => loadLeaderboard(gameSlug), 30000);
}

async function loadLeaderboard(gameSlug) {
    const container = document.getElementById('leaderboardContent');
    if (!container) return;

    try {
        container.innerHTML = '<p class="loading-text">Cargando ranking...</p>';
        const data = await API.get('/api/score/leaderboard', { game_slug: gameSlug, limit: 20 });

        if (!data.scores || data.scores.length === 0) {
            container.innerHTML = '<p class="empty-state">No hay puntuaciones todavía. ¡Sé el primero!</p>';
            return;
        }

        let html = `<div class="leaderboard-header">
            <span class="lb-total">${data.total_players} jugadores</span>
        </div>
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Jugador</th>
                    <th>Puntos</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>`;

        data.scores.forEach((score, i) => {
            const avatar = score.avatar || `https://api.dicebear.com/7.x/initials/svg?seed=${encodeURIComponent(score.nombre)}&size=24`;
            const isCurrent = data.user_rank && (i + 1) === data.user_rank;
            const cls = isCurrent ? 'class="current-user"' : '';
            const date = new Date(score.played_at).toLocaleDateString('es-CO', { day: '2-digit', month: '2-digit' });

            // Medallas para el top 3
            let rankBadge = score.rank;
            if (score.rank === 1) rankBadge = '🥇';
            else if (score.rank === 2) rankBadge = '🥈';
            else if (score.rank === 3) rankBadge = '🥉';

            html += `<tr ${cls}>
                <td class="rank">${rankBadge}</td>
                <td class="player">
                    <img src="${avatar}" alt="" class="mini-avatar" loading="lazy">
                    <span>${escapeHtml(score.nombre)}</span>
                </td>
                <td class="score">${formatScore(score.score)}</td>
                <td class="date">${date}</td>
            </tr>`;
        });

        html += '</tbody></table>';

        if (data.user_rank && data.user_rank > 20) {
            html += `<p class="user-rank-note">Tu posición: #${data.user_rank}</p>`;
        }

        container.innerHTML = html;
    } catch (err) {
        console.error('Error al cargar el ranking:', err);
        container.innerHTML = '<p class="error-text">Error al cargar ranking</p>';
    }
}

function formatScore(score) {
    return score.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}