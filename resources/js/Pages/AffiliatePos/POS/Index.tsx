import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { ShoppingCart, Search, Plus, Minus, Trash2, CreditCard, ArrowLeft } from 'lucide-react';
import { __ } from '@/lib/i18n';

export default function PosIndex({ initialProducts, categories }: any) {
    const [cart, setCart] = useState<any[]>([]);
    const [searchQuery, setSearchQuery] = useState('');

    const products = initialProducts?.data || [];

    const addToCart = (product: any) => {
        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            setCart(cart.map(item => item.id === product.id ? { ...item, qty: item.qty + 1 } : item));
        } else {
            setCart([...cart, { ...product, qty: 1 }]);
        }
    };

    const updateQty = (id: number, delta: number) => {
        setCart(cart.map(item => {
            if (item.id === id) {
                const newQty = item.qty + delta;
                return newQty > 0 ? { ...item, qty: newQty } : item;
            }
            return item;
        }));
    };

    const removeItem = (id: number) => {
        setCart(cart.filter(item => item.id !== id));
    };

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    return (
        <div className="flex h-screen bg-gray-50 overflow-hidden font-sans">
            <Head title={__('general.point_of_sale')} />
            
            {/* Main Product Area */}
            <div className="flex-1 flex flex-col h-full overflow-hidden">
                {/* Header */}
                <header className="bg-white border-b px-6 py-4 flex items-center justify-end gap-4 shadow-sm">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" className="rounded-full">
                            <ArrowLeft className="w-5 h-5 text-gray-600" />
                        </Button>
                        <h1 className="text-xl font-semibold text-gray-800 tracking-tight">{__('general.affiliate_pos')}</h1>
                    </div>
                    <div className="flex-1 max-w-xl mx-8">
                        <div className="relative">
                            <Search className="absolute start-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <Input 
                                type="text"
                                placeholder={__('general.search_by_name_sku_or_scan_barcode')}
                                className="w-full ps-10 pe-4 py-6 rounded-xl border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>
                </header>

                {/* Categories */}
                <div className="bg-white border-b px-6 py-3 flex gap-2 overflow-x-auto no-scrollbar">
                    <Button variant="default" className="rounded-full px-5">{__('general.all_categories')}</Button>
                    {categories.map((cat: any) => (
                        <Button key={cat.id} variant="secondary" className="rounded-full px-5 text-gray-700 bg-gray-100 hover:bg-gray-200">
                            {cat.name}
                        </Button>
                    ))}
                </div>

                {/* Products Grid */}
                <div className="flex-1 overflow-y-auto p-6 bg-gray-50/50">
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        {products.map((product: any) => (
                            <div 
                                key={product.id} 
                                onClick={() => addToCart(product)}
                                className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden cursor-pointer hover:shadow-md hover:border-blue-200 transition-all group"
                            >
                                <div className="aspect-square bg-gray-100 relative overflow-hidden">
                                    {/* Placeholder Image */}
                                    <div className="absolute inset-0 flex items-center justify-center text-gray-300">
                                        <ShoppingCart className="w-12 h-12" />
                                    </div>
                                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors" />
                                </div>
                                <div className="p-4">
                                    <h3 className="font-medium text-gray-900 line-clamp-1">{product.name}</h3>
                                    <div className="mt-1 flex items-center justify-between">
                                        <span className="text-sm font-semibold text-blue-600">EGP {product.price}</span>
                                        <span className="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                            {product.skus?.[0]?.stock_count || 0} left
                                        </span>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Cart Sidebar */}
            <div className="w-[400px] bg-white border-s shadow-xl flex flex-col z-10">
                <div className="p-6 border-b bg-gray-50/50">
                    <h2 className="text-lg font-semibold flex items-center gap-2 text-gray-800">
                        <ShoppingCart className="w-5 h-5" />{__('general.current_order')}</h2>
                </div>

                {/* Cart Items */}
                <div className="flex-1 overflow-y-auto p-4 space-y-3">
                    {cart.length === 0 ? (
                        <div className="h-full flex flex-col items-center justify-center text-gray-400 space-y-4">
                            <div className="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center">
                                <ShoppingCart className="w-10 h-10" />
                            </div>
                            <p className="font-medium">{__('general.cart_is_empty')}</p>
                        </div>
                    ) : (
                        cart.map(item => (
                            <div key={item.id} className="flex gap-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-gray-200 transition-colors">
                                <div className="flex-1">
                                    <h4 className="font-medium text-gray-800 leading-tight">{item.name}</h4>
                                    <div className="text-blue-600 font-semibold mt-1">EGP {item.price}</div>
                                </div>
                                <div className="flex flex-col items-end justify-end gap-4 gap-3">
                                    <Button 
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeItem(item.id)}
                                        className="text-red-400 hover:text-red-500 hover:bg-red-50 h-8 w-8"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                    <div className="flex items-center gap-3 bg-gray-50 rounded-lg p-1 border">
                                        <Button 
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => updateQty(item.id, -1)}
                                            className="h-6 w-6 bg-white shadow-sm"
                                        >
                                            <Minus className="w-3 h-3" />
                                        </Button>
                                        <span className="font-medium w-4 text-center text-sm">{item.qty}</span>
                                        <Button 
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => updateQty(item.id, 1)}
                                            className="h-6 w-6 bg-white shadow-sm"
                                        >
                                            <Plus className="w-3 h-3" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>

                {/* Checkout Footer */}
                <div className="p-6 bg-white border-t shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                    <div className="space-y-3 mb-6">
                        <div className="flex justify-between text-gray-500">
                            <span>{__('general.subtotal')}</span>
                            <span>EGP {total}</span>
                        </div>
                        <div className="flex justify-between text-gray-500">
                            <span>Tax (0%)</span>
                            <span>{__('general.egp_0')}</span>
                        </div>
                        <div className="flex justify-between text-xl font-bold text-gray-900 pt-3 border-t">
                            <span>{__('general.total')}</span>
                            <span>EGP {total}</span>
                        </div>
                    </div>
                    
                    <Button 
                        disabled={cart.length === 0}
                        className="w-full bg-blue-600 hover:bg-blue-700 h-14 rounded-xl font-semibold text-lg flex items-center justify-center gap-2 shadow-lg shadow-blue-600/20"
                    >
                        <CreditCard className="w-5 h-5" />
                        Charge EGP {total}
                    </Button>
                </div>
            </div>
        </div>
    );
}
