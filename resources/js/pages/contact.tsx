import { useForm, usePage } from '@inertiajs/react';
import { Clock, Mail, MapPin, MessageSquare, Phone } from 'lucide-react';
import type { FormEventHandler } from 'react';
import InputError from '@/components/input-error';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import StorefrontLayout from '@/layouts/storefront-layout';

type ContactForm = {
    name: string;
    email: string;
    topic: string;
    subject: string;
    message: string;
};

const TOPICS = [
    'General enquiry',
    'Order help',
    'Product question',
    'Press & partnerships',
    'Other',
] as const;

export default function Contact() {
    const flashSuccess = (
        usePage().props.flash as { success?: string } | undefined
    )?.success;

    const { data, setData, post, processing, errors, reset } =
        useForm<ContactForm>({
            name: '',
            email: '',
            topic: TOPICS[0],
            subject: '',
            message: '',
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/contact', {
            preserveScroll: true,
            onSuccess: () => reset('subject', 'message'),
        });
    };

    return (
        <StorefrontLayout>
            <SEO
                title="Contact us"
                description="Get in touch with InkPavo. Questions about an order, a product, or a partnership? We'd love to hear from you."
            />

            {/* Page header */}
            <section className="bg-white py-14 md:py-20">
                <div className="mx-auto w-full max-w-3xl px-4 text-center md:px-6">
                    <p className="text-xs font-semibold tracking-wider text-[#1e3a5f] uppercase">
                        We're here to help
                    </p>
                    <h1 className="mt-2 text-3xl font-bold tracking-tight text-neutral-900 md:text-4xl lg:text-5xl">
                        Get in touch
                    </h1>
                    <p className="mx-auto mt-4 max-w-xl text-base text-neutral-600 md:text-lg">
                        Questions about an order, a product, or a partnership?
                        Drop us a message and a real human will get back to you
                        within one business day.
                    </p>
                </div>
            </section>

            {/* Form + sidebar */}
            <section className="bg-[#fbfaf6] py-12 md:py-16">
                <div className="mx-auto grid w-full max-w-6xl gap-10 px-4 md:grid-cols-3 md:px-6 lg:gap-14">
                    {/* Contact info sidebar */}
                    <aside className="md:col-span-1">
                        <h2 className="text-xl font-bold tracking-tight text-neutral-900">
                            Other ways to reach us
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            Prefer not to fill out a form? Pick whichever works
                            best for you.
                        </p>

                        <ul className="mt-6 space-y-5 text-sm text-neutral-700">
                            <InfoRow
                                icon={<Mail className="size-4" />}
                                label="Email"
                                value={
                                    <a
                                        href="mailto:hello@inkpavo.com"
                                        className="text-[#1e3a5f] hover:underline"
                                    >
                                        hello@inkpavo.com
                                    </a>
                                }
                            />
                            <InfoRow
                                icon={<Phone className="size-4" />}
                                label="Phone"
                                value={
                                    <a
                                        href="tel:+18005551234"
                                        className="text-[#1e3a5f] hover:underline"
                                    >
                                        +1 (800) 555-1234
                                    </a>
                                }
                            />
                            <InfoRow
                                icon={<Clock className="size-4" />}
                                label="Hours"
                                value={
                                    <>
                                        Mon–Fri · 9am – 6pm ET
                                        <br />
                                        Sat · 10am – 2pm ET
                                    </>
                                }
                            />
                            <InfoRow
                                icon={<MapPin className="size-4" />}
                                label="Studio"
                                value={
                                    <>
                                        100 Print Lane
                                        <br />
                                        Brooklyn, NY 11201
                                    </>
                                }
                            />
                            <InfoRow
                                icon={<MessageSquare className="size-4" />}
                                label="Order help"
                                value={
                                    <>
                                        Already a customer?{' '}
                                        <a
                                            href="/tickets/create"
                                            className="text-[#1e3a5f] hover:underline"
                                        >
                                            Open a support ticket
                                        </a>{' '}
                                        and we'll pick it up from your account.
                                    </>
                                }
                            />
                        </ul>
                    </aside>

                    {/* The form */}
                    <div className="md:col-span-2">
                        <div className="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm md:p-10">
                            {flashSuccess && (
                                <div
                                    role="status"
                                    className="mb-6 rounded-md border border-[#1e3a5f]/20 bg-[#eaf3ec] px-4 py-3 text-sm text-[#1e3a5f]"
                                >
                                    {flashSuccess}
                                </div>
                            )}

                            <form
                                onSubmit={submit}
                                className="space-y-5"
                                noValidate
                            >
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field
                                        id="name"
                                        label="Your name"
                                        error={errors.name}
                                    >
                                        <Input
                                            id="name"
                                            type="text"
                                            autoComplete="name"
                                            required
                                            value={data.name}
                                            onChange={(e) =>
                                                setData('name', e.target.value)
                                            }
                                        />
                                    </Field>
                                    <Field
                                        id="email"
                                        label="Email address"
                                        error={errors.email}
                                    >
                                        <Input
                                            id="email"
                                            type="email"
                                            autoComplete="email"
                                            required
                                            value={data.email}
                                            onChange={(e) =>
                                                setData('email', e.target.value)
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field
                                    id="topic"
                                    label="What can we help with?"
                                    error={errors.topic}
                                >
                                    <select
                                        id="topic"
                                        value={data.topic}
                                        onChange={(e) =>
                                            setData('topic', e.target.value)
                                        }
                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        {TOPICS.map((t) => (
                                            <option key={t} value={t}>
                                                {t}
                                            </option>
                                        ))}
                                    </select>
                                </Field>

                                <Field
                                    id="subject"
                                    label="Subject"
                                    optional
                                    error={errors.subject}
                                >
                                    <Input
                                        id="subject"
                                        type="text"
                                        maxLength={255}
                                        value={data.subject}
                                        onChange={(e) =>
                                            setData('subject', e.target.value)
                                        }
                                        placeholder="A short summary of your question"
                                    />
                                </Field>

                                <Field
                                    id="message"
                                    label="Message"
                                    error={errors.message}
                                    hint={`${data.message.length} / 5000`}
                                >
                                    <textarea
                                        id="message"
                                        required
                                        rows={6}
                                        maxLength={5000}
                                        value={data.message}
                                        onChange={(e) =>
                                            setData('message', e.target.value)
                                        }
                                        className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        placeholder={'Tell us what\'s on your mind\u2026'}
                                    />
                                </Field>

                                <p className="text-xs text-neutral-500">
                                    By submitting this form, you agree to our{' '}
                                    <a
                                        href="/privacy"
                                        className="text-[#1e3a5f] underline-offset-2 hover:underline"
                                    >
                                        privacy policy
                                    </a>
                                    . We only use your details to reply to your
                                    enquiry.
                                </p>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:ring-primary/40 sm:w-auto"
                                >
                                    {processing ? 'Sending\u2026' : 'Send message'}
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}

function InfoRow({
    icon,
    label,
    value,
}: {
    icon: React.ReactNode;
    label: string;
    value: React.ReactNode;
}) {
    return (
        <li className="flex gap-3">
            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-[#1e3a5f]/10 text-[#1e3a5f]">
                {icon}
            </span>
            <div>
                <p className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                    {label}
                </p>
                <p className="mt-1 text-sm leading-relaxed text-neutral-800">
                    {value}
                </p>
            </div>
        </li>
    );
}

function Field({
    id,
    label,
    error,
    optional,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    optional?: boolean;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <div className="mb-1.5 flex items-baseline justify-between">
                <Label
                    htmlFor={id}
                    className="text-sm font-medium text-neutral-800"
                >
                    {label}
                    {optional && (
                        <span className="ml-1 text-xs font-normal text-neutral-400">
                            (optional)
                        </span>
                    )}
                </Label>
                {hint && (
                    <span className="text-xs text-neutral-400">{hint}</span>
                )}
            </div>
            {children}
            <InputError message={error} className="mt-1" />
        </div>
    );
}
