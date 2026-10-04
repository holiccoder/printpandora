// Content sourced from `content/hardcoded-content.json` via useContent('shop_orders_index_page').
import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatOrderOptions } from '@/lib/order-options';
import { ORDER_STATUS_COLORS, orderStatusLabel } from '@/lib/order-status';

interface OrderItem {
    id: number;
    quantity: number;
    unit_price: string;
    subtotal: string;
    options?: Record<string, unknown> | null;
    product: {
        id: number;
        name: string;
        slug: string;
        featured_image: string | null;
    };
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

export default function OrderIndex({ orders }: Props) {
    const c = useContent('shop_orders_index_page') as any;

    return (
        <>
            <SEO title={c.seo_title} />

            <StorefrontLayout>
                <div className="mx-auto w-full max-w-4xl px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">
                        {c.page_heading}
                    </h1>

                    {orders.data.length === 0 ? (
                        <p className="text-[#706f6c]">{c.empty_state}</p>
                    ) : (
                        <div className="space-y-4">
                            {orders.data.map((order) => (
                                <Link
                                    key={order.id}
                                    href={`/dashboard/orders/${order.id}`}
                                    className="block rounded-lg border border-[#e3e3e0] bg-white p-6 transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="font-semibold">
                                            {c.order_label_prefix}
                                            {order.id}
                                        </span>
                                        <span
                                            className={`rounded-full px-3 py-1 text-xs font-medium ${ORDER_STATUS_COLORS[order.status] ?? 'bg-neutral-100'}`}
                                        >
                                            {orderStatusLabel(order.status)}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between text-sm text-[#706f6c]">
                                        <span>
                                            {order.items.length}{' '}
                                            {c.items_suffix}
                                        </span>
                                        <span className="font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                                            $
                                            {parseFloat(order.total).toFixed(2)}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-xs text-[#706f6c]">
                                        {new Date(
                                            order.created_at,
                                        ).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </p>
                                    {order.items.length > 0 && (
                                        <div className="mt-4 space-y-2 border-t border-[#e3e3e0] pt-3 text-sm dark:border-[#3E3E3A]">
                                            {order.items.map((item) => {
                                                const options =
                                                    formatOrderOptions(
                                                        item.options ?? {},
                                                    );

                                                return (
                                                    <div
                                                        key={item.id}
                                                        className="break-words text-[#706f6c]"
                                                    >
                                                        <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                                            {item.product.name}{' '}
                                                            × {item.quantity}
                                                        </span>
                                                        {options && (
                                                            <span className="mt-0.5 block text-xs">
                                                                Options:{' '}
                                                                {options}
                                                            </span>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </StorefrontLayout>
        </>
    );
}
