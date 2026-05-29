import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/utils/currency';
import { Banknote, CreditCard, Wallet } from 'lucide-react';

interface CheckoutModalProps {
    isOpen: boolean;
    onClose: () => void;
    subtotal: number;
    currency: any;
    onConfirm: (paymentMethod: string) => void;
    isProcessing: boolean;
}

const __ = (key: string) => key;

export default function CheckoutModal({ isOpen, onClose, subtotal, currency, onConfirm, isProcessing }: CheckoutModalProps) {
    const [selectedMethod, setSelectedMethod] = React.useState<string>('cash');

    const paymentMethods = [
        { id: 'cash', name: __('Cash'), icon: <Banknote className="w-6 h-6 mb-2" /> },
        { id: 'card', name: __('Credit Card'), icon: <CreditCard className="w-6 h-6 mb-2" /> },
        { id: 'wallet', name: __('Wallet / Transfer'), icon: <Wallet className="w-6 h-6 mb-2" /> },
    ];

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{__('Complete Payment')}</DialogTitle>
                    <DialogDescription>
                        {__('Select a payment method to complete the checkout.')}
                    </DialogDescription>
                </DialogHeader>
                
                <div className="py-6 flex flex-col items-center border-y border-dashed my-2">
                    <span className="text-sm text-gray-500 mb-1">{__('Amount Due')}</span>
                    <span className="text-4xl font-bold text-gray-900">
                        {formatCurrency(subtotal, currency)}
                    </span>
                </div>

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

                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isProcessing}>
                        {__('Cancel')}
                    </Button>
                    <Button onClick={() => onConfirm(selectedMethod)} disabled={isProcessing}>
                        {isProcessing ? __('Processing...') : __('Confirm Payment')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
