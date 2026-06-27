<template>
    <div>
        <div class="page-header">
            <h1>📈 Financial Projections</h1>
            <p>Visualize your financial future over the next 12 months</p>
        </div>

        <div v-if="loading" class="loading-container">
            <div class="spinner"></div>
            <p>Calculating projections...</p>
        </div>

        <div v-else>
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card income">
                    <p class="summary-label">Income (12 months)</p>
                    <p class="summary-value">{{ formatMoney(getTotalIncome()) }}</p>
                </div>
                <div class="summary-card savings">
                    <p class="summary-label">Savings (12 months)</p>
                    <p class="summary-value">{{ formatMoney(getTotalSavings()) }}</p>
                </div>
                <div class="summary-card debt">
                    <p class="summary-label">Debt Payments (12 months)</p>
                    <p class="summary-value">{{ formatMoney(getTotalDebtPayment()) }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="table-card">
                <h3>Monthly Projection</h3>
                <div class="table-wrapper">
                    <table class="projection-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Income</th>
                                <th>Savings</th>
                                <th>Debt Payments</th>
                                <th>Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(month, index) in projection" :key="index">
                                <td data-label="Month"><span class="month-badge">Month {{ month.month }}</span></td>
                                <td data-label="Income" class="income-col">{{ formatMoney(month.income) }}</td>
                                <td data-label="Savings" class="savings-col">{{ formatMoney(getSavingsForMonth(month)) }}</td>
                                <td data-label="Debt Payments" class="debt-col">{{ formatMoney(getDebtForMonth(month)) }}</td>
                                <td data-label="Available" :class="['available-col', month.remaining >= 0 ? 'positive' : 'negative']">
                                    {{ formatMoney(month.remaining) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Details -->
            <div class="details-grid">
                <div class="detail-card">
                    <h3>Savings Breakdown</h3>
                    <div v-if="projection.length > 0" class="detail-list">
                        <div v-for="goal in getUniqueSavingGoals()" :key="goal.goal_id" class="detail-item">
                            <span class="detail-name">{{ goal.goal_name }}</span>
                            <span class="detail-value">{{ formatMoney(goal.amount) }}/mo</span>
                        </div>
                        <div v-if="getUniqueSavingGoals().length === 0" class="empty">
                            No saving goals set
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <h3>Debt Payments Breakdown</h3>
                    <div v-if="projection.length > 0" class="detail-list">
                        <div v-for="debt in getUniqueDebts()" :key="debt.debt_id" class="detail-item">
                            <span class="detail-name">{{ debt.debt_name }}</span>
                            <span class="detail-value">{{ formatMoney(debt.amount) }}/mo</span>
                        </div>
                        <div v-if="getUniqueDebts().length === 0" class="empty">
                            No debts configured
                        </div>
                    </div>
                </div>
            </div>

            <!-- Final Status -->
            <div class="final-status">
                <h3>Debt Status at End of Period</h3>
                <div v-if="projection.length > 0" class="status-list">
                    <div v-for="debt in getDebtStatusAtEnd()" :key="debt.debt_id" class="status-item">
                        <div class="status-header">
                            <p class="debt-name">{{ debt.debt_name }}</p>
                            <p :class="['debt-remaining', debt.remaining > 0 ? 'red' : 'green']">
                                {{ formatMoney(debt.remaining) }}
                            </p>
                        </div>
                        <p class="debt-monthly">Payment: {{ formatMoney(debt.amount) }}/mo</p>
                    </div>
                    <div v-if="getDebtStatusAtEnd().length === 0" class="celebration">
                        🎉 All debts paid off within 12 months!
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from '../api.js';
import { useCurrency } from '../composables/useCurrency.js';

const projection = ref([]);
const loading = ref(true);

const { formatMoney } = useCurrency();

const fetchProjection = async () => {
    try {
        projection.value = await api.get('/finance/projection');
    } catch (error) {
        console.error('Error:', error);
    } finally {
        loading.value = false;
    }
};

const getTotalIncome = () => projection.value.reduce((sum, month) => sum + month.income, 0);
const getTotalSavings = () => projection.value.reduce((sum, month) => sum + month.saving_goals.reduce((gSum, goal) => gSum + goal.amount, 0), 0);
const getTotalDebtPayment = () => projection.value.reduce((sum, month) => sum + month.debts.reduce((dSum, debt) => dSum + debt.amount, 0), 0);
const getSavingsForMonth = (month) => month.saving_goals.reduce((sum, goal) => sum + goal.amount, 0);
const getDebtForMonth = (month) => month.debts.reduce((sum, debt) => sum + debt.amount, 0);
const getUniqueSavingGoals = () => projection.value.length > 0 ? projection.value[0].saving_goals : [];
const getUniqueDebts = () => projection.value.length > 0 ? projection.value[0].debts : [];
const getDebtStatusAtEnd = () => projection.value.length > 0 ? projection.value[projection.value.length - 1].debts : [];

onMounted(fetchProjection);
</script>

<style scoped>
.page-header { margin-bottom: 2rem; animation: fadeUp 0.35s ease; }
.page-header h1 {
    font-size: 2rem; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 0.375rem;
    color: var(--color-primary);
}
.page-header p { color: var(--text-muted); font-size: 0.95rem; }

.loading-container {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 4rem 2rem; color: var(--text-secondary); gap: 1rem;
}
.spinner {
    width: 36px; height: 36px;
    border: 3px solid var(--border-default);
    border-top-color: var(--color-secondary);
    border-radius: 50%; animation: spin 0.9s linear infinite;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.25rem; margin-bottom: 1.5rem;
}
.summary-card {
    padding: 1.5rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-default);
    background: var(--bg-elevated);
    box-shadow: var(--shadow-md);
    transition: transform 0.2s, box-shadow 0.2s;
}
.summary-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
.summary-card.income { border-left: 3px solid #059669; }
.summary-card.savings { border-left: 3px solid #2563eb; }
.summary-card.debt    { border-left: 3px solid #d97706; }
.summary-label {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 0.5rem;
}
.summary-card.income .summary-value  { color: #059669; }
.summary-card.savings .summary-value { color: #2563eb; }
.summary-card.debt .summary-value    { color: #d97706; }
.summary-value { font-size: 1.625rem; font-weight: 700; letter-spacing: -0.02em; color: var(--text-primary); }

.table-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    padding: 1.75rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-md);
}
.table-card h3 {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1.5rem;
}

.table-wrapper { overflow-x: auto; }
.projection-table { width: 100%; border-collapse: collapse; }
.projection-table thead tr { border-bottom: 1px solid var(--border-default); }
.projection-table th {
    padding: 0.75rem 1rem; text-align: left;
    color: var(--text-muted); font-weight: 700;
    font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em;
}
.projection-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border-default);
    color: var(--text-secondary); font-size: 0.875rem;
}
.projection-table tbody tr { transition: background 0.15s; }
.projection-table tbody tr:hover { background: var(--bg-surface); }
.projection-table tbody tr:last-child td { border-bottom: none; }

.month-badge {
    background: var(--bg-surface);
    color: var(--color-primary);
    padding: 0.3rem 0.7rem;
    border-radius: var(--radius-full);
    font-size: 0.8rem; font-weight: 700;
    border: 1px solid var(--border-default);
}
.income-col  { color: #059669; font-weight: 700; }
.savings-col { color: #2563eb; font-weight: 700; }
.debt-col    { color: #d97706; font-weight: 700; }
.available-col { font-weight: 700; }
.available-col.positive { color: #059669; }
.available-col.negative { color: #dc2626; }

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.25rem; margin-bottom: 1.5rem;
}

.detail-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
}
.detail-card h3 {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1rem;
}
.detail-list { display: flex; flex-direction: column; gap: 0.6rem; }
.detail-item {
    display: flex; justify-content: space-between;
    padding: 0.7rem 0.875rem;
    background: var(--bg-surface);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-default);
}
.detail-name { color: var(--text-secondary); font-size: 0.875rem; }
.detail-value { color: #2563eb; font-weight: 700; font-size: 0.875rem; }
.empty { text-align: center; color: var(--text-muted); padding: 1.5rem; font-size: 0.875rem; }

.final-status {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    padding: 1.75rem;
    box-shadow: var(--shadow-md);
}
.final-status h3 {
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1.25rem;
}
.status-list { display: flex; flex-direction: column; gap: 0.75rem; }
.status-item {
    background: var(--bg-surface);
    padding: 1rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-default);
    border-left: 3px solid #d97706;
}
.status-header { display: flex; justify-content: space-between; margin-bottom: 0.375rem; align-items: baseline; }
.debt-name { font-weight: 700; font-size: 0.9rem; color: var(--text-primary); }
.debt-remaining { font-weight: 700; font-size: 1rem; }
.debt-remaining.red { color: #dc2626; }
.debt-remaining.green { color: #059669; }
.debt-monthly { font-size: 0.8rem; color: var(--text-muted); }
.celebration {
    text-align: center; font-size: 1.125rem; font-weight: 700;
    color: #059669; padding: 2rem;
    background: #ecfdf5; border-radius: var(--radius-md);
    border: 1px solid #6ee7b7;
}

@media (max-width: 1024px) {
    .page-header h1 { font-size: 1.75rem; }
    .details-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .page-header h1 { font-size: 1.5rem; }
    .summary-cards { gap: 1rem; margin-bottom: 1.25rem; }
    .summary-value { font-size: 1.375rem; }
    .table-card { padding: 1.25rem; }
    .details-grid { gap: 1rem; }
    .detail-card { padding: 1.25rem; }
}

@media (max-width: 600px) {
    .projection-table thead { display: none; }

    .projection-table tbody tr {
        display: block;
        border: 1px solid var(--border-default);
        border-radius: var(--radius-md);
        margin-bottom: 0.75rem;
        padding: 0.25rem 0;
        background: var(--bg-surface);
    }

    .projection-table tbody tr:last-child { margin-bottom: 0; }

    .projection-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.55rem 0.875rem;
        border-bottom: 1px solid var(--border-default);
        font-size: 0.875rem;
    }

    .projection-table td:last-child { border-bottom: none; }

    .projection-table td::before {
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
    .summary-cards { grid-template-columns: 1fr; gap: 0.75rem; }
    .summary-value { font-size: 1.25rem; }
    .table-card, .detail-card, .final-status { padding: 1rem; }
    .detail-item { flex-direction: column; gap: 0.2rem; }
    .status-header { flex-direction: column; gap: 0.25rem; }
}
</style>
