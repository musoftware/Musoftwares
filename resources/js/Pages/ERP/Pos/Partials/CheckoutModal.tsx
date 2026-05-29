import React, { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { Banknote, CreditCard, Wallet } from 'lucide-react';
import { ClientAutocomplete } from '@/components/ClientAutocomplete';

interface CheckoutModalProps {
    isOpen: boolean;
    onClose: () => void;
    subtotal: number;
    currency: any;
    onConfirm: (payload: { paymentMethod: string; clientId: string; discountAmount: number; isPaid: boolean }) => void;
    isProcessing: boolean;
}

const __ = (key: string) => key;

export default function CheckoutModal({ isOpen, onClose, subtotal, currency, onConfirm, isProcessing }: CheckoutModalProps) {
    const [selectedMethod, setSelectedMethod] = useState<string>('cash');
    const [clientId, setClientId] = useState('');
    const [discountAmount, setDiscountAmount] = useState<number>(0);
    const [isPaid, setIsPaid] = useState<boolean>(true);

    const paymentMethods = [
        { id: 'cash', name: __('Cash'), icon: <Banknote className="w-6 h-6 mb-2" /> },
        { id: 'card', name: __('Credit Card'), icon: <CreditCard className="w-6 h-6 mb-2" /> },
        { id: 'wallet', name: __('Wallet / Transfer'), icon: <Wallet className="w-6 h-6 mb-2" /> },
    ];

    const total = Math.max(0, subtotal - discountAmount);

    const handleConfirm = () => {
        onConfirm({
            paymentMethod: selectedMethod,
            clientId,
            discountAmount,
            isPaid
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{__('Complete Payment')}</DialogTitle>
                    <DialogDescription>
                        {__('Configure checkout options and select payment method.')}
                    </DialogDescription>
                </DialogHeader>
                
                <div className="grid gap-4 py-4">
                    <div className="flex flex-col space-y-1.5">
                        <Label>{__('Client (Optional)')}</Label>
                        <ClientAutocomplete value={clientId} onChange={setClientId} />
                    </div>

                    <div className="flex flex-col space-y-1.5">
                        <Label>{__('Discount Amount')}</Label>
                        <Input 
                            type="number" 
                            min="0" 
                            value={discountAmount} 
                            onChange={(e) => setDiscountAmount(Number(e.target.value))} 
                        />
                    </div>

                    <div className="flex items-center space-x-2 pt-2">
                        <Switch id="is-paid" checked={isPaid} onCheckedChange={setIsPaid} />
                        <Label htmlFor="is-paid">{__('Mark as Paid')}</Label>
                    </div>
                </div>

                <div className="py-4 flex flex-col items-center border-y border-dashed my-2">
                    <span className="text-sm text-gray-500 mb-1">{__('Amount Due')}</span>
                    <span className="text-4xl font-bold text-gray-900">
                        {formatCurrency(total, currency)}
                    </span>
                    {discountAmount > 0 && (
                        <span className="text-sm text-emerald-600 mt-1">
                            {__('Discount applied: ')} {formatCurrency(discountAmount, currency)}
                        </span>
                    )}
                </div>

                {isPaid && (
                    <div className="grid grid-cols-3 gap-3 mb-4">
                        {paymentMethods.map((method) => (
                            <button
                                key={method.id}
                                className={`flex flex-col items-center justify-center p-4 border rounded-xl transition-all ${
                                    selectedMethod === method.id 
                                        ? 'border-primary bg-primary/5 text-primary ring-1 ring-primary' 
                                        : 'border-gray-200 hover:border-gray-300 text-gray-600 hover:bg-gray-50'
                                }`}
                                onClick={() => setSelectedMethod(method.id)}
                                type="button"
                            >
                                {method.icon}
                                <span className="text-sm font-medium">{method.name}</span>
                            </button>
                        ))}
                    </div>
                )}

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isProcessing}>
                        {__('Cancel')}
                    </Button>
                    <Button onClick={handleConfirm} disabled={isProcessing}>
                        {isProcessing ? __('Processing...') : __('Confirm Checkout')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
