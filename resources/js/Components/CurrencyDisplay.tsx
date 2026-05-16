interface CurrencyDisplayProps {
    amount: number | string;
    currency?: string;
    className?: string;
}

export default function CurrencyDisplay({
    amount,
    currency = 'USD',
    className = '',
}: CurrencyDisplayProps) {
    const numericAmount =
        typeof amount === 'string' ? parseFloat(amount) : amount;

    if (isNaN(numericAmount)) {
        return <span className={className}>Invalid Amount</span>;
    }

    const formatted = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(numericAmount);

    return <span className={`font-medium ${className}`}>{formatted}</span>;
}
