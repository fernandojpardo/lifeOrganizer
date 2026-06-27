<template>
    <div class="page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Accounts</h1>
                <p class="page-subtitle">Manage your cash, bank accounts and wallets</p>
            </div>
            <button class="btn btn-primary" @click="openForm()">+ New Account</button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-grid">
            <div v-for="i in 3" :key="i" class="skeleton-card"></div>
        </div>

        <!-- Accounts grid -->
        <div v-else-if="accounts.length" class="accounts-grid">
            <div
                v-for="acc in accounts"
                :key="acc.id"
                class="account-card"
                :style="{ '--acc-color': acc.color || '#1e293b' }"
            >
                <div class="account-header">
                    <div class="account-icon">{{ acc.icon || '🏦' }}</div>
                    <div class="account-meta">
                        <div class="account-name">{{ acc.name }}</div>
                        <div class="account-type-badge">{{ typeLabel(acc.type) }}</div>
                    </div>
                    <span v-if="acc.is_default" class="default-badge">Default</span>
                </div>
                <div class="account-balance">
                    <span class="balance-currency">{{ acc.currency }}</span>
                    <span class="balance-amount" :class="{ negative: Number(acc.balance) < 0 }">
                        {{ formatMoney(acc.balance) }}
                    </span>
                </div>
                <div class="account-actions">
                    <button class="action-btn" @click="openForm(acc)">Edit</button>
                    <button class="action-btn action-btn-danger" @click="confirmDelete(acc)">Delete</button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="empty-state">
            <div class="empty-icon">🏦</div>
            <h3>No accounts yet</h3>
            <p>Add your first account to start tracking your finances.</p>
            <button class="btn btn-primary" @click="openForm()">Add Account</button>
        </div>

        <!-- Net worth footer -->
        <div v-if="accounts.length" class="net-worth-bar">
            <span>Total net worth</span>
            <strong>{{ totalCurrency }} {{ formatMoney(netWorth) }}</strong>
        </div>

        <!-- Form Modal -->
        <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
            <div class="modal-card">
                <div class="modal-header">
                    <h2>{{ editing ? 'Edit Account' : 'New Account' }}</h2>
                    <button class="btn-close" @click="closeForm">✕</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2">
                            <label>Name *</label>
                            <input v-model="form.name" type="text" class="form-input" placeholder="e.g. Main Bank" required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Type *</label>
                            <select v-model="form.type" class="form-input" required>
                                <option v-for="t in accountTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Currency</label>
                            <input v-model="form.currency" type="text" maxlength="3" class="form-input" placeholder="USD" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Balance</label>
                            <input v-model.number="form.balance" type="number" step="0.01" class="form-input" placeholder="0.00" />
                        </div>
                        <div class="form-group">
                            <label>Icon (emoji)</label>
                            <input v-model="form.icon" type="text" class="form-input" placeholder="🏦" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Color</label>
                            <input v-model="form.color" type="color" class="form-input color-input" />
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <input v-model="form.notes" type="text" class="form-input" placeholder="Optional" />
                        </div>
                    </div>
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="form.is_default" />
                        <span>Set as default account</span>
                    </label>
                    <p v-if="formError" class="error-msg">{{ formError }}</p>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="closeForm">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>{{ editing ? 'Save' : 'Create' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete confirm -->
        <div v-if="deletingAccount" class="modal-backdrop" @click.self="deletingAccount = null">
            <div class="modal-card modal-sm">
                <div class="modal-header">
                    <h2>Delete Account</h2>
                    <button class="btn-close" @click="deletingAccount = null">✕</button>
                </div>
                <div class="modal-form">
                    <p class="text-muted">Are you sure you want to delete <strong>{{ deletingAccount.name }}</strong>? This cannot be undone.</p>
                    <p v-if="deleteError" class="error-msg">{{ deleteError }}</p>
                    <div class="modal-actions">
                        <button class="btn btn-secondary" @click="deletingAccount = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="saving" @click="doDelete">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../api.js';
import { useCurrency } from '../composables/useCurrency.js';

const accounts       = ref([]);
const loading        = ref(true);
const showForm       = ref(false);
const editing        = ref(null);
const saving         = ref(false);
const formError      = ref('');
const deletingAccount = ref(null);
const deleteError    = ref('');

const accountTypes = [
    { value: 'cash',           label: '💵 Cash' },
    { value: 'bank',           label: '🏦 Bank' },
    { value: 'credit_card',    label: '💳 Credit Card' },
    { value: 'debit_card',     label: '💳 Debit Card' },
    { value: 'digital_wallet', label: '📱 Digital Wallet' },
    { value: 'other',          label: '❓ Other' },
];

const typeLabel = (type) => accountTypes.find(t => t.value === type)?.label || type;

const defaultForm = () => ({
    name:       '',
    type:       'bank',
    currency:   'USD',
    balance:    0,
    color:      '#1e293b',
    icon:       '',
    is_default: false,
    notes:      '',
});

const form = ref(defaultForm());

const netWorth     = computed(() => accounts.value.reduce((sum, a) => sum + Number(a.balance), 0));
const totalCurrency = computed(() => accounts.value[0]?.currency || 'USD');

const { formatMoney } = useCurrency();

const loadAccounts = async () => {
    loading.value = true;
    try {
        const data = await api.get('/accounts');
        accounts.value = Array.isArray(data) ? data : (data.data || []);
    } finally {
        loading.value = false;
    }
};

const openForm = (acc = null) => {
    editing.value = acc;
    formError.value = '';
    form.value = acc ? { ...acc, balance: Number(acc.balance) } : defaultForm();
    showForm.value = true;
};

const closeForm = () => { showForm.value = false; editing.value = null; };

const handleSubmit = async () => {
    formError.value = '';
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`/accounts/${editing.value.id}`, form.value);
        } else {
            await api.post('/accounts', form.value);
        }
        closeForm();
        await loadAccounts();
    } catch (e) {
        formError.value = e.message || 'An error occurred';
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (acc) => {
    deletingAccount.value = acc;
    deleteError.value = '';
};

const doDelete = async () => {
    saving.value = true;
    deleteError.value = '';
    try {
        const res = await api.delete(`/accounts/${deletingAccount.value.id}`);
        if (res && res.message) { deleteError.value = res.message; return; }
        deletingAccount.value = null;
        await loadAccounts();
    } catch (e) {
        deleteError.value = e.message || 'Cannot delete account';
    } finally {
        saving.value = false;
    }
};

onMounted(loadAccounts);
</script>

<style scoped>
.page { padding: 1.5rem; max-width: 1100px; margin: 0 auto; }

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.75rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.page-title { font-size: 1.5rem; font-weight: 800; color: var(--color-primary); margin: 0; }
.page-subtitle { font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0; }

.accounts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.account-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    box-shadow: var(--shadow-md);
    transition: transform 0.15s, box-shadow 0.15s;
    border-top: 3px solid var(--acc-color);
}

.account-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

.account-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }

.account-icon { font-size: 1.75rem; }

.account-meta { flex: 1; min-width: 0; }
.account-name { font-size: 1rem; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.account-type-badge { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.125rem; }

.default-badge {
    font-size: 0.65rem;
    font-weight: 700;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #6ee7b7;
    border-radius: 999px;
    padding: 0.2rem 0.5rem;
    white-space: nowrap;
}

.account-balance {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
    margin-bottom: 1.125rem;
}

.balance-currency { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); }
.balance-amount { font-size: 1.75rem; font-weight: 800; color: var(--color-primary); letter-spacing: -0.02em; }
.balance-amount.negative { color: #dc2626; }

.account-actions { display: flex; gap: 0.5rem; }

.action-btn {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.15s;
}

.action-btn:hover { background: var(--bg-card-hover); }
.action-btn-danger:hover { background: #fff5f5; border-color: #fca5a5; color: #dc2626; }

.net-worth-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
    padding: 1rem 1.25rem;
    background: var(--color-primary);
    color: white;
    border-radius: var(--radius-lg);
    font-size: 0.9rem;
}

.net-worth-bar strong { font-size: 1.25rem; font-weight: 800; }

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
.empty-state p { margin-bottom: 1.5rem; }

.loading-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.skeleton-card { height: 180px; background: var(--bg-surface); border-radius: var(--radius-lg); animation: pulse 1.5s ease-in-out infinite; }

/* Modal shared */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9, 20, 38, 0.4);
    backdrop-filter: blur(4px);
    z-index: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 520px;
    box-shadow: var(--shadow-lg);
    animation: fadeUp 0.2s ease;
}

.modal-sm { max-width: 380px; }

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-default);
}

.modal-header h2 { font-size: 1.125rem; font-weight: 700; color: var(--color-primary); }

.btn-close {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    color: var(--text-muted);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-md);
    transition: all 0.15s;
    font-family: inherit;
}
.btn-close:hover { background: var(--bg-surface); }

.modal-form {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

@media (max-width: 480px) {
    .form-row { grid-template-columns: 1fr; }
    .modal-card { border-radius: var(--radius-lg); max-width: 100%; }
    .modal-form { padding: 1.25rem; }
}

.form-group { display: flex; flex-direction: column; gap: 0.35rem; }

.form-group label { font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); }

.form-input {
    padding: 0.65rem 0.875rem;
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 0.9rem;
    font-family: inherit;
    transition: border-color 0.2s;
}

.form-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(9,20,38,0.08); }

.color-input { padding: 0.25rem; height: 44px; cursor: pointer; }

.checkbox-label { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-secondary); cursor: pointer; }

.error-msg {
    background: #fff5f5;
    border: 1px solid #fca5a5;
    color: #dc2626;
    padding: 0.65rem 1rem;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
}

.text-muted { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; }

.modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 0.5rem; border-top: 1px solid var(--border-default); }

.btn {
    min-height: 44px;
    padding: 0.6rem 1.5rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary { background: var(--color-secondary); color: white; }
.btn-primary:hover:not(:disabled) { background: var(--color-secondary-dark); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary { background: var(--bg-surface); color: var(--text-secondary); border: 1px solid var(--border-default); }
.btn-secondary:hover { background: var(--bg-card-hover); }

.btn-danger { background: #dc2626; color: white; }
.btn-danger:hover:not(:disabled) { background: #b91c1c; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

.spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
