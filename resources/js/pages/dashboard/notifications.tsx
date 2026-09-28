import { Link, router } from '@inertiajs/react';
import { Bell, Check, ExternalLink } from 'lucide-react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

type CustomerNotification = {
    id: string;
    category: 'system' | 'admin' | string;
    type: 'system' | 'admin' | string;
    title: string;
    body: string;
    action_url: string | null;
    order_id: number | null;
    read_at: string | null;
    created_at: string | null;
};

type PaginatedNotifications = {
    data: CustomerNotification[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    notifications: PaginatedNotifications;
    unreadCount: number;
};

export default function DashboardNotifications({
    notifications,
    unreadCount,
}: Props) {
    const markAsRead = (notification: CustomerNotification) => {
        if (notification.read_at) {
            return;
        }

        router.patch(
            `/dashboard/notifications/${encodeURIComponent(notification.id)}/read`,
            {},
            { preserveScroll: true },
        );
    };

    const openNotification = (notification: CustomerNotification) => {
        if (notification.read_at) {
            if (notification.action_url) {
                router.visit(notification.action_url);
            }

            return;
        }

        router.patch(
            `/dashboard/notifications/${encodeURIComponent(notification.id)}/read`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    if (notification.action_url) {
                        router.visit(notification.action_url);
                    }
                },
            },
        );
    };

    const markAllAsRead = () => {
        router.post(
            '/dashboard/notifications/read-all',
            {},
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <StorefrontLayout>
            <SEO
                title="Notifications"
                description="Your InkPavo notifications."
            />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-4xl px-4 py-10 lg:py-14">
                    <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2 text-[#800020]">
                                <Bell className="size-5" />
                                <span className="text-sm font-semibold">
                                    Customer center
                                </span>
                            </div>
                            <h1 className="mt-2 text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                                Notifications
                            </h1>
                            <p className="mt-1 text-sm text-neutral-600">
                                Order updates and messages from the InkPavo
                                team.
                            </p>
                        </div>

                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={markAllAsRead}
                                className="inline-flex items-center gap-1.5 rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm font-semibold text-neutral-700 shadow-sm hover:border-[#800020] hover:text-[#800020]"
                            >
                                <Check className="size-4" />
                                Mark all as read
                            </button>
                        )}
                    </header>

                    <div className="overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-sm">
                        {notifications.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <Bell className="mx-auto size-10 text-neutral-300" />
                                <p className="mt-3 text-sm font-medium text-neutral-700">
                                    You are all caught up.
                                </p>
                                <p className="mt-1 text-sm text-neutral-500">
                                    New order updates will appear here.
                                </p>
                            </div>
                        ) : (
                            <ul className="divide-y divide-neutral-100">
                                {notifications.data.map((notification) => (
                                    <li
                                        key={notification.id}
                                        className={`px-5 py-5 sm:px-6 ${notification.read_at ? 'bg-white' : 'bg-[#800020]/[0.03]'}`}
                                    >
                                        <div className="flex gap-4">
                                            <span
                                                className={`mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full ${notification.category === 'admin' ? 'bg-amber-100 text-amber-700' : 'bg-[#800020]/10 text-[#800020]'}`}
                                            >
                                                <Bell className="size-4" />
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-start justify-between gap-2">
                                                    <div>
                                                        <p className="font-semibold text-neutral-900">
                                                            {notification.title}
                                                        </p>
                                                        <span className="mt-1 inline-flex rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">
                                                            {notification.category ===
                                                            'admin'
                                                                ? 'Administrator'
                                                                : 'System'}
                                                        </span>
                                                    </div>
                                                    {notification.created_at && (
                                                        <time
                                                            dateTime={
                                                                notification.created_at
                                                            }
                                                            className="text-xs text-neutral-500"
                                                        >
                                                            {new Date(
                                                                notification.created_at,
                                                            ).toLocaleString()}
                                                        </time>
                                                    )}
                                                </div>
                                                <p className="mt-3 text-sm leading-6 whitespace-pre-wrap text-neutral-600">
                                                    {notification.body}
                                                </p>
                                                <div className="mt-4 flex flex-wrap items-center gap-4">
                                                    {notification.action_url && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                openNotification(
                                                                    notification,
                                                                )
                                                            }
                                                            className="inline-flex items-center gap-1 text-sm font-semibold text-[#800020] hover:underline"
                                                        >
                                                            View details
                                                            <ExternalLink className="size-3.5" />
                                                        </button>
                                                    )}
                                                    {!notification.read_at && (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                markAsRead(
                                                                    notification,
                                                                )
                                                            }
                                                            className="text-sm font-medium text-neutral-500 hover:text-neutral-900 hover:underline"
                                                        >
                                                            Mark as read
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                            {!notification.read_at && (
                                                <span className="mt-2 size-2 shrink-0 rounded-full bg-[#800020]" />
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {notifications.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-neutral-100 px-5 py-3 text-sm sm:px-6">
                                <span className="text-neutral-500">
                                    Showing {notifications.from ?? 0}–
                                    {notifications.to ?? 0} of{' '}
                                    {notifications.total}
                                </span>
                                <div className="flex gap-2">
                                    {notifications.prev_page_url ? (
                                        <Link
                                            href={notifications.prev_page_url}
                                            preserveScroll
                                            className="rounded-md border border-neutral-200 px-3 py-1.5 font-medium text-neutral-700 hover:bg-neutral-50"
                                        >
                                            Previous
                                        </Link>
                                    ) : null}
                                    {notifications.next_page_url ? (
                                        <Link
                                            href={notifications.next_page_url}
                                            preserveScroll
                                            className="rounded-md border border-neutral-200 px-3 py-1.5 font-medium text-neutral-700 hover:bg-neutral-50"
                                        >
                                            Next
                                        </Link>
                                    ) : null}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
