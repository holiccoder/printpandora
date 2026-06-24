// Content sourced from `content/hardcoded-content.json` via useContent('shop_orders_show_page').
import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';

interface OrderItem {
    id: number;
    quantity: number;
    unit_price: string;
    subtotal: string;
    product: { id: number; name: string; slug: string; featured_image: string | null };
}

interface Order {
    id: number;
    status: string;
    total: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string | null;
    shipping_address: string;
    shipping_city: string;
    shipping_state: string | null;
    shipping_zip: string;
    shipping_country: string;
    notes: string | null;
    created_at: string;
    items: OrderItem[];
}

interface Props {
    order: Order;
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    processing: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
    shipped: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100',
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
};

export default function OrderShow({ order }: Props) {
    const c = useContent('shop_orders_show_page') as any;

    return (
        <>
            <SEO title={`${c.seo_title_prefix}${order.id}`} />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">{c.brand}</Link>
                        <Link href="/orders" className="text-sm text-[#706f6c] hover:text-[#1b1b18]">{c.nav_my_orders}</Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
                    <Link href="/orders" className="mb-6 inline-flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18]">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        {c.back_link}
                    </Link>

                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-3xl font-semibold tracking-tight">{c.page_heading_prefix}{order.id}</h1>
                        <span className={`rounded-full px-3 py-1 text-sm font-medium capitalize ${statusColors[order.status] ?? 'bg-neutral-100'}`}>
                            {order.status}
                        </span>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-3">
                        <div className="space-y-4 lg:col-span-2">
                            <h2 className="text-lg font-semibold">{c.section_headings.items}</h2>
                            {order.items.map((item) => (
                                <div key={item.id} className="flex items-center gap-4 rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                    <div className="h-16 w-16 shrink-0 overflow-hidden rounded bg-neutral-100 dark:bg-neutral-800">
                                        {item.product.featured_image ? (
                                            <img src={item.product.featured_image} alt={item.product.name} className="h-full w-full object-cover" />
                                        ) : (
                                            <div className="flex h-full items-center justify-center text-neutral-400">
                                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-semibold">{item.product.name}</p>
                                        <p className="text-sm text-[#706f6c]">{c.qty_label} {item.quantity} &times; ${parseFloat(item.unit_price).toFixed(2)}</p>
                                    </div>
                                    <p className="font-semibold">${parseFloat(item.subtotal).toFixed(2)}</p>
                                </div>
                            ))}

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-4 text-right text-lg font-bold dark:border-[#3E3E3A] dark:bg-[#161615]">
                                {c.total_label} ${parseFloat(order.total).toFixed(2)}
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-3 text-lg font-semibold">{c.section_headings.customer}</h2>
                                <p className="text-sm">{order.customer_name}</p>
                                <p className="text-sm text-[#706f6c]">{order.customer_email}</p>
                                {order.customer_phone && <p className="text-sm text-[#706f6c]">{order.customer_phone}</p>}
                            </div>
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-3 text-lg font-semibold">{c.section_headings.shipping}</h2>
                                <p className="text-sm">{order.shipping_address}</p>
                                <p className="text-sm text-[#706f6c]">{order.shipping_city}{order.shipping_state ? `, ${order.shipping_state}` : ''} {order.shipping_zip}</p>
                                <p className="text-sm text-[#706f6c]">{order.shipping_country}</p>
                            </div>
                            {order.notes && (
                                <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                    <h2 className="mb-3 text-lg font-semibold">{c.section_headings.notes}</h2>
                                    <p className="text-sm text-[#706f6c]">{order.notes}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
