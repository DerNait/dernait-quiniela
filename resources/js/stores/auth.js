import { defineStore } from 'pinia';
import api from '../lib/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        ready: false, // becomes true once we've tried to restore the session
    }),
    getters: {
        isAuthenticated: (state) => !!state.user,
        isAdmin: (state) => !!state.user?.is_admin,
    },
    actions: {
        setToken(token) {
            this.token = token;
            if (token) {
                localStorage.setItem('token', token);
            } else {
                localStorage.removeItem('token');
            }
        },

        async register(payload) {
            const { data } = await api.post('/register', payload);
            this.setToken(data.token);
            this.user = data.user;
        },

        async login(payload) {
            const { data } = await api.post('/login', payload);
            this.setToken(data.token);
            this.user = data.user;
        },

        async logout() {
            try {
                await api.post('/logout');
            } catch (e) {
                // ignore network errors on logout
            }
            this.setToken(null);
            this.user = null;
        },

        // Restore the user from a stored token on first load.
        async restore() {
            if (this.token) {
                try {
                    const { data } = await api.get('/me');
                    this.user = data.user;
                } catch (e) {
                    this.setToken(null);
                    this.user = null;
                }
            }
            this.ready = true;
        },
    },
});
