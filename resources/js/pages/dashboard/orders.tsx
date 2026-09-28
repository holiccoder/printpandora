// Content (headings/labels/pagination) sourced from `content/hardcoded-content.json` via useContent('dashboard_orders_page').
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Download } from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import { ORDER_STATUS_COLORS, orderStatusLabel } from '@/lib/order-status';

const ACCENT = '#800020';

type Order = {
    id: number;
    status: string;
    tracking_number: string | null;
    tracking_url: string | null;
    invoice_url: string | null;
    total: number;
    item_count: number;
    product_names: string[];
    notes: string | null;
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
    statusOptions: Record<string, string>;
    selectedStatus: string | null;
};

export default function DashboardOrders({
    orders,
    statusOptions,
    selectedStatus,
}: Props) {
    const c = useContent('dashboard_orders_page') as any;
    const statusTabs = [
        { value: null, label: c.status_filter.all },
        ...Object.entries(statusOptions).map(([value, label]) => ({
            value,
            label,
        })),
    ];

    return (
        <StorefrontLayout>
            <SEO title={c.seo.title} description={c.seo.description} />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-10 lg:py-14">
                    <header className="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                                {c.page_heading}
                            </h1>
                            <p className="mt-1 text-sm text-neutral-600">
                                {orders.total}{' '}
                                {orders.total === 1
                                    ? c.page_subheading_singular
                                    : c.page_subheading_plural}
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

                    <nav
                        aria-label={c.status_filter.label}
                        className="mb-6 overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-sm"
                    >
                        <div className="flex min-w-max">
                            {statusTabs.map((tab) => {
                                const isActive = selectedStatus === tab.value;
                                const href = tab.value
                                    ? `/dashboard/orders?status=${encodeURIComponent(tab.value)}`
                                    : '/dashboard/orders';

                                return (
                                    <Link
                                        key={tab.value ?? 'all'}
                                        href={href}
                                        preserveScroll
                                        className={`border-b-2 px-4 py-3 text-sm font-medium transition-colors ${
                                            isActive
                                                ? 'border-[#800020] text-[#800020]'
                                                : 'border-transparent text-neutral-600 hover:border-neutral-300 hover:text-neutral-900'
                                        }`}
                                        aria-current={
                                            isActive ? 'page' : undefined
                                        }
                                    >
                                        {tab.label}
                                    </Link>
                                );
                            })}
                        </div>
                    </nav>

                    <div className="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
                        {orders.data.length === 0 ? (
                            <div className="px-6 py-12 text-center text-sm text-neutral-600">
                                {c.empty_state_prefix}
                                <Link
                                    href="/business-cards"
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
                                    <thead className="bg-neutral-50 text-xs tracking-wide text-neutral-500 uppercase">
                                        <tr>
                                            <Th>{c.table_headers.order}</Th>
                                            <Th>{c.table_headers.date}</Th>
                                            <Th>{c.table_headers.products}</Th>
                                            <Th>{c.table_headers.status}</Th>
                                            <Th>{c.table_headers.tracking}</Th>
                                            <Th>{c.table_headers.note}</Th>
                                            <Th className="text-right">
                                                {c.table_headers.total}
                                            </Th>
                                            <Th className="sr-only">
                                                {c.table_headers.actions}
                                            </Th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {orders.data.map((order) => (
                                            <tr
                                                key={order.id}
                                                className="hover:bg-neutral-50"
                                            >
                                                <Td className="font-semibold text-neutral-900">
                                                    #{order.id}
                                                </Td>
                                                <Td className="text-neutral-600">
                                                    {order.created_at
                                                        ? new Date(
                                                              order.created_at,
                                                          ).toLocaleDateString()
                                                        : '—'}
                                                </Td>
                                                <Td className="max-w-sm min-w-56 text-neutral-600">
                                                    {order.product_names
                                                        .length > 0 ? (
                                                        <ul className="space-y-1">
                                                            {order.product_names.map(
                                                                (
                                                                    name,
                                                                    index,
                                                                ) => (
                                                                    <li
                                                                        key={`${name}-${index}`}
                                                                        className="break-words"
                                                                    >
                                                                        {name}
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    ) : (
                                                        <span className="text-neutral-400">
                                                            —
                                                        </span>
                                                    )}
                                                </Td>
                                                <Td>
                                                    <StatusPill
                                                        status={order.status}
                                                    />
                                                </Td>
                                                <Td className="max-w-52">
                                                    {order.tracking_number ||
                                                    order.tracking_url ? (
                                                        <div className="flex flex-col gap-1">
                                                            {order.tracking_number && (
                                                                <span className="truncate font-medium text-neutral-900">
                                                                    {
                                                                        order.tracking_number
                                                                    }
                                                                </span>
                                                            )}
                                                            {order.tracking_url && (
                                                                <a
                                                                    href={
                                                                        order.tracking_url
                                                                    }
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="font-semibold hover:underline"
                                                                    style={{
                                                                        color: ACCENT,
                                                                    }}
                                                                >
                                                                    {
                                                                        c.tracking_link
                                                                    }
                                                                </a>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-neutral-400">
                                                            —
                                                        </span>
                                                    )}
                                                </Td>
                                                <Td className="max-w-xs break-words whitespace-pre-wrap text-neutral-600">
                                                    {order.notes || (
                                                        <span className="text-neutral-400">
                                                            —
                                                        </span>
                                                    )}
                                                </Td>
                                                <Td className="text-right font-semibold text-neutral-900">
                                                    ${order.total.toFixed(2)}
                                                </Td>
                                                <Td className="text-right">
                                                    <div className="flex flex-wrap justify-end gap-x-4 gap-y-1">
                                                        <Link
                                                            href={`/orders/${order.id}`}
                                                            className="inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                                                            style={{
                                                                color: ACCENT,
                                                            }}
                                                        >
                                                            {c.view_link}{' '}
                                                            <ChevronRight className="size-3.5" />
                                                        </Link>
                                                        {order.invoice_url && (
                                                            <a
                                                                href={
                                                                    order.invoice_url
                                                                }
                                                                className="inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                                                                style={{
                                                                    color: ACCENT,
                                                                }}
                                                                download
                                                            >
                                                                {c.invoice_link}{' '}
                                                                <Download className="size-3.5" />
                                                            </a>
                                                        )}
                                                    </div>
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
    const cls =
        ORDER_STATUS_COLORS[status.toLowerCase()] ??
        'bg-neutral-100 text-neutral-700';

    return (
        <span
            className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ${cls}`}
        >
            {orderStatusLabel(status)}
        </span>
    );
}

function Pagination({ orders, c }: { orders: PaginatedOrders; c: any }) {
    return (
        <nav className="flex items-center justify-between border-t border-neutral-100 bg-white px-4 py-3 text-sm">
            <p className="text-neutral-600">
                {c.showing}{' '}
                <span className="font-semibold">{orders.from ?? 0}</span>–
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
