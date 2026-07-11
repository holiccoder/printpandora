// Content (headings/labels/pagination) sourced from `content/hardcoded-content.json` via useContent('dashboard_orders_page').
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#0f4c3a';

type Order = {
    id: number;
    status: string;
    payment_status: string;
    payment_method: string | null;
    total: number;
    item_count: number;
    created_at: string | null;
};

type PaginatedOrders = {
    data: Order[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    orders: PaginatedOrders;
};

export default function DashboardOrders({ orders }: Props) {
    const c = useContent('dashboard_orders_page') as any;

    return (
        <StorefrontLayout>
            <SEO
                title={c.seo.title}
                description={c.seo.description}
            />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-10 lg:py-14">
                    <header className="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                                {c.page_heading}
                            </h1>
                            <p className="mt-1 text-sm text-neutral-600">
                                {orders.total} {orders.total === 1 ? c.page_subheading_singular : c.page_subheading_plural}
                            </p>
                        </div>
                        <Link
                            href="/dashboard"
                            className="inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                            style={{ color: ACCENT }}
                        >
                            <ChevronLeft className="size-4" /> {c.back_link}
                        </Link>
                    </header>

                    <div className="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
                        {orders.data.length === 0 ? (
                            <div className="px-6 py-12 text-center text-sm text-neutral-600">
                                {c.empty_state_prefix}
                                <Link
                                    href="/shop"
                                    className="font-semibold hover:underline"
                                    style={{ color: ACCENT }}
                                >
                                    {c.empty_state_link}
                                </Link>
                                {c.empty_state_suffix}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500">
                                        <tr>
                                            <Th>{c.table_headers.order}</Th>
                                            <Th>{c.table_headers.date}</Th>
                                            <Th>{c.table_headers.items}</Th>
                                            <Th>{c.table_headers.status}</Th>
                                            <Th>{c.table_headers.payment}</Th>
                                            <Th className="text-right">{c.table_headers.total}</Th>
                                            <Th className="sr-only">{c.table_headers.actions}</Th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {orders.data.map((order) => (
                                            <tr key={order.id} className="hover:bg-neutral-50">
                                                <Td className="font-semibold text-neutral-900">
                                                    #{order.id}
                                                </Td>
                                                <Td className="text-neutral-600">
                                                    {order.created_at
                                                        ? new Date(order.created_at).toLocaleDateString()
                                                        : '—'}
                                                </Td>
                                                <Td className="text-neutral-600">{order.item_count}</Td>
                                                <Td>
                                                    <StatusPill status={order.status} />
                                                </Td>
                                                <Td className="text-neutral-600 capitalize">
                                                    {order.payment_status}
                                                    {order.payment_method ? ` · ${order.payment_method}` : ''}
                                                </Td>
                                                <Td className="text-right font-semibold text-neutral-900">
                                                    ${order.total.toFixed(2)}
                                                </Td>
                                                <Td className="text-right">
                                                    <Link
                                                        href={`/orders/${order.id}`}
                                                        className="inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                                                        style={{ color: ACCENT }}
                                                    >
                                                        {c.view_link} <ChevronRight className="size-3.5" />
                                                    </Link>
                                                </Td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {orders.last_page > 1 && (
                            <Pagination orders={orders} c={c.pagination} />
                        )}
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}

function Th({
    children,
    className = '',
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return <th className={`px-4 py-3 font-medium ${className}`}>{children}</th>;
}

function Td({
    children,
    className = '',
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return <td className={`px-4 py-3 ${className}`}>{children}</td>;
}

function StatusPill({ status }: { status: string }) {
    const map: Record<string, string> = {
        pending: 'bg-amber-50 text-amber-700',
        processing: 'bg-blue-50 text-blue-700',
        shipped: 'bg-indigo-50 text-indigo-700',
        completed: 'bg-emerald-50 text-emerald-700',
        cancelled: 'bg-neutral-100 text-neutral-600',
    };
    const cls = map[status.toLowerCase()] ?? 'bg-neutral-100 text-neutral-700';

    return (
        <span
            className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium capitalize ${cls}`}
        >
            {status}
        </span>
    );
}

function Pagination({ orders, c }: { orders: PaginatedOrders; c: any }) {
    return (
        <nav className="flex items-center justify-between border-t border-neutral-100 bg-white px-4 py-3 text-sm">
            <p className="text-neutral-600">
                {c.showing} <span className="font-semibold">{orders.from ?? 0}</span>–
                <span className="font-semibold">{orders.to ?? 0}</span> {c.of}{' '}
                <span className="font-semibold">{orders.total}</span>
            </p>
            <div className="flex gap-2">
                {orders.prev_page_url ? (
                    <Link
                        href={orders.prev_page_url}
                        className="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50"
                        preserveScroll
                    >
                        <ChevronLeft className="size-4" /> {c.prev}
                    </Link>
                ) : (
                    <span className="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-sm font-medium text-neutral-300">
                        <ChevronLeft className="size-4" /> {c.prev}
                    </span>
                )}
                {orders.next_page_url ? (
                    <Link
                        href={orders.next_page_url}
                        className="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50"
                        preserveScroll
                    >
                        {c.next} <ChevronRight className="size-4" />
                    </Link>
                ) : (
                    <span className="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-sm font-medium text-neutral-300">
                        {c.next} <ChevronRight className="size-4" />
                    </span>
                )}
            </div>
        </nav>
    );
}