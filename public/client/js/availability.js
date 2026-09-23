requireAuth();

let myArtistId = null;
let markedDates = new Set();
let selectedDates = new Set();
let currentMonthOffset = 0;
let referenceMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);

async function loadAvailability() {
    const container = document.getElementById('calendar-container');
    container.innerHTML = '<p class="empty-state">Loading...</p>';

    try {
        const me = await apiGet('/me');
        myArtistId = me.artist.id;

        const data = await apiGet(`/artists/${myArtistId}/availabilities`);
        markedDates = new Set(data.availabilities.map((a) => a.date));
        selectedDates.clear();

        renderCalendar();
        renderActions();
    } catch (error) {
        container.innerHTML = `<p class="empty-state">Failed to load availability: ${error.message}</p>`;
    }
}

function getVisibleMonthDate() {
    return new Date(referenceMonth.getFullYear(), referenceMonth.getMonth() + currentMonthOffset, 1);
}

function renderCalendar() {
    const container = document.getElementById('calendar-container');

    const monthDate = getVisibleMonthDate();
    const year = monthDate.getFullYear();
    const month = monthDate.getMonth();

    const monthName = monthDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    let startWeekday = firstDay.getDay();
    startWeekday = startWeekday === 0 ? 6 : startWeekday - 1;

    let html = `
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <button class="btn btn-ghost" onclick="changeMonth(-1)">←</button>
            <strong>${monthName}</strong>
            <button class="btn btn-ghost" onclick="changeMonth(1)">→</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.35rem; margin-bottom: 0.5rem;">
            ${['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'].map((d) => `<div style="text-align: center; font-size: 0.75rem; font-weight: 700; color: var(--gray-400);">${d}</div>`).join('')}
        </div>
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.35rem;">
    `;

    for (let i = 0; i < startWeekday; i++) {
        html += '<div></div>';
    }

    for (let day = 1; day <= lastDay.getDate(); day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isMarked = markedDates.has(dateStr);
        const isSelected = selectedDates.has(dateStr);

        let background = 'var(--gray-100)';
        let color = 'var(--gray-500)';
        let border = 'none';

        if (isMarked) {
            background = 'var(--lima-200)';
            color = 'var(--gray-900)';
        }

        if (isSelected) {
            border = '2px solid var(--lavender-500)';
        }

        html += `
            <div onclick="toggleSelect('${dateStr}')" style="
                aspect-ratio: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.6rem;
                font-size: 0.85rem;
                font-weight: 700;
                cursor: pointer;
                box-sizing: border-box;
                background: ${background};
                color: ${color};
                border: ${border};
            ">${day}</div>
        `;
    }

    html += '</div>';
    container.innerHTML = html;
}

function renderActions() {
    const box = document.getElementById('actions-container');

    if (selectedDates.size === 0) {
        box.innerHTML = '<p style="color: var(--gray-400); font-size: 0.85rem; margin: 0;">Tap days to select them, then mark or remove them below.</p>';
        return;
    }

    box.innerHTML = `
        <p style="font-weight: 700; margin: 0 0 0.75rem;">${selectedDates.size} day${selectedDates.size > 1 ? 's' : ''} selected</p>
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-primary" onclick="markSelected()">Mark as available</button>
            <button class="btn btn-danger" onclick="removeSelected()">Remove availability</button>
            <button class="btn btn-ghost" onclick="clearSelection()">Clear</button>
        </div>
    `;
}

function toggleSelect(dateStr) {
    if (selectedDates.has(dateStr)) {
        selectedDates.delete(dateStr);
    } else {
        selectedDates.add(dateStr);
    }
    renderCalendar();
    renderActions();
}

function selectRange() {
    const fromInput = document.getElementById('range-from');
    const toInput = document.getElementById('range-to');

    if (!fromInput.value || !toInput.value) return;

    const from = new Date(fromInput.value + 'T00:00:00');
    const to = new Date(toInput.value + 'T00:00:00');

    if (from > to) return;

    const cursor = new Date(from);
    while (cursor <= to) {
        const dateStr = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}-${String(cursor.getDate()).padStart(2, '0')}`;
        selectedDates.add(dateStr);
        cursor.setDate(cursor.getDate() + 1);
    }

    // Jump the visible calendar to the month of the range's start date.
    referenceMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
    currentMonthOffset = (from.getFullYear() - referenceMonth.getFullYear()) * 12 + (from.getMonth() - referenceMonth.getMonth());

    renderCalendar();
    renderActions();
}

function onRangeInputChange() {
    selectRange();
}

function clearSelection() {
    selectedDates.clear();
    renderCalendar();
    renderActions();
}

async function markSelected() {
    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    try {
        await apiPost(`/artists/${myArtistId}/availabilities`, { dates: Array.from(selectedDates) });
        selectedDates.forEach((d) => markedDates.add(d));
        clearSelection();
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    }
}

async function removeSelected() {
    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    try {
        await apiDelete(`/artists/${myArtistId}/availabilities`, { dates: Array.from(selectedDates) });
        selectedDates.forEach((d) => markedDates.delete(d));
        clearSelection();
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    }
}

function changeMonth(delta) {
    currentMonthOffset += delta;
    renderCalendar();
    renderActions();
}

loadAvailability();