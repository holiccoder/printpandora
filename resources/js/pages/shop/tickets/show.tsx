import { Link, useForm } from '@inertiajs/react';
import SEO from '@/components/seo';

interface Reply {
    id: number;
    message: string;
    user_name: string;
    is_admin: boolean;
    created_at: string;
}

interface Ticket {
    id: number;
    subject: string;
    status: string;
    priority: string;
    order_id: number | null;
    created_at: string;
    replies: Reply[];
}

interface Props {
    ticket: Ticket;
}

const statusColors: Record<string, string> = {
    open: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    closed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
};

export default function TicketShow({ ticket }: Props) {
    const { data, setData, post, processing, reset } = useForm({
        message: '',
    });

    const submitReply = () => {
        post(`/tickets/${ticket.id}/reply`, {
            onSuccess: () => reset(),
        });
    };

    return (
        <>
            <SEO title={`Ticket #${ticket.id}`} />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">PrintPandora</Link>
                        <Link href="/tickets" className="text-sm text-[#706f6c] hover:text-[#1b1b18]">My Tickets</Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
                    <Link href="/tickets" className="mb-6 inline-flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        All tickets
                    </Link>

                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-3xl font-semibold tracking-tight">{ticket.subject}</h1>
                        <div className="flex items-center gap-2">
                            <span className={`rounded-full px-3 py-1 text-sm font-medium capitalize ${statusColors[ticket.status] ?? 'bg-neutral-100'}`}>
                                {ticket.status.replace('_', ' ')}
                            </span>
                        </div>
                    </div>

                    <p className="mb-8 text-sm text-[#706f6c]">
                        Opened {new Date(ticket.created_at).toLocaleDateString('en-US', {
                            year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit',
                        })}
                        {ticket.order_id && <> &middot; Order #{ticket.order_id}</>}
                    </p>

                    <div className="mb-8 space-y-4">
                        {ticket.replies.map((reply) => (
                            <div
                                key={reply.id}
                                className={`rounded-lg border p-4 ${
                                    reply.is_admin
                                        ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950'
                                        : 'border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]'
                                }`}
                            >
                                <div className="mb-2 flex items-center gap-2">
                                    <span className="text-sm font-semibold">{reply.user_name}</span>
                                    {reply.is_admin && (
                                        <span className="rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                                            Staff
                                        </span>
                                    )}
                                    <span className="text-xs text-[#706f6c]">
                                        {new Date(reply.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
                                        })}
                                    </span>
                                </div>
                                <p className="whitespace-pre-wrap text-sm">{reply.message}</p>
                            </div>
                        ))}
                    </div>

                    {ticket.status !== 'closed' && (
                        <div className="rounded-lg border border-[#e3e3e0] bg-white p-4 dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <h2 className="mb-3 text-lg font-semibold">Add Reply</h2>
                            <form onSubmit={(e) => {
 e.preventDefault(); submitReply(); 
}} className="space-y-4">
                                <textarea
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    rows={4}
                                    className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                    placeholder="Type your reply..."
                                />
                                <button
                                    type="submit"
                                    disabled={processing || !data.message}
                                    className="rounded-lg bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-50"
                                >
                                    {processing ? 'Sending...' : 'Send Reply'}
                                </button>
                            </form>
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
