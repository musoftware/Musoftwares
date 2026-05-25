import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { ShoppingCart, Search, Plus, Minus, Trash2, CreditCard, ArrowLeft } from 'lucide-react';

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
            <Head title="Point of Sale" />
            
            {/* Main Product Area */}
            <div className="flex-1 flex flex-col h-full overflow-hidden">
                {/* Header */}
                <header className="bg-white border-b px-6 py-4 flex items-center justify-between shadow-sm">
                    <div className="flex items-center gap-4">
                        <button className="p-2 hover:bg-gray-100 rounded-full transition-colors">
                            <ArrowLeft className="w-5 h-5 text-gray-600" />
                        </button>
                        <h1 className="text-xl font-semibold text-gray-800 tracking-tight">Affiliate POS</h1>
                    </div>
                    <div className="flex-1 max-w-xl mx-8">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input 
                                type="text"
                                placeholder="Search by name, SKU, or scan barcode..."
                                className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>
                </header>

                {/* Categories */}
                <div className="bg-white border-b px-6 py-3 flex gap-2 overflow-x-auto no-scrollbar">
                    <button className="px-5 py-2 rounded-full bg-blue-600 text-white font-medium text-sm whitespace-nowrap shadow-sm hover:bg-blue-700 transition-colors">
                        All Categories
                    </button>
                    {categories.map((cat: any) => (
                        <button key={cat.id} className="px-5 py-2 rounded-full bg-gray-100 text-gray-700 font-medium text-sm whitespace-nowrap hover:bg-gray-200 transition-colors">
                            {cat.name}
                        </button>
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
            <div className="w-[400px] bg-white border-l shadow-xl flex flex-col z-10">
                <div className="p-6 border-b bg-gray-50/50">
                    <h2 className="text-lg font-semibold flex items-center gap-2 text-gray-800">
                        <ShoppingCart className="w-5 h-5" />
                        Current Order
                    </h2>
                </div>

                {/* Cart Items */}
                <div className="flex-1 overflow-y-auto p-4 space-y-3">
                    {cart.length === 0 ? (
                        <div className="h-full flex flex-col items-center justify-center text-gray-400 space-y-4">
                            <div className="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center">
                                <ShoppingCart className="w-10 h-10" />
                            </div>
                            <p className="font-medium">Cart is empty</p>
                        </div>
                    ) : (
                        cart.map(item => (
                            <div key={item.id} className="flex gap-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:border-gray-200 transition-colors">
                                <div className="flex-1">
                                    <h4 className="font-medium text-gray-800 leading-tight">{item.name}</h4>
                                    <div className="text-blue-600 font-semibold mt-1">EGP {item.price}</div>
                                </div>
                                <div className="flex flex-col items-end justify-between gap-3">
                                    <button 
                                        onClick={() => removeItem(item.id)}
                                        className="text-red-400 hover:text-red-500 hover:bg-red-50 p-1.5 rounded-lg transition-colors"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                    <div className="flex items-center gap-3 bg-gray-50 rounded-lg p-1 border">
                                        <button 
                                            onClick={() => updateQty(item.id, -1)}
                                            className="p-1 hover:bg-white rounded shadow-sm transition-colors text-gray-600"
                                        >
                                            <Minus className="w-3 h-3" />
                                        </button>
                                        <span className="font-medium w-4 text-center text-sm">{item.qty}</span>
                                        <button 
                                            onClick={() => updateQty(item.id, 1)}
                                            className="p-1 hover:bg-white rounded shadow-sm transition-colors text-gray-600"
                                        >
                                            <Plus className="w-3 h-3" />
                                        </button>
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
                            <span>Subtotal</span>
                            <span>EGP {total}</span>
                        </div>
                        <div className="flex justify-between text-gray-500">
                            <span>Tax (0%)</span>
                            <span>EGP 0</span>
                        </div>
                        <div className="flex justify-between text-xl font-bold text-gray-900 pt-3 border-t">
                            <span>Total</span>
                            <span>EGP {total}</span>
                        </div>
                    </div>
                    
                    <button 
                        disabled={cart.length === 0}
                        className="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white py-4 rounded-xl font-semibold text-lg flex items-center justify-center gap-2 shadow-lg shadow-blue-600/20 transition-all active:scale-[0.98]"
                    >
                        <CreditCard className="w-5 h-5" />
                        Charge EGP {total}
                    </button>
                </div>
            </div>
        </div>
    );
}
