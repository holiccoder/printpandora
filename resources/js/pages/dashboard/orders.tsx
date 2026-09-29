// Content (headings/labels/pagination) sourced from `content/hardcoded-content.json` via useContent('dashboard_orders_page').
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Download, Filter } from 'lucide-react';
import { useState } from 'react';
import OrderFilesModal from '@/components/order-files-modal';
import type { OrderFileSections } from '@/components/order-files-modal';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import DashboardLayout from '@/layouts/dashboard-layout';
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
    weight: number | null;
    product_names: string[];
    products?: OrderedProduct[];
    notes: string | null;
    created_at: string | null;
    uploaded_files: OrderFileSections['uploaded_files'];
    awaiting_confirmation: OrderFileSections['awaiting_confirmation'];
    confirmed_files: OrderFileSections['confirmed_files'];
};

type OrderedProduct = {
    name: string;
    options: Record<string, unknown>;
    weight: number | null;
};

function productsForOrder(order: Order): OrderedProduct[] {
    return (
        order.products ??
        order.product_names.map((name) => ({
            name,
            options: {},
            weight: null,
        }))
    );
}

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

export type DashboardOrderFilters = {
    order_time_start: string;
    order_time_end: string;
    keyword: string;
    shipping_number: string;
};

type Props = {
    orders: PaginatedOrders;
    statusOptions: Record<string, string>;
    selectedStatus: string | null;
    filters: DashboardOrderFilters;
};

export default function DashboardOrders({
    orders,
    statusOptions,
    selectedStatus,
    filters,
}: Props) {
    const c = useContent('dashboard_orders_page') as any;
    const [openOrder, setOpenOrder] = useState<Order | null>(null);
    const statusTabs = [
        { value: null, label: c.status_filter.all },
        ...Object.keys(statusOptions).map((value) => ({
            value,
            label: orderStatusLabel(value),
        })),
    ];

    return (
        <DashboardLayout>
            <SEO title={c.seo.title} description={c.seo.description} />

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
                className="mb-4 overflow-x-auto rounded-lg border border-neutral-200 bg-white shadow-sm"
            >
                <div className="flex min-w-max">
                    {statusTabs.map((tab) => {
                        const isActive = selectedStatus === tab.value;
                        const href = buildOrdersUrl(tab.value, filters);

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
                                aria-current={isActive ? 'page' : undefined}
                            >
                                {tab.label}
                            </Link>
                        );
                    })}
                </div>
            </nav>

            <OrderFilters
                c={c.filters}
                filters={filters}
                selectedStatus={selectedStatus}
            />

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
                                    <Th>{c.table_headers.weight}</Th>
                                    <Th>{c.table_headers.status}</Th>
                                    <Th>{c.table_headers.tracking}</Th>
                                    <Th>{c.table_headers.file_downloads}</Th>
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
                                            {productsForOrder(order).length >
                                            0 ? (
                                                <ul className="space-y-1">
                                                    {productsForOrder(
                                                        order,
                                                    ).map((product, index) => {
                                                        const options =
                                                            formatOptions(
                                                                product.options,
                                                            );

                                                        return (
                                                            <li
                                                                key={`${product.name}-${index}`}
                                                                className="break-words"
                                                            >
                                                                <span className="block">
                                                                    {
                                                                        product.name
                                                                    }
                                                                </span>
                                                                {options && (
                                                                    <span className="mt-0.5 block text-xs text-neutral-500">
                                                                        {
                                                                            c
                                                                                .table_headers
                                                                                .options
                                                                        }
                                                                        :{' '}
                                                                        {
                                                                            options
                                                                        }
                                                                    </span>
                                                                )}
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            ) : (
                                                <span className="text-neutral-400">
                                                    —
                                                </span>
                                            )}
                                        </Td>
                                        <Td className="whitespace-nowrap text-neutral-600">
                                            {productsForOrder(order).length >
                                            0 ? (
                                                <span>
                                                    {formatWeight(order.weight)}
                                                </span>
                                            ) : (
                                                <span className="text-neutral-400">
                                                    —
                                                </span>
                                            )}
                                        </Td>
                                        <Td>
                                            <StatusPill status={order.status} />
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
                                                            {c.tracking_link}
                                                        </a>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-neutral-400">
                                                    —
                                                </span>
                                            )}
                                        </Td>
                                        <Td>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setOpenOrder(order)
                                                }
                                                className="inline-flex items-center gap-1.5 font-semibold whitespace-nowrap hover:underline"
                                                style={{
                                                    color: ACCENT,
                                                }}
                                            >
                                                {c.file_downloads_link}{' '}
                                                <Download className="size-3.5" />
                                            </button>
                                        </Td>
                                        <Td className="text-right font-semibold text-neutral-900">
                                            ${order.total.toFixed(2)}
                                        </Td>
                                        <Td className="text-right">
                                            <div className="flex flex-wrap justify-end gap-x-4 gap-y-1">
                                                <Link
                                                    href={`/dashboard/orders/${order.id}`}
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
                                                        href={order.invoice_url}
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

            {openOrder && (
                <OrderFilesModal
                    orderId={openOrder.id}
                    files={{
                        uploaded_files: openOrder.uploaded_files,
                        awaiting_confirmation: openOrder.awaiting_confirmation,
                        confirmed_files: openOrder.confirmed_files,
                    }}
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setOpenOrder(null);
                        }
                    }}
                    content={c.file_downloads_modal}
                />
            )}
        </DashboardLayout>
    );
}

function OrderFilters({
    c,
    filters,
    selectedStatus,
}: {
    c: any;
    filters: DashboardOrderFilters;
    selectedStatus: string | null;
}) {
    return (
        <form
            action="/dashboard/orders"
            method="get"
            className="mb-6 rounded-lg border border-neutral-200 bg-white p-4 shadow-sm sm:p-5"
        >
            <div className="mb-4 flex items-center gap-2 text-sm font-semibold text-neutral-900">
                <Filter className="size-4" style={{ color: ACCENT }} />
                <span>{c.title}</span>
            </div>

            {selectedStatus && (
                <input type="hidden" name="status" value={selectedStatus} />
            )}

            <div className="grid gap-4 lg:grid-cols-3">
                <FilterGroup label={c.order_time}>
                    <DateField
                        id="order-time-start"
                        name="order_time_start"
                        label={c.start_date}
                        value={filters.order_time_start}
                    />
                    <DateField
                        id="order-time-end"
                        name="order_time_end"
                        label={c.end_date}
                        value={filters.order_time_end}
                    />
                </FilterGroup>

                <TextField
                    id="order-keyword"
                    name="keyword"
                    label={c.keyword}
                    placeholder={c.keyword_placeholder}
                    value={filters.keyword}
                />
                <TextField
                    id="shipping-number"
                    name="shipping_number"
                    label={c.shipping_number}
                    placeholder={c.shipping_number_placeholder}
                    value={filters.shipping_number}
                />
            </div>

            <div className="mt-4 flex flex-wrap items-center justify-end gap-3 border-t border-neutral-100 pt-4">
                <Link
                    href={buildOrdersUrl(selectedStatus, emptyFilters())}
                    className="text-sm font-semibold text-neutral-600 hover:text-neutral-900 hover:underline"
                >
                    {c.reset}
                </Link>
                <button
                    type="submit"
                    className="rounded-md px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90"
                    style={{ backgroundColor: ACCENT }}
                >
                    {c.apply}
                </button>
            </div>
        </form>
    );
}

function FilterGroup({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <fieldset className="grid gap-2">
            <legend className="text-sm font-medium text-neutral-700">
                {label}
            </legend>
            <div className="grid gap-3 sm:grid-cols-2">{children}</div>
        </fieldset>
    );
}

function DateField({
    id,
    name,
    label,
    value,
}: {
    id: string;
    name: string;
    label: string;
    value: string;
}) {
    return (
        <label className="block" htmlFor={id}>
            <input
                id={id}
                name={name}
                type="date"
                defaultValue={value}
                aria-label={label}
                className="h-9 w-full rounded-md border border-neutral-200 bg-white px-3 text-sm text-neutral-900 transition outline-none focus:border-[#800020] focus:ring-2 focus:ring-[#800020]/15"
            />
        </label>
    );
}

function TextField({
    id,
    name,
    label,
    placeholder,
    value,
}: {
    id: string;
    name: string;
    label: string;
    placeholder: string;
    value: string;
}) {
    return (
        <label
            className="grid gap-1.5 text-sm font-medium text-neutral-700"
            htmlFor={id}
        >
            <span>{label}</span>
            <input
                id={id}
                name={name}
                type="text"
                defaultValue={value}
                placeholder={placeholder}
                className="h-9 w-full rounded-md border border-neutral-200 bg-white px-3 text-sm font-normal text-neutral-900 transition outline-none placeholder:text-neutral-400 focus:border-[#800020] focus:ring-2 focus:ring-[#800020]/15"
            />
        </label>
    );
}

function formatOptions(options: Record<string, unknown>): string {
    return Object.entries(options)
        .filter(
            ([key, value]) =>
                key !== 'design_service_request_id' &&
                value !== null &&
                value !== '' &&
                !(Array.isArray(value) && value.length === 0),
        )
        .map(
            ([key, value]) =>
                `${humanizeOptionText(key)}: ${formatOptionValue(value)}`,
        )
        .join(', ');
}

function formatOptionValue(value: unknown): string {
    if (Array.isArray(value)) {
        return value.map(formatOptionValue).join(', ');
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (typeof value === 'object' && value !== null) {
        return Object.entries(value as Record<string, unknown>)
            .map(
                ([key, nestedValue]) =>
                    `${humanizeOptionText(key)}: ${formatOptionValue(nestedValue)}`,
            )
            .join(', ');
    }

    return humanizeOptionText(String(value));
}

function humanizeOptionText(value: string): string {
    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatWeight(weight: number | null): string {
    return typeof weight === 'number' && weight > 0 ? `${weight} g` : '—';
}

function emptyFilters(): DashboardOrderFilters {
    return {
        order_time_start: '',
        order_time_end: '',
        keyword: '',
        shipping_number: '',
    };
}

function buildOrdersUrl(
    status: string | null,
    filters: DashboardOrderFilters,
): string {
    const params = new URLSearchParams();

    if (status) {
        params.set('status', status);
    }

    for (const [key, value] of Object.entries(filters)) {
        if (value) {
            params.set(key, value);
        }
    }

    const query = params.toString();

    return query ? `/dashboard/orders?${query}` : '/dashboard/orders';
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
