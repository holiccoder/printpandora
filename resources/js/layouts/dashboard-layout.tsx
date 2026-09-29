import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    CircleUserRound,
    LayoutDashboard,
    LifeBuoy,
    Package,
    TicketPercent,
    UsersRound,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useContent } from '@/hooks/use-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#800020';

const navigation = [
    { href: '/dashboard', label: 'overview', icon: LayoutDashboard },
    { href: '/dashboard/orders', label: 'orders', icon: Package },
    {
        href: '/dashboard/notifications',
        label: 'notifications',
        icon: Bell,
    },
    {
        href: '/dashboard/discount-coupons',
        label: 'discount_coupons',
        icon: TicketPercent,
    },
    { href: '/tickets', label: 'support_tickets', icon: LifeBuoy },
    {
        href: '/settings/affiliate',
        label: 'affiliate',
        icon: UsersRound,
    },
    {
        href: '/dashboard/profile',
        label: 'profile',
        icon: CircleUserRound,
    },
];

type Props = {
    children: ReactNode;
};

export default function DashboardLayout({ children }: Props) {
    return (
        <StorefrontLayout>
            <section className="bg-neutral-50">
                <div className="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:py-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-8 lg:py-14">
                    <DashboardSidebar />
                    <main className="min-w-0">{children}</main>
                </div>
            </section>
        </StorefrontLayout>
    );
}

function DashboardSidebar() {
    const c = useContent('dashboard_navigation') as any;
    const { isCurrentUrl } = useCurrentUrl();
    const { auth, customer_notifications: customerNotifications } = usePage()
        .props as any;
    const unreadCount = customerNotifications?.unread_count ?? 0;

    return (
        <aside className="lg:sticky lg:top-24 lg:self-start">
            <div className="rounded-xl border border-neutral-200 bg-white p-3 shadow-sm">
                <div className="hidden border-b border-neutral-100 px-3 pb-3 lg:block">
                    <p className="text-xs font-semibold tracking-wide text-neutral-500 uppercase">
                        {c.title}
                    </p>
                    {auth?.user?.name && (
                        <p className="mt-1 truncate text-sm font-semibold text-neutral-900">
                            {auth.user.name}
                        </p>
                    )}
                </div>

                <nav
                    aria-label={c.aria_label}
                    className="flex gap-1 overflow-x-auto lg:mt-2 lg:block lg:space-y-1"
                >
                    {navigation.map((item) => {
                        const active = isCurrentUrl(item.href);
                        const Icon = item.icon;

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`group flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors lg:w-full ${
                                    active
                                        ? 'bg-[#800020]/10 text-[#800020]'
                                        : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'
                                }`}
                                aria-current={active ? 'page' : undefined}
                            >
                                <Icon
                                    className="size-4 shrink-0"
                                    strokeWidth={active ? 2.4 : 2}
                                />
                                <span className="whitespace-nowrap">
                                    {c.links[item.label]}
                                </span>
                                {item.href === '/dashboard/notifications' &&
                                    unreadCount > 0 && (
                                        <span
                                            className="ml-auto rounded-full px-1.5 py-0.5 text-[10px] leading-4 font-bold text-white"
                                            style={{ backgroundColor: ACCENT }}
                                        >
                                            {unreadCount > 99
                                                ? '99+'
                                                : unreadCount}
                                        </span>
                                    )}
                            </Link>
                        );
                    })}
                </nav>
            </div>
        </aside>
    );
}
