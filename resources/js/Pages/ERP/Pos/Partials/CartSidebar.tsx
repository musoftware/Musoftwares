import React from 'react';
import { Button } from '@/components/ui/button';
import { Trash2, Plus, Minus } from 'lucide-react';
import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface CartItem {
    product_id: number;
    name: string;
    unit_price: number;
    quantity: number;
    stock: number;
}

interface CartSidebarProps {
    cart: CartItem[];
    updateQuantity: (productId: number, quantity: number) => void;
    removeFromCart: (productId: number) => void;
    onCheckout: () => void;
    currency: any;
}


export default function CartSidebar({ cart, updateQuantity, removeFromCart, onCheckout, currency }: CartSidebarProps) {
    const subtotal = cart.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);

    return (
        <div className="w-full h-full flex flex-col bg-white border-s">
            <div className="p-4 border-b">
                <h2 className="text-lg font-semibold">{__('general.current_order')}</h2>
            </div>
            
            <div className="flex-1 overflow-y-auto p-4 space-y-4">
                {cart.length === 0 ? (
                    <div className="flex flex-col items-center justify-center h-full text-gray-400">
                        <p>{__('general.cart_is_empty')}</p>
                    </div>
                ) : (
                    cart.map((item) => (
                        <div key={item.product_id} className="flex flex-col gap-2 p-3 bg-gray-50 rounded-lg border">
                            <div className="flex justify-between items-start">
                                <span className="font-medium text-sm line-clamp-2">{item.name}</span>
                                <span className="font-bold whitespace-nowrap ms-2">
                                    {formatCurrency(item.unit_price * item.quantity, currency)}
                                </span>
                            </div>
                            <div className="flex items-center justify-between mt-2">
                                <div className="flex items-center bg-white rounded-md border overflow-hidden">
                                    <button 
                                        className="px-2 py-1 hover:bg-gray-100 disabled:opacity-50"
                                        onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                        disabled={item.quantity <= 1}
                                    >
                                        <Minus className="w-4 h-4" />
                                    </button>
                                    <span className="px-3 py-1 text-sm font-medium border-x">{item.quantity}</span>
                                    <button 
                                        className="px-2 py-1 hover:bg-gray-100 disabled:opacity-50"
                                        onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                        disabled={item.quantity >= item.stock}
                                    >
                                        <Plus className="w-4 h-4" />
                                    </button>
                                </div>
                                <button 
                                    className="p-2 text-red-500 hover:bg-red-50 rounded-md transition-colors"
                                    onClick={() => removeFromCart(item.product_id)}
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))
                )}
            </div>

            <div className="p-4 border-t bg-gray-50">
                <div className="flex justify-between items-center mb-4 text-lg font-bold">
                    <span>{__('general.total')}</span>
                    <span>{formatCurrency(subtotal, currency)}</span>
                </div>
                <Button 
                    className="w-full h-12 text-lg" 
                    size="lg" 
                    onClick={onCheckout}
                    disabled={cart.length === 0}
                >
                    {__('payment.checkout')}
                </Button>
            </div>
        </div>
    );
}
