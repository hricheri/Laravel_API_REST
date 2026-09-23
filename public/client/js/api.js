const API_BASE_URL = 'http://127.0.0.1:8000/api';

function getToken() {
    return localStorage.getItem('auth_token');
}

function setToken(token) {
    localStorage.setItem('auth_token', token);
}

function clearToken() {
    localStorage.removeItem('auth_token');
}

function isLoggedIn() {
    return !!getToken();
}

function requireAuth() {
    if (!isLoggedIn()) {
        window.location.href = 'login.html';
    }
}

async function apiRequest(endpoint, options = {}) {
    const token = getToken();

    const headers = {
        Accept: 'application/json',
        ...(options.headers || {}),
    };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const isFormData = options.body instanceof FormData;

    if (!isFormData && options.body) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...options,
        headers,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'Something went wrong.');
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

function apiGet(endpoint) {
    return apiRequest(endpoint, { method: 'GET' });
}

function apiPost(endpoint, body) {
    return apiRequest(endpoint, {
        method: 'POST',
        body: body instanceof FormData ? body : JSON.stringify(body),
    });
}

function apiPut(endpoint, body) {
    return apiRequest(endpoint, {
        method: 'PUT',
        body: body instanceof FormData ? body : JSON.stringify(body),
    });
}

function apiDelete(endpoint, body) {
    return apiRequest(endpoint, {
        method: 'DELETE',
        body: body ? JSON.stringify(body) : undefined,
    });
}