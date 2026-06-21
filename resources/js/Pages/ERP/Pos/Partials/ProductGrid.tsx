import React from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-react';
import { router } from '@inertiajs/react';

import { formatMoney as formatCurrency } from '@/lib/utils';
import { __ } from '@/lib/i18n';

interface Product {
    id: number;
    name: string;
    price: number;
    stock_quantity: number;
    image_url: string | null;
    sku: string | null;
}

interface ProductGridProps {
    products: {
        data: Product[];
        links: any[];
    };
    onAddToCart: (product: Product) => void;
    currency: any;
}


export default function ProductGrid({ products, onAddToCart, currency }: ProductGridProps) {
    const [search, setSearch] = React.useState('');

    React.useEffect(() => {
        const timer = setTimeout(() => {
            router.get(route('erp.pos.index'), { search }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    return (
        <div className="flex flex-col h-full">
            <div className="p-4 border-b bg-white">
                <div className="relative">
                    <Search className="absolute start-3 top-3 h-4 w-4 text-gray-400" />
                    <Input
                        placeholder={__('general.search_products_by_name_or')}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="ps-9"
                    />
                </div>
            </div>
            
            <div className="flex-1 overflow-y-auto p-4 bg-gray-50">
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    {(products.data as any).map((product) => (
                        <Card 
                            key={product.id} 
                            className={`cursor-pointer hover:border-primary transition-colors ${product.stock_quantity <= 0 ? 'opacity-50 pointer-events-none' : ''}`}
                            onClick={() => onAddToCart(product)}
                        >
                            <CardContent className="p-4 flex flex-col items-center text-center">
                                {product.image_url ? (
                                    <img src={product.image_url} alt={product.name} className="w-24 h-24 object-cover rounded-md mb-3" />
                                ) : (
                                    <div className="w-24 h-24 bg-gray-200 rounded-md mb-3 flex items-center justify-center text-gray-400 text-xs">
                                        {__('general.no_image')}
                                    </div>
                                )}
                                <h4 className="font-medium text-sm line-clamp-2 mb-1">{product.name}</h4>
                                <p className="text-primary font-bold">{formatCurrency(product.price, currency)}</p>
                                <p className="text-xs text-gray-500 mt-2">{__('general.stock')} {product.stock_quantity}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
                {(products.data as any).length === 0 && (
                    <div className="flex flex-col items-center justify-center h-64 text-gray-500">
                        <p>{__('general.no_products_found')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}
