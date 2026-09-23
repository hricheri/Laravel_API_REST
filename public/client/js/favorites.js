requireAuth();

async function loadFavorites() {
    const container = document.getElementById('favorites-container');
    container.innerHTML = '<p class="empty-state">Loading...</p>';

    try {
        const data = await apiGet('/favorites');
        renderFavorites(data.favorites);
    } catch (error) {
        container.innerHTML = `<p class="empty-state">Failed to load favorites: ${error.message}</p>`;
    }
}

function renderFavorites(favorites) {
    const container = document.getElementById('favorites-container');

    if (favorites.length === 0) {
        container.innerHTML = '<p class="empty-state">You haven\'t liked anyone yet. <a href="explore.html" class="link">Explore artists →</a></p>';
        return;
    }

    container.innerHTML = `<div class="card-grid">${favorites.map(favoriteCard).join('')}</div>`;
}

function favoriteCard(favorite) {
    const artist = favorite.artist;
    const photo = artist.profile_photo
        ? `http://127.0.0.1:8000/storage/${artist.profile_photo}`
        : null;

    return `
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    ${photo ? `<img src="${photo}" class="profile-photo" style="width: 48px; height: 48px;">` : ''}
                    <div>
                        <strong>${artist.user?.name || 'Unknown'}</strong>
                        <p style="margin: 0; color: var(--gray-500); font-size: 0.85rem;">${artist.city || 'No city'}</p>
                    </div>
                </div>
                <span class="badge ${favorite.is_match ? 'badge-lavender' : 'badge-gray'}">
                    ${favorite.is_match ? '🎨 Match!' : '💖 Liked'}
                </span>
            </div>
            ${favorite.is_match
                ? `<button class="btn btn-secondary btn-block" onclick="startSwap(${artist.id})">Start a Swap</button>`
                : `<p style="color: var(--gray-400); font-size: 0.85rem;">Waiting for them to like you back.</p>`
            }
        </div>
    `;
}

async function startSwap(artistId) {
    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    try {
        await apiPost('/swaps', { artist_id: artistId });
        window.location.href = 'swaps.html';
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    }
}

loadFavorites();