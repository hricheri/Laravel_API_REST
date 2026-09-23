requireAuth();

let currentFilter = 'all';

async function loadExplore() {
    const container = document.getElementById('artists-container');
    container.innerHTML = '<p class="empty-state">Loading...</p>';

    let endpoint = '/artists';
    if (currentFilter === 'city') {
        const city = document.getElementById('city-input').value;
        endpoint = `/artists?filter=city&city=${encodeURIComponent(city)}`;
    } else if (currentFilter !== 'all') {
        endpoint = `/artists?filter=${currentFilter}`;
    }

    try {
        const data = await apiGet(endpoint);
        renderArtists(data.artists);
    } catch (error) {
        container.innerHTML = `<p class="empty-state">Failed to load artists: ${error.message}</p>`;
    }
}

function renderArtists(artists) {
    const container = document.getElementById('artists-container');

    if (artists.length === 0) {
        container.innerHTML = '<p class="empty-state">No artists found.</p>';
        return;
    }

    container.innerHTML = `<div class="card-grid">${artists.map(artistCard).join('')}</div>`;
}

function artistCard(artist) {
    const photo = artist.profile_photo
        ? `http://127.0.0.1:8000/storage/${artist.profile_photo}`
        : null;

    return `
        <div class="card">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                ${photo ? `<img src="${photo}" class="profile-photo" style="width: 48px; height: 48px;">` : ''}
                <div>
                    <strong>${artist.user?.name || 'Unknown'}</strong>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.85rem;">${artist.city || 'No city'}</p>
                </div>
            </div>
            <p style="color: var(--gray-700); font-size: 0.9rem;">${artist.bio || 'No bio yet.'}</p>
            <button class="btn btn-primary btn-block" onclick="likeArtist(${artist.id})">💖 Like</button>
        </div>
    `;
}

async function likeArtist(artistId) {
    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    try {
        await apiPost('/likes', { liked_artist_id: artistId });
        loadExplore();
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    }
}

function setFilter(filter) {
    currentFilter = filter;

    document.querySelectorAll('.filter-btn').forEach((btn) => btn.classList.remove('active-filter'));
    document.getElementById(`filter-${filter}`).classList.add('active-filter');

    document.getElementById('city-filter-box').style.display = filter === 'city' ? 'flex' : 'none';

    if (filter !== 'city') {
        loadExplore();
    }
}

loadExplore();