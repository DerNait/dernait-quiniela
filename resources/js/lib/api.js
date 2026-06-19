import axios from 'axios';

// Single axios instance for the whole app. The bearer token (if any) is read
// from localStorage on each request so it survives reloads.
const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
