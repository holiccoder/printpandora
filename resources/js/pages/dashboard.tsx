// Content (labels/headings/links) sourced from `content/hardcoded-content.json` via useContent('dashboard_index_page').
import { Link } from '@inertiajs/react';
import { ChevronRight, MapPin, Package, ShieldCheck, User as UserIcon } from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#0f4c3a';

type RecentOrder = {
    id: number;
    status: string;
    payment_status: string;
    total: number;
    item_count: number;
    created_at: string | null;
};

type Address = {
    name: string | null;
    phone: string | null;
    line: string | null;
    city: string | null;
    state: string | null;
    zip: string | null;
    country: string | null;
};

type AffiliateInfo = {
    referral_code: string;
    commission_rate: number;
    status: string;
    total_earnings: number;
    paid_earnings: number;
    pending_earnings: number;
    referral_url: string;
};

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        email_verified_at: string | null;
        created_at: string | null;
    };
    recentOrders: RecentOrder[];
    address: Address | null;
    affiliate: AffiliateInfo | null;
};

export default function Dashboard({ user, recentOrders, address, affiliate }: Props) {
    const c = useContent('dashboard_index_page') as any;

    return (
        <StorefrontLayout>
            <SEO
                title={c.seo.title}
                description={c.seo.description}
            />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-10 lg:py-14">
                    <header className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                            {String(c.welcome_heading_template).replace('{user.name}', user.name)}
                        </h1>
                        <p className="mt-2 text-sm text-neutral-600 sm:text-base">
                            {c.welcome_description}
                        </p>
                    </header>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ProfileCard user={user} c={c.cards.profile} />
                        <RecentOrdersCard orders={recentOrders} c={c.cards.recent_orders} />
                        <AddressCard address={address} c={c.cards.address} />
                        <AffiliateCard affiliate={affiliate} c={c.cards.affiliate} />
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}

/* -------------------------------- cards -------------------------------- */

function Card({
    title,
    icon,
    action,
    children,
}: {
    title: string;
    icon: React.ReactNode;
    action?: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <article className="flex flex-col rounded-lg border border-neutral-200 bg-white shadow-sm">
            <header className="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
                <div className="flex items-center gap-2">
                    <span style={{ color: ACCENT }}>{icon}</span>
                    <h2 className="text-base font-bold text-neutral-900">{title}</h2>
                </div>
                {action}
            </header>
            <div className="flex-1 px-5 py-4">{children}</div>
        </article>
    );
}

function ProfileCard({ user, c }: { user: Props['user']; c: any }) {
    const verified = user.email_verified_at !== null;

    return (
        <Card
            title={c.title}
            icon={<UserIcon className="size-4" />}
            action={
                <Link
                    href="/dashboard/profile"
                    className="text-sm font-semibold hover:underline"
                    style={{ color: ACCENT }}
                >
                    {c.edit_link}
                </Link>
            }
        >
            <dl className="space-y-3 text-sm">
                <Row label={c.rows.name} value={user.name} />
                <Row label={c.rows.email} value={user.email} />
                <Row
                    label={c.rows.status}
                    value={
                        <span
                            className={
                                verified
                                    ? 'inline-flex items-center gap-1 text-[#0f4c3a]'
                                    : 'inline-flex items-center gap-1 text-amber-600'
                            }
                        >
                            <ShieldCheck className="size-3.5" />
                            {verified ? c.status_verified : c.status_unverified}
                        </span>
                    }
                />
                {user.created_at && (
                    <Row
                        label={c.rows.member_since}
                        value={new Date(user.created_at).toLocaleDateString()}
                    />
                )}
            </dl>
        </Card>
    );
}

function RecentOrdersCard({ orders, c }: { orders: RecentOrder[]; c: any }) {
    return (
        <Card
            title={c.title}
            icon={<Package className="size-4" />}
            action={
                <Link
                    href="/dashboard/orders"
                    className="inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                    style={{ color: ACCENT }}
                >
                    {c.view_all_link} <ChevronRight className="size-3.5" />
                </Link>
            }
        >
            {orders.length === 0 ? (
                <EmptyHint>
                    {c.empty_state_prefix}
                    <Link
                        href="/shop"
                        className="font-semibold hover:underline"
                        style={{ color: ACCENT }}
                    >
                        {c.empty_state_link}
                    </Link>
                    {c.empty_state_suffix}
                </EmptyHint>
            ) : (
                <ul className="divide-y divide-neutral-100">
                    {orders.map((order) => (
                        <li key={order.id} className="flex items-center justify-between py-3">
                            <div>
                                <p className="text-sm font-semibold text-neutral-900">
                                    {c.order_label_prefix}{order.id}
                                </p>
                                <p className="text-xs text-neutral-500">
                                    {order.created_at &&
                                        new Date(order.created_at).toLocaleDateString()}{' '}
                                    · {order.item_count} {order.item_count === 1 ? c.item_singular : c.item_plural}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <StatusPill status={order.status} />
                                <span className="text-sm font-semibold text-neutral-900">
                                    ${order.total.toFixed(2)}
                                </span>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}

function AddressCard({ address, c }: { address: Address | null; c: any }) {
    return (
        <Card title={c.title} icon={<MapPin className="size-4" />}>
            {!address ? (
                <EmptyHint>
                    {c.empty_state}
                </EmptyHint>
            ) : (
                <div className="space-y-1 text-sm leading-relaxed text-neutral-700">
                    {address.name && <p className="font-semibold text-neutral-900">{address.name}</p>}
                    {address.line && <p>{address.line}</p>}
                    <p>
                        {[address.city, address.state, address.zip].filter(Boolean).join(', ')}
                    </p>
                    {address.country && <p>{address.country}</p>}
                    {address.phone && (
                        <p className="pt-1 text-xs text-neutral-500">{c.phone_label} {address.phone}</p>
                    )}
                </div>
            )}
        </Card>
    );
}

function AffiliateCard({ affiliate, c }: { affiliate: AffiliateInfo | null; c: any }) {
    return (
        <Card
            title={c.title}
            icon={
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    className="size-4"
                    aria-hidden
                >
                    <circle cx="9" cy="7" r="4" />
                    <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    <path d="M21 21v-2a4 4 0 0 0-3-3.87" />
                </svg>
            }
            action={
                <Link
                    href="/settings/affiliate"
                    className="text-sm font-semibold hover:underline"
                    style={{ color: ACCENT }}
                >
                    {c.manage_link}
                </Link>
            }
        >
            {!affiliate ? (
                <EmptyHint>
                    {c.empty_state_prefix}
                    <Link
                        href="/settings/affiliate"
                        className="font-semibold hover:underline"
                        style={{ color: ACCENT }}
                    >
                        {c.empty_state_link}
                    </Link>
                    {c.empty_state_suffix}
                </EmptyHint>
            ) : (
                <div className="space-y-4 text-sm">
                    <Row
                        label={c.rows.referral_code}
                        value={
                            <code className="rounded bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-900">
                                {affiliate.referral_code}
                            </code>
                        }
                    />
                    <Row
                        label={c.rows.commission_rate}
                        value={`${affiliate.commission_rate.toFixed(2)}%`}
                    />
                    <div className="grid grid-cols-3 gap-3 pt-2">
                        <Stat label={c.stats.total_earned} value={`$${affiliate.total_earnings.toFixed(2)}`} />
                        <Stat label={c.stats.paid_out} value={`$${affiliate.paid_earnings.toFixed(2)}`} />
                        <Stat
                            label={c.stats.pending}
                            value={`$${affiliate.pending_earnings.toFixed(2)}`}
                            highlight
                        />
                    </div>
                </div>
            )}
        </Card>
    );
}

/* ------------------------------- helpers ------------------------------- */

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-3">
            <dt className="text-xs font-medium uppercase tracking-wide text-neutral-500">{label}</dt>
            <dd className="text-right text-sm text-neutral-900">{value}</dd>
        </div>
    );
}

function Stat({
    label,
    value,
    highlight,
}: {
    label: string;
    value: string;
    highlight?: boolean;
}) {
    return (
        <div className="rounded-md border border-neutral-100 bg-neutral-50 px-3 py-2">
            <p className="text-[10px] font-medium uppercase tracking-wide text-neutral-500">
                {label}
            </p>
            <p
                className={`mt-1 text-base font-bold ${highlight ? '' : 'text-neutral-900'}`}
                style={highlight ? { color: ACCENT } : undefined}
            >
                {value}
            </p>
        </div>
    );
}

function EmptyHint({ children }: { children: React.ReactNode }) {
    return <p className="text-sm text-neutral-600">{children}</p>;
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