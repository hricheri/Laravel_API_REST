requireAuth();

let currentUser = null;
let currentArtist = null;

async function loadProfile() {
    try {
        const data = await apiGet('/me');
        currentUser = data.user;
        currentArtist = data.artist;

        renderProfile();
        fillForm();
    } catch (error) {
        document.getElementById('profile-view').innerHTML = `<p>Failed to load profile.</p>`;
    }
}

function renderProfile() {
    const view = document.getElementById('profile-view');

    const photo = currentArtist.profile_photo
        ? `http://127.0.0.1:8000/storage/${currentArtist.profile_photo}`
        : null;

    view.innerHTML = `
        ${photo ? `<img src="${photo}" class="profile-photo" style="margin-bottom: 1rem;">` : ''}
        <h2 style="margin: 0 0 0.25rem;">${currentUser.name}</h2>
        <p style="color: var(--gray-500); margin: 0 0 0.5rem;">${currentArtist.city || 'No city set'}</p>
        <span class="badge ${currentArtist.is_verified ? 'badge-lima' : 'badge-gray'}">
            ${currentArtist.is_verified ? '✓ Verified' : 'Not verified'}
        </span>
        <p style="margin-top: 1rem; color: var(--gray-700);">${currentArtist.bio || 'No bio yet.'}</p>
    `;
}

function fillForm() {
    document.getElementById('name').value = currentUser.name || '';
    document.getElementById('city').value = currentArtist.city || '';
    document.getElementById('bio').value = currentArtist.bio || '';
}

async function handleUpdateProfile(event) {
    event.preventDefault();

    const statusBox = document.getElementById('status-box');
    const errorBox = document.getElementById('error-box');
    statusBox.style.display = 'none';
    errorBox.style.display = 'none';

    const formData = new FormData();
    formData.append('name', document.getElementById('name').value);
    formData.append('city', document.getElementById('city').value);
    formData.append('bio', document.getElementById('bio').value);

    const photoInput = document.getElementById('profile_photo');
    if (photoInput.files[0]) {
        formData.append('profile_photo', photoInput.files[0]);
    }

    // Laravel doesn't parse multipart PUT bodies natively via fetch, so we
    // send it as POST with a method override field.
    formData.append('_method', 'PUT');

    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';

    try {
        const data = await apiPost('/me', formData);
        currentUser = data.user;
        currentArtist = data.artist;
        renderProfile();

        statusBox.textContent = 'Profile updated successfully!';
        statusBox.style.display = 'block';
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Changes';
    }
}

loadProfile();