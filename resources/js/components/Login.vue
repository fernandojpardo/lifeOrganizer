<template>
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo">💰</div>
            <h1>LifeOrganizer</h1>
            <p class="login-subtitle">Smart Financial Organizer</p>

            <form @submit.prevent="handleLogin" class="login-form">
                <div class="form-group">
                    <label>Email</label>
                    <input
                        v-model="email"
                        type="email"
                        required
                        class="form-input"
                        placeholder="demo@example.com"
                        :disabled="loading"
                    />
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            class="form-input"
                            placeholder="••••••••"
                            :disabled="loading"
                        />
                        <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                            {{ showPassword ? '🙈' : '👁️' }}
                        </button>
                    </div>
                </div>

                <p v-if="error" class="error-msg">{{ error }}</p>

                <button type="submit" class="btn-login" :disabled="loading">
                    <span v-if="loading" class="spinner"></span>
                    <span v-else>Sign In</span>
                </button>
            </form>

            <p class="demo-hint">Demo: demo@example.com / password</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from '../api.js';

const emit = defineEmits(['logged-in']);

const email = ref('demo@example.com');
const password = ref('password');
const error = ref('');
const loading = ref(false);
const showPassword = ref(false);

const handleLogin = async () => {
    error.value = '';
    loading.value = true;
    try {
        const data = await api.login(email.value, password.value);
        emit('logged-in', data.user);
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-base);
    padding: 1rem;
}

.login-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-xl);
    padding: 2.75rem 2.5rem;
    width: 100%;
    max-width: 420px;
    text-align: center;
    box-shadow: var(--shadow-lg);
    animation: fadeUp 0.4s ease;
}

.login-logo {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

h1 {
    font-size: 1.875rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-bottom: 0.4rem;
    color: var(--color-primary);
}

.login-subtitle {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 2.25rem;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.125rem;
    text-align: left;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.form-group label {
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.password-wrapper { position: relative; }

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(9, 20, 38, 0.1);
}

.form-input:disabled { opacity: 0.5; cursor: not-allowed; }
.form-input::placeholder { color: var(--text-muted); }

.toggle-password {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    padding: 0.25rem;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.toggle-password:hover { opacity: 1; }

.error-msg {
    background: #fff0f0;
    border: 1px solid #fecaca;
    color: var(--color-error);
    padding: 0.7rem 1rem;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    text-align: center;
}

.btn-login {
    width: 100%;
    min-height: 48px;
    padding: 0.875rem;
    background: var(--color-secondary);
    color: white;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    font-weight: 700;
    font-family: inherit;
    letter-spacing: 0.02em;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.btn-login:hover:not(:disabled) {
    background: var(--color-secondary-dark);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
    transform: translateY(-1px);
}

.btn-login:active:not(:disabled) { transform: translateY(0); }
.btn-login:disabled { opacity: 0.55; cursor: not-allowed; }

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.demo-hint {
    margin-top: 1.75rem;
    color: var(--text-muted);
    font-size: 0.78rem;
    padding: 0.6rem 1rem;
    background: var(--bg-surface);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-default);
}

@media (max-width: 480px) {
    .login-card { padding: 2rem 1.5rem; }
    h1 { font-size: 1.6rem; }
    .login-logo { font-size: 2rem; }
}
</style>
