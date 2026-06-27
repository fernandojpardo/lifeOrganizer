<template>
    <div class="page">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>🎯 Saving Goals</h1>
                <p>Track progress toward your financial goals</p>
            </div>
            <button class="btn-new" @click="showForm = !showForm">
                {{ showForm ? '✕ Cancel' : '+ New Goal' }}
            </button>
        </div>

        <!-- Summary bar -->
        <div class="summary-bar" v-if="goals.length">
            <div class="sum-item">
                <span class="sum-label">Total Saved</span>
                <span class="sum-val green">{{ formatMoney(totalSaved) }}</span>
            </div>
            <div class="sum-divider"></div>
            <div class="sum-item">
                <span class="sum-label">Total Target</span>
                <span class="sum-val">{{ formatMoney(totalTarget) }}</span>
            </div>
            <div class="sum-divider"></div>
            <div class="sum-item">
                <span class="sum-label">Remaining</span>
                <span class="sum-val amber">{{ formatMoney(Math.max(0, totalTarget - totalSaved)) }}</span>
            </div>
            <div class="sum-divider"></div>
            <div class="sum-item">
                <span class="sum-label">Overall</span>
                <span class="sum-val">{{ overallPct.toFixed(0) }}%</span>
            </div>
        </div>

        <!-- Create form -->
        <transition name="slide-down">
            <div v-if="showForm" class="form-card">
                <h3>Create New Goal</h3>
                <form @submit.prevent="addGoal" class="form-grid">
                    <div class="form-group">
                        <label>Name *</label>
                        <input v-model="newGoal.name" type="text" required class="form-input"
                            placeholder="e.g. Vacation, Emergency Fund" />
                    </div>
                    <div class="form-group">
                        <label>Target Amount *</label>
                        <input v-model.number="newGoal.target_amount" type="number" step="0.01" min="0.01" required
                            class="form-input" placeholder="0.00" />
                    </div>
                    <div class="form-group">
                        <label>Monthly % of Income</label>
                        <input v-model.number="newGoal.monthly_percentage" type="number" step="0.1" min="0" max="100"
                            class="form-input" placeholder="10" />
                    </div>
                    <div class="form-group">
                        <label>Target Date</label>
                        <input v-model="newGoal.target_date" type="date" class="form-input" />
                    </div>
                    <div class="form-group span-full">
                        <label>Notes</label>
                        <input v-model="newGoal.description" type="text" class="form-input"
                            placeholder="Optional description" />
                    </div>
                    <div class="form-actions span-full">
                        <button type="button" class="btn btn-ghost" @click="showForm = false">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>Create Goal</span>
                        </button>
                    </div>
                </form>
            </div>
        </transition>

        <!-- Empty state -->
        <div v-if="goals.length === 0 && !loading" class="empty-card">
            <div class="empty-icon">🎯</div>
            <h3>No saving goals yet</h3>
            <p>Create your first goal to start tracking your savings progress.</p>
            <button class="btn btn-primary" @click="showForm = true">+ Create Goal</button>
        </div>

        <!-- Goals grid -->
        <div class="goals-grid">
            <div v-for="goal in goals" :key="goal.id" class="goal-card">
                <!-- Card header -->
                <div class="card-top">
                    <div class="goal-icon">{{ goalEmoji(goal) }}</div>
                    <div class="goal-title-block">
                        <h4 class="goal-name">{{ goal.name }}</h4>
                        <span v-if="goal.target_date" class="deadline" :class="deadlineClass(goal)">
                            {{ deadlineLabel(goal) }}
                        </span>
                    </div>
                    <button class="btn-delete" @click="confirmDelete(goal)" title="Delete goal">✕</button>
                </div>

                <!-- Progress ring + amounts -->
                <div class="progress-section">
                    <div class="ring-wrap">
                        <svg class="ring" viewBox="0 0 80 80" style="border:none;outline:none;box-shadow:none;background:transparent">
                            <circle class="ring-bg"  cx="40" cy="40" r="34" />
                            <circle class="ring-fill" cx="40" cy="40" r="34"
                                :stroke-dasharray="ringDash(goal)"
                                :stroke="ringColor(goal)" />
                        </svg>
                        <div class="ring-label">
                            <span class="ring-pct">{{ getProgress(goal).toFixed(0) }}<span class="ring-sym">%</span></span>
                        </div>
                    </div>

                    <div class="amounts-block">
                        <div class="amount-row">
                            <span class="amt-label">Saved</span>
                            <span class="amt-val green">{{ formatMoney(goal.current_amount) }}</span>
                        </div>
                        <div class="amount-row">
                            <span class="amt-label">Target</span>
                            <span class="amt-val">{{ formatMoney(goal.target_amount) }}</span>
                        </div>
                        <div class="amount-row">
                            <span class="amt-label">Remaining</span>
                            <span class="amt-val amber">{{ formatMoney(Math.max(0, goal.target_amount - goal.current_amount)) }}</span>
                        </div>
                        <div class="amount-row" v-if="goal.monthly_percentage">
                            <span class="amt-label">Monthly %</span>
                            <span class="amt-val">{{ goal.monthly_percentage }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Linear progress bar -->
                <div class="bar-track">
                    <div class="bar-fill" :style="{ width: getProgress(goal) + '%', background: ringColor(goal) }"></div>
                </div>

                <!-- Deposit button / inline form -->
                <div v-if="depositingGoal?.id === goal.id" class="deposit-form">
                    <div class="deposit-row">
                        <input v-model.number="depositForm.amount" type="number" step="0.01" min="0.01"
                            class="form-input" placeholder="Amount" />
                        <select v-model="depositForm.account_id" class="form-input">
                            <option value="">Account</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">
                                {{ a.icon || '🏦' }} {{ a.name }} ({{ formatMoney(a.balance) }})
                            </option>
                        </select>
                    </div>
                    <div class="deposit-actions">
                        <button class="btn btn-ghost btn-sm" @click="depositingGoal = null">Cancel</button>
                        <button class="btn btn-primary btn-sm" @click="submitDeposit(goal)"
                            :disabled="depositing || !depositForm.amount || !depositForm.account_id">
                            <span v-if="depositing" class="spinner sm"></span>
                            <span v-else>Confirm Deposit</span>
                        </button>
                    </div>
                    <p v-if="depositError" class="field-error">{{ depositError }}</p>
                </div>

                <div v-else class="card-actions">
                    <button class="btn-deposit" @click="openDeposit(goal)" :disabled="getProgress(goal) >= 100">
                        <span>💰</span> Add to Savings
                    </button>
                    <button v-if="goal.target_date" class="btn-info">
                        📅 {{ formatDate(goal.target_date) }}
                    </button>
                </div>

                <!-- Completed banner -->
                <div v-if="getProgress(goal) >= 100" class="completed-banner">
                    🎉 Goal Completed!
                </div>
            </div>
        </div>

        <!-- Delete confirmation modal -->
        <div v-if="deletingGoal" class="modal-backdrop" @click.self="deletingGoal = null">
            <div class="modal-box">
                <h3>Delete "{{ deletingGoal.name }}"?</h3>
                <p>This will remove the goal and its saved progress. This cannot be undone.</p>
                <div class="modal-actions">
                    <button class="btn btn-ghost" @click="deletingGoal = null">Cancel</button>
                    <button class="btn btn-danger" @click="deleteGoal">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { api } from '../api.js';
import { useCurrency } from '../composables/useCurrency.js';

const { formatMoney } = useCurrency();

const goals        = ref([]);
const accounts     = ref([]);
const loading      = ref(true);
const saving       = ref(false);
const showForm     = ref(false);
const deletingGoal = ref(null);
const depositingGoal = ref(null);
const depositing   = ref(false);
const depositError = ref('');

const newGoal = ref({
    name: '',
    target_amount: null,
    monthly_percentage: 10,
    target_date: '',
    description: '',
});

const depositForm = ref({ amount: null, account_id: '' });

const totalSaved   = computed(() => goals.value.reduce((s, g) => s + Number(g.current_amount), 0));
const totalTarget  = computed(() => goals.value.reduce((s, g) => s + Number(g.target_amount), 0));
const overallPct   = computed(() => totalTarget.value > 0 ? Math.min(100, (totalSaved.value / totalTarget.value) * 100) : 0);

const getProgress = (goal) => {
    if (!goal.target_amount || goal.target_amount === 0) return 0;
    return Math.min(100, (Number(goal.current_amount) / Number(goal.target_amount)) * 100);
};

const ringDash = (goal) => {
    const circumference = 2 * Math.PI * 34;
    const pct = getProgress(goal) / 100;
    return `${circumference * pct} ${circumference}`;
};

const ringColor = (goal) => {
    const p = getProgress(goal);
    if (p >= 100) return '#10b981';
    if (p >= 70)  return '#3b82f6';
    if (p >= 40)  return '#f59e0b';
    return '#6366f1';
};

const goalEmoji = (goal) => {
    const name = goal.name.toLowerCase();
    if (name.includes('vacation') || name.includes('trip') || name.includes('travel')) return '✈️';
    if (name.includes('car') || name.includes('vehicle')) return '🚗';
    if (name.includes('house') || name.includes('home')) return '🏠';
    if (name.includes('emergency') || name.includes('fund')) return '🛡️';
    if (name.includes('wedding')) return '💍';
    if (name.includes('education') || name.includes('school')) return '🎓';
    if (name.includes('phone') || name.includes('laptop') || name.includes('tech')) return '💻';
    return '🎯';
};

const parseDate = (date) => new Date(String(date).slice(0, 10) + 'T00:00:00');

const formatDate = (date) => {
    if (!date) return '';
    return parseDate(date).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
};

const daysUntil = (dateStr) => {
    if (!dateStr) return null;
    return Math.ceil((parseDate(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
};

const deadlineLabel = (goal) => {
    const d = daysUntil(goal.target_date);
    if (d === null) return '';
    if (d < 0)    return `${Math.abs(d)}d overdue`;
    if (d === 0)  return 'Due today';
    if (d <= 7)   return `${d}d left`;
    if (d <= 30)  return `${d}d left`;
    const months = Math.ceil(d / 30);
    return `~${months}mo left`;
};

const deadlineClass = (goal) => {
    const d = daysUntil(goal.target_date);
    if (d === null) return '';
    if (d < 0)   return 'dl-overdue';
    if (d <= 14) return 'dl-soon';
    return 'dl-ok';
};

const openDeposit = (goal) => {
    depositingGoal.value = goal;
    depositForm.value = { amount: null, account_id: accounts.value.find(a => a.is_default)?.id || '' };
    depositError.value = '';
};

const submitDeposit = async (goal) => {
    if (!depositForm.value.amount || !depositForm.value.account_id) return;
    depositing.value = true;
    depositError.value = '';
    try {
        await api.post(`/saving-goals/${goal.id}/deposit`, depositForm.value);
        depositingGoal.value = null;
        await load();
    } catch (e) {
        depositError.value = e?.response?.data?.message || 'Error processing deposit.';
    } finally {
        depositing.value = false;
    }
};

const confirmDelete = (goal) => { deletingGoal.value = goal; };

const deleteGoal = async () => {
    try {
        await api.delete(`/saving-goals/${deletingGoal.value.id}`);
        deletingGoal.value = null;
        await load();
    } catch (e) {
        console.error(e);
    }
};

const addGoal = async () => {
    saving.value = true;
    try {
        await api.post('/saving-goals', newGoal.value);
        newGoal.value = { name: '', target_amount: null, monthly_percentage: 10, target_date: '', description: '' };
        showForm.value = false;
        await load();
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
};

const load = async () => {
    loading.value = true;
    try {
        const [g, a] = await Promise.all([api.get('/saving-goals'), api.get('/accounts')]);
        goals.value    = Array.isArray(g) ? g : (g.data || []);
        accounts.value = Array.isArray(a) ? a : (a.data || []);
    } finally {
        loading.value = false;
    }
};

onMounted(load);
</script>

<style scoped>
.page { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Header ──────────────────────────────────── */
.page-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    animation: fadeUp 0.35s ease;
}
.page-header h1 { font-size: 1.75rem; font-weight: 700; letter-spacing: -0.02em; color: var(--color-primary); margin-bottom: 0.25rem; }
.page-header p  { color: var(--text-muted); font-size: 0.9rem; }
.btn-new {
    padding: 0.55rem 1.25rem; border: 1.5px solid var(--color-primary);
    background: transparent; color: var(--color-primary);
    border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 700;
    font-family: inherit; cursor: pointer; white-space: nowrap;
    transition: all 0.2s;
}
.btn-new:hover { background: var(--color-primary); color: white; }

/* ── Summary bar ─────────────────────────────── */
.summary-bar {
    display: flex; align-items: center; gap: 1.5rem;
    background: var(--bg-elevated); border: 1px solid var(--border-default);
    border-radius: var(--radius-lg); padding: 1rem 1.5rem;
    box-shadow: var(--shadow-sm);
}
.sum-item { display: flex; flex-direction: column; gap: 0.2rem; }
.sum-label { font-size: 0.7rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.07em; }
.sum-val { font-size: 1rem; font-weight: 800; color: var(--text-primary); }
.sum-val.green { color: #059669; }
.sum-val.amber { color: #d97706; }
.sum-divider { width: 1px; height: 2.5rem; background: var(--border-default); flex-shrink: 0; }

/* ── Form card ───────────────────────────────── */
.form-card {
    background: var(--bg-elevated); border: 1px solid var(--border-default);
    border-radius: var(--radius-lg); padding: 1.5rem;
    box-shadow: var(--shadow-md);
}
.form-card h3 { font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1.25rem; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.span-full { grid-column: 1 / -1; }
.form-group { display: flex; flex-direction: column; gap: 0.35rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); letter-spacing: 0.04em; }
.form-input {
    padding: 0.65rem 0.875rem;
    background: var(--bg-surface); border: 1px solid var(--border-default);
    border-radius: var(--radius-md); color: var(--text-primary);
    font-size: 0.9rem; font-family: inherit;
    transition: border-color 0.2s;
}
.form-input:focus { outline: none; border-color: var(--color-primary); }
.form-input::placeholder { color: var(--text-muted); }
.form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; align-items: center; }

/* ── Buttons ─────────────────────────────────── */
.btn {
    padding: 0.6rem 1.25rem; border: none; border-radius: var(--radius-md);
    font-size: 0.875rem; font-weight: 700; font-family: inherit; cursor: pointer;
    transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem;
}
.btn-primary { background: var(--color-secondary); color: white; }
.btn-primary:hover:not(:disabled) { background: var(--color-secondary-dark); transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-ghost { background: transparent; border: 1px solid var(--border-default); color: var(--text-secondary); }
.btn-ghost:hover { border-color: var(--text-secondary); color: var(--text-primary); }
.btn-danger { background: #dc2626; color: white; }
.btn-danger:hover { background: #b91c1c; }
.btn-sm { padding: 0.4rem 0.875rem; font-size: 0.8rem; }

/* ── Empty state ─────────────────────────────── */
.empty-card {
    background: var(--bg-elevated); border: 1px dashed var(--border-default);
    border-radius: var(--radius-lg); padding: 3rem 2rem;
    text-align: center; display: flex; flex-direction: column;
    align-items: center; gap: 0.75rem;
}
.empty-icon { font-size: 2.5rem; }
.empty-card h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
.empty-card p  { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem; }

/* ── Goals grid ──────────────────────────────── */
.goals-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem; }

/* ── Goal card ───────────────────────────────── */
.goal-card {
    background: var(--bg-elevated); border: 1px solid var(--border-default);
    border-radius: var(--radius-lg); padding: 1.5rem;
    box-shadow: var(--shadow-md);
    display: flex; flex-direction: column; gap: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative; overflow: hidden;
}
.goal-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

.card-top { display: flex; align-items: flex-start; gap: 0.75rem; }
.goal-icon { font-size: 1.75rem; flex-shrink: 0; line-height: 1; }
.goal-title-block { flex: 1; min-width: 0; }
.goal-name { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.deadline {
    display: inline-block; font-size: 0.7rem; font-weight: 700;
    padding: 0.2rem 0.5rem; border-radius: var(--radius-full);
    letter-spacing: 0.03em;
}
.dl-ok      { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.dl-soon    { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.dl-overdue { background: #fff5f5; color: #b91c1c; border: 1px solid #fecaca; }

.btn-delete {
    width: 28px; height: 28px; border: none; background: transparent;
    color: var(--text-muted); border-radius: var(--radius-md);
    font-size: 0.8rem; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.btn-delete:hover { background: #fff5f5; color: #dc2626; }

/* ── Progress ring ───────────────────────────── */
.progress-section { display: flex; align-items: center; gap: 1.25rem; }
.ring-wrap { position: relative; width: 80px; height: 80px; flex-shrink: 0; }
.ring { width: 80px; height: 80px; transform: rotate(-90deg); border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important; display: block; overflow: visible; }
.ring-wrap { position: relative; width: 80px; height: 80px; flex-shrink: 0; border: none !important; outline: none !important; box-shadow: none !important; background: transparent !important; }
.ring-bg   { fill: none; stroke: var(--bg-surface); stroke-width: 8; }
.ring-fill {
    fill: none; stroke-width: 8; stroke-linecap: round;
    transition: stroke-dasharray 0.6s cubic-bezier(.4,0,.2,1);
}
.ring-label {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
}
.ring-pct { font-size: 1.05rem; font-weight: 800; color: var(--text-primary); }
.ring-sym { font-size: 0.65rem; }

.amounts-block { flex: 1; display: flex; flex-direction: column; gap: 0.3rem; }
.amount-row { display: flex; justify-content: space-between; font-size: 0.82rem; }
.amt-label { color: var(--text-muted); font-weight: 500; }
.amt-val { font-weight: 700; color: var(--text-primary); }
.amt-val.green { color: #059669; }
.amt-val.amber { color: #d97706; }

/* ── Bar track ───────────────────────────────── */
.bar-track { height: 6px; background: var(--bg-surface); border-radius: 999px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 999px; transition: width 0.6s cubic-bezier(.4,0,.2,1); }

/* ── Deposit inline form ─────────────────────── */
.deposit-form { display: flex; flex-direction: column; gap: 0.65rem; padding: 0.875rem;
    background: var(--bg-surface); border-radius: var(--radius-md);
    border: 1px solid var(--border-default); }
.deposit-row { display: flex; gap: 0.6rem; }
.deposit-row .form-input { flex: 1; min-width: 0; }
.deposit-actions { display: flex; justify-content: flex-end; gap: 0.5rem; }
.field-error { font-size: 0.78rem; color: #dc2626; margin: 0; }

/* ── Card actions ────────────────────────────── */
.card-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
.btn-deposit {
    flex: 1; padding: 0.6rem 1rem; background: #f0fdf9;
    border: 1.5px solid #6ee7b7; color: #065f46;
    border-radius: var(--radius-md); font-size: 0.85rem; font-weight: 700;
    font-family: inherit; cursor: pointer; display: flex; align-items: center;
    justify-content: center; gap: 0.4rem; transition: all 0.18s;
}
.btn-deposit:hover:not(:disabled) { background: #d1fae5; border-color: #34d399; }
.btn-deposit:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-info {
    padding: 0.5rem 0.75rem; background: transparent;
    border: 1px solid var(--border-default); color: var(--text-muted);
    border-radius: var(--radius-md); font-size: 0.75rem; font-weight: 600;
    font-family: inherit; cursor: default;
}

/* ── Completed banner ────────────────────────── */
.completed-banner {
    position: absolute; top: 0; right: 0;
    background: #10b981; color: white;
    font-size: 0.72rem; font-weight: 700;
    padding: 0.25rem 0.875rem; border-radius: 0 var(--radius-lg) 0 var(--radius-md);
    letter-spacing: 0.03em;
}

/* ── Delete modal ────────────────────────────── */
.modal-backdrop {
    position: fixed; inset: 0; background: rgba(0,0,0,0.45);
    display: flex; align-items: center; justify-content: center;
    z-index: 500; padding: 1rem;
}
.modal-box {
    background: var(--bg-elevated); border-radius: var(--radius-lg);
    padding: 1.75rem; max-width: 400px; width: 100%;
    box-shadow: var(--shadow-lg);
}
.modal-box h3 { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.6rem; }
.modal-box p  { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.25rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; }

/* ── Spinner ─────────────────────────────────── */
.spinner {
    width: 14px; height: 14px;
    border: 2px solid rgba(255,255,255,0.4);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    display: inline-block;
}
.spinner.sm { width: 12px; height: 12px; }

/* ── Transitions ─────────────────────────────── */
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-12px); }

@keyframes fadeUp   { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
@keyframes spin     { to { transform: rotate(360deg); } }

/* ── Responsive ──────────────────────────────── */
@media (max-width: 768px) {
    .page-header h1 { font-size: 1.4rem; }
    .summary-bar { flex-wrap: wrap; gap: 0.75rem; }
    .sum-divider { display: none; }
    .goals-grid { grid-template-columns: 1fr; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
