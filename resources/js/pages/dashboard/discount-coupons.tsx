// Content (labels/headings/filters) sourced from `content/hardcoded-content.json` via useContent('dashboard_discount_coupons_page').
import { Link } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, Search, TicketPercent } from 'lucide-react';
import { useMemo, useState } from 'react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#800020';

type Coupon = {
    code: string;
    type: 'percent' | 'fixed';
    value: number;
    minimum_subtotal: number;
    starts_at: string | null;
    ends_at: string | null;
    first_order_only: boolean;
};

type Props = {
    coupons: Coupon[];
};

type CouponFilter = 'all' | Coupon['type'];

export default function DashboardDiscountCoupons({ coupons }: Props) {
    const c = useContent('dashboard_discount_coupons_page') as any;
    const [search, setSearch] = useState('');
    const [typeFilter, setTypeFilter] = useState<CouponFilter>('all');

    const filteredCoupons = useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();

        return coupons.filter((coupon) => {
            const matchesSearch =
                normalizedSearch === '' ||
                coupon.code.toLowerCase().includes(normalizedSearch);
            const matchesType =
                typeFilter === 'all' || coupon.type === typeFilter;

            return matchesSearch && matchesType;
        });
    }, [coupons, search, typeFilter]);

    const resultLabel =
        filteredCoupons.length === 1
            ? c.results_count_singular
            : c.results_count_plural;

    return (
        <StorefrontLayout>
            <SEO title={c.seo.title} description={c.seo.description} />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-5xl px-4 py-10 lg:py-14">
                    <header className="mb-6">
                        <Link
                            href="/dashboard"
                            className="mb-3 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                            style={{ color: ACCENT }}
                        >
                            <ChevronLeft className="size-4" /> {c.back_link}
                        </Link>
                        <div className="flex items-start gap-3">
                            <span
                                className="mt-1 inline-flex rounded-md bg-[#800020]/10 p-2"
                                style={{ color: ACCENT }}
                            >
                                <TicketPercent className="size-5" />
                            </span>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                                    {c.page_heading}
                                </h1>
                                <p className="mt-1 text-sm text-neutral-600">
                                    {c.page_subheading}
                                </p>
                            </div>
                        </div>
                    </header>

                    <div className="mb-6 rounded-lg border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
                        <div className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_12rem]">
                            <label className="grid gap-1.5">
                                <span className="text-xs font-semibold tracking-wide text-neutral-600 uppercase">
                                    {c.filters.search_label}
                                </span>
                                <span className="relative">
                                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-neutral-400" />
                                    <input
                                        type="search"
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder={
                                            c.filters.search_placeholder
                                        }
                                        className="h-10 w-full rounded-md border border-neutral-200 bg-white pr-3 pl-9 text-sm text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-[#800020] focus:ring-2 focus:ring-[#800020]/10"
                                    />
                                </span>
                            </label>

                            <label className="grid gap-1.5">
                                <span className="text-xs font-semibold tracking-wide text-neutral-600 uppercase">
                                    {c.filters.type_label}
                                </span>
                                <select
                                    value={typeFilter}
                                    onChange={(event) =>
                                        setTypeFilter(
                                            event.target.value as CouponFilter,
                                        )
                                    }
                                    className="h-10 rounded-md border border-neutral-200 bg-white px-3 text-sm text-neutral-900 outline-none focus:border-[#800020] focus:ring-2 focus:ring-[#800020]/10"
                                >
                                    <option value="all">
                                        {c.filters.type_options.all}
                                    </option>
                                    <option value="percent">
                                        {c.filters.type_options.percent}
                                    </option>
                                    <option value="fixed">
                                        {c.filters.type_options.fixed}
                                    </option>
                                </select>
                            </label>
                        </div>
                        <p className="mt-4 text-sm text-neutral-600">
                            <span className="font-semibold text-neutral-900">
                                {filteredCoupons.length}
                            </span>{' '}
                            {resultLabel}
                        </p>
                    </div>

                    {filteredCoupons.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-neutral-300 bg-white px-6 py-12 text-center shadow-sm">
                            <TicketPercent className="mx-auto size-8 text-neutral-300" />
                            <p className="mt-3 text-sm text-neutral-600">
                                {coupons.length === 0
                                    ? c.empty_state.no_coupons
                                    : c.empty_state.no_matches}
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            {filteredCoupons.map((coupon) => (
                                <CouponCard
                                    key={coupon.code}
                                    coupon={coupon}
                                    c={c.card}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </StorefrontLayout>
    );
}

function CouponCard({ coupon, c }: { coupon: Coupon; c: any }) {
    return (
        <article className="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="font-mono text-base font-bold tracking-wide text-neutral-900">
                        {coupon.code}
                    </p>
                    <p
                        className="mt-1 text-sm font-semibold"
                        style={{ color: ACCENT }}
                    >
                        {coupon.type === 'percent'
                            ? `${formatNumber(coupon.value)}% ${c.percent_off_label}`
                            : `${formatMoney(coupon.value)} ${c.fixed_off_label}`}
                    </p>
                </div>
                <span className="inline-flex shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                    {c.available_label}
                </span>
            </div>

            <dl className="mt-5 space-y-3 border-t border-neutral-100 pt-4 text-sm">
                <DetailRow
                    label={c.minimum_subtotal_label}
                    value={
                        coupon.minimum_subtotal > 0
                            ? formatMoney(coupon.minimum_subtotal)
                            : c.no_minimum_label
                    }
                />
                <DetailRow
                    label={c.validity_label}
                    value={
                        coupon.ends_at
                            ? `${c.valid_until_prefix} ${formatDate(coupon.ends_at)}`
                            : c.no_expiry_label
                    }
                />
                {coupon.first_order_only && (
                    <DetailRow
                        label={c.eligibility_label}
                        value={c.first_order_label}
                    />
                )}
            </dl>

            <div className="mt-4 flex items-center gap-2 text-xs text-neutral-500">
                <CalendarDays className="size-3.5" />
                <span>{c.checkout_hint}</span>
            </div>
        </article>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4">
            <dt className="text-neutral-500">{label}</dt>
            <dd className="text-right font-medium text-neutral-900">{value}</dd>
        </div>
    );
}

function formatNumber(value: number): string {
    return Number.isInteger(value) ? String(value) : value.toFixed(2);
}

function formatMoney(value: number): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(value);
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}
