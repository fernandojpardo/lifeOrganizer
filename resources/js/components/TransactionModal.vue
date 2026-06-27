<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <div class="modal-card">
            <div class="modal-header">
                <h2>{{ editMode ? 'Edit Transaction' : 'New Transaction' }}</h2>
                <button class="btn-close" @click="$emit('close')">✕</button>
            </div>

            <form @submit.prevent="handleSubmit" class="modal-form">
                <!-- Type selector -->
                <div class="type-tabs">
                    <button
                        v-for="t in types"
                        :key="t.value"
                        type="button"
                        :class="['type-tab', { active: form.type === t.value }]"
                        @click="form.type = t.value"
                    >
                        {{ t.icon }} {{ t.label }}
                    </button>
                </div>

                <div class="form-row">
                    <!-- Amount -->
                    <div class="form-group">
                        <label>Amount *</label>
                        <input v-model.number="form.amount" type="number" step="0.01" min="0.01"
                            class="form-input" placeholder="0.00" required />
                    </div>

                    <!-- Date -->
                    <div class="form-group">
                        <label>Date *</label>
                        <input v-model="form.date" type="date" class="form-input" required />
                    </div>
                </div>

                <div class="form-row">
                    <!-- Account -->
                    <div class="form-group">
                        <label>{{ form.type === 'transfer' ? 'From Account' : 'Account' }} *</label>
                        <select v-model="form.account_id" class="form-input" required>
                            <option value="">Select account</option>
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                {{ acc.icon || '🏦' }} {{ acc.name }} ({{ formatMoney(acc.balance) }} {{ acc.currency }})
                            </option>
                        </select>
                    </div>

                    <!-- To Account (transfer only) -->
                    <div class="form-group" v-if="form.type === 'transfer'">
                        <label>To Account *</label>
                        <select v-model="form.to_account_id" class="form-input" required>
                            <option value="">Select account</option>
                            <option v-for="acc in accounts.filter(a => a.id !== form.account_id)" :key="acc.id" :value="acc.id">
                                {{ acc.icon || '🏦' }} {{ acc.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="form-group" v-else>
                        <label>Category</label>
                        <div class="category-field">
                            <select v-model="form.category_id" class="form-input">
                                <option value="">No category</option>
                                <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                                    {{ cat.icon }} {{ cat.name }}
                                </option>
                            </select>
                            <span v-if="suggestion && !form.category_id" class="suggestion-badge" @click="applySuggestion">
                                💡 {{ suggestion.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label>Description</label>
                    <input v-model="form.description" type="text" class="form-input"
                        placeholder="What was this for?"
                        @blur="fetchSuggestion" />
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label>Notes</label>
                    <textarea v-model="form.notes" class="form-input form-textarea"
                        placeholder="Optional additional notes" rows="2"></textarea>
                </div>

                <!-- Recurring -->
                <label class="checkbox-label">
                    <input type="checkbox" v-model="form.is_recurring" />
                    <span>Recurring transaction</span>
                </label>

                <p v-if="error" class="error-msg">{{ error }}</p>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span v-if="saving" class="spinner"></span>
                        <span v-else>{{ editMode ? 'Save Changes' : 'Add Transaction' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { api } from '../api.js';
import { useCurrency } from '../composables/useCurrency.js';

const props = defineProps({
    transaction: { type: Object, default: null },
    prefill:     { type: Object, default: null },
    accounts:    { type: Array, default: () => [] },
    categories:  { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const editMode = computed(() => !!props.transaction);

const types = [
    { value: 'income',     label: 'Income',     icon: '↑' },
    { value: 'expense',    label: 'Expense',     icon: '↓' },
    { value: 'transfer',   label: 'Transfer',    icon: '↔' },
    { value: 'adjustment', label: 'Adjustment',  icon: '⚖' },
];

const defaultForm = () => ({
    type:          'expense',
    amount:        null,
    date:          new Date().toISOString().split('T')[0],
    account_id:    props.accounts.find(a => a.is_default)?.id || props.accounts[0]?.id || '',
    to_account_id: '',
    category_id:   '',
    description:   '',
    notes:         '',
    is_recurring:  false,
});

const form       = ref(defaultForm());
const saving     = ref(false);
const error      = ref('');
const suggestion = ref(null);

const filteredCategories = computed(() => {
    if (!props.categories.length) return [];
    if (form.value.type === 'income') {
        return props.categories.filter(c => c.type === 'income' || c.type === 'both');
    }
    return props.categories.filter(c => c.type === 'expense' || c.type === 'both');
});

watch(() => form.value.type, () => {
    form.value.category_id   = '';
    form.value.to_account_id = '';
    suggestion.value = null;
});

onMounted(() => {
    if (props.transaction) {
        form.value = {
            type:          props.transaction.type,
            amount:        props.transaction.amount,
            date:          props.transaction.date,
            account_id:    props.transaction.account_id,
            to_account_id: props.transaction.to_account_id || '',
            category_id:   props.transaction.category_id || '',
            description:   props.transaction.description || '',
            notes:         props.transaction.notes || '',
            is_recurring:  props.transaction.is_recurring,
        };
    } else if (props.prefill) {
        form.value = { ...defaultForm(), ...props.prefill };
    }
});

const fetchSuggestion = async () => {
    if (!form.value.description || form.value.category_id || form.value.type === 'transfer') return;
    try {
        const result = await api.get('/categories/suggest', { description: form.value.description });
        suggestion.value = result;
    } catch { suggestion.value = null; }
};

const applySuggestion = () => {
    if (suggestion.value) {
        form.value.category_id = suggestion.value.category_id;
        suggestion.value = null;
    }
};

const { formatMoney } = useCurrency();

const handleSubmit = async () => {
    error.value = '';
    saving.value = true;

    const payload = { ...form.value };
    if (!payload.to_account_id) delete payload.to_account_id;
    if (!payload.category_id)   delete payload.category_id;
    if (!payload.notes)         delete payload.notes;

    try {
        if (editMode.value) {
            await api.put(`/transactions/${props.transaction.id}`, payload);
        } else {
            await api.post('/transactions', payload);
        }
        emit('saved');
    } catch (e) {
        error.value = e.message || 'An error occurred';
    } finally {
        saving.value = false;
    }
};
</script>

<style scoped>
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
    max-width: 560px;
    max-height: 90vh;
    overflow-y: auto;
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

.modal-header h2 {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-primary);
}

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
.btn-close:hover { background: var(--bg-surface); color: var(--text-primary); }

.modal-form {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.125rem;
}

.type-tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
}

.type-tab {
    padding: 0.5rem 0.25rem;
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-surface);
    color: var(--text-secondary);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    font-family: inherit;
    text-align: center;
}

.type-tab.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.form-group label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-secondary);
    letter-spacing: 0.04em;
}

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

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(9, 20, 38, 0.08);
}

.form-textarea { resize: vertical; min-height: 64px; }

.category-field { position: relative; }

.suggestion-badge {
    display: inline-flex;
    align-items: center;
    margin-top: 0.35rem;
    padding: 0.25rem 0.625rem;
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 600;
    color: #065f46;
    cursor: pointer;
    transition: background 0.15s;
}

.suggestion-badge:hover { background: #d1fae5; }

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
    cursor: pointer;
}

.error-msg {
    background: #fff5f5;
    border: 1px solid #fca5a5;
    color: #dc2626;
    padding: 0.65rem 1rem;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    padding-top: 0.5rem;
    border-top: 1px solid var(--border-default);
}

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

.btn-primary {
    background: var(--color-secondary);
    color: white;
}
.btn-primary:hover:not(:disabled) { background: var(--color-secondary-dark); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
    background: var(--bg-surface);
    color: var(--text-secondary);
    border: 1px solid var(--border-default);
}
.btn-secondary:hover { background: var(--bg-card-hover); }

.spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.35);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@media (max-width: 480px) {
    .type-tabs { grid-template-columns: repeat(2, 1fr); }
    .form-row { grid-template-columns: 1fr; }
    .modal-card { border-radius: var(--radius-lg); }
    .modal-form { padding: 1.25rem; }
}
</style>
