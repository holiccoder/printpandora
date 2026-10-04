import { Link } from '@inertiajs/react';
import {
    Check,
    ChevronLeft,
    Download,
    ExternalLink,
    Files,
    MapPin,
    Package,
    Phone,
    Truck,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import OrderFilesModal from '@/components/order-files-modal';
import type { OrderFileSections } from '@/components/order-files-modal';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import DashboardLayout from '@/layouts/dashboard-layout';
import { formatOrderOptionValue } from '@/lib/order-options';
import { ORDER_STATUS_COLORS, orderStatusLabel } from '@/lib/order-status';

const ACCENT = '#800020';

const ORDER_STEPS = [
    'pending',
    'pending_review',
    'needs_reupload',
    'pending_confirmation',
    'confirmed',
    'production',
    'shipped',
] as const;

type OrderStatus = (typeof ORDER_STEPS)[number];

type OrderFile = {
    id: string;
    filename: string;
    label: string;
    size: number | null;
    uploaded_at: string | null;
    download_url: string | null;
};

type OrderItem = {
    id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    options: Record<string, unknown>;
    product: {
        id: number;
        name: string;
        slug: string | null;
        featured_image: string | null;
    } | null;
};

type Props = {
    order: {
        id: number;
        status: string;
        total: number;
        created_at: string | null;
        notes: string | null;
        coupon_code: string | null;
        items: OrderItem[];
        files: {
            uploaded_files: OrderFileSections['uploaded_files'];
            awaiting_confirmation: OrderFileSections['awaiting_confirmation'];
            confirmed_files: OrderFileSections['confirmed_files'];
        };
        can_manage_files: boolean;
        can_confirm_files: boolean;
        file_upload_url: string;
        file_confirm_url: string;
        contact: {
            name: string | null;
            email: string | null;
            phone: string | null;
        };
        address: {
            line: string | null;
            city: string | null;
            state: string | null;
            zip: string | null;
            country: string | null;
        };
        shipping: {
            carrier: string | null;
            method: string | null;
            expenses: number;
            number: string | null;
            tracking_link: string | null;
        };
        invoice_url: string | null;
    };
};

export default function DashboardOrderShow({ order }: Props) {
    const c = useContent('dashboard_order_show_page') as any;
    const [filesOpen, setFilesOpen] = useState(false);
    const awaitingFileSignature = order.files.awaiting_confirmation
        .map((file) => file.id)
        .join('|');
    const fileReviewKey = `${order.id}:${order.status}:${awaitingFileSignature}`;
    const [filesViewed, setFilesViewed] = useState({
        key: fileReviewKey,
        value: order.can_confirm_files,
    });
    const hasViewedCurrentFiles =
        filesViewed.key === fileReviewKey
            ? filesViewed.value
            : order.can_confirm_files;

    return (
        <DashboardLayout>
            <SEO
                title={`${c.seo_title_prefix}${order.id}`}
                description={c.seo_description}
            />

            <header className="mb-6">
                <Link
                    href="/dashboard/orders"
                    className="mb-4 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                    style={{ color: ACCENT }}
                >
                    <ChevronLeft className="size-4" />
                    {c.back_link}
                </Link>
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                            {c.page_heading_prefix}
                            {order.id}
                        </h1>
                        <p className="mt-1 text-sm text-neutral-600">
                            {c.placed_on_prefix} {formatDate(order.created_at)}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <StatusPill status={order.status} />
                        {order.invoice_url && (
                            <a
                                href={order.invoice_url}
                                download
                                className="inline-flex items-center gap-1.5 text-sm font-semibold hover:underline"
                                style={{ color: ACCENT }}
                            >
                                <Download className="size-4" />
                                {c.invoice_link}
                            </a>
                        )}
                    </div>
                </div>
            </header>

            <div className="space-y-6">
                <OrderProgress status={order.status} c={c} />

                <Section
                    title={c.sections.order_information}
                    icon={<Package className="size-5" />}
                >
                    <div className="divide-y divide-neutral-100">
                        {order.items.length > 0 ? (
                            order.items.map((item) => (
                                <OrderItemCard
                                    key={item.id}
                                    item={item}
                                    c={c}
                                />
                            ))
                        ) : (
                            <p className="px-5 py-6 text-sm text-neutral-500">
                                {c.not_available}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-4 border-t border-neutral-200 bg-neutral-50/70 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <DetailValue label="Order ID" value={`#${order.id}`} />
                        <DetailValue
                            label={c.labels.order_note}
                            value={order.notes || c.no_note}
                            multiline
                        />
                        {order.coupon_code && (
                            <DetailValue
                                label={c.labels.coupon_code}
                                value={order.coupon_code}
                            />
                        )}
                        <DetailValue
                            label={c.labels.order_total}
                            value={formatCurrency(order.total)}
                            emphasis
                        />
                    </div>
                </Section>

                <Section
                    title={c.sections.design_files}
                    icon={<Files className="size-5" />}
                >
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
                        <p className="text-sm text-neutral-600">
                            {c.file_sections.description}
                        </p>
                        <button
                            type="button"
                            onClick={() => setFilesOpen(true)}
                            className="text-sm font-semibold text-[#800020] hover:underline"
                        >
                            {fileActionLabel(order.status, c)}
                        </button>
                    </div>
                    <div className="divide-y divide-neutral-100">
                        <FileRow
                            title={c.file_sections.awaiting_confirmation}
                            files={order.files.awaiting_confirmation}
                            c={c}
                        />
                        <FileRow
                            title={c.file_sections.confirmed_files}
                            files={order.files.confirmed_files}
                            c={c}
                        />
                    </div>
                </Section>

                <OrderFilesModal
                    orderId={order.id}
                    files={order.files}
                    open={filesOpen}
                    onOpenChange={setFilesOpen}
                    content={c.file_downloads_modal}
                    canManage={order.can_manage_files}
                    canConfirm={
                        order.can_confirm_files || hasViewedCurrentFiles
                    }
                    isPendingConfirmation={
                        order.status === 'pending_confirmation'
                    }
                    showConfirmButton={[
                        'pending',
                        'pending_review',
                        'needs_reupload',
                        'pending_confirmation',
                    ].includes(order.status)}
                    onFileDownloaded={() =>
                        setFilesViewed({ key: fileReviewKey, value: true })
                    }
                    uploadUrl={order.file_upload_url}
                    confirmUrl={order.file_confirm_url}
                />

                <Section
                    title={c.sections.address_contact}
                    icon={<MapPin className="size-5" />}
                >
                    <div className="grid gap-6 p-5 md:grid-cols-2">
                        <InfoColumn
                            title={c.labels.contact}
                            icon={<Phone className="size-4" />}
                        >
                            <dl className="space-y-3 text-sm">
                                <DetailValue
                                    label={c.labels.name}
                                    value={
                                        order.contact.name || c.not_available
                                    }
                                />
                                <DetailValue
                                    label={c.labels.email}
                                    value={
                                        order.contact.email || c.not_available
                                    }
                                />
                                <DetailValue
                                    label={c.labels.phone}
                                    value={
                                        order.contact.phone || c.not_available
                                    }
                                />
                            </dl>
                        </InfoColumn>
                        <InfoColumn
                            title={c.labels.address}
                            icon={<MapPin className="size-4" />}
                        >
                            <address className="text-sm leading-6 text-neutral-700 not-italic">
                                {order.address.line && (
                                    <span className="block">
                                        {order.address.line}
                                    </span>
                                )}
                                <span className="block">
                                    {[
                                        order.address.city,
                                        order.address.state,
                                        order.address.zip,
                                    ]
                                        .filter(Boolean)
                                        .join(', ') || c.not_available}
                                </span>
                                {order.address.country && (
                                    <span className="block">
                                        {order.address.country}
                                    </span>
                                )}
                            </address>
                        </InfoColumn>
                    </div>
                </Section>

                <Section
                    title={c.sections.shipping}
                    icon={<Truck className="size-5" />}
                >
                    <div className="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <DetailValue
                            label={c.labels.carrier}
                            value={order.shipping.carrier || c.not_available}
                        />
                        <DetailValue
                            label={c.labels.shipping_expenses}
                            value={formatCurrency(order.shipping.expenses)}
                        />
                        <DetailValue
                            label={c.labels.shipping_number}
                            value={order.shipping.number || c.not_available}
                        />
                        <DetailValue
                            label={c.labels.shipping_method}
                            value={
                                humanize(order.shipping.method) ||
                                c.not_available
                            }
                        />
                    </div>
                    <div className="border-t border-neutral-100 px-5 py-4">
                        {order.shipping.tracking_link ? (
                            <a
                                href={order.shipping.tracking_link}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-2 text-sm font-semibold hover:underline"
                                style={{ color: ACCENT }}
                            >
                                <ExternalLink className="size-4" />
                                {c.labels.tracking_link}
                            </a>
                        ) : (
                            <p className="text-sm text-neutral-500">
                                {c.labels.tracking_link}: {c.not_available}
                            </p>
                        )}
                    </div>
                </Section>
            </div>
        </DashboardLayout>
    );
}

function OrderProgress({ status, c }: { status: string; c: any }) {
    const currentIndex = Math.max(
        0,
        ORDER_STEPS.indexOf(status as OrderStatus),
    );

    return (
        <section
            aria-labelledby="order-progress-heading"
            className="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
        >
            <div className="mb-6 flex flex-wrap items-center justify-between gap-2">
                <h2
                    id="order-progress-heading"
                    className="text-base font-bold text-neutral-900"
                >
                    {c.current_stage}
                </h2>
                <StatusPill status={status} />
            </div>
            <ol className="grid gap-4 md:grid-cols-7 md:gap-0">
                {ORDER_STEPS.map((step, index) => {
                    const complete = index < currentIndex;
                    const active = index === currentIndex;

                    return (
                        <li
                            key={step}
                            className="relative flex items-start gap-3 md:block md:px-1 md:text-center"
                            aria-current={active ? 'step' : undefined}
                        >
                            {index < ORDER_STEPS.length - 1 && (
                                <span
                                    aria-hidden="true"
                                    className={`absolute top-4 right-[calc(-50%+1rem)] left-[calc(50%+1rem)] hidden h-0.5 md:block ${index < currentIndex ? 'bg-[#800020]' : 'bg-neutral-200'}`}
                                />
                            )}
                            <span
                                className={`relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold md:mx-auto ${
                                    complete || active
                                        ? 'border-[#800020] bg-[#800020] text-white'
                                        : 'border-neutral-300 bg-white text-neutral-500'
                                }`}
                            >
                                {complete ? (
                                    <Check className="size-4" />
                                ) : (
                                    index + 1
                                )}
                            </span>
                            <span className="min-w-0 pt-0.5 md:mt-3 md:block md:pt-0">
                                <span
                                    className={`block text-sm font-semibold ${active ? 'text-[#800020]' : complete ? 'text-neutral-800' : 'text-neutral-500'}`}
                                >
                                    {c.steps[step]}
                                </span>
                                <span className="mt-0.5 block text-xs text-neutral-400">
                                    {active
                                        ? c.current_stage
                                        : complete
                                          ? c.completed
                                          : c.upcoming}
                                </span>
                            </span>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}

function Section({
    title,
    icon,
    children,
}: {
    title: string;
    icon: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
            <header className="flex items-center gap-2 border-b border-neutral-100 px-5 py-4">
                <span style={{ color: ACCENT }}>{icon}</span>
                <h2 className="text-base font-bold text-neutral-900">
                    {title}
                </h2>
            </header>
            {children}
        </section>
    );
}

function OrderItemCard({ item, c }: { item: OrderItem; c: any }) {
    return (
        <article className="grid gap-4 p-5 sm:grid-cols-[5rem_minmax(0,1fr)_auto] sm:items-start">
            <div className="flex size-20 items-center justify-center overflow-hidden rounded-lg bg-neutral-100 sm:size-20">
                {item.product?.featured_image ? (
                    <img
                        src={item.product.featured_image}
                        alt={item.product.name}
                        className="size-full object-cover"
                    />
                ) : (
                    <Package className="size-7 text-neutral-400" />
                )}
            </div>
            <div className="min-w-0">
                <h3 className="font-semibold text-neutral-900">
                    {item.product?.name || c.not_available}
                </h3>
                <p className="mt-1 text-sm text-neutral-500">
                    {c.labels.quantity}: {item.quantity} ×{' '}
                    {formatCurrency(item.unit_price)}
                </p>
                <OptionList options={item.options} c={c} />
            </div>
            <p className="text-right text-base font-bold text-neutral-900">
                {formatCurrency(item.subtotal)}
            </p>
        </article>
    );
}

function OptionList({
    options,
    c,
}: {
    options: Record<string, unknown>;
    c: any;
}) {
    const entries = Object.entries(options).filter(([, value]) =>
        hasValue(value),
    );

    return (
        <div className="mt-3">
            <p className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">
                {c.labels.product_options}
            </p>
            {entries.length > 0 ? (
                <dl className="mt-2 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-2">
                    {entries.map(([key, value]) => (
                        <div key={key} className="flex gap-2">
                            <dt className="text-neutral-500">
                                {humanize(key)}:
                            </dt>
                            <dd className="font-medium text-neutral-800">
                                {formatOrderOptionValue(value)}
                            </dd>
                        </div>
                    ))}
                </dl>
            ) : (
                <p className="mt-1 text-sm text-neutral-500">{c.no_options}</p>
            )}
        </div>
    );
}

function FileRow({
    title,
    files,
    c,
}: {
    title: string;
    files: OrderFile[];
    c: any;
}) {
    return (
        <div className="grid gap-3 p-5 sm:grid-cols-[14rem_minmax(0,1fr)] sm:items-start">
            <div>
                <h3 className="font-semibold text-neutral-900">{title}</h3>
                <p className="mt-1 text-xs text-neutral-500">
                    {files.length} {files.length === 1 ? 'file' : 'files'}
                </p>
            </div>
            {files.length > 0 ? (
                <ul className="space-y-2">
                    {files.map((file) => (
                        <li
                            key={file.id}
                            className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2.5"
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium text-neutral-900">
                                    {file.label}
                                </p>
                                <p className="truncate text-xs text-neutral-500">
                                    {file.filename}
                                    {file.size !== null &&
                                        ` · ${formatFileSize(file.size)}`}
                                </p>
                            </div>
                            {file.download_url ? (
                                <a
                                    href={file.download_url}
                                    download
                                    className="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold hover:underline"
                                    style={{ color: ACCENT }}
                                >
                                    <Download className="size-3.5" />
                                    {c.download}
                                </a>
                            ) : (
                                <span className="text-xs text-neutral-400">
                                    {c.unavailable}
                                </span>
                            )}
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="rounded-lg border border-dashed border-neutral-200 px-3 py-3 text-sm text-neutral-500">
                    {c.no_files}
                </p>
            )}
        </div>
    );
}

function InfoColumn({
    title,
    icon,
    children,
}: {
    title: string;
    icon: ReactNode;
    children: ReactNode;
}) {
    return (
        <div>
            <h3 className="mb-4 flex items-center gap-2 text-sm font-bold text-neutral-900">
                <span className="text-neutral-500">{icon}</span>
                {title}
            </h3>
            {children}
        </div>
    );
}

function DetailValue({
    label,
    value,
    emphasis = false,
    multiline = false,
}: {
    label: string;
    value: ReactNode;
    emphasis?: boolean;
    multiline?: boolean;
}) {
    return (
        <div className={multiline ? 'sm:col-span-2 lg:col-span-2' : undefined}>
            <dt className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">
                {label}
            </dt>
            <dd
                className={`mt-1 text-sm ${emphasis ? 'text-lg font-bold text-[#800020]' : 'text-neutral-800'} ${multiline ? 'whitespace-pre-wrap' : ''}`}
            >
                {value}
            </dd>
        </div>
    );
}

function StatusPill({ status }: { status: string }) {
    return (
        <span
            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${ORDER_STATUS_COLORS[status] ?? 'bg-neutral-100 text-neutral-700'}`}
        >
            {orderStatusLabel(status)}
        </span>
    );
}

function formatCurrency(value: number): string {
    return `$${value.toFixed(2)}`;
}

function fileActionLabel(status: string, c: any): string {
    return (
        c.file_action_labels?.[status] ??
        c.file_action_labels?.default ??
        c.file_downloads_link ??
        'View files'
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function hasValue(value: unknown): boolean {
    if (value === null || value === undefined || value === '') {
        return false;
    }

    if (Array.isArray(value)) {
        return value.length > 0;
    }

    if (typeof value === 'object') {
        return Object.keys(value).length > 0;
    }

    return true;
}

function humanize(value: string | null): string {
    if (!value) {
        return '';
    }

    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}
