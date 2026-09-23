requireAuth();

let myArtistId = null;

async function loadSwaps() {
    const container = document.getElementById('swaps-container');
    container.innerHTML = '<p class="empty-state">Loading...</p>';

    try {
        const me = await apiGet('/me');
        myArtistId = me.artist.id;

        const data = await apiGet('/swaps');
        renderSwaps(data.swaps);
    } catch (error) {
        container.innerHTML = `<p class="empty-state">Failed to load swaps: ${error.message}</p>`;
    }
}

function renderSwaps(swaps) {
    const container = document.getElementById('swaps-container');

    if (swaps.length === 0) {
        container.innerHTML = '<p class="empty-state">You don\'t have any swaps yet. <a href="favorites.html" class="link">Go to your favorites →</a> and start a swap with someone who matched you back.</p>';
        return;
    }

    container.innerHTML = swaps.map(swapCard).join('');
}

function statusBadge(status) {
    const map = {
        pending: '<span class="badge badge-gray">Pending</span>',
        confirmed: '<span class="badge badge-lima">✓ Confirmed</span>',
        rejected: '<span class="badge badge-gray">Rejected</span>',
        cancelled: '<span class="badge badge-gray">Cancelled</span>',
    };
    return map[status] || status;
}

function swapCard(swap) {
    const otherArtist = swap.artist_a_id === myArtistId ? swap.artist_b : swap.artist_a;
    const myConfirmed = swap.artist_a_id === myArtistId ? swap.confirmed_by_a : swap.confirmed_by_b;
    const otherName = otherArtist?.user?.name || 'Unknown artist';

    const dates = swap.start_date && swap.end_date
        ? `${swap.start_date} → ${swap.end_date}`
        : 'No overlapping dates found';

    let actions = '';

    if (swap.status === 'pending') {
        actions = `
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                ${!myConfirmed ? `<button class="btn btn-primary" onclick="confirmSwap(${swap.id})">Confirm Dates</button>` : ''}
                <button class="btn btn-danger" onclick="rejectSwap(${swap.id})">Reject</button>
            </div>
        `;
    } else if (swap.status === 'confirmed') {
        actions = `
            <div style="margin-top: 1rem;">
                <button class="btn btn-danger" onclick="rejectSwap(${swap.id})">Cancel Swap</button>
            </div>
        `;
    }

    return `
        <div class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <strong>${otherName}</strong>
                ${statusBadge(swap.status)}
            </div>
            <p style="color: var(--gray-700); margin: 0;">📅 ${dates}</p>
            ${swap.status === 'pending' ? `<p style="color: var(--gray-400); font-size: 0.85rem; margin-top: 0.25rem;">${myConfirmed ? 'You confirmed. Waiting for the other artist.' : 'Waiting for your confirmation.'}</p>` : ''}
            ${actions}
        </div>
    `;
}

async function confirmSwap(swapId) {
    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    try {
        await apiPut(`/swaps/${swapId}`);
        loadSwaps();
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    }
}

async function rejectSwap(swapId) {
    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    try {
        await apiDelete(`/swaps/${swapId}/reject`);
        loadSwaps();
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    }
}

loadSwaps();