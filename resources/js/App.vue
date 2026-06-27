<template>
    <div class="app-container">
        <!-- Login -->
        <Login v-if="!isAuthenticated" @logged-in="onLogin" />

        <template v-else>
            <!-- Header -->
            <header class="app-header">
                <div class="header-content">
                    <!-- Desktop logo -->
                    <div class="logo-section">
                        <div class="logo">💰</div>
                        <h1>LifeOrganizer</h1>
                    </div>
                    <!-- Mobile greeting -->
                    <div class="mobile-greeting">
                        <div class="avatar-icon">👤</div>
                        <div class="greeting-text">
                            <span class="greeting-hello">Hello,</span>
                            <span class="greeting-name">{{ user?.name }}</span>
                        </div>
                    </div>
                    <div class="header-right">
                        <span class="user-name">{{ user?.name }}</span>
                        <select class="currency-select" :value="selectedCode" @change="setCurrency($event.target.value)" title="Currency">
                            <option v-for="c in CURRENCIES" :key="c.code" :value="c.code">{{ c.code }} {{ c.symbol }}</option>
                        </select>
                        <button class="btn-logout" @click="handleLogout">Log out</button>
                    </div>
                </div>
            </header>

            <div class="app-layout">
                <!-- Sidebar (desktop only) -->
                <aside class="app-sidebar">
                    <nav class="nav-menu">
                        <p class="nav-label">MENU</p>
                        <button
                            v-for="tab in tabs"
                            :key="tab"
                            @click="selectTab(tab)"
                            :class="['nav-item', { active: activeTab === tab }]"
                        >
                            <span class="nav-icon">{{ getIcon(tab) }}</span>
                            <span class="nav-text">{{ tab }}</span>
                        </button>
                    </nav>
                </aside>

                <!-- Main -->
                <main class="app-main">
                    <div class="content-wrapper">
                        <Dashboard     v-if="activeTab === 'Dashboard'"     @navigate="selectTab" />
                        <Transactions  v-else-if="activeTab === 'Transactions'" />
                        <Accounts      v-else-if="activeTab === 'Accounts'" />
                        <Budgets       v-else-if="activeTab === 'Budgets'" />
                        <Debts         v-else-if="activeTab === 'Debts'" />
                        <SavingGoals   v-else-if="activeTab === 'Savings'" />
                    </div>
                </main>
            </div>

            <!-- Bottom nav (mobile only) -->
            <nav class="bottom-nav">
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    @click="selectTab(tab)"
                    :class="['bottom-nav-item', { active: activeTab === tab }]"
                >
                    <span class="bottom-nav-icon">{{ getIcon(tab) }}</span>
                    <span class="bottom-nav-label">{{ tab }}</span>
                </button>
            </nav>
        </template>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { api } from './api.js';
import { useCurrency, CURRENCIES } from './composables/useCurrency.js';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';
import Transactions from './components/Transactions.vue';
import Accounts from './components/Accounts.vue';
import Budgets from './components/Budgets.vue';
import Debts from './components/Debts.vue';
import SavingGoals from './components/SavingGoals.vue';

const { selectedCode, setCurrency } = useCurrency();

const isAuthenticated = ref(api.isAuthenticated());
const user = ref(api.getUser());
const activeTab = ref('Dashboard');
const sidebarOpen = ref(false);

const tabs = [
    'Dashboard',
    'Transactions',
    'Accounts',
    'Budgets',
    'Debts',
    'Savings',
];

const icons = {
    'Dashboard':    '📊',
    'Transactions': '↕️',
    'Accounts':     '🏦',
    'Budgets':      '📋',
    'Debts':        '💳',
    'Savings':      '🎯',
};

const getIcon = (tab) => icons[tab] || '📌';

const selectTab = (tab) => {
    activeTab.value = tab;
    sidebarOpen.value = false;
};

const onLogin = (loggedUser) => {
    user.value = loggedUser;
    isAuthenticated.value = true;
};

const handleLogout = async () => {
    await api.logout();
    isAuthenticated.value = false;
    user.value = null;
    activeTab.value = 'Dashboard';

};
</script>

<style scoped>
.app-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: var(--bg-base);
}

/* ── Header ─────────────────────────────────── */
.app-header {
    background: var(--bg-elevated);
    border-bottom: 1px solid var(--border-default);
    padding: 0.875rem 1.5rem;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow-sm);
}

.header-content {
    max-width: 1800px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.logo { font-size: 1.5rem; }

.logo-section h1 {
    color: var(--color-primary);
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    margin-left: auto;
}

.user-name {
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
}

.currency-select {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    color: var(--text-secondary);
    padding: 0.4rem 0.6rem;
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}

.currency-select:focus {
    border-color: var(--color-primary);
}

.btn-logout {
    background: transparent;
    border: 1px solid var(--border-default);
    color: var(--text-secondary);
    padding: 0.4rem 1rem;
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    letter-spacing: 0.01em;
    font-family: inherit;
}

.btn-logout:hover {
    background: #fff0f0;
    border-color: var(--color-error);
    color: var(--color-error);
}

/* ── Layout ──────────────────────────────────── */
.app-layout {
    display: flex;
    flex: 1;
    overflow: hidden;
    position: relative;
}

/* ── Sidebar ─────────────────────────────────── */
.app-sidebar {
    width: 256px;
    background: var(--bg-elevated);
    border-right: 1px solid var(--border-default);
    overflow-y: auto;
    padding: 1.25rem 0.75rem;
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.nav-label {
    color: var(--text-muted);
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin: 0 0.5rem 0.75rem;
    display: block;
}

.nav-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.875rem;
    margin-bottom: 0.25rem;
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.18s ease;
    text-align: left;
    font-family: inherit;
}

.nav-item:hover {
    background: var(--bg-surface);
    color: var(--text-primary);
    border-color: var(--border-default);
}

.nav-item.active {
    background: #f0fdf9;
    color: var(--color-secondary-dark);
    border-color: #6ee7b7;
    font-weight: 600;
}

.nav-icon {
    font-size: 1.1rem;
    width: 1.4rem;
    flex-shrink: 0;
    text-align: center;
}

.nav-text { flex: 1; }

/* ── Main ────────────────────────────────────── */
.app-main {
    flex: 1;
    overflow-y: auto;
    background: var(--bg-base);
}

.content-wrapper {
    max-width: 1800px;
    margin: 0 auto;
    padding: 2rem 1.75rem;
}

/* ── Mobile greeting ─────────────────────────── */
.mobile-greeting {
    display: none;
    align-items: center;
    gap: 0.625rem;
}

.avatar-icon {
    width: 2.25rem;
    height: 2.25rem;
    background: #f0fdf9;
    border: 1.5px solid #6ee7b7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.greeting-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.greeting-hello {
    font-size: 0.7rem;
    color: var(--text-muted);
    font-weight: 500;
}

.greeting-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* ── Bottom nav ──────────────────────────────── */
.bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: var(--bg-elevated);
    border-top: 1px solid var(--border-default);
    padding: 0.5rem 0.25rem;
    padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));
    box-shadow: 0 -4px 12px rgba(30, 41, 59, 0.06);
}

.bottom-nav-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.35rem 0.25rem;
    border-radius: var(--radius-md);
    transition: all 0.18s ease;
    font-family: inherit;
    min-width: 0;
}

.bottom-nav-icon {
    font-size: 1.25rem;
    line-height: 1;
    opacity: 0.5;
    transition: all 0.18s ease;
}

.bottom-nav-label {
    font-size: 0.55rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    transition: color 0.18s ease;
}

.bottom-nav-item.active .bottom-nav-icon { opacity: 1; }

.bottom-nav-item.active .bottom-nav-label {
    color: var(--color-secondary-dark);
}

.bottom-nav-item.active {
    background: #f0fdf9;
}

/* ── Responsive ──────────────────────────────── */
@media (max-width: 1024px) {
    .app-sidebar { width: 220px; }
    .content-wrapper { padding: 1.5rem 1.25rem; }
}

@media (max-width: 768px) {
    .logo-section { display: none; }
    .mobile-greeting { display: flex; }
    .user-name { display: none; }
    .btn-logout { font-size: 0.75rem; padding: 0.3rem 0.7rem; }

    .app-sidebar { display: none; }
    .bottom-nav { display: flex; }

    .app-main { padding-bottom: 72px; }
    .content-wrapper { padding: 1.25rem 1rem; }
}

@media (max-width: 480px) {
    .content-wrapper { padding: 1rem 0.875rem; }
    .app-header { padding: 0.75rem 1rem; }
}
</style>
