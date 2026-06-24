// Content sourced from `content/hardcoded-content.json` via useContent('shop_tickets_create_page').
import { Link, useForm } from '@inertiajs/react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';

export default function TicketCreate() {
    const c = useContent('shop_tickets_create_page') as any;
    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        message: '',
        priority: 'medium',
        order_id: '',
    });

    const submit = () => {
        post('/tickets');
    };

    return (
        <>
            <SEO title={c.seo_title} />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">{c.brand}</Link>
                        <Link href="/tickets" className="text-sm text-[#706f6c] hover:text-[#1b1b18]">{c.nav_my_tickets}</Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
                    <Link href="/tickets" className="mb-6 inline-flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        {c.back_link}
                    </Link>

                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">{c.page_heading}</h1>

                    <form onSubmit={(e) => {
 e.preventDefault(); submit();
}} className="max-w-xl space-y-6">
                        <div>
                            <label htmlFor="subject" className="mb-1 block text-sm font-medium">{c.labels.subject}</label>
                            <input
                                id="subject"
                                type="text"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                placeholder={c.placeholders.subject}
                            />
                            {errors.subject && <p className="mt-1 text-sm text-red-600">{errors.subject}</p>}
                        </div>

                        <div>
                            <label htmlFor="priority" className="mb-1 block text-sm font-medium">{c.labels.priority}</label>
                            <select
                                id="priority"
                                value={data.priority}
                                onChange={(e) => setData('priority', e.target.value)}
                                className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                            >
                                <option value="low">{c.priority_options.low}</option>
                                <option value="medium">{c.priority_options.medium}</option>
                                <option value="high">{c.priority_options.high}</option>
                            </select>
                        </div>

                        <div>
                            <label htmlFor="order_id" className="mb-1 block text-sm font-medium">{c.labels.order_id}</label>
                            <input
                                id="order_id"
                                type="text"
                                value={data.order_id}
                                onChange={(e) => setData('order_id', e.target.value)}
                                className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                placeholder={c.placeholders.order_id}
                            />
                        </div>

                        <div>
                            <label htmlFor="message" className="mb-1 block text-sm font-medium">{c.labels.message}</label>
                            <textarea
                                id="message"
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                rows={6}
                                className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                placeholder={c.placeholders.message}
                            />
                            {errors.message && <p className="mt-1 text-sm text-red-600">{errors.message}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-primary px-6 py-3 font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {processing ? c.buttons.submitting : c.buttons.submit}
                        </button>
                    </form>
                </main>
            </div>
        </>
    );
}
