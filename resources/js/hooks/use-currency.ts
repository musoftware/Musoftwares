import { formatCurrency } from '../lib/currency';

export function useCurrency(defaultCurrency = 'USD') {
    return {
        format: (amount: number, currency: string = defaultCurrency) =>
            formatCurrency(amount, currency),
    };
}
