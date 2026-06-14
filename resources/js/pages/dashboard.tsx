import { Link } from '@inertiajs/react';
import { ChevronRight, MapPin, Package, ShieldCheck, User as UserIcon } from 'lucide-react';
import SEO from '@/components/seo';
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
    return (
        <StorefrontLayout>
            <SEO
                title="Dashboard"
                description="Your PrintPandora dashboard - manage orders, designs, and account settings."
            />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-10 lg:py-14">
                    <header className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                            Welcome back, {user.name}
                        </h1>
                        <p className="mt-2 text-sm text-neutral-600 sm:text-base">
                            Manage your orders, profile, address and affiliate program from one place.
                        </p>
                    </header>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <ProfileCard user={user} />
                        <RecentOrdersCard orders={recentOrders} />
                        <AddressCard address={address} />
                        <AffiliateCard affiliate={affiliate} />
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

function ProfileCard({ user }: { user: Props['user'] }) {
    const verified = user.email_verified_at !== null;

    return (
        <Card
            title="Profile"
            icon={<UserIcon className="size-4" />}
            action={
                <Link
                    href="/dashboard/profile"
                    className="text-sm font-semibold hover:underline"
                    style={{ color: ACCENT }}
                >
                    Edit
                </Link>
            }
        >
            <dl className="space-y-3 text-sm">
                <Row label="Name" value={user.name} />
                <Row label="Email" value={user.email} />
                <Row
                    label="Status"
                    value={
                        <span
                            className={
                                verified
                                    ? 'inline-flex items-center gap-1 text-[#0f4c3a]'
                                    : 'inline-flex items-center gap-1 text-amber-600'
                            }
                        >
                            <ShieldCheck className="size-3.5" />
                            {verified ? 'Email verified' : 'Email not verified'}
                        </span>
                    }
                />
                {user.created_at && (
                    <Row
                        label="Member since"
                        value={new Date(user.created_at).toLocaleDateString()}
                    />
                )}
            </dl>
        </Card>
    );
}

function RecentOrdersCard({ orders }: { orders: RecentOrder[] }) {
    return (
        <Card
            title="Recent orders"
            icon={<Package className="size-4" />}
            action={
                <Link
                    href="/dashboard/orders"
                    className="inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                    style={{ color: ACCENT }}
                >
                    View all <ChevronRight className="size-3.5" />
                </Link>
            }
        >
            {orders.length === 0 ? (
                <EmptyHint>
                    You haven’t placed any orders yet.{' '}
                    <Link
                        href="/shop"
                        className="font-semibold hover:underline"
                        style={{ color: ACCENT }}
                    >
                        Start shopping
                    </Link>
                    .
                </EmptyHint>
            ) : (
                <ul className="divide-y divide-neutral-100">
                    {orders.map((order) => (
                        <li key={order.id} className="flex items-center justify-between py-3">
                            <div>
                                <p className="text-sm font-semibold text-neutral-900">
                                    Order #{order.id}
                                </p>
                                <p className="text-xs text-neutral-500">
                                    {order.created_at &&
                                        new Date(order.created_at).toLocaleDateString()}{' '}
                                    · {order.item_count} item{order.item_count === 1 ? '' : 's'}
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

function AddressCard({ address }: { address: Address | null }) {
    return (
        <Card title="Delivery address" icon={<MapPin className="size-4" />}>
            {!address ? (
                <EmptyHint>
                    No address on file yet — we’ll save your shipping details after your first order.
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
                        <p className="pt-1 text-xs text-neutral-500">Phone: {address.phone}</p>
                    )}
                </div>
            )}
        </Card>
    );
}

function AffiliateCard({ affiliate }: { affiliate: AffiliateInfo | null }) {
    return (
        <Card
            title="Affiliate program"
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
                    Manage
                </Link>
            }
        >
            {!affiliate ? (
                <EmptyHint>
                    You aren’t in the affiliate program yet.{' '}
                    <Link
                        href="/settings/affiliate"
                        className="font-semibold hover:underline"
                        style={{ color: ACCENT }}
                    >
                        Join now
                    </Link>{' '}
                    to start earning commissions.
                </EmptyHint>
            ) : (
                <div className="space-y-4 text-sm">
                    <Row
                        label="Referral code"
                        value={
                            <code className="rounded bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-900">
                                {affiliate.referral_code}
                            </code>
                        }
                    />
                    <Row
                        label="Commission rate"
                        value={`${affiliate.commission_rate.toFixed(2)}%`}
                    />
                    <div className="grid grid-cols-3 gap-3 pt-2">
                        <Stat label="Total earned" value={`$${affiliate.total_earnings.toFixed(2)}`} />
                        <Stat label="Paid out" value={`$${affiliate.paid_earnings.toFixed(2)}`} />
                        <Stat
                            label="Pending"
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
