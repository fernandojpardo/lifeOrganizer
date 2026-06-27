import { ref, computed } from 'vue';

export const CURRENCIES = [
    { code: 'USD', symbol: '$',  name: 'US Dollar' },
    { code: 'EUR', symbol: '€',  name: 'Euro' },
    { code: 'GBP', symbol: '£',  name: 'British Pound' },
    { code: 'MXN', symbol: '$',  name: 'Mexican Peso' },
    { code: 'CAD', symbol: '$',  name: 'Canadian Dollar' },
    { code: 'BRL', symbol: 'R$', name: 'Brazilian Real' },
    { code: 'ARS', symbol: '$',  name: 'Argentine Peso' },
    { code: 'CLP', symbol: '$',  name: 'Chilean Peso' },
    { code: 'COP', symbol: '$',  name: 'Colombian Peso' },
    { code: 'JPY', symbol: '¥',  name: 'Japanese Yen' },
    { code: 'CNY', symbol: '¥',  name: 'Chinese Yuan' },
    { code: 'INR', symbol: '₹',  name: 'Indian Rupee' },
    { code: 'CHF', symbol: 'Fr', name: 'Swiss Franc' },
    { code: 'AUD', symbol: '$',  name: 'Australian Dollar' },
];

const STORAGE_KEY = 'life_organizer_currency';

const selectedCode = ref(localStorage.getItem(STORAGE_KEY) || 'USD');

export function useCurrency() {
    const currency = computed(() => CURRENCIES.find(c => c.code === selectedCode.value) || CURRENCIES[0]);

    const setCurrency = (code) => {
        selectedCode.value = code;
        localStorage.setItem(STORAGE_KEY, code);
    };

    const formatMoney = (value) => {
        const num = Number(value || 0);
        try {
            return num.toLocaleString('en-US', {
                style: 'currency',
                currency: selectedCode.value,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        } catch {
            const sym = currency.value.symbol;
            return `${sym}${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    };

    return { currency, selectedCode, setCurrency, formatMoney, CURRENCIES };
}
