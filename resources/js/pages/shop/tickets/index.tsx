import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';

interface Ticket {
    id: number;
    subject: string;
    status: string;
    priority: string;
    replies_count?: number;
    created_at: string;
}

interface Props {
    tickets: {
        data: Ticket[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}

const statusColors: Record<string, string> = {
    open: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    closed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
};

const priorityColors: Record<string, string> = {
    low: 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
    medium: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    high: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
};

export default function TicketIndex({ tickets }: Props) {
    return (
        <>
            <SEO title="Support Tickets" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">PrintPandora</Link>
                        <nav className="flex items-center gap-4 text-sm">
                            <Link href="/shop" className="text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]">Shop</Link>
                            <Link href="/orders" className="text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]">Orders</Link>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-3xl font-semibold tracking-tight">Support Tickets</h1>
                        <Link
                            href="/tickets/create"
                            className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                        >
                            New Ticket
                        </Link>
                    </div>

                    {tickets.data.length === 0 ? (
                        <p className="text-[#706f6c]">No tickets yet.</p>
                    ) : (
                        <div className="space-y-4">
                            {tickets.data.map((ticket) => (
                                <Link
                                    key={ticket.id}
                                    href={`/tickets/${ticket.id}`}
                                    className="block rounded-lg border border-[#e3e3e0] bg-white p-6 transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <span className="font-semibold">{ticket.subject}</span>
                                        <div className="flex items-center gap-2">
                                            <span className={`rounded-full px-3 py-1 text-xs font-medium capitalize ${statusColors[ticket.status] ?? 'bg-neutral-100'}`}>
                                                {ticket.status.replace('_', ' ')}
                                            </span>
                                            <span className={`rounded-full px-3 py-1 text-xs font-medium capitalize ${priorityColors[ticket.priority] ?? 'bg-neutral-100'}`}>
                                                {ticket.priority}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex items-center justify-between text-sm text-[#706f6c]">
                                        <span>{ticket.replies_count ?? 0} {ticket.replies_count === 1 ? 'reply' : 'replies'}</span>
                                        <span>
                                            {new Date(ticket.created_at).toLocaleDateString('en-US', {
                                                year: 'numeric', month: 'long', day: 'numeric',
                                            })}
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}

                    {tickets.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-center gap-4">
                            {tickets.prev_page_url && (
                                <Link href={tickets.prev_page_url} className="text-sm text-amber-600 hover:text-amber-700">
                                    Previous
                                </Link>
                            )}
                            <span className="text-sm text-[#706f6c]">Page {tickets.current_page} of {tickets.last_page}</span>
                            {tickets.next_page_url && (
                                <Link href={tickets.next_page_url} className="text-sm text-amber-600 hover:text-amber-700">
                                    Next
                                </Link>
                            )}
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
