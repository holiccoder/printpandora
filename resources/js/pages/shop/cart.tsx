// Content sourced from `content/hardcoded-content.json` via useContent('cart_page').
import { Link, router } from '@inertiajs/react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useContent } from '@/hooks/use-content';

interface CartItem {
    key: string;
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    image: string | null;
    slug: string;
    options?: Record<string, string>;
}

interface Props {
    cart: Record<string, CartItem>;
    subtotal: number;
    count: number;
}

export default function Cart({ cart: cartItems, subtotal }: Props) {
    const c = useContent('cart_page') as any;
    const items = Object.entries(cartItems).map(([key, item]) => ({
        ...item,
        key: item.key ?? key,
    }));

    const removeItem = (itemKey: string) => {
        router.delete('/cart/remove', {
            data: { item_key: itemKey },
            preserveScroll: true,
        });
    };

    return (
        <StorefrontLayout>
            <SEO title={c.seo_title} />

            <div className="bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <div className="mx-auto w-full max-w-4xl px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">{c.page_heading}</h1>

                    {items.length === 0 ? (
                        <div className="text-center py-16">
                            <p className="mb-4 text-[#706f6c]">{c.empty_state}</p>
                            <Link href="/shop" className="text-amber-600 hover:text-amber-700 font-medium">{c.empty_state_cta}</Link>
                        </div>
                    ) : (
                        <>
                            <div className="space-y-4">
                                {items.map((item) => (
                                    <div key={item.key} className="flex items-center gap-4 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3E] dark:bg-[#161615]">
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
                                            <Link href={`/${item.slug}`} className="font-semibold hover:text-amber-600">{item.name}</Link>
                                            <p className="text-sm text-[#706f6c]">${item.price.toFixed(2)}</p>
                                        </div>
                                        <div className="text-sm text-[#706f6c]">
                                            {c.quantity_label ?? 'Qty'}: 1
                                        </div>
                                        <p className="w-20 text-right font-semibold">${(item.price).toFixed(2)}</p>
                                        <button
                                            onClick={() => removeItem(item.key)}
                                            className="ml-2 text-sm text-red-500 hover:text-red-700"
                                        >
                                            {c.remove_button}
                                        </button>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-8 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <div className="flex justify-between text-lg font-semibold">
                                    <span>{c.subtotal_label}</span>
                                    <span>${subtotal.toFixed(2)}</span>
                                </div>
                                <Link
                                    href="/checkout"
                                    className="mt-4 block w-full rounded-lg bg-primary px-6 py-3 text-center font-semibold text-primary-foreground hover:bg-primary/90"
                                >
                                    {c.checkout_cta}
                                </Link>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </StorefrontLayout>
    );
}
