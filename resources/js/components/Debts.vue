<template>
    <div>
        <div class="page-header">
            <h1>💳 Debts</h1>
            <p>Track and pay off your debts intelligently</p>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <h3>Add New Debt</h3>
            <form @submit.prevent="addDebt" class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input v-model="newDebt.name" type="text" required class="form-input"
                        placeholder="e.g. Credit card" />
                </div>
                <div class="form-group">
                    <label>Total Amount</label>
                    <input v-model.number="newDebt.total_amount" type="number" step="0.01" required
                        class="form-input" placeholder="0.00" />
                </div>
                <div class="form-group">
                    <label>Remaining Amount</label>
                    <input v-model.number="newDebt.remaining_amount" type="number" step="0.01" required
                        class="form-input" placeholder="0.00" />
                </div>
                <div class="form-group">
                    <label>Monthly %</label>
                    <input v-model.number="newDebt.monthly_percentage" type="number" step="0.01" min="0" max="100"
                        class="form-input" placeholder="10" />
                </div>
                <div class="form-group">
                    <label>Creditor</label>
                    <input v-model="newDebt.creditor" type="text" class="form-input"
                        placeholder="Bank, company, etc." />
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input v-model="newDebt.due_date" type="date" class="form-input" />
                </div>
                <button type="submit" class="btn btn-primary">Add Debt</button>
            </form>
        </div>

        <!-- Pay Debt Modal -->
        <div v-if="payingDebt" class="modal-backdrop" @click.self="payingDebt = null">
            <div class="modal-card">
                <div class="modal-header">
                    <h2>💳 Pay Debt</h2>
                    <button class="btn-close" @click="payingDebt = null">✕</button>
                </div>
                <form @submit.prevent="submitPayment" class="modal-form">
                    <div class="pay-debt-info">
                        <div class="pay-debt-name">{{ payingDebt.name }}</div>
                        <div class="pay-debt-remaining">Remaining: <strong>{{ formatMoney(payingDebt.remaining_amount) }}</strong></div>
                    </div>

                    <div class="form-group">
                        <label>Payment Amount *</label>
                        <input v-model.number="payForm.amount" type="number" step="0.01" min="0.01"
                            :max="payingDebt.remaining_amount"
                            class="form-input" required />
                        <span class="field-hint">Weekly suggestion: {{ formatMoney(weeklyPayment(payingDebt)) }}</span>
                    </div>

                    <div class="form-group">
                        <label>Account *</label>
                        <select v-model="payForm.account_id" class="form-input" required>
                            <option value="">Select account</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">
                                {{ a.icon || '🏦' }} {{ a.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date *</label>
                        <input v-model="payForm.date" type="date" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <input v-model="payForm.notes" type="text" class="form-input"
                            placeholder="Optional note" />
                    </div>

                    <p v-if="payError" class="error-msg">{{ payError }}</p>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="payingDebt = null">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="paying">
                            <span v-if="paying" class="spinner"></span>
                            <span v-else>Confirm Payment</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="debts.length === 0" class="data-card">
            <p class="empty-state">Congratulations! 🎉 You have no debts recorded</p>
        </div>

        <div v-else class="debts-grid">
            <div v-for="debt in debts" :key="debt.id" class="debt-card">
                <div class="debt-header">
                    <h4>{{ debt.name }}</h4>
                    <button @click="deleteDebt(debt.id)" class="btn-action delete">Delete</button>
                </div>

                <p class="debt-creditor">{{ debt.creditor || 'No creditor specified' }}</p>

                <div class="progress-section">
                    <div class="progress-info">
                        <span>Payment Progress</span>
                        <span class="progress-text">{{ formatMoney(debt.total_amount - debt.remaining_amount) }} / {{ formatMoney(debt.total_amount) }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: getProgress(debt) + '%' }"></div>
                    </div>
                    <p class="progress-percent">{{ getProgress(debt).toFixed(0) }}% paid</p>
                </div>

                <!-- Weekly payment highlight -->
                <div class="weekly-payment">
                    <div class="weekly-amount">{{ formatMoney(weeklyPayment(debt)) }}<span class="weekly-label">/week</span></div>
                    <div class="weekly-meta">{{ weeksLeft(debt) }} weeks left{{ debt.due_date ? ' · due ' + formatDate(debt.due_date) : '' }}</div>
                </div>

                <div class="debt-stats">
                    <div class="stat">
                        <p class="stat-label">Remaining</p>
                        <p class="stat-value red">{{ formatMoney(debt.remaining_amount) }}</p>
                    </div>
                    <div class="stat">
                        <p class="stat-label">Paid</p>
                        <p class="stat-value green">{{ formatMoney(debt.total_amount - debt.remaining_amount) }}</p>
                    </div>
                    <div class="stat">
                        <p class="stat-label">Total</p>
                        <p class="stat-value">{{ formatMoney(debt.total_amount) }}</p>
                    </div>
                    <div class="stat">
                        <p class="stat-label">Monthly %</p>
                        <p class="stat-value">{{ debt.monthly_percentage }}%</p>
                    </div>
                </div>

                <div class="debt-actions-row">
                    <div class="debt-input">
                        <input type="number" v-model.number="debt.remaining_amount" step="0.01"
                            class="form-input" placeholder="Update remaining" />
                        <button @click="updateDebt(debt)" class="btn btn-sm">Save</button>
                    </div>
                    <button @click="openPayment(debt)" class="btn btn-pay">💳 Pay</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api.js';
import { useCurrency } from '../composables/useCurrency.js';

const debts      = ref([]);
const accounts   = ref([]);
const categories = ref([]);
const payingDebt = ref(null);
const paying     = ref(false);
const payError   = ref('');
const payForm    = ref({ amount: null, account_id: '', date: '', notes: '' });

const newDebt = ref({
    name: '',
    total_amount: null,
    remaining_amount: null,
    monthly_percentage: 10,
    creditor: '',
    due_date: '',
    description: ''
});

const { formatMoney } = useCurrency();

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const weeksLeft = (debt) => {
    if (!debt.due_date) return 4;
    const days = Math.ceil((new Date(debt.due_date) - new Date()) / (1000 * 60 * 60 * 24));
    return Math.max(1, Math.ceil(days / 7));
};

const weeklyPayment = (debt) => {
    if (!debt.remaining_amount) return 0;
    return debt.remaining_amount / weeksLeft(debt);
};

const getProgress = (debt) => {
    if (debt.total_amount === 0) return 0;
    return ((debt.total_amount - debt.remaining_amount) / debt.total_amount) * 100;
};

const fetchDebts = async () => {
    try {
        debts.value = await api.get('/debts');
    } catch (error) {
        console.error('Error:', error);
    }
};

const addDebt = async () => {
    try {
        await api.post('/debts', newDebt.value);
        newDebt.value = { name: '', total_amount: null, remaining_amount: null, monthly_percentage: 10, creditor: '', due_date: '', description: '' };
        fetchDebts();
    } catch (error) {
        console.error('Error:', error);
    }
};

const updateDebt = async (debt) => {
    try {
        await api.put(`/debts/${debt.id}`, { remaining_amount: debt.remaining_amount });
        fetchDebts();
    } catch (error) {
        console.error('Error:', error);
    }
};

const deleteDebt = async (id) => {
    if (confirm('Are you sure?')) {
        try {
            await api.delete(`/debts/${id}`);
            fetchDebts();
        } catch (error) {
            console.error('Error:', error);
        }
    }
};

const openPayment = (debt) => {
    payingDebt.value = debt;
    payError.value   = '';
    const defaultAcc = accounts.value.find(a => a.is_default) || accounts.value[0];
    payForm.value = {
        amount:     weeklyPayment(debt),
        account_id: defaultAcc?.id || '',
        date:       new Date().toISOString().split('T')[0],
        notes:      '',
    };
};

const submitPayment = async () => {
    paying.value   = true;
    payError.value = '';
    const debt     = payingDebt.value;
    const amount   = payForm.value.amount;

    try {
        const debtCat = categories.value.find(c => c.slug === 'debt-payment');

        // 1. Register expense transaction
        await api.post('/transactions', {
            type:        'expense',
            amount,
            date:        payForm.value.date,
            account_id:  payForm.value.account_id,
            category_id: debtCat?.id || undefined,
            description: `Debt payment: ${debt.name}`,
            notes:       payForm.value.notes || undefined,
        });

        // 2. Reduce debt remaining_amount
        const newRemaining = Math.max(0, Number(debt.remaining_amount) - amount);
        await api.put(`/debts/${debt.id}`, { remaining_amount: newRemaining });

        payingDebt.value = null;
        fetchDebts();
    } catch (e) {
        payError.value = e.message || 'Payment failed';
    } finally {
        paying.value = false;
    }
};

onMounted(async () => {
    const [, accs, cats] = await Promise.all([
        fetchDebts(),
        api.get('/accounts'),
        api.get('/categories'),
    ]);
    accounts.value   = Array.isArray(accs) ? accs : (accs.data || []);
    categories.value = Array.isArray(cats) ? cats : (cats.data || []);
});
</script>

<style scoped>
.page-header { margin-bottom: 2rem; animation: fadeUp 0.35s ease; }
.page-header h1 {
    font-size: 2rem; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 0.375rem;
    color: var(--color-primary);
}
.page-header p { color: var(--text-muted); font-size: 0.95rem; }

.form-card, .data-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    padding: 1.75rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-md);
}
.form-card h3 {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
}
.form-group { display: flex; flex-direction: column; }
.form-group label {
    font-size: 0.75rem; margin-bottom: 0.4rem;
    color: var(--text-secondary); font-weight: 600;
    letter-spacing: 0.05em;
}
.form-input {
    padding: 0.7rem 0.875rem;
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 0.9rem; font-family: inherit;
    transition: all 0.2s ease;
}
.form-input::placeholder { color: var(--text-muted); }
.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(9, 20, 38, 0.1);
}

.btn {
    min-height: 48px;
    padding: 0.7rem 1.5rem; border: none;
    border-radius: var(--radius-md);
    font-size: 0.9rem; font-weight: 700; font-family: inherit;
    letter-spacing: 0.02em; cursor: pointer; transition: all 0.2s ease;
}
.btn-primary {
    grid-column: 1 / -1;
    background: var(--color-secondary);
    color: white;
}
.btn-primary:hover {
    background: var(--color-secondary-dark);
    box-shadow: 0 4px 16px rgba(16,185,129,0.3);
    transform: translateY(-1px);
}
.btn-primary:active { transform: translateY(0); }

.btn-sm {
    min-height: auto;
    padding: 0.45rem 0.9rem;
    font-size: 0.8rem;
    background: var(--color-secondary);
    color: white;
}
.btn-sm:hover { background: var(--color-secondary-dark); transform: translateY(-1px); }

.empty-state { text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); font-size: 0.9rem; }

.debts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.25rem;
}

.debt-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-left: 3px solid #d97706;
    border-radius: var(--radius-md);
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
    transition: transform 0.2s, box-shadow 0.2s;
}
.debt-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

.debt-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.375rem;
}
.debt-header h4 { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
.debt-creditor { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem; }

.btn-action {
    padding: 0.35rem 0.75rem;
    background: transparent;
    border: 1px solid #fca5a5;
    color: #dc2626;
    border-radius: var(--radius-md);
    font-size: 0.75rem; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all 0.18s ease; white-space: nowrap;
}
.btn-action:hover { background: #fff5f5; border-color: #dc2626; }

.progress-section { margin-bottom: 1.25rem; }
.progress-info { display: flex; justify-content: space-between; margin-bottom: 0.6rem; font-size: 0.85rem; color: var(--text-secondary); }
.progress-text { color: #059669; font-weight: 700; }
.progress-bar {
    height: 6px;
    background: var(--border-default);
    border-radius: var(--radius-full);
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: #d97706;
    border-radius: var(--radius-full);
    transition: width 0.6s cubic-bezier(.4,0,.2,1);
}
.progress-percent { font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem; }

.debt-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.6rem;
    margin-bottom: 1.25rem;
    padding: 0.875rem;
    background: var(--bg-surface);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-default);
}
.stat .stat-label { font-size: 0.65rem; color: var(--text-muted); margin-bottom: 0.2rem; text-transform: uppercase; letter-spacing: 0.05em; }
.stat .stat-value { font-size: 0.875rem; font-weight: 700; color: var(--text-primary); word-break: break-word; }
.stat .stat-value.red { color: #dc2626; }
.stat .stat-value.green { color: #059669; }

.weekly-payment {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: var(--radius-md);
    padding: 0.75rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.weekly-amount {
    font-size: 1.5rem;
    font-weight: 800;
    color: #d97706;
    line-height: 1;
}
.weekly-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #92400e;
    margin-left: 2px;
}
.weekly-meta {
    font-size: 0.75rem;
    color: #92400e;
    font-weight: 500;
}

.debt-actions-row { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
.debt-input { display: flex; gap: 0.6rem; flex: 1; min-width: 0; }
.debt-input .form-input { flex: 1; }

.btn-pay {
    background: #fffbeb;
    border: 1.5px solid #fcd34d;
    color: #92400e;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}

@media (max-width: 1024px) {
    .page-header h1 { font-size: 1.75rem; }
    .form-grid { grid-template-columns: repeat(2, 1fr); }
    .debts-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .page-header h1 { font-size: 1.5rem; }
    .form-card { padding: 1.25rem; }
    .form-grid { grid-template-columns: 1fr; }
    .debts-grid { grid-template-columns: 1fr; }
    .debt-card { padding: 1.25rem; }
    .debt-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .page-header h1 { font-size: 1.375rem; }
    .form-card, .debt-card { padding: 1rem; }
    .form-grid { gap: 0.875rem; }
    .debt-header { flex-direction: column; gap: 0.5rem; }
    .debt-input { flex-direction: column; }
    .form-input { padding: 0.6rem 0.75rem; }
    .debt-stats { grid-template-columns: repeat(2, 1fr); padding: 0.75rem; }
}

/* Pay Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(9,20,38,0.45);
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
    max-width: 420px;
    box-shadow: var(--shadow-lg);
    animation: fadeUp 0.2s ease;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-default);
}

.modal-header h2 { font-size: 1.125rem; font-weight: 700; color: var(--color-primary); }

.btn-close {
    background: transparent; border: none; cursor: pointer;
    font-size: 1.1rem; color: var(--text-muted);
    padding: 0.25rem 0.5rem; border-radius: var(--radius-md);
    transition: all 0.15s; font-family: inherit;
}
.btn-close:hover { background: var(--bg-surface); }

.modal-form {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.pay-debt-info {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: var(--radius-md);
    padding: 0.75rem 1rem;
}
.pay-debt-name { font-size: 1rem; font-weight: 700; color: var(--color-primary); margin-bottom: 0.25rem; }
.pay-debt-remaining { font-size: 0.875rem; color: var(--text-secondary); }
.pay-debt-remaining strong { color: #dc2626; }

.field-hint { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; }

.modal-actions {
    display: flex; gap: 0.75rem; justify-content: flex-end;
    padding-top: 0.5rem; border-top: 1px solid var(--border-default);
}

.btn-secondary {
    background: var(--bg-surface); color: var(--text-secondary);
    border: 1px solid var(--border-default);
}
.btn-secondary:hover { background: var(--bg-card-hover); }

.btn-primary { background: var(--color-secondary); color: white; }
.btn-primary:hover:not(:disabled) { background: var(--color-secondary-dark); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.spinner {
    width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.error-msg {
    background: #fff5f5; border: 1px solid #fca5a5;
    color: #dc2626; padding: 0.65rem 1rem;
    border-radius: var(--radius-md); font-size: 0.875rem;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
