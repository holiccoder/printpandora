// Content sourced from `content/hardcoded-content.json` via useContent('checkout_page').
import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { countries, countriesByCode } from '@/data/countries';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';
import { useContent } from '@/hooks/use-content';

interface CartItem {
    key: string;
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    image: string | null;
    slug: string;
    options?: Record<string, string>;
}

interface PaypalConfig {
    client_id: string | null;
    mode: string;
    currency: string;
}

interface Props {
    cart: Record<string, CartItem>;
    subtotal: number;
    paypal: PaypalConfig;
}

declare global {
    interface Window {
        paypal?: any;
    }
}

type PaymentMethod = 'manual' | 'paypal';

export default function Checkout({ cart, subtotal, paypal }: Props) {
    const c = useContent('checkout_page') as any;
    const { data, setData, post, processing, errors } = useForm({
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        shipping_address: '',
        shipping_city: '',
        shipping_state: '',
        shipping_zip: '',
        shipping_country: c.form_sections.shipping_address.fields.country.default_value ?? 'US',
        notes: '',
    });

    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('manual');

    const availableStates = useMemo(
        () => countriesByCode[data.shipping_country]?.states ?? [],
        [data.shipping_country],
    );

    const handleCountryChange = (countryCode: string) => {
        const country = countriesByCode[countryCode];
        const newState = country?.states.some((s) => s.code === data.shipping_state)
            ? data.shipping_state
            : '';
        setData({ shipping_country: countryCode, shipping_state: newState });
    };

    const [paypalReady, setPaypalReady] = useState(false);
    const [paypalError, setPaypalError] = useState<string | null>(null);
    const [paypalProcessing, setPaypalProcessing] = useState(false);
    const paypalContainerRef = useRef<HTMLDivElement | null>(null);
    const paypalButtonsRef = useRef<any>(null);
    const dataRef = useRef(data);

    // Keep latest form values available inside PayPal SDK callbacks.
    useEffect(() => {
        dataRef.current = data;
    }, [data]);

    // Load the PayPal JS SDK once when the user picks PayPal.
    useEffect(() => {
        if (paymentMethod !== 'paypal') {
return;
}

        if (!paypal.client_id) {
            setPaypalError(c.error_messages.paypal_not_configured);

            return;
        }

        if (window.paypal) {
            setPaypalReady(true);

            return;
        }

        const scriptId = 'paypal-sdk';

        if (document.getElementById(scriptId)) {
return;
}

        const script = document.createElement('script');
        script.id = scriptId;
        script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(paypal.client_id)}&currency=${encodeURIComponent(paypal.currency)}&intent=capture`;
        script.async = true;
        script.onload = () => setPaypalReady(true);
        script.onerror = () => setPaypalError(c.error_messages.sdk_load_failed);
        document.body.appendChild(script);
    }, [paymentMethod, paypal.client_id, paypal.currency, c.error_messages]);

    // Render the PayPal button when the SDK is ready and the method is selected.
    useEffect(() => {
        if (paymentMethod !== 'paypal' || !paypalReady || !window.paypal) {
return;
}

        if (!paypalContainerRef.current) {
return;
}

        // Tear down a previous render before re-rendering.
        if (paypalButtonsRef.current?.close) {
            paypalButtonsRef.current.close();
            paypalButtonsRef.current = null;
        }

        paypalContainerRef.current.innerHTML = '';

        const csrfToken =
            (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';

        const buttons = window.paypal.Buttons({
            style: { layout: 'vertical', shape: 'rect', label: 'paypal' },

            createOrder: async () => {
                setPaypalError(null);
                const res = await fetch('/checkout/paypal/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                    },
                });
                const json = await res.json().catch(() => ({}));

                if (!res.ok || !json.id) {
                    throw new Error(json.error || c.error_messages.create_order_failed);
                }

                return json.id;
            },

            onApprove: async (paypalData: { orderID: string }) => {
                setPaypalProcessing(true);

                try {
                    const res = await fetch('/checkout/paypal/capture', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({
                            ...dataRef.current,
                            paypal_order_id: paypalData.orderID,
                        }),
                    });
                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        const msg =
                            json.error ||
                            (json.errors ? Object.values(json.errors).flat().join(' ') : null) ||
                            c.error_messages.capture_failed;
                        setPaypalError(msg);

                        return;
                    }

                    if (json.redirect) {
                        router.visit(json.redirect);
                    }
                } catch (err) {
                    setPaypalError((err as Error).message || c.error_messages.capture_failed);
                } finally {
                    setPaypalProcessing(false);
                }
            },

            onError: (err: unknown) => {
                console.error('PayPal error', err);
                setPaypalError(c.error_messages.generic_error);
            },

            onCancel: () => {
                setPaypalError(c.error_messages.cancelled);
            },
        });

        paypalButtonsRef.current = buttons;
        buttons.render(paypalContainerRef.current).catch((err: unknown) => {
            console.error('PayPal render error', err);
            setPaypalError(c.error_messages.render_failed);
        });

        return () => {
            if (paypalButtonsRef.current?.close) {
                paypalButtonsRef.current.close();
                paypalButtonsRef.current = null;
            }
        };
    }, [paymentMethod, paypalReady, c.error_messages]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (paymentMethod !== 'manual') {
return;
}

        const form = e.currentTarget as HTMLFormElement;
        const fd = new FormData(form);

        router.post('/checkout', Object.fromEntries(fd));
    };

    const contact = c.form_sections.contact_information;
    const shipping = c.form_sections.shipping_address;
    const payment = c.form_sections.payment_method;
    // Cache payment option lookups by `value` so we can read labels/descriptions
    // without depending on positional ordering.
    const payOpt = (value: string) =>
        payment.options.find((o: any) => o.value === value) ?? {};
    const manualOpt = payOpt('manual');
    const paypalOpt = payOpt('paypal');
    const summary = c.order_summary_sidebar;

    return (
        <StorefrontLayout>
            <SEO title={c.seo_title} />

            <div className="bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <div className="mx-auto w-full max-w-4xl px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">{c.page_heading}</h1>

                    <form
                        method="post"
                        action="/checkout"
                        onSubmit={handleSubmit}
                        className="grid gap-8 lg:grid-cols-3"
                    >
                        <div className="space-y-6 lg:col-span-2">
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-xl font-semibold">Cart Items</h2>

                                <div className="space-y-4">
                                    {Object.values(cart).map((item) => (
                                        <div key={item.key} className="flex items-center gap-4">
                                            <Link
                                                href={`/${item.slug}`}
                                                className="block h-16 w-16 shrink-0 overflow-hidden rounded bg-neutral-100 dark:bg-neutral-800"
                                            >
                                                {item.image ? (
                                                    <img
                                                        src={item.image}
                                                        alt={item.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="flex h-full items-center justify-center text-neutral-400">
                                                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                        </svg>
                                                    </div>
                                                )}
                                            </Link>
                                            <div className="min-w-0 flex-1">
                                                <Link href={`/${item.slug}`} className="text-base font-semibold hover:text-amber-600">{item.name}</Link>
                                                <p className="text-sm text-[#706f6c]">
                                                    Qty: {item.quantity}
                                                    {item.options && Object.keys(item.options).length > 0 && (
                                                        <span className="ml-1">• {formatOptions(item.options)}</span>
                                                    )}
                                                </p>
                                            </div>
                                            <span className="text-base font-semibold">${(item.price * item.quantity).toFixed(2)}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">{contact.heading}</h2>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">{contact.fields.name.label}</label>
                                        <input
                                            type={contact.fields.name.type}
                                            name="customer_name"
                                            value={data.customer_name}
                                            onChange={(e) => setData('customer_name', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                        {errors.customer_name && <p className="mt-1 text-xs text-red-500">{errors.customer_name}</p>}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">{contact.fields.email.label}</label>
                                        <input
                                            type={contact.fields.email.type}
                                            name="customer_email"
                                            value={data.customer_email}
                                            onChange={(e) => setData('customer_email', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                        {errors.customer_email && <p className="mt-1 text-xs text-red-500">{errors.customer_email}</p>}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">{contact.fields.phone.label}</label>
                                        <input
                                            type={contact.fields.phone.type}
                                            name="customer_phone"
                                            value={data.customer_phone}
                                            onChange={(e) => setData('customer_phone', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">{shipping.heading}</h2>
                                <div className="grid gap-4">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">{shipping.fields.address.label}</label>
                                        <input
                                            type={shipping.fields.address.type}
                                            name="shipping_address"
                                            value={data.shipping_address}
                                            onChange={(e) => setData('shipping_address', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                        {errors.shipping_address && <p className="mt-1 text-xs text-red-500">{errors.shipping_address}</p>}
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">{shipping.fields.city.label}</label>
                                            <input
                                                type={shipping.fields.city.type}
                                                name="shipping_city"
                                                value={data.shipping_city}
                                                onChange={(e) => setData('shipping_city', e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                            {errors.shipping_city && <p className="mt-1 text-xs text-red-500">{errors.shipping_city}</p>}
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">{shipping.fields.state.label}</label>
                                            <select
                                                name="shipping_state"
                                                value={data.shipping_state}
                                                onChange={(e) => setData('shipping_state', e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm disabled:opacity-60 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            >
                                                {availableStates.length === 0 ? (
                                                    <option value="">N/A</option>
                                                ) : (
                                                    availableStates.map((state) => (
                                                        <option key={state.code} value={state.code}>
                                                            {state.name}
                                                        </option>
                                                    ))
                                                )}
                                            </select>
                                        </div>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">{shipping.fields.zip.label}</label>
                                            <input
                                                type={shipping.fields.zip.type}
                                                name="shipping_zip"
                                                value={data.shipping_zip}
                                                onChange={(e) => setData('shipping_zip', e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                            {errors.shipping_zip && <p className="mt-1 text-xs text-red-500">{errors.shipping_zip}</p>}
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">{shipping.fields.country.label}</label>
                                            <select
                                                name="shipping_country"
                                                value={data.shipping_country}
                                                onChange={(e) => handleCountryChange(e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            >
                                                {countries.map((country) => (
                                                    <option key={country.code} value={country.code}>
                                                        {country.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">{shipping.fields.notes.label}</label>
                                        <textarea
                                            name="notes"
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            rows={2}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">{payment.heading}</h2>
                                <div className="space-y-3">
                                    <label className="flex cursor-pointer items-start gap-3 rounded-md border border-[#e3e3e0] p-3 dark:border-[#3E3E3A]">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="manual"
                                            checked={paymentMethod === 'manual'}
                                            onChange={() => setPaymentMethod('manual')}
                                            className="mt-1"
                                        />
                                        <div>
                                            <div className="font-medium">{manualOpt.label}</div>
                                            <div className="text-xs text-[#706f6c]">{manualOpt.description}</div>
                                        </div>
                                    </label>
                                    <label className="flex cursor-pointer items-start gap-3 rounded-md border border-[#e3e3e0] p-3 dark:border-[#3E3E3A]">
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value="paypal"
                                            checked={paymentMethod === 'paypal'}
                                            onChange={() => setPaymentMethod('paypal')}
                                            className="mt-1"
                                            disabled={!paypal.client_id}
                                        />
                                        <div>
                                            <div className="font-medium">
                                                {paypalOpt.label}
                                                {paypal.mode === 'sandbox' && (
                                                    <span className="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                        {paypalOpt.sandbox_badge}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-xs text-[#706f6c]">
                                                {paypal.client_id
                                                    ? paypalOpt.description_configured
                                                    : paypalOpt.description_not_configured}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div className="sticky top-6 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">{summary.heading}</h2>

                                <div className="flex justify-between text-sm">
                                    <span>{summary.subtotal_label}</span>
                                    <span>${subtotal.toFixed(2)}</span>
                                </div>
                                <div className="mt-2 flex justify-between text-lg font-semibold">
                                    <span>{summary.total_label}</span>
                                    <span>${subtotal.toFixed(2)}</span>
                                </div>

                                {paymentMethod === 'manual' && (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="mt-4 w-full rounded-lg bg-primary px-6 py-3 font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                    >
                                        {processing ? summary.placing_order_button : summary.place_order_button}
                                    </button>
                                )}

                                {paymentMethod === 'paypal' && (
                                    <div className="mt-4 space-y-2">
                                        {!paypalReady && !paypalError && (
                                            <div className="text-center text-xs text-[#706f6c]">{c.paypal_section.loading}</div>
                                        )}
                                        <div ref={paypalContainerRef} />
                                        {paypalProcessing && (
                                            <div className="text-center text-xs text-[#706f6c]">{c.paypal_section.finalizing}</div>
                                        )}
                                        {paypalError && (
                                            <p className="text-xs text-red-500">{paypalError}</p>
                                        )}
                                        <p className="text-[10px] text-[#706f6c]">
                                            {c.paypal_section.disclaimer}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </StorefrontLayout>
    );
}

function formatOptions(options: Record<string, string>): string {
    return Object.entries(options)
        .map(([key, value]) => {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

            return `${label}: ${value}`;
        })
        .join(', ');
}
