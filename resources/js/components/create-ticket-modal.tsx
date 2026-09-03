import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { useContent } from '@/hooks/use-content';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface CreateTicketModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export default function CreateTicketModal({
    isOpen,
    onClose,
}: CreateTicketModalProps) {
    const c = useContent('shop_tickets_create_page') as any;
    const { data, setData, post, processing, errors, reset, transform } =
        useForm({
            subject: '',
            message: '',
            priority: 'medium',
            order_id: '',
        });

    // Reset form fields when modal closes
    useEffect(() => {
        if (!isOpen) {
            reset();
        }
    }, [isOpen, reset]);

    const submit = () => {
        transform((data) => ({
            ...data,
            order_id:
                data.order_id && data.order_id.trim() !== ''
                    ? isNaN(Number(data.order_id))
                        ? data.order_id
                        : Number(data.order_id)
                    : null,
        }));
        post('/tickets');
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="bg-white text-neutral-900 sm:max-w-lg dark:bg-[#161615] dark:text-neutral-50">
                <DialogHeader>
                    <DialogTitle className="text-2xl font-bold tracking-tight">
                        {c.page_heading}
                    </DialogTitle>
                    <DialogDescription className="text-sm text-neutral-500">
                        {c.seo_title}
                    </DialogDescription>
                </DialogHeader>

                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        submit();
                    }}
                    className="mt-2 space-y-4"
                >
                    <div>
                        <label
                            htmlFor="subject"
                            className="mb-1 block text-sm font-medium"
                        >
                            {c.labels.subject}
                        </label>
                        <input
                            id="subject"
                            type="text"
                            value={data.subject}
                            onChange={(e) => setData('subject', e.target.value)}
                            className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm text-neutral-900 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-neutral-100"
                            placeholder={c.placeholders.subject}
                            required
                        />
                        {errors.subject && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.subject}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="priority"
                            className="mb-1 block text-sm font-medium"
                        >
                            {c.labels.priority}
                        </label>
                        <select
                            id="priority"
                            value={data.priority}
                            onChange={(e) =>
                                setData('priority', e.target.value)
                            }
                            className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm text-neutral-900 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-neutral-100"
                        >
                            <option value="low">
                                {c.priority_options.low}
                            </option>
                            <option value="medium">
                                {c.priority_options.medium}
                            </option>
                            <option value="high">
                                {c.priority_options.high}
                            </option>
                        </select>
                        {errors.priority && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.priority}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="order_id"
                            className="mb-1 block text-sm font-medium"
                        >
                            {c.labels.order_id}
                        </label>
                        <input
                            id="order_id"
                            type="text"
                            value={data.order_id}
                            onChange={(e) =>
                                setData('order_id', e.target.value)
                            }
                            className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm text-neutral-900 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-neutral-100"
                            placeholder={c.placeholders.order_id}
                        />
                        {errors.order_id && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.order_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="message"
                            className="mb-1 block text-sm font-medium"
                        >
                            {c.labels.message}
                        </label>
                        <textarea
                            id="message"
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2.5 text-sm text-neutral-900 focus:ring-2 focus:ring-primary/20 focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-neutral-100"
                            placeholder={c.placeholders.message}
                            required
                        />
                        {errors.message && (
                            <p className="mt-1 text-sm text-red-600">
                                {errors.message}
                            </p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-lg bg-primary px-6 py-3 font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {processing ? c.buttons.submitting : c.buttons.submit}
                    </button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
