<template>
    <div>
        <div class="page-header">
            <h1>💵 Income</h1>
            <p>Track and manage all your income sources</p>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <h3>Add New Income</h3>
            <form @submit.prevent="addIncome" class="form-grid">
                <div class="form-group">
                    <label>Amount</label>
                    <input v-model.number="newIncome.amount" type="number" step="0.01" required
                        class="form-input" placeholder="0.00" />
                </div>
                <div class="form-group">
                    <label>Frequency</label>
                    <select v-model="newIncome.frequency" class="form-input">
                        <option value="weekly">Weekly</option>
                        <option value="biweekly">Bi-weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Next Date</label>
                    <input v-model="newIncome.next_date" type="date" required class="form-input" />
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input v-model="newIncome.description" type="text" class="form-input"
                        placeholder="e.g. Salary, Freelance" />
                </div>
                <button type="submit" class="btn btn-primary">Add Income</button>
            </form>
        </div>

        <!-- Data Card -->
        <div class="data-card">
            <div class="card-header">
                <h3>Your Income</h3>
            </div>

            <div v-if="incomes.length === 0" class="empty-state">
                <p>No income recorded yet. Start by adding one!</p>
            </div>

            <div v-else class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Frequency</th>
                            <th>Next Date</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="income in incomes" :key="income.id">
                            <td data-label="Amount" class="amount">{{ formatMoney(income.amount) }}</td>
                            <td data-label="Frequency">
                                <span class="badge">{{ freqLabel(income.frequency) }}</span>
                            </td>
                            <td data-label="Next Date">{{ formatDate(income.next_date) }}</td>
                            <td data-label="Description">{{ income.description || '-' }}</td>
                            <td data-label="Actions">
                                <button @click="deleteIncome(income.id)" class="btn-action delete">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api.js';
import { useCurrency } from '../composables/useCurrency.js';

const incomes = ref([]);
const newIncome = ref({
    amount: null,
    frequency: 'monthly',
    next_date: '',
    description: ''
});

const { formatMoney } = useCurrency();

const formatDate = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    if (isNaN(d)) return date;
    return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
};

const freqLabel = (freq) => {
    return { 'weekly': 'Weekly', 'biweekly': 'Bi-weekly', 'monthly': 'Monthly' }[freq] || freq;
};

const fetchIncomes = async () => {
    try {
        incomes.value = await api.get('/incomes');
    } catch (error) {
        console.error('Error fetching incomes:', error);
    }
};

const addIncome = async () => {
    try {
        await api.post('/incomes', newIncome.value);
        newIncome.value = { amount: null, frequency: 'monthly', next_date: '', description: '' };
        fetchIncomes();
    } catch (error) {
        console.error('Error adding income:', error);
    }
};

const deleteIncome = async (id) => {
    if (confirm('Are you sure you want to delete this income?')) {
        try {
            await api.delete(`/incomes/${id}`);
            fetchIncomes();
        } catch (error) {
            console.error('Error deleting income:', error);
        }
    }
};

onMounted(fetchIncomes);
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
.form-card h3, .data-card h3 {
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
.form-input::-webkit-outer-spin-button,
.form-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

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

.card-header {
    border-bottom: 1px solid var(--border-default);
    padding-bottom: 1rem; margin-bottom: 1rem;
}
.empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-muted); font-size: 0.9rem; }
.table-wrapper { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead tr { border-bottom: 1px solid var(--border-default); }
.data-table th {
    padding: 0.75rem 1rem; text-align: left;
    color: var(--text-muted); font-weight: 700;
    font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em;
}
.data-table td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border-default);
    color: var(--text-secondary); font-size: 0.875rem;
}
.data-table tbody tr { transition: background 0.15s; }
.data-table tbody tr:hover { background: var(--bg-surface); }
.data-table tbody tr:last-child td { border-bottom: none; }
.amount { color: #059669; font-weight: 700; }
.badge {
    background: #ecfdf5; color: #065f46;
    padding: 0.3rem 0.7rem; border-radius: var(--radius-full);
    font-size: 0.78rem; font-weight: 600;
    border: 1px solid #6ee7b7; white-space: nowrap;
}
.btn-action {
    padding: 0.35rem 0.8rem;
    background: transparent;
    border: 1px solid var(--border-default);
    color: var(--text-secondary);
    border-radius: var(--radius-md);
    font-size: 0.78rem; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all 0.18s ease; white-space: nowrap;
}
.btn-action.delete:hover {
    background: #fff5f5;
    border-color: #fca5a5;
    color: #dc2626;
}

@media (max-width: 1024px) {
    .page-header h1 { font-size: 1.75rem; }
    .form-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .page-header h1 { font-size: 1.5rem; }
    .form-card, .data-card { padding: 1.25rem; margin-bottom: 1.25rem; }
    .form-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
}

@media (max-width: 600px) {
    .data-table thead { display: none; }

    .data-table tbody tr {
        display: block;
        border: 1px solid var(--border-default);
        border-radius: var(--radius-md);
        margin-bottom: 0.75rem;
        padding: 0.25rem 0;
        background: var(--bg-surface);
    }

    .data-table tbody tr:last-child { margin-bottom: 0; }

    .data-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.55rem 0.875rem;
        border-bottom: 1px solid var(--border-default);
        font-size: 0.875rem;
    }

    .data-table td:last-child { border-bottom: none; }

    .data-table td::before {
        content: attr(data-label);
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        flex-shrink: 0;
        margin-right: 1rem;
    }
}

@media (max-width: 480px) {
    .page-header h1 { font-size: 1.375rem; }
    .form-card, .data-card { padding: 1rem; margin-bottom: 1rem; }
    .form-grid { grid-template-columns: 1fr; gap: 0.875rem; }
    .form-input { padding: 0.6rem 0.75rem; }
}
</style>
