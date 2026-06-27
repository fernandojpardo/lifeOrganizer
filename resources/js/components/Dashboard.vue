<template>
    <div class="page">
        <!-- Loading -->
        <div v-if="loading" class="loading-grid">
            <div v-for="i in 4" :key="i" class="skeleton-card"></div>
        </div>

        <template v-else>
            <!-- Hero: Net Worth -->
            <div class="hero-card">
                <div class="hero-label">Net Worth</div>
                <div class="hero-amount">{{ formatMoney(data.net_worth) }}</div>
                <div class="hero-meta">
                    <span class="hero-stat income">↑ {{ formatMoney(data.month_income) }} income this month</span>
                    <span class="hero-divider">·</span>
                    <span class="hero-stat expense">↓ {{ formatMoney(data.month_expense) }} expenses</span>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="stat-row">
                <div class="stat-card">
                    <div class="stat-icon income-icon">↑</div>
                    <div class="stat-body">
                        <div class="stat-label">Month Income</div>
                        <div class="stat-value income">{{ formatMoney(data.month_income) }}</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon expense-icon">↓</div>
                    <div class="stat-body">
                        <div class="stat-label">Month Expenses</div>
                        <div class="stat-value expense">{{ formatMoney(data.month_expense) }}</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon net-icon">⚖</div>
                    <div class="stat-body">
                        <div class="stat-label">Net This Month</div>
                        <div class="stat-value" :class="netMonth >= 0 ? 'income' : 'expense'">
                            {{ formatMoney(netMonth) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cash Flow Chart -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Weekly Cash Flow</h2>
                    <span class="card-subtitle">{{ currentMonth }}</span>
                </div>
                <div class="chart-wrap">
                    <canvas ref="chartCanvas" height="200"></canvas>
                </div>
            </div>

            <!-- Debts Widget -->
            <div class="card" v-if="data.debts_summary?.length">
                <div class="card-header">
                    <h2 class="card-title">💳 Debt Payments</h2>
                    <span class="card-subtitle">Weekly obligation: {{ formatMoney(data.total_weekly_debt) }}</span>
                </div>
                <div class="debts-mini">
                    <div v-for="d in data.debts_summary" :key="d.id" class="debt-mini-row">
                        <div class="debt-mini-left">
                            <span class="debt-mini-name">{{ d.name }}</span>
                            <span class="debt-mini-sub" v-if="d.creditor">{{ d.creditor }}</span>
                        </div>
                        <div class="debt-mini-right">
                            <span class="debt-mini-weekly">{{ formatMoney(d.weekly_payment) }}<span class="debt-mini-label">/wk</span></span>
                            <span class="debt-mini-weeks">{{ d.weeks_left }} weeks left</span>
                        </div>
                        <button class="pay-btn" @click="openDebtPayment(d)" title="Register payment">Pay</button>
                    </div>
                    <div class="debt-mini-total">
                        <span>Total remaining</span>
                        <span class="debt-total-val">{{ formatMoney(data.total_debt) }}</span>
                    </div>
                </div>
            </div>

            <!-- Savings Widget -->
            <div class="card" v-if="data.savings_summary?.length">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">🎯 Savings Goals</h2>
                        <span class="card-subtitle">{{ formatMoney(data.total_savings) }} / {{ formatMoney(data.total_savings_target) }} · {{ overallSavingsPct.toFixed(0) }}%</span>
                    </div>
                </div>
                <div class="savings-widget">
                    <div class="savings-chart-wrap" :style="{ height: Math.max(120, data.savings_summary.length * 48) + 'px' }">
                        <canvas ref="savingsCanvas"></canvas>
                    </div>
                    <div class="savings-legend">
                        <div v-for="(g, i) in data.savings_summary" :key="g.id" class="sav-legend-row">
                            <span class="sav-dot" :style="{ background: savingsColor(g.percentage, i) }"></span>
                            <div class="sav-legend-body">
                                <div class="sav-legend-top">
                                    <span class="sav-legend-name">{{ g.name }}</span>
                                    <span class="sav-legend-pct" :style="{ color: savingsColor(g.percentage, i) }">{{ g.percentage }}%</span>
                                </div>
                                <div class="sav-legend-bottom">
                                    <span class="sav-legend-amounts">{{ formatMoney(g.current_amount) }} <span class="sav-sep">of</span> {{ formatMoney(g.target_amount) }}</span>
                                    <span v-if="g.target_date" class="sav-date">· {{ shortDate(g.target_date) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-column row: Budget Alerts + Accounts -->
            <div class="two-col">
                <!-- Budget Alerts -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Budget Alerts</h2>
                    </div>
                    <div v-if="data.budget_alerts?.length" class="alerts-list">
                        <div v-for="b in data.budget_alerts" :key="b.id" class="alert-row">
                            <div class="alert-meta">
                                <span>{{ b.category?.icon || '📂' }} {{ b.category?.name }}</span>
                                <span :class="alertClass(b.percentage)" class="alert-pct">{{ b.percentage }}%</span>
                            </div>
                            <div class="mini-bar">
                                <div class="mini-fill" :class="alertClass(b.percentage)" :style="{ width: Math.min(b.percentage, 100) + '%' }"></div>
                            </div>
                            <div class="alert-amounts">{{ formatMoney(b.spent) }} / {{ formatMoney(b.amount) }}</div>
                        </div>
                    </div>
                    <div v-else class="empty-mini">
                        <span>✓ All budgets on track</span>
                    </div>
                </div>

                <!-- Accounts -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Accounts</h2>
                    </div>
                    <div v-if="data.accounts?.length" class="accounts-mini">
                        <div v-for="acc in data.accounts" :key="acc.id" class="account-mini">
                            <div class="acc-left">
                                <span class="acc-icon">{{ acc.icon || '🏦' }}</span>
                                <span class="acc-name">{{ acc.name }}</span>
                            </div>
                            <span class="acc-balance" :class="{ 'acc-neg': Number(acc.balance) < 0 }">
                                {{ acc.currency }} {{ formatMoney(acc.balance) }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="empty-mini">
                        <span>No accounts yet</span>
                    </div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="quick-bar">
                <button class="btn-quick" @click="openModal">+ New Transaction</button>
                <button class="btn-quick btn-outline" @click="$emit('navigate', 'Accounts')">Manage Accounts</button>
            </div>
        </template>

        <!-- FAB -->
        <button class="fab" @click="openModal" title="New Transaction">+</button>

        <!-- Transaction Modal -->
        <TransactionModal
            v-if="showModal"
            :transaction="null"
            :prefill="prefillTx"
            :accounts="accounts"
            :categories="categories"
            @close="showModal = false"
            @saved="onSaved"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, DoughnutController, ArcElement } from 'chart.js';
import { api } from '../api.js';
import { useCurrency } from '../composables/useCurrency.js';
import TransactionModal from './TransactionModal.vue';

const { formatMoney, currency } = useCurrency();

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, DoughnutController, ArcElement);

defineEmits(['navigate']);

const data        = ref({});
const loading     = ref(true);
const chartCanvas   = ref(null);
const savingsCanvas = ref(null);
let chartInstance   = null;
let savingsChart    = null;

const accounts   = ref([]);
const categories = ref([]);
const showModal  = ref(false);
const prefillTx  = ref(null);

const netMonth        = computed(() => (data.value.month_income || 0) - (data.value.month_expense || 0));
const currentMonth    = computed(() => new Date().toLocaleString('en-US', { month: 'long', year: 'numeric' }));
const overallSavingsPct = computed(() => {
    const target = data.value.total_savings_target || 0;
    const saved  = data.value.total_savings || 0;
    return target > 0 ? Math.min(100, (saved / target) * 100) : 0;
});


const GOAL_COLORS = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#f97316','#8b5cf6','#84cc16'];
const savingsColor = (_, idx) => GOAL_COLORS[idx % GOAL_COLORS.length];

const shortDate = (d) => {
    if (!d) return '';
    return new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
};

const alertClass = (pct) => {
    if (pct >= 100) return 'danger';
    if (pct >= 70)  return 'warning';
    return 'success';
};

const buildChart = () => {
    if (!chartCanvas.value || !data.value.cashflow_weekly?.length) return;
    if (chartInstance) chartInstance.destroy();

    const weekly = data.value.cashflow_weekly;
    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels: weekly.map(w => w.label),
            datasets: [
                {
                    label: 'Income',
                    data: weekly.map(w => w.income),
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    borderRadius: 6,
                },
                {
                    label: 'Expenses',
                    data: weekly.map(w => w.expense),
                    backgroundColor: 'rgba(239, 68, 68, 0.65)',
                    borderRadius: 6,
                },
                {
                    label: 'Debts',
                    data: weekly.map(w => -(w.debt || 0)),
                    backgroundColor: 'rgba(217, 119, 6, 0.7)',
                    borderRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, padding: 16, font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const val = Math.abs(ctx.parsed.y);
                            return ` ${ctx.dataset.label}: ${formatMoney(val)}`;
                        },
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: {
                    border: { display: false },
                    grid: { color: 'rgba(0,0,0,0.06)' },
                    ticks: {
                        callback: v => formatMoney(Math.abs(v)),
                        font: { size: 11 },
                    },
                },
            },
        },
    });
};

const buildSavingsChart = () => {
    if (!savingsCanvas.value || !data.value.savings_summary?.length) return;
    if (savingsChart) savingsChart.destroy();

    const goals = data.value.savings_summary;
    savingsChart = new Chart(savingsCanvas.value, {
        type: 'bar',
        data: {
            labels: goals.map(g => g.name),
            datasets: [
                {
                    label: 'Saved',
                    data: goals.map(g => Math.min(g.percentage, 100)),
                    backgroundColor: goals.map((g, i) => savingsColor(g.percentage, i)),
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Remaining',
                    data: goals.map(g => Math.max(0, 100 - g.percentage)),
                    backgroundColor: 'rgba(0,0,0,0.07)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    display: false,
                    grid: { display: false },
                    min: 0,
                    max: 100,
                },
                y: {
                    stacked: true,
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 12 }, color: '#45474c' },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const g = goals[ctx.dataIndex];
                            if (ctx.datasetIndex === 0) return ` Saved: ${formatMoney(g.current_amount)} (${g.percentage}%)`;
                            return ` Remaining: ${formatMoney(Math.max(0, g.target_amount - g.current_amount))}`;
                        },
                    },
                },
            },
        },
    });
};

const openModal = () => { prefillTx.value = null; showModal.value = true; };

const openDebtPayment = (debt) => {
    const debtCat = categories.value.find(c => c.slug === 'debt-payment');
    prefillTx.value = {
        type:        'expense',
        amount:      debt.weekly_payment,
        description: `Debt payment: ${debt.name}`,
        category_id: debtCat?.id || '',
    };
    showModal.value = true;
};

const onSaved = async () => {
    showModal.value = false;
    await load();
};

const load = async () => {
    loading.value = true;
    try {
        const [dash, accs, cats] = await Promise.all([
            api.get('/finance/dashboard'),
            api.get('/accounts'),
            api.get('/categories'),
        ]);
        data.value      = dash;
        accounts.value  = Array.isArray(accs) ? accs : (accs.data || []);
        categories.value = Array.isArray(cats) ? cats : (cats.data || []);
    } finally {
        loading.value = false;
    }
    await nextTick();
    buildChart();
    buildSavingsChart();
};

onMounted(load);
</script>

<style scoped>
.page { padding: 1.5rem; max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem; }

.loading-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }
.skeleton-card { height: 120px; background: var(--bg-surface); border-radius: var(--radius-lg); animation: pulse 1.5s ease-in-out infinite; }

.hero-card {
    background: var(--color-primary);
    border-radius: var(--radius-lg);
    padding: 2rem 2.5rem;
    color: white;
}

.hero-label  { font-size: 0.8rem; font-weight: 600; opacity: 0.6; letter-spacing: 0.08em; margin-bottom: 0.5rem; }
.hero-amount { font-size: 2.5rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 0.75rem; }
.hero-meta   { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; font-size: 0.85rem; opacity: 0.85; }
.hero-divider { opacity: 0.4; }
.hero-stat.income  { color: #6ee7b7; }
.hero-stat.expense { color: #fca5a5; }

.stat-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }

.stat-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.income-icon  { background: #ecfdf5; color: #059669; }
.expense-icon { background: #fff5f5; color: #dc2626; }
.net-icon     { background: #eff6ff; color: #2563eb; }

.stat-label { font-size: 0.72rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.25rem; }
.stat-value { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); }
.stat-value.income  { color: #059669; }
.stat-value.expense { color: #dc2626; }

.card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-default);
}

.card-title    { font-size: 0.95rem; font-weight: 700; color: var(--color-primary); margin: 0; }
.card-subtitle { font-size: 0.8rem; color: var(--text-muted); }

.chart-wrap { padding: 1.25rem; height: 220px; position: relative; }

/* ── Savings widget ──────────────────────────── */
.savings-widget { display: flex; flex-direction: column; gap: 0.75rem; padding: 1.25rem; }
.savings-chart-wrap { position: relative; width: 100%; }
.savings-legend { display: flex; flex-direction: column; gap: 0.5rem; }
.sav-legend-row { display: flex; align-items: flex-start; gap: 0.6rem; }
.sav-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 0.3rem; }
.sav-legend-body { flex: 1; min-width: 0; }
.sav-legend-top { display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.1rem; }
.sav-legend-name { font-size: 0.85rem; font-weight: 700; color: var(--text-primary); }
.sav-legend-pct  { font-size: 0.82rem; font-weight: 800; flex-shrink: 0; }
.sav-legend-bottom { display: flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; color: var(--text-muted); flex-wrap: wrap; }
.sav-sep { opacity: 0.5; }
.sav-date { color: var(--text-muted); }

.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

.alerts-list { padding: 0.75rem 1.25rem; display: flex; flex-direction: column; gap: 0.875rem; }

.alert-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem; font-size: 0.85rem; font-weight: 600; color: var(--text-primary); }
.alert-pct { font-size: 0.75rem; font-weight: 700; }
.alert-pct.danger  { color: #dc2626; }
.alert-pct.warning { color: #d97706; }
.alert-pct.success { color: #059669; }

.mini-bar { height: 6px; background: var(--bg-surface); border-radius: 999px; overflow: hidden; margin-bottom: 0.25rem; }
.mini-fill { height: 100%; border-radius: 999px; transition: width 0.4s ease; }
.mini-fill.danger  { background: #ef4444; }
.mini-fill.warning { background: #f59e0b; }
.mini-fill.success { background: #10b981; }

.alert-amounts { font-size: 0.72rem; color: var(--text-muted); }

.empty-mini { padding: 1.5rem 1.25rem; font-size: 0.875rem; color: var(--text-muted); text-align: center; }

.accounts-mini { padding: 0.5rem 1.25rem; display: flex; flex-direction: column; }

.account-mini {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--border-subtle);
    font-size: 0.875rem;
}

.account-mini:last-child { border-bottom: none; }

.acc-left    { display: flex; align-items: center; gap: 0.5rem; }
.acc-icon    { font-size: 1.1rem; }
.acc-name    { font-weight: 600; color: var(--text-primary); }
.acc-balance { font-weight: 700; color: var(--color-primary); }
.acc-neg     { color: #dc2626; }

/* FAB */
.fab {
    position: fixed;
    bottom: 5.5rem;
    right: 1.5rem;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--color-secondary);
    color: white;
    border: none;
    font-size: 1.75rem;
    font-weight: 300;
    cursor: pointer;
    box-shadow: var(--shadow-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    z-index: 100;
    line-height: 1;
}
.fab:hover { background: var(--color-secondary-dark); transform: scale(1.08); }

@media (max-width: 768px) {
    .fab { bottom: 5rem; }
}

/* Debts widget */
.debts-mini { padding: 0.5rem 1.25rem 1rem; display: flex; flex-direction: column; gap: 0; }

.debt-mini-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--border-subtle);
    gap: 1rem;
}

.debt-mini-left { display: flex; flex-direction: column; min-width: 0; }
.debt-mini-name { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.debt-mini-sub  { font-size: 0.72rem; color: var(--text-muted); margin-top: 0.1rem; }

.debt-mini-right { display: flex; flex-direction: column; align-items: flex-end; flex-shrink: 0; }
.debt-mini-weekly { font-size: 1rem; font-weight: 800; color: #d97706; }
.debt-mini-label { font-size: 0.65rem; font-weight: 600; color: #92400e; margin-left: 1px; }
.debt-mini-weeks  { font-size: 0.7rem; color: var(--text-muted); margin-top: 0.1rem; }

.debt-mini-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.75rem;
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 600;
}
.debt-total-val { font-size: 0.95rem; font-weight: 800; color: var(--text-primary); }

.pay-btn {
    flex-shrink: 0;
    padding: 0.3rem 0.75rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
    border-radius: var(--radius-md);
    font-size: 0.75rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.15s;
    margin-left: 0.5rem;
}
.pay-btn:hover { background: #fef3c7; border-color: #d97706; }

.quick-bar { display: flex; gap: 0.75rem; flex-wrap: wrap; }

.btn-quick {
    min-height: 44px;
    padding: 0.6rem 1.5rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
    background: var(--color-secondary);
    color: white;
}

.btn-quick:hover { background: var(--color-secondary-dark); }

.btn-quick.btn-outline {
    background: transparent;
    color: var(--color-primary);
    border: 1.5px solid var(--color-primary);
}

.btn-quick.btn-outline:hover { background: var(--color-primary); color: white; }

@media (max-width: 768px) {
    .page      { padding: 1rem; gap: 1rem; }
    .hero-card { padding: 1.5rem; }
    .hero-amount { font-size: 1.875rem; }
    .stat-row  { grid-template-columns: 1fr; }
    .two-col   { grid-template-columns: 1fr; }
}

@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
