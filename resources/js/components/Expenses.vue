<template>
    <div>
        <div class="page-header">
            <h1>💸 Expenses</h1>
            <p>Record and categorize all your expenses</p>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <h3>Add New Expense</h3>
            <form @submit.prevent="addExpense" class="form-grid">
                <div class="form-group">
                    <label>Amount</label>
                    <input v-model.number="newExpense.amount" type="number" step="0.01" required
                        class="form-input" placeholder="0.00" />
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select v-model="newExpense.category" class="form-input" required>
                        <option value="">Select category</option>
                        <option value="Alimentación">Food</option>
                        <option value="Transporte">Transport</option>
                        <option value="Servicios">Utilities</option>
                        <option value="Entretenimiento">Entertainment</option>
                        <option value="Salud">Health</option>
                        <option value="Educación">Education</option>
                        <option value="Otros">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input v-model="newExpense.date" type="date" required class="form-input" />
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input v-model="newExpense.description" type="text" class="form-input"
                        placeholder="Expense details" />
                </div>
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </form>
        </div>

        <!-- Data Card -->
        <div class="data-card">
            <div class="card-header">
                <h3>Your Expenses</h3>
            </div>

            <div v-if="expenses.length === 0" class="empty-state">
                <p>No expenses recorded yet. Start tracking your spending!</p>
            </div>

            <div v-else class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in expenses" :key="expense.id">
                            <td data-label="Amount" class="amount">{{ formatMoney(expense.amount) }}</td>
                            <td data-label="Category">
                                <span class="badge">{{ getCategoryEmoji(expense.category) }} {{ getCategoryLabel(expense.category) }}</span>
                            </td>
                            <td data-label="Date">{{ formatDate(expense.date) }}</td>
                            <td data-label="Description">{{ expense.description || '-' }}</td>
                            <td data-label="Actions">
                                <button @click="deleteExpense(expense.id)" class="btn-action delete">
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

const expenses = ref([]);
const newExpense = ref({
    amount: null,
    category: '',
    date: new Date().toISOString().split('T')[0],
    description: ''
});

const { formatMoney } = useCurrency();

const formatDate = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    if (isNaN(d)) return date;
    return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
};

const getCategoryEmoji = (category) => {
    const emojis = {
        'Alimentación': '🍔',
        'Transporte': '🚗',
        'Servicios': '🏠',
        'Entretenimiento': '🎮',
        'Salud': '🏥',
        'Educación': '📚',
        'Otros': '❓'
    };
    return emojis[category] || '📦';
};

const getCategoryLabel = (category) => {
    const labels = {
        'Alimentación': 'Food',
        'Transporte': 'Transport',
        'Servicios': 'Utilities',
        'Entretenimiento': 'Entertainment',
        'Salud': 'Health',
        'Educación': 'Education',
        'Otros': 'Other'
    };
    return labels[category] || category;
};

const fetchExpenses = async () => {
    try {
        expenses.value = await api.get('/expenses');
    } catch (error) {
        console.error('Error fetching expenses:', error);
    }
};

const addExpense = async () => {
    try {
        await api.post('/expenses', newExpense.value);
        newExpense.value = {
            amount: null,
            category: '',
            date: new Date().toISOString().split('T')[0],
            description: ''
        };
        fetchExpenses();
    } catch (error) {
        console.error('Error adding expense:', error);
    }
};

const deleteExpense = async (id) => {
    if (confirm('Are you sure you want to delete this expense?')) {
        try {
            await api.delete(`/expenses/${id}`);
            fetchExpenses();
        } catch (error) {
            console.error('Error deleting expense:', error);
        }
    }
};

onMounted(fetchExpenses);
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
    font-size: 0.9rem;
    font-family: inherit;
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

.card-header {
    border-bottom: 1px solid var(--border-default);
    padding-bottom: 1rem; margin-bottom: 1rem;
}

.empty-state {
    text-align: center; padding: 3rem 1rem; color: var(--text-muted);
    font-size: 0.9rem;
}

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

.amount { color: #dc2626; font-weight: 700; }

.badge {
    background: #f5f3ff;
    color: #6d28d9;
    padding: 0.3rem 0.7rem;
    border-radius: var(--radius-full);
    font-size: 0.78rem; font-weight: 600;
    border: 1px solid #ddd6fe;
    white-space: nowrap;
}

.btn-action {
    padding: 0.35rem 0.8rem;
    background: transparent;
    border: 1px solid var(--border-default);
    color: var(--text-secondary);
    border-radius: var(--radius-md);
    font-size: 0.78rem; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: all 0.18s ease;
    white-space: nowrap;
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
    .form-card, .data-card { padding: 1.25rem; }
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
    .form-card, .data-card { padding: 1rem; }
    .form-grid { grid-template-columns: 1fr; gap: 0.875rem; }
    .form-input { padding: 0.6rem 0.75rem; }
}
</style>
