<template>
    <div class="page">
        <div class="page-header">
            <div>
                <h1 class="page-title">Transactions</h1>
                <p class="page-subtitle">{{ pagination.total || 0 }} records</p>
            </div>
            <div class="export-btns">
                <button class="btn btn-outline" :disabled="exporting" @click="exportCsv">
                    <span v-if="exporting === 'csv'" class="spinner spinner-dark"></span>
                    <span v-else>↓ CSV</span>
                </button>
                <button class="btn btn-outline btn-pdf" :disabled="exporting" @click="exportPdf">
                    <span v-if="exporting === 'pdf'" class="spinner spinner-red"></span>
                    <span v-else>↓ PDF</span>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-grid">
                <div class="form-group">
                    <label>From</label>
                    <input v-model="filters.date_from" type="date" class="form-input" />
                </div>
                <div class="form-group">
                    <label>To</label>
                    <input v-model="filters.date_to" type="date" class="form-input" />
                </div>
                <div class="form-group">
                    <label>Account</label>
                    <select v-model="filters.account_id" class="form-input">
                        <option value="">All accounts</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.icon || '🏦' }} {{ a.name }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select v-model="filters.category_id" class="form-input">
                        <option value="">All categories</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select v-model="filters.type" class="form-input">
                        <option value="">All types</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                        <option value="transfer">Transfer</option>
                        <option value="adjustment">Adjustment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keyword</label>
                    <input v-model="filters.search" type="text" class="form-input" placeholder="Search description…" />
                </div>
            </div>
            <div class="filter-actions">
                <button class="btn btn-secondary" @click="resetFilters">Clear</button>
                <button class="btn btn-primary" @click="loadTransactions(1)">Search</button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-rows">
            <div v-for="i in 8" :key="i" class="skeleton-row"></div>
        </div>

        <!-- Table (desktop) -->
        <div v-else-if="transactions.length" class="table-wrapper">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Account</th>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tx in transactions" :key="tx.id">
                        <td class="date-cell">{{ formatDate(tx.date) }}</td>
                        <td><span :class="['type-badge', `type-${tx.type}`]">{{ tx.type }}</span></td>
                        <td>
                            <span v-if="tx.category">{{ tx.category.icon }} {{ tx.category.name }}</span>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td>
                            <span v-if="tx.account">{{ tx.account.icon || '🏦' }} {{ tx.account.name }}</span>
                            <span v-if="tx.to_account"> → {{ tx.to_account.icon || '🏦' }} {{ tx.to_account.name }}</span>
                        </td>
                        <td class="desc-cell">
                            <span :title="tx.description">{{ tx.description || '—' }}</span>
                            <span v-if="tx.is_recurring" class="recur-icon" title="Recurring">🔁</span>
                        </td>
                        <td class="text-right">
                            <span :class="amountClass(tx)">{{ amountSign(tx) }}{{ formatMoney(tx.amount) }}</span>
                        </td>
                        <td class="actions-cell">
                            <button class="icon-btn" title="Edit" @click="editTx(tx)">✏️</button>
                            <button class="icon-btn" title="Delete" @click="confirmDelete(tx)">🗑️</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <button class="pag-btn" :disabled="pagination.current_page <= 1" @click="loadTransactions(pagination.current_page - 1)">← Prev</button>
                <span class="pag-info">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                <button class="pag-btn" :disabled="pagination.current_page >= pagination.last_page" @click="loadTransactions(pagination.current_page + 1)">Next →</button>
            </div>
        </div>

        <!-- Empty -->
        <div v-else class="empty-state">
            <div class="empty-icon">↕️</div>
            <h3>No transactions found</h3>
            <p>Try clearing the filters or add your first transaction.</p>
        </div>

        <!-- Mobile cards -->
        <div class="mobile-cards" v-if="!loading && transactions.length">
            <div v-for="group in groupedTransactions" :key="group.dateKey" class="day-group">
                <div class="day-header">{{ group.label }}</div>
                <div class="day-cards">
                    <div v-for="tx in group.transactions" :key="`m-${tx.id}`" class="mobile-card">
                        <div class="mobile-card-left">
                            <div class="mobile-card-icon">
                                <span v-if="tx.category">{{ tx.category.icon }}</span>
                                <span v-else>💳</span>
                            </div>
                            <div class="mobile-card-info">
                                <div class="mobile-card-desc">{{ tx.description || tx.category?.name || '—' }}</div>
                                <div class="mobile-card-meta">
                                    <span v-if="tx.category">{{ tx.category.name }}</span>
                                    <span v-else-if="tx.account">{{ tx.account.name }}</span>
                                    <span v-if="tx.date"> · {{ formatTime(tx.date) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mobile-card-right">
                            <span :class="amountClass(tx)" class="mobile-amount">{{ amountSign(tx) }}{{ formatMoney(tx.amount) }}</span>
                            <div class="mobile-card-actions">
                                <button class="icon-btn" @click="editTx(tx)">✏️</button>
                                <button class="icon-btn" @click="confirmDelete(tx)">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pagination">
                <button class="pag-btn" :disabled="pagination.current_page <= 1" @click="loadTransactions(pagination.current_page - 1)">← Prev</button>
                <span class="pag-info">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                <button class="pag-btn" :disabled="pagination.current_page >= pagination.last_page" @click="loadTransactions(pagination.current_page + 1)">Next →</button>
            </div>
        </div>

        <!-- FAB -->
        <button class="fab" @click="openModal()" title="New Transaction">+</button>

        <!-- Transaction modal -->
        <TransactionModal
            v-if="showModal"
            :transaction="editingTx"
            :accounts="accounts"
            :categories="categories"
            @close="showModal = false"
            @saved="onSaved"
        />

        <!-- Delete confirm -->
        <div v-if="deletingTx" class="modal-backdrop" @click.self="deletingTx = null">
            <div class="modal-card modal-sm">
                <div class="modal-header">
                    <h2>Delete Transaction</h2>
                    <button class="btn-close" @click="deletingTx = null">✕</button>
                </div>
                <div class="modal-form">
                    <p class="text-muted">This will reverse the balance effect on the account. Cannot be undone.</p>
                    <p v-if="deleteError" class="error-msg">{{ deleteError }}</p>
                    <div class="modal-actions">
                        <button class="btn btn-secondary" @click="deletingTx = null">Cancel</button>
                        <button class="btn btn-danger" :disabled="deleting" @click="doDelete">
                            <span v-if="deleting" class="spinner"></span>
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
import TransactionModal from './TransactionModal.vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const transactions = ref([]);
const accounts     = ref([]);
const categories   = ref([]);
const loading      = ref(true);
const exporting    = ref(null); // 'csv' | 'pdf' | null
const showModal    = ref(false);
const editingTx    = ref(null);
const deletingTx   = ref(null);
const deleteError  = ref('');
const deleting     = ref(false);

const pagination = ref({ current_page: 1, last_page: 1, total: 0 });

const filters = ref({
    date_from:   '',
    date_to:     '',
    account_id:  '',
    category_id: '',
    type:        '',
    search:      '',
});

const resetFilters = () => {
    filters.value = { date_from:'', date_to:'', account_id:'', category_id:'', type:'', search:'' };
    loadTransactions(1);
};

const loadTransactions = async (page = 1) => {
    loading.value = true;
    try {
        const res = await api.get('/transactions', { ...filters.value, page });
        transactions.value = res.data || [];
        pagination.value = {
            current_page: res.current_page,
            last_page:    res.last_page,
            total:        res.total,
        };
    } finally {
        loading.value = false;
    }
};

const loadMeta = async () => {
    const [accs, cats] = await Promise.all([
        api.get('/accounts'),
        api.get('/categories'),
    ]);
    accounts.value  = Array.isArray(accs) ? accs : (accs.data || []);
    categories.value = Array.isArray(cats) ? cats : (cats.data || []);
};

const exportCsv = async () => {
    exporting.value = 'csv';
    try {
        await api.download('/transactions/export', filters.value);
    } finally {
        exporting.value = null;
    }
};

const exportPdf = async () => {
    exporting.value = 'pdf';
    try {
        // Fetch all records (no pagination) reusing the export endpoint data via CSV
        // Instead, fetch all pages or use a large per_page param
        const res = await api.get('/transactions', { ...filters.value, per_page: 1000, page: 1 });
        const rows = res.data || [];

        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const W = doc.internal.pageSize.getWidth();
        const primary   = [9, 20, 38];      // #091426
        const secondary = [16, 185, 129];    // #10b981
        const muted     = [117, 119, 125];   // #75777d
        const light     = [247, 249, 251];   // --bg-base

        // ── Header bar ──────────────────────────────────────────────────
        doc.setFillColor(...primary);
        doc.rect(0, 0, W, 22, 'F');

        doc.setTextColor(255, 255, 255);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('LifeOrganizer', 12, 13);

        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(180, 200, 220);
        doc.text('Transaction Report', 12, 19);

        const now = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        doc.setTextColor(180, 200, 220);
        doc.text(now, W - 12, 19, { align: 'right' });

        // ── Date range banner ────────────────────────────────────────────
        let yPos = 28;
        const dateFrom = filters.value.date_from;
        const dateTo   = filters.value.date_to;
        if (dateFrom || dateTo) {
            const fmtDate = (d) => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : null;
            const rangeLabel = dateFrom && dateTo
                ? `Period: ${fmtDate(dateFrom)}  –  ${fmtDate(dateTo)}`
                : dateFrom ? `From: ${fmtDate(dateFrom)}` : `Until: ${fmtDate(dateTo)}`;

            doc.setFillColor(239, 246, 255);
            doc.roundedRect(12, yPos, W - 24, 10, 2, 2, 'F');
            doc.setDrawColor(191, 219, 254);
            doc.roundedRect(12, yPos, W - 24, 10, 2, 2, 'S');
            doc.setFontSize(8);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(30, 64, 175);
            doc.text(rangeLabel, (W / 2), yPos + 6.5, { align: 'center' });
            yPos += 14;
        }

        // ── Active filters summary ───────────────────────────────────────
        let filterParts = [];
        if (filters.value.type)   filterParts.push(`Type: ${filters.value.type}`);
        if (filters.value.search) filterParts.push(`Keyword: "${filters.value.search}"`);

        if (filterParts.length) {
            doc.setFontSize(8);
            doc.setTextColor(...muted);
            doc.setFont('helvetica', 'normal');
            doc.text('Filters: ' + filterParts.join('  ·  '), 12, yPos);
            yPos += 6;
        }

        // ── Summary pills ────────────────────────────────────────────────
        const totalIncome  = rows.filter(r => r.type === 'income').reduce((s, r) => s + Number(r.amount), 0);
        const totalExpense = rows.filter(r => r.type === 'expense').reduce((s, r) => s + Number(r.amount), 0);
        const net = totalIncome - totalExpense;

        const pills = [
            { label: 'Total Income',   value: formatMoney(totalIncome),  color: [6, 95, 70] },
            { label: 'Total Expenses', value: formatMoney(totalExpense), color: [153, 27, 27] },
            { label: 'Net',            value: formatMoney(net),          color: net >= 0 ? [6, 95, 70] : [153, 27, 27] },
            { label: 'Records',        value: String(rows.length),                                                        color: [...primary] },
        ];

        let px = 12;
        pills.forEach(p => {
            const pillW = 58;
            doc.setFillColor(...light);
            doc.roundedRect(px, yPos, pillW, 12, 2, 2, 'F');
            doc.setDrawColor(226, 232, 240);
            doc.roundedRect(px, yPos, pillW, 12, 2, 2, 'S');

            doc.setFontSize(7);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(...muted);
            doc.text(p.label.toUpperCase(), px + 4, yPos + 4.5);

            doc.setFontSize(9);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...p.color);
            doc.text(p.value, px + 4, yPos + 10);

            px += pillW + 4;
        });

        yPos += 18;

        // ── Table ────────────────────────────────────────────────────────
        const typeColors = {
            income:     { text: [6, 95, 70],   bg: [236, 253, 245] },
            expense:    { text: [153, 27, 27],  bg: [255, 245, 245] },
            transfer:   { text: [30, 64, 175],  bg: [239, 246, 255] },
            adjustment: { text: [91, 33, 182],  bg: [245, 243, 255] },
        };

        autoTable(doc, {
            startY: yPos,
            margin: { left: 12, right: 12 },
            head: [['Date', 'Type', 'Category', 'Account', 'Description', 'Amount']],
            body: rows.map(r => [
                (() => { const [y,m,d] = String(r.date).slice(0,10).split('-').map(Number); return new Date(y,m-1,d).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); })(),
                r.type,
                r.category ? r.category.name : '—',
                r.account?.name || '—',
                r.description || '—',
                (r.type === 'income' ? '+' : r.type === 'expense' ? '-' : '') + formatMoney(r.amount),
            ]),
            headStyles: {
                fillColor: primary,
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                fontSize: 8,
                cellPadding: { top: 4, bottom: 4, left: 4, right: 4 },
            },
            alternateRowStyles: { fillColor: light },
            bodyStyles: {
                fontSize: 7.5,
                textColor: [25, 28, 30],
                cellPadding: { top: 3, bottom: 3, left: 4, right: 4 },
            },
            columnStyles: {
                0: { cellWidth: 28 },
                1: { cellWidth: 24 },
                2: { cellWidth: 36 },
                3: { cellWidth: 36 },
                4: { cellWidth: 'auto' },
                5: { cellWidth: 30, halign: 'right', fontStyle: 'bold' },
            },
            didDrawCell(data) {
                // Color "Type" cell text
                if (data.section === 'body' && data.column.index === 1) {
                    const type = data.cell.raw;
                    const scheme = typeColors[type];
                    if (scheme) {
                        const { x, y, width, height } = data.cell;
                        doc.setFillColor(...scheme.bg);
                        doc.roundedRect(x + 2, y + 1.5, width - 4, height - 3, 1.5, 1.5, 'F');
                        doc.setTextColor(...scheme.text);
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(7);
                        doc.text(type, x + width / 2, y + height / 2 + 1, { align: 'center' });
                    }
                }
                // Color Amount cell
                if (data.section === 'body' && data.column.index === 5) {
                    const raw = String(data.cell.raw);
                    if (raw.startsWith('+')) doc.setTextColor(6, 95, 70);
                    else if (raw.startsWith('-')) doc.setTextColor(153, 27, 27);
                    else doc.setTextColor(30, 64, 175);
                }
            },
            // Footer per page
            didDrawPage(data) {
                const pageH = doc.internal.pageSize.getHeight();
                doc.setFillColor(...primary);
                doc.rect(0, pageH - 8, W, 8, 'F');
                doc.setTextColor(180, 200, 220);
                doc.setFontSize(7);
                doc.setFont('helvetica', 'normal');
                doc.text(
                    `Page ${data.pageNumber}  ·  LifeOrganizer  ·  Generated ${now}`,
                    W / 2, pageH - 3, { align: 'center' }
                );
            },
        });

        // ── Accent line under header ─────────────────────────────────────
        doc.setDrawColor(...secondary);
        doc.setLineWidth(0.8);
        doc.line(0, 22, W, 22);

        const dateStr = new Date().toISOString().slice(0, 10);
        doc.save(`transactions-${dateStr}.pdf`);
    } finally {
        exporting.value = null;
    }
};

const openModal  = ()    => { editingTx.value = null; showModal.value = true; };
const editTx     = (tx)  => { editingTx.value = tx;   showModal.value = true; };
const confirmDelete = (tx) => { deletingTx.value = tx; deleteError.value = ''; };

const doDelete = async () => {
    deleting.value = true;
    deleteError.value = '';
    try {
        await api.delete(`/transactions/${deletingTx.value.id}`);
        deletingTx.value = null;
        await loadTransactions(pagination.value.current_page);
    } catch (e) {
        deleteError.value = e.message || 'Delete failed';
    } finally {
        deleting.value = false;
    }
};

const onSaved = async () => {
    showModal.value = false;
    await loadTransactions(pagination.value.current_page);
};

const formatDate  = (d) => {
    if (!d) return '—';
    // Take only YYYY-MM-DD to avoid timezone issues with full ISO strings
    const clean = String(d).slice(0, 10);
    const [y, m, day] = clean.split('-').map(Number);
    return new Date(y, m - 1, day).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};
const { formatMoney, currency } = useCurrency();

const amountClass = (tx) => {
    if (tx.type === 'income')   return 'amount-income';
    if (tx.type === 'expense')  return 'amount-expense';
    if (tx.type === 'transfer') return 'amount-transfer';
    return 'amount-adjustment';
};

const amountSign = (tx) => {
    if (tx.type === 'income')  return '+';
    if (tx.type === 'expense') return '-';
    return '';
};

onMounted(async () => {
    await loadMeta();
    await loadTransactions();
});
</script>

<style scoped>
.page { padding: 1.5rem; max-width: 1200px; margin: 0 auto; }

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.page-title { font-size: 1.5rem; font-weight: 800; color: var(--color-primary); margin: 0; }
.page-subtitle { font-size: 0.875rem; color: var(--text-muted); margin: 0.25rem 0 0; }

.filter-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: var(--shadow-md);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.875rem;
    margin-bottom: 0.875rem;
}

.filter-actions { display: flex; gap: 0.5rem; justify-content: flex-end; }

.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-group label { font-size: 0.72rem; font-weight: 600; color: var(--text-secondary); }

.form-input {
    padding: 0.55rem 0.75rem;
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 0.875rem;
    font-family: inherit;
    transition: border-color 0.2s;
}

.form-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(9,20,38,0.08); }

/* Table */
.table-wrapper {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.tx-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.tx-table th {
    background: var(--bg-surface);
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.06em;
    border-bottom: 1px solid var(--border-default);
}

.tx-table td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-primary);
    vertical-align: middle;
}

.tx-table tr:last-child td { border-bottom: none; }
.tx-table tr:hover td { background: var(--bg-surface); }

.date-cell { white-space: nowrap; color: var(--text-secondary); font-size: 0.8rem; }

.desc-cell {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.recur-icon { margin-left: 0.35rem; }

.type-badge {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: capitalize;
}

.type-income     { background: #ecfdf5; color: #065f46; }
.type-expense    { background: #fff5f5; color: #991b1b; }
.type-transfer   { background: #eff6ff; color: #1e40af; }
.type-adjustment { background: #f5f3ff; color: #5b21b6; }

.amount-income     { color: #059669; font-weight: 700; }
.amount-expense    { color: #dc2626; font-weight: 700; }
.amount-transfer   { color: #2563eb; font-weight: 700; }
.amount-adjustment { color: #7c3aed; font-weight: 700; }

.text-right { text-align: right; }
.text-muted { color: var(--text-muted); }

.actions-cell { white-space: nowrap; text-align: right; }
.icon-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: var(--radius-sm);
    font-size: 0.85rem;
    transition: background 0.15s;
}
.icon-btn:hover { background: var(--bg-surface); }

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    border-top: 1px solid var(--border-default);
}

.pag-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-elevated);
    color: var(--text-secondary);
    font-size: 0.85rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.15s;
}

.pag-btn:hover:not(:disabled) { background: var(--bg-surface); }
.pag-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pag-info { font-size: 0.85rem; color: var(--text-muted); }

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

/* Empty */
.empty-state { text-align: center; padding: 4rem 2rem; color: var(--text-muted); }
.empty-icon  { font-size: 2.5rem; margin-bottom: 1rem; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }

/* Loading */
.loading-rows { display: flex; flex-direction: column; gap: 0.5rem; }
.skeleton-row { height: 52px; background: var(--bg-surface); border-radius: var(--radius-md); animation: pulse 1.5s ease-in-out infinite; }

/* Mobile cards — hidden on desktop, shown on mobile */
.mobile-cards { display: none; }

@media (max-width: 768px) {
    .page { padding: 1rem; }
    .table-wrapper { display: none; }
    .mobile-cards { display: flex; flex-direction: column; gap: 0.75rem; }

    .mobile-card {
        background: var(--bg-elevated);
        border: 1px solid var(--border-default);
        border-radius: var(--radius-lg);
        padding: 1rem;
        box-shadow: var(--shadow-md);
    }

    .mobile-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .mobile-amount { font-size: 1.1rem; font-weight: 800; }

    .mobile-card-desc { font-weight: 600; color: var(--text-primary); margin-bottom: 0.4rem; font-size: 0.9rem; }

    .mobile-card-meta { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem; }

    .mobile-card-actions { display: flex; gap: 0.5rem; }

    .filter-grid { grid-template-columns: 1fr; }

    .fab { bottom: 5rem; }
}

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
    max-width: 420px;
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

.modal-actions { display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 0.5rem; border-top: 1px solid var(--border-default); }

.error-msg {
    background: #fff5f5;
    border: 1px solid #fca5a5;
    color: #dc2626;
    padding: 0.65rem 1rem;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
}

.btn {
    min-height: 40px;
    padding: 0.55rem 1.25rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
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

.export-btns { display: flex; gap: 0.5rem; }

.btn-outline { background: transparent; color: var(--color-primary); border: 1.5px solid var(--color-primary); }
.btn-outline:hover:not(:disabled) { background: var(--color-primary); color: white; }
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-pdf { color: #dc2626; border-color: #dc2626; }
.btn-pdf:hover:not(:disabled) { background: #dc2626; color: white; }

.spinner-red {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(220, 38, 38, 0.25);
    border-top-color: #dc2626;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.btn-danger { background: #dc2626; color: white; }
.btn-danger:hover:not(:disabled) { background: #b91c1c; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

.action-btn {
    flex: 1;
    padding: 0.45rem;
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

.spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.spinner-dark {
    border: 2px solid rgba(9,20,38,0.2);
    border-top-color: var(--color-primary);
}

@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
