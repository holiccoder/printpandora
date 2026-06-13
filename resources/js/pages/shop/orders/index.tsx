import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';

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
    created_at: string;
    items: OrderItem[];
}

interface Props {
    orders: {
        data: Order[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    processing: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
    shipped: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100',
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
};

export default function OrderIndex({ orders }: Props) {
    return (
        <>
            <SEO title="My Orders" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">PrintPandora</Link>
                        <Link href="/shop" className="text-sm text-[#706f6c] hover:text-[#1b1b18]">Continue Shopping</Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">My Orders</h1>

                    {orders.data.length === 0 ? (
                        <p className="text-[#706f6c]">No orders yet.</p>
                    ) : (
                        <div className="space-y-4">
                            {orders.data.map((order) => (
                                <Link
                                    key={order.id}
                                    href={`/orders/${order.id}`}
                                    className="block rounded-lg border border-[#e3e3e0] bg-white p-6 transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="flex items-center justify-between mb-3">
                                        <span className="font-semibold">Order #{order.id}</span>
                                        <span className={`rounded-full px-3 py-1 text-xs font-medium capitalize ${statusColors[order.status] ?? 'bg-neutral-100'}`}>
                                            {order.status}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm text-[#706f6c]">
                                        <span>{order.items.length} item(s)</span>
                                        <span className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                                            ${parseFloat(order.total).toFixed(2)}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-xs text-[#706f6c]">
                                        {new Date(order.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric', month: 'long', day: 'numeric',
                                        })}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
