import { Link, useForm } from '@inertiajs/react';
import SEO from '@/components/seo';

interface CartItem {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    image: string | null;
    slug: string;
}

interface Props {
    cart: Record<string, CartItem>;
    subtotal: number;
    count: number;
}

export default function Cart({ cart: cartItems, subtotal }: Props) {
    const items = Object.values(cartItems);
    const updateForm = useForm({});
    const removeForm = useForm({});

    const updateQuantity = (productId: number, quantity: number) => {
        updateForm.patch('/cart/update', {
            product_id: productId,
            quantity,
        });
    };

    const removeItem = (productId: number) => {
        removeForm.delete('/cart/remove', {
            data: { product_id: productId },
        });
    };

    return (
        <>
            <SEO title="Cart" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">PrintPandora</Link>
                        <Link href="/shop" className="text-sm text-[#706f6c] hover:text-[#1b1b18]">Continue Shopping</Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">Shopping Cart</h1>

                    {items.length === 0 ? (
                        <div className="text-center py-16">
                            <p className="mb-4 text-[#706f6c]">Your cart is empty.</p>
                            <Link href="/shop" className="text-amber-600 hover:text-amber-700 font-medium">Browse products</Link>
                        </div>
                    ) : (
                        <>
                            <div className="space-y-4">
                                {items.map((item) => (
                                    <div key={item.product_id} className="flex items-center gap-4 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                        <div className="h-20 w-20 shrink-0 overflow-hidden rounded bg-neutral-100 dark:bg-neutral-800">
                                            {item.image ? (
                                                <img src={item.image} alt={item.name} className="h-full w-full object-cover" />
                                            ) : (
                                                <div className="flex h-full items-center justify-center text-neutral-400">
                                                    <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <Link href={`/shop/${item.slug}`} className="font-semibold hover:text-amber-600">{item.name}</Link>
                                            <p className="text-sm text-[#706f6c]">${item.price.toFixed(2)}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                                className="flex h-8 w-8 items-center justify-center rounded border border-[#e3e3e0] hover:bg-neutral-50 dark:border-[#3E3E3A]"
                                            >
                                                -
                                            </button>
                                            <span className="w-8 text-center text-sm font-medium">{item.quantity}</span>
                                            <button
                                                onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                                className="flex h-8 w-8 items-center justify-center rounded border border-[#e3e3e0] hover:bg-neutral-50 dark:border-[#3E3E3A]"
                                            >
                                                +
                                            </button>
                                        </div>
                                        <p className="w-20 text-right font-semibold">${(item.price * item.quantity).toFixed(2)}</p>
                                        <button
                                            onClick={() => removeItem(item.product_id)}
                                            className="ml-2 text-sm text-red-500 hover:text-red-700"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-8 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div className="flex justify-between text-lg font-semibold">
                                    <span>Subtotal</span>
                                    <span>${subtotal.toFixed(2)}</span>
                                </div>
                                <Link
                                    href="/checkout"
                                    className="mt-4 block w-full rounded-lg bg-amber-600 px-6 py-3 text-center font-semibold text-white hover:bg-amber-700"
                                >
                                    Proceed to Checkout
                                </Link>
                            </div>
                        </>
                    )}
                </main>
            </div>
        </>
    );
}
