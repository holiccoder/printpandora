import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import SEO from '@/components/seo';

interface CartItem {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
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

export default function Checkout({ subtotal, paypal }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        shipping_address: '',
        shipping_city: '',
        shipping_state: '',
        shipping_zip: '',
        shipping_country: 'US',
        notes: '',
    });

    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('manual');
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
        if (paymentMethod !== 'paypal') return;
        if (!paypal.client_id) {
            setPaypalError('PayPal is not configured.');
            return;
        }
        if (window.paypal) {
            setPaypalReady(true);
            return;
        }

        const scriptId = 'paypal-sdk';
        if (document.getElementById(scriptId)) return;

        const script = document.createElement('script');
        script.id = scriptId;
        script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(paypal.client_id)}&currency=${encodeURIComponent(paypal.currency)}&intent=capture`;
        script.async = true;
        script.onload = () => setPaypalReady(true);
        script.onerror = () => setPaypalError('Failed to load PayPal SDK.');
        document.body.appendChild(script);
    }, [paymentMethod, paypal.client_id, paypal.currency]);

    // Render the PayPal button when the SDK is ready and the method is selected.
    useEffect(() => {
        if (paymentMethod !== 'paypal' || !paypalReady || !window.paypal) return;
        if (!paypalContainerRef.current) return;

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
                    throw new Error(json.error || 'Unable to create PayPal order.');
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
                            'Payment capture failed.';
                        setPaypalError(msg);
                        return;
                    }
                    if (json.redirect) {
                        router.visit(json.redirect);
                    }
                } catch (err) {
                    setPaypalError((err as Error).message || 'Payment capture failed.');
                } finally {
                    setPaypalProcessing(false);
                }
            },

            onError: (err: unknown) => {
                console.error('PayPal error', err);
                setPaypalError('PayPal encountered an error. Please try again.');
            },

            onCancel: () => {
                setPaypalError('Payment was cancelled.');
            },
        });

        paypalButtonsRef.current = buttons;
        buttons.render(paypalContainerRef.current).catch((err: unknown) => {
            console.error('PayPal render error', err);
            setPaypalError('Failed to render PayPal buttons.');
        });

        return () => {
            if (paypalButtonsRef.current?.close) {
                paypalButtonsRef.current.close();
                paypalButtonsRef.current = null;
            }
        };
    }, [paymentMethod, paypalReady]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (paymentMethod !== 'manual') return;
        post('/checkout');
    };

    return (
        <>
            <SEO title="Checkout" />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">PrintPandora</Link>
                        <Link href="/cart" className="text-sm text-[#706f6c] hover:text-[#1b1b18]">Back to Cart</Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">Checkout</h1>

                    <form onSubmit={handleSubmit} className="grid gap-8 lg:grid-cols-3">
                        <div className="space-y-6 lg:col-span-2">
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">Contact Information</h2>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Name *</label>
                                        <input
                                            type="text"
                                            value={data.customer_name}
                                            onChange={(e) => setData('customer_name', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                        {errors.customer_name && <p className="mt-1 text-xs text-red-500">{errors.customer_name}</p>}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Email *</label>
                                        <input
                                            type="email"
                                            value={data.customer_email}
                                            onChange={(e) => setData('customer_email', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                        {errors.customer_email && <p className="mt-1 text-xs text-red-500">{errors.customer_email}</p>}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Phone</label>
                                        <input
                                            type="text"
                                            value={data.customer_phone}
                                            onChange={(e) => setData('customer_phone', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">Shipping Address</h2>
                                <div className="grid gap-4">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Address *</label>
                                        <input
                                            type="text"
                                            value={data.shipping_address}
                                            onChange={(e) => setData('shipping_address', e.target.value)}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                        {errors.shipping_address && <p className="mt-1 text-xs text-red-500">{errors.shipping_address}</p>}
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">City *</label>
                                            <input
                                                type="text"
                                                value={data.shipping_city}
                                                onChange={(e) => setData('shipping_city', e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                            {errors.shipping_city && <p className="mt-1 text-xs text-red-500">{errors.shipping_city}</p>}
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">State</label>
                                            <input
                                                type="text"
                                                value={data.shipping_state}
                                                onChange={(e) => setData('shipping_state', e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">ZIP Code *</label>
                                            <input
                                                type="text"
                                                value={data.shipping_zip}
                                                onChange={(e) => setData('shipping_zip', e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                            {errors.shipping_zip && <p className="mt-1 text-xs text-red-500">{errors.shipping_zip}</p>}
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">Country</label>
                                            <input
                                                type="text"
                                                value={data.shipping_country}
                                                onChange={(e) => setData('shipping_country', e.target.value)}
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Order Notes</label>
                                        <textarea
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            rows={2}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">Payment Method</h2>
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
                                            <div className="font-medium">Manual / Pay Later</div>
                                            <div className="text-xs text-[#706f6c]">Place the order and arrange payment offline.</div>
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
                                                PayPal
                                                {paypal.mode === 'sandbox' && (
                                                    <span className="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                        Sandbox
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-xs text-[#706f6c]">
                                                {paypal.client_id
                                                    ? 'Pay securely with PayPal or a credit card.'
                                                    : 'PayPal is not configured.'}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div className="sticky top-6 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">Order Summary</h2>
                                <div className="flex justify-between text-sm">
                                    <span>Subtotal</span>
                                    <span>${subtotal.toFixed(2)}</span>
                                </div>
                                <div className="mt-2 flex justify-between text-lg font-semibold">
                                    <span>Total</span>
                                    <span>${subtotal.toFixed(2)}</span>
                                </div>

                                {paymentMethod === 'manual' && (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="mt-4 w-full rounded-lg bg-amber-600 px-6 py-3 font-semibold text-white hover:bg-amber-700 disabled:opacity-50"
                                    >
                                        {processing ? 'Placing Order...' : 'Place Order'}
                                    </button>
                                )}

                                {paymentMethod === 'paypal' && (
                                    <div className="mt-4 space-y-2">
                                        {!paypalReady && !paypalError && (
                                            <div className="text-center text-xs text-[#706f6c]">Loading PayPal…</div>
                                        )}
                                        <div ref={paypalContainerRef} />
                                        {paypalProcessing && (
                                            <div className="text-center text-xs text-[#706f6c]">Finalizing payment…</div>
                                        )}
                                        {paypalError && (
                                            <p className="text-xs text-red-500">{paypalError}</p>
                                        )}
                                        <p className="text-[10px] text-[#706f6c]">
                                            Make sure your contact &amp; shipping details are filled in before paying.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
