import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import ERPLayout from '@/Layouts/ERPLayout';
import { useERPMenu } from '@/hooks/useERPMenu';
import ProductGrid from './Partials/ProductGrid';
import CartSidebar from './Partials/CartSidebar';
import CheckoutModal from './Partials/CheckoutModal';
import { toast } from 'sonner';

const __ = (key: string) => key;

interface Product {
    id: number;
    name: string;
    price: number;
    stock: number;
    image_url: string | null;
    barcode: string | null;
}

interface CartItem extends Product {
    quantity: number;
    unit_price: number;
    product_id: number;
}

export default function Index({ products, auth }: any) {
    const { menuItems, lockedAddons, workspaceName, tenantId } = useERPMenu('pos');
    const [cart, setCart] = useState<CartItem[]>([]);
    const [isCheckoutModalOpen, setIsCheckoutModalOpen] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);
    
    // We get the business currency for now. Ideally, this should come from props.
    const currency = auth?.user?.tenant?.currency || { code: 'USD', symbol: '$' };

    // Barcode scanner listener
    useEffect(() => {
        let barcodeBuffer = '';
        let timeout: NodeJS.Timeout | null = null;

        const handleKeyDown = (e: KeyboardEvent) => {
            // Ignore if typing in an input
            if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
                return;
            }

            if (e.key === 'Enter') {
                if (barcodeBuffer.length > 0) {
                    handleBarcodeScanned(barcodeBuffer);
                    barcodeBuffer = '';
                }
            } else {
                barcodeBuffer += e.key;
                
                if (timeout) clearTimeout(timeout);
                timeout = setTimeout(() => {
                    barcodeBuffer = '';
                }, 100); // 100ms timeout for scanner speed
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [products]);

    const handleBarcodeScanned = (barcode: string) => {
        // Search in currently loaded products, or trigger an API call if not found
        const product = products.data.find((p: Product) => p.barcode === barcode);
        if (product) {
            handleAddToCart(product);
            toast.success(__('Product added via barcode'));
        } else {
            toast.error(__('Barcode not found'));
            // Optionally, make an API request to search for the barcode globally
        }
    };

    const handleAddToCart = (product: Product) => {
        setCart(prevCart => {
            const existingItem = prevCart.find(item => item.product_id === product.id);
            if (existingItem) {
                if (existingItem.quantity >= product.stock) {
                    toast.error(__('Insufficient stock available.'));
                    return prevCart;
                }
                return prevCart.map(item => 
                    item.product_id === product.id 
                        ? { ...item, quantity: item.quantity + 1 }
                        : item
                );
            }
            if (product.stock <= 0) {
                toast.error(__('Product is out of stock.'));
                return prevCart;
            }
            return [...prevCart, { 
                ...product, 
                product_id: product.id,
                unit_price: product.price,
                quantity: 1 
            }];
        });
    };

    const updateQuantity = (productId: number, quantity: number) => {
        setCart(prevCart => 
            prevCart.map(item => 
                item.product_id === productId 
                    ? { ...item, quantity }
                    : item
            )
        );
    };

    const removeFromCart = (productId: number) => {
        setCart(prevCart => prevCart.filter(item => item.product_id !== productId));
    };

    const handleCheckout = (paymentMethod: string) => {
        setIsProcessing(true);

        const payload = {
            items: cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.unit_price
            })),
            payment_method: paymentMethod
        };

        window.axios.post(route('erp.pos.checkout'), payload)
            .then(response => {
                toast.success(response.data.message);
                setCart([]);
                setIsCheckoutModalOpen(false);
                // Refresh products to update stock
                router.reload({ only: ['products'] });
            })
            .catch(error => {
                toast.error(error.response?.data?.message || __('Checkout failed'));
            })
            .finally(() => {
                setIsProcessing(false);
            });
    };

    const subtotal = cart.reduce((sum, item) => sum + item.unit_price * item.quantity, 0);

    return (
        <ERPLayout
            title={__('POS System')}
            workspaceName={workspaceName}
            tenantId={tenantId}
            menuItems={menuItems}
            lockedAddons={lockedAddons}
        >
            <Head title={__('POS System')} />
            
            <div className="h-[calc(100vh-64px)] flex overflow-hidden">
                <div className="flex-1 overflow-hidden">
                    <ProductGrid 
                        products={products} 
                        onAddToCart={handleAddToCart}
                        currency={currency}
                    />
                </div>
                <div className="w-96 flex-shrink-0">
                    <CartSidebar 
                        cart={cart}
                        updateQuantity={updateQuantity}
                        removeFromCart={removeFromCart}
                        onCheckout={() => setIsCheckoutModalOpen(true)}
                        currency={currency}
                    />
                </div>
            </div>

            <CheckoutModal 
                isOpen={isCheckoutModalOpen}
                onClose={() => setIsCheckoutModalOpen(false)}
                subtotal={subtotal}
                currency={currency}
                onConfirm={handleCheckout}
                isProcessing={isProcessing}
            />
        </ERPLayout>
    );
}
