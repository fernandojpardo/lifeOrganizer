<template>
    <div class="page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Budgets</h1>
                <p class="page-subtitle">Monthly spending limits</p>
            </div>
            <button class="btn btn-primary" @click="openForm()">+ New Budget</button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-rows">
            <div v-for="i in 4" :key="i" class="skeleton-row"></div>
        </div>

        <!-- Budget list -->
        <div v-else-if="budgets.length" class="budgets-list">
            <div v-for="b in budgets" :key="b.id" class="budget-card">
                <div class="budget-header">
                    <div class="budget-meta">
                        <span class="budget-icon">{{ b.category?.icon || '📂' }}</span>
                        <div>
                            <div class="budget-name">{{ b.category?.name || 'Unknown' }}</div>
                            <div class="budget-period">{{ capitalize(b.period) }}</div>
                        </div>
                    </div>
                    <div class="budget-amounts">
                        <span :class="['budget-spent', spentClass(b.percentage)]">{{ formatMoney(b.spent) }}</span>
                        <span class="budget-limit"> / {{ formatMoney(b.amount) }}</span>
                    </div>
                </div>

                <div class="progress-wrap">
                    <div class="progress-bar">
                        <div
                            class="progress-fill"
                            :class="progressClass(b.percentage)"
                            :style="{ width: Math.min(b.percentage, 100) + '%' }"
                        ></div>
                    </div>
                    <span :class="['progress-pct', progressClass(b.percentage)]">{{ b.percentage }}%</span>
                </div>

                <div class="budget-footer">
                    <span v-if="b.percentage >= 100" class="alert-chip alert-over">⚠ Over budget</span>
                    <span v-else-if="b.percentage >= 70" class="alert-chip alert-warn">⚡ Approaching limit</span>
                    <span v-else class="alert-chip alert-ok">✓ On track</span>
                    <div class="budget-actions">
                        <button class="action-btn" @click="openForm(b)">Edit</button>
                        <button class="action-btn action-btn-danger" @click="confirmDelete(b)">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div v-else class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No budgets yet</h3>
            <p>Create spending limits to stay on track with your goals.</p>
            <button class="btn btn-primary" @click="openForm()">Create Budget</button>
        </div>

        <!-- Form Modal -->
        <div v-if="showForm" class="modal-backdrop" @click.self="closeForm">
            <div class="modal-card">
                <div class="modal-header">
                    <h2>{{ editing ? 'Edit Budget' : 'New Budget' }}</h2>
                    <button class="btn-close" @click="closeForm">✕</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-group" v-if="!editing">
                        <label>Category *</label>
                        <select v-model="form.category_id" class="form-input" required>
                            <option value="">Select category</option>
                            <option v-for="c in expenseCategories" :key="c.id" :value="c.id">
                                {{ c.icon }} {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Limit Amount *</label>
                            <input v-model.number="form.amount" type="number" step="0.01" min="0.01"
                                class="form-input" placeholder="0.00" required />
                        </div>
                        <div class="form-group">
                            <label>Period</label>
                            <select v-model="form.period" class="form-input">
                                <option value="monthly">Monthly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
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
        <div v-if="deletingBudget" class="modal-backdrop" @click.self="deletingBudget = null">
            <div class="modal-card modal-sm">
                <div class="modal-header">
                    <h2>Delete Budget</h2>
                    <button class="btn-close" @click="deletingBudget = null">✕</button>
                </div>
                <div class="modal-form">
                    <p class="text-muted">Remove the budget limit for <strong>{{ deletingBudget.category?.name }}</strong>?</p>
                    <p v-if="deleteError" class="error-msg">{{ deleteError }}</p>
                    <div class="modal-actions">
                        <button class="btn btn-secondary" @click="deletingBudget = null">Cancel</button>
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

const budgets        = ref([]);
const categories     = ref([]);
const loading        = ref(true);
const showForm       = ref(false);
const editing        = ref(null);
const saving         = ref(false);
const formError      = ref('');
const deletingBudget = ref(null);
const deleteError    = ref('');

const expenseCategories = computed(() =>
    categories.value.filter(c => c.type === 'expense' || c.type === 'both')
);

const defaultForm = () => ({ category_id: '', amount: null, period: 'monthly' });
const form = ref(defaultForm());

const progressClass = (pct) => {
    if (pct >= 100) return 'danger';
    if (pct >= 70)  return 'warning';
    return 'success';
};

const spentClass = (pct) => {
    if (pct >= 100) return 'text-danger';
    if (pct >= 70)  return 'text-warning';
    return 'text-success';
};

const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
const { formatMoney } = useCurrency();

const loadBudgets = async () => {
    loading.value = true;
    try {
        const data = await api.get('/budgets');
        budgets.value = Array.isArray(data) ? data : (data.data || []);
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    const data = await api.get('/categories');
    categories.value = Array.isArray(data) ? data : (data.data || []);
};

const openForm = (b = null) => {
    editing.value = b;
    formError.value = '';
    form.value = b ? { category_id: b.category_id, amount: Number(b.amount), period: b.period } : defaultForm();
    showForm.value = true;
};

const closeForm = () => { showForm.value = false; editing.value = null; };

const handleSubmit = async () => {
    formError.value = '';
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`/budgets/${editing.value.id}`, { amount: form.value.amount });
        } else {
            await api.post('/budgets', form.value);
        }
        closeForm();
        await loadBudgets();
    } catch (e) {
        formError.value = e.message || 'An error occurred';
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (b) => { deletingBudget.value = b; deleteError.value = ''; };

const doDelete = async () => {
    saving.value = true;
    deleteError.value = '';
    try {
        await api.delete(`/budgets/${deletingBudget.value.id}`);
        deletingBudget.value = null;
        await loadBudgets();
    } catch (e) {
        deleteError.value = e.message || 'Delete failed';
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await Promise.all([loadBudgets(), loadCategories()]);
});
</script>

<style scoped>
.page { padding: 1.5rem; max-width: 800px; margin: 0 auto; }

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

.budgets-list { display: flex; flex-direction: column; gap: 1rem; }

.budget-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    box-shadow: var(--shadow-md);
}

.budget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.875rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.budget-meta { display: flex; align-items: center; gap: 0.75rem; }
.budget-icon { font-size: 1.5rem; }
.budget-name { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
.budget-period { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.125rem; }

.budget-amounts { text-align: right; }
.budget-spent { font-size: 1.25rem; font-weight: 800; }
.budget-limit { font-size: 0.875rem; color: var(--text-muted); }

.text-success  { color: #059669; }
.text-warning  { color: #d97706; }
.text-danger   { color: #dc2626; }

.progress-wrap { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.875rem; }

.progress-bar { flex: 1; height: 8px; background: var(--bg-surface); border-radius: 999px; overflow: hidden; }

.progress-fill { height: 100%; border-radius: 999px; transition: width 0.5s ease; }
.progress-fill.success { background: #10b981; }
.progress-fill.warning { background: #f59e0b; }
.progress-fill.danger  { background: #ef4444; }

.progress-pct { font-size: 0.8rem; font-weight: 700; width: 44px; text-align: right; }
.progress-pct.success { color: #059669; }
.progress-pct.warning { color: #d97706; }
.progress-pct.danger  { color: #dc2626; }

.budget-footer { display: flex; align-items: center; justify-content: space-between; }

.alert-chip {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.625rem;
    border-radius: 999px;
}

.alert-ok   { background: #ecfdf5; color: #065f46; }
.alert-warn { background: #fffbeb; color: #92400e; }
.alert-over { background: #fff5f5; color: #991b1b; }

.budget-actions { display: flex; gap: 0.5rem; }

.action-btn {
    padding: 0.35rem 0.875rem;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    color: var(--text-secondary);
    font-size: 0.78rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.15s;
}

.action-btn:hover { background: var(--bg-card-hover); }
.action-btn-danger:hover { background: #fff5f5; border-color: #fca5a5; color: #dc2626; }

/* Empty */
.empty-state { text-align: center; padding: 4rem 2rem; color: var(--text-muted); }
.empty-icon  { font-size: 2.5rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
.empty-state p { margin-bottom: 1.5rem; }

.loading-rows { display: flex; flex-direction: column; gap: 0.75rem; }
.skeleton-row { height: 120px; background: var(--bg-surface); border-radius: var(--radius-lg); animation: pulse 1.5s ease-in-out infinite; }

/* Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9,20,38,0.4);
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
    max-width: 440px;
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

.form-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; }

@media (max-width: 480px) {
    .form-row { grid-template-columns: 1fr; }
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
    min-height: 42px;
    padding: 0.55rem 1.25rem;
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

.btn-primary  { background: var(--color-secondary); color: white; }
.btn-primary:hover:not(:disabled)  { background: var(--color-secondary-dark); }
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
