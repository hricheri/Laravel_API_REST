async function handleLogin(event) {
    event.preventDefault();

    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';

    try {
        const data = await apiPost('/login', { email, password });
        setToken(data.token);
        window.location.href = 'profile.html';
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Log In';
    }
}

async function handleRegister(event) {
    event.preventDefault();

    const errorBox = document.getElementById('error-box');
    errorBox.style.display = 'none';

    const formData = new FormData();
    formData.append('name', document.getElementById('name').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('password', document.getElementById('password').value);
    formData.append('password_confirmation', document.getElementById('password_confirmation').value);

    const bio = document.getElementById('bio').value;
    if (bio) formData.append('bio', bio);

    const city = document.getElementById('city').value;
    if (city) formData.append('city', city);

    const photoInput = document.getElementById('profile_photo');
    if (photoInput.files[0]) {
        formData.append('profile_photo', photoInput.files[0]);
    }

    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating account...';

    try {
        const data = await apiPost('/register', formData);
        setToken(data.token);
        window.location.href = 'profile.html';
    } catch (error) {
        const messages = error.data?.errors
            ? Object.values(error.data.errors).flat().join(' ')
            : error.message;
        errorBox.textContent = messages;
        errorBox.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
    }
}

async function handleLogout() {
    try {
        await apiPost('/logout');
    } catch (error) {
        // Even if the request fails, we still clear the local token.
    }
    clearToken();
    window.location.href = 'login.html';
}