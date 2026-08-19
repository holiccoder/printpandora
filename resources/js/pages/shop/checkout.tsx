// Content sourced from `content/hardcoded-content.json` via useContent('checkout_page').
import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import SEO from '@/components/seo';
import { countries, countriesByCode } from '@/data/countries';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import { isPvcProductSlug } from '@/lib/product-images';

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

interface CryptomusConfig {
    configured: boolean;
    currency: string;
    test: boolean;
}

interface ShippingMethod {
    code: string;
    label: string;
    carrier: string;
    fee: number;
    description: string;
    estimated_delivery: string;
}

interface Props {
    cart: Record<string, CartItem>;
    subtotal: number;
    discountAmount: number;
    itemsTotal: number;
    total: number;
    shippingFee: number;
    shippingMethods: ShippingMethod[];
    defaultShippingMethod: string;
    discountCode: string | null;
    paypal: PaypalConfig;
    cryptomus: CryptomusConfig;
}

declare global {
    interface Window {
        paypal?: any;
    }
}

type PaymentMethod = 'paypal' | 'cryptomus';

export default function Checkout({
    cart,
    subtotal,
    discountAmount,
    itemsTotal,
    total,
    shippingFee,
    shippingMethods,
    defaultShippingMethod,
    discountCode,
    paypal,
    cryptomus,
}: Props) {
    const c = useContent('checkout_page') as any;
    const { data, setData, errors } = useForm({
        shipping_address: '',
        shipping_city: '',
        shipping_state: '',
        shipping_zip: '',
        shipping_country:
            c.form_sections.shipping_address.fields.country.default_value ??
            'US',
        shipping_method: defaultShippingMethod,
        notes: '',
    });

    const [paymentMethod] = useState<PaymentMethod | null>(
        paypal.client_id ? 'paypal' : cryptomus.configured ? 'cryptomus' : null,
    );
    const [discountInput, setDiscountInput] = useState(discountCode ?? '');
    const [discountError, setDiscountError] = useState<string | null>(null);

    const applyDiscount = (e: React.FormEvent) => {
        e.preventDefault();
        setDiscountError(null);
        router.post(
            '/cart/discount',
            { code: discountInput },
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) =>
                    setDiscountError(
                        (errors as Record<string, string>).discount_code ??
                            'Unable to apply that discount code.',
                    ),
            },
        );
    };

    const removeDiscount = () => {
        setDiscountError(null);
        setDiscountInput('');
        router.delete('/cart/discount', {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const availableStates = useMemo(
        () => countriesByCode[data.shipping_country]?.states ?? [],
        [data.shipping_country],
    );

    const handleCountryChange = (countryCode: string) => {
        const country = countriesByCode[countryCode];
        const newState = country?.states.some(
            (s) => s.code === data.shipping_state,
        )
            ? data.shipping_state
            : '';
        setData({ shipping_country: countryCode, shipping_state: newState });
    };

    const [paypalSdkLoaded, setPaypalSdkLoaded] = useState(false);
    // The SDK may already be on the page from a prior visit; deriving
    // readiness avoids setState inside the loading effect.
    const paypalReady =
        paypalSdkLoaded ||
        (typeof window !== 'undefined' && Boolean(window.paypal));
    const [paypalError, setPaypalError] = useState<string | null>(null);

    // Missing PayPal config is derived at render time instead of via
    // setState inside the SDK-loading effect.
    const paypalDisplayError =
        paypalError ??
        (paypal.client_id ? null : c.error_messages.paypal_not_configured);
    const [paypalProcessing, setPaypalProcessing] = useState(false);
    const paypalContainerRef = useRef<HTMLDivElement | null>(null);
    const paypalButtonsRef = useRef<any>(null);
    const dataRef = useRef(data);

    const [cryptomusLoading, setCryptomusLoading] = useState(false);
    const [cryptomusError, setCryptomusError] = useState<string | null>(null);

    const selectedShipping =
        shippingMethods.find(
            (method) => method.code === data.shipping_method,
        ) ?? shippingMethods[0];
    const orderTotal =
        shippingMethods.length > 0
            ? itemsTotal + (selectedShipping?.fee ?? shippingFee)
            : total;

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
            return;
        }

        if (window.paypal) {
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
        script.onload = () => setPaypalSdkLoaded(true);
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
            (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement | null
            )?.content ?? '';

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
                    body: JSON.stringify({
                        ...dataRef.current,
                    }),
                });
                const json = await res.json().catch(() => ({}));

                if (!res.ok || !json.id) {
                    throw new Error(
                        json.error || c.error_messages.create_order_failed,
                    );
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
                            paypal_order_id: paypalData.orderID,
                        }),
                    });
                    const json = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        const msg =
                            json.error ||
                            (json.errors
                                ? Object.values(json.errors).flat().join(' ')
                                : null) ||
                            c.error_messages.capture_failed;
                        setPaypalError(msg);

                        return;
                    }

                    if (json.redirect) {
                        router.visit(json.redirect);
                    }
                } catch (err) {
                    setPaypalError(
                        (err as Error).message ||
                            c.error_messages.capture_failed,
                    );
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

    const handleCryptomusSubmit = async () => {
        if (cryptomusLoading) {
            return;
        }

        setCryptomusLoading(true);
        setCryptomusError(null);

        const csrfToken =
            (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement | null
            )?.content ?? '';

        try {
            const res = await fetch('/checkout/cryptomus/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(data),
            });
            const json = await res.json().catch(() => ({}));

            if (!res.ok) {
                setCryptomusError(
                    json.error ||
                        (json.errors
                            ? Object.values(json.errors).flat().join(' ')
                            : null) ||
                        c.error_messages.cryptomus_create_failed,
                );

                return;
            }

            if (json.redirect) {
                window.location.href = json.redirect;
            } else {
                setCryptomusError(c.error_messages.cryptomus_create_failed);
            }
        } catch (err) {
            setCryptomusError(
                (err as Error).message ||
                    c.error_messages.cryptomus_create_failed,
            );
        } finally {
            setCryptomusLoading(false);
        }
    };

    const shipping = c.form_sections.shipping_address;
    const shippingMethodSection = c.form_sections.shipping_method;
    const payment = c.form_sections.payment_method;
    const summary = c.order_summary_sidebar;

    return (
        <StorefrontLayout>
            <SEO title={c.seo_title} />

            <div className="bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <div className="mx-auto w-full max-w-7xl px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">
                        {c.page_heading}
                    </h1>

                    <div className="grid gap-8 lg:grid-cols-3">
                        <div className="space-y-6 lg:col-span-2">
                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-xl font-semibold">
                                    Cart Items
                                </h2>

                                <div className="space-y-4">
                                    {Object.values(cart).map((item) => (
                                        <div
                                            key={item.key}
                                            className="flex items-center gap-4"
                                        >
                                            <Link
                                                href={`/${item.slug}`}
                                                className="block h-16 w-16 shrink-0 overflow-hidden rounded bg-neutral-100 dark:bg-neutral-800"
                                            >
                                                {item.image ? (
                                                    <img
                                                        src={item.image}
                                                        alt={item.name}
                                                        className={`h-full w-full ${isPvcProductSlug(item.slug) ? 'object-contain' : 'object-cover'}`}
                                                    />
                                                ) : (
                                                    <div className="flex h-full items-center justify-center text-neutral-400">
                                                        <svg
                                                            className="h-6 w-6"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth={
                                                                    1.5
                                                                }
                                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                                            />
                                                        </svg>
                                                    </div>
                                                )}
                                            </Link>
                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={`/${item.slug}`}
                                                    className="text-base font-semibold hover:text-amber-600"
                                                >
                                                    {item.name}
                                                </Link>
                                                <p className="text-sm text-[#706f6c]">
                                                    Qty: {item.quantity}
                                                    {item.options &&
                                                        Object.keys(
                                                            item.options,
                                                        ).length > 0 && (
                                                            <span className="ml-1">
                                                                •{' '}
                                                                {formatOptions(
                                                                    item.options,
                                                                )}
                                                            </span>
                                                        )}
                                                </p>
                                            </div>
                                            <span className="text-base font-semibold">
                                                $
                                                {(
                                                    item.price * item.quantity
                                                ).toFixed(2)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">
                                    {shipping.heading}
                                </h2>
                                <div className="grid gap-4">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">
                                            {shipping.fields.address.label}
                                        </label>
                                        <input
                                            type={shipping.fields.address.type}
                                            name="shipping_address"
                                            value={data.shipping_address}
                                            onChange={(e) =>
                                                setData(
                                                    'shipping_address',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                        {errors.shipping_address && (
                                            <p className="mt-1 text-xs text-red-500">
                                                {errors.shipping_address}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">
                                                {shipping.fields.city.label}
                                            </label>
                                            <input
                                                type={shipping.fields.city.type}
                                                name="shipping_city"
                                                value={data.shipping_city}
                                                onChange={(e) =>
                                                    setData(
                                                        'shipping_city',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                            {errors.shipping_city && (
                                                <p className="mt-1 text-xs text-red-500">
                                                    {errors.shipping_city}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">
                                                {shipping.fields.state.label}
                                            </label>
                                            <select
                                                name="shipping_state"
                                                value={data.shipping_state}
                                                onChange={(e) =>
                                                    setData(
                                                        'shipping_state',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm disabled:opacity-60 dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            >
                                                {availableStates.length ===
                                                0 ? (
                                                    <option value="">
                                                        N/A
                                                    </option>
                                                ) : (
                                                    availableStates.map(
                                                        (state) => (
                                                            <option
                                                                key={state.code}
                                                                value={
                                                                    state.code
                                                                }
                                                            >
                                                                {state.name}
                                                            </option>
                                                        ),
                                                    )
                                                )}
                                            </select>
                                        </div>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">
                                                {shipping.fields.zip.label}
                                            </label>
                                            <input
                                                type={shipping.fields.zip.type}
                                                name="shipping_zip"
                                                value={data.shipping_zip}
                                                onChange={(e) =>
                                                    setData(
                                                        'shipping_zip',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            />
                                            {errors.shipping_zip && (
                                                <p className="mt-1 text-xs text-red-500">
                                                    {errors.shipping_zip}
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <label className="mb-1 block text-sm font-medium">
                                                {shipping.fields.country.label}
                                            </label>
                                            <select
                                                name="shipping_country"
                                                value={data.shipping_country}
                                                onChange={(e) =>
                                                    handleCountryChange(
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                            >
                                                {countries.map((country) => (
                                                    <option
                                                        key={country.code}
                                                        value={country.code}
                                                    >
                                                        {country.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">
                                            {shipping.fields.notes.label}
                                        </label>
                                        <textarea
                                            name="notes"
                                            value={data.notes}
                                            onChange={(e) =>
                                                setData('notes', e.target.value)
                                            }
                                            rows={2}
                                            className="w-full rounded-md border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">
                                    {shippingMethodSection.heading}
                                </h2>
                                <div className="space-y-3">
                                    {shippingMethods.map((method) => {
                                        const optionContent =
                                            shippingMethodSection.options[
                                                method.code
                                            ] ?? {};

                                        return (
                                            <label
                                                key={method.code}
                                                className="flex cursor-pointer items-start gap-3 rounded-md border border-[#e3e3e0] p-3 dark:border-[#3E3E3A]"
                                            >
                                                <input
                                                    type="radio"
                                                    name="shipping_method"
                                                    value={method.code}
                                                    checked={
                                                        data.shipping_method ===
                                                        method.code
                                                    }
                                                    onChange={() =>
                                                        setData(
                                                            'shipping_method',
                                                            method.code,
                                                        )
                                                    }
                                                    className="mt-1"
                                                />
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center justify-between gap-2 font-medium">
                                                        <span>
                                                            {method.label ||
                                                                optionContent.fallback_label}
                                                        </span>
                                                        <span>
                                                            $
                                                            {method.fee.toFixed(
                                                                2,
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div className="text-xs text-[#706f6c]">
                                                        {method.description ||
                                                            optionContent.fallback_description}{' '}
                                                        {method.estimated_delivery &&
                                                            `(${method.estimated_delivery})`}
                                                    </div>
                                                </div>
                                            </label>
                                        );
                                    })}
                                </div>
                                {errors.shipping_method && (
                                    <p className="mt-2 text-xs text-red-500">
                                        {errors.shipping_method}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <div className="sticky top-6 rounded-lg border border-[#e3e3e0] bg-white p-6 dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <h2 className="mb-4 text-lg font-semibold">
                                    {summary.heading}
                                </h2>

                                <form onSubmit={applyDiscount} className="mb-5">
                                    <label className="mb-2 block text-sm font-medium">
                                        Discount code
                                    </label>
                                    <div className="flex gap-2">
                                        <input
                                            value={discountInput}
                                            onChange={(e) =>
                                                setDiscountInput(e.target.value)
                                            }
                                            placeholder="Enter code"
                                            className="min-w-0 flex-1 rounded-md border border-[#d8d8d5] px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#0f0f0e]"
                                        />
                                        <button
                                            type="submit"
                                            className="rounded-md bg-neutral-900 px-3 py-2 text-sm font-medium text-white hover:bg-neutral-700 dark:bg-white dark:text-neutral-900"
                                        >
                                            Apply
                                        </button>
                                    </div>
                                    {(discountError ||
                                        (errors as Record<string, string>)
                                            .discount_code) && (
                                        <p className="mt-2 text-sm text-red-500">
                                            {discountError ??
                                                (
                                                    errors as Record<
                                                        string,
                                                        string
                                                    >
                                                ).discount_code}
                                        </p>
                                    )}
                                </form>

                                <div className="flex justify-between text-sm">
                                    <span>{summary.subtotal_label}</span>
                                    <span>${subtotal.toFixed(2)}</span>
                                </div>
                                {discountCode && discountAmount > 0 && (
                                    <>
                                        <div className="mt-2 flex justify-between text-sm text-green-700 dark:text-green-400">
                                            <span>
                                                Discount ({discountCode})
                                            </span>
                                            <span>
                                                -${discountAmount.toFixed(2)}
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={removeDiscount}
                                            className="mt-2 text-sm text-neutral-500 underline hover:text-neutral-800"
                                        >
                                            Remove discount
                                        </button>
                                    </>
                                )}
                                <div className="mt-2 flex justify-between text-sm">
                                    <span>
                                        {shippingMethodSection.fee_label}
                                        {selectedShipping?.label
                                            ? ` (${selectedShipping.label})`
                                            : ''}
                                    </span>
                                    <span>
                                        $
                                        {(
                                            selectedShipping?.fee ?? shippingFee
                                        ).toFixed(2)}
                                    </span>
                                </div>
                                <div className="mt-2 flex justify-between text-lg font-semibold">
                                    <span>{summary.total_label}</span>
                                    <span>${orderTotal.toFixed(2)}</span>
                                </div>

                                {paymentMethod === 'paypal' && (
                                    <div className="mt-4 space-y-2">
                                        {!paypalReady &&
                                            !paypalDisplayError && (
                                                <div className="text-center text-xs text-[#706f6c]">
                                                    {c.paypal_section.loading}
                                                </div>
                                            )}
                                        <div ref={paypalContainerRef} />
                                        {paypalProcessing && (
                                            <div className="text-center text-xs text-[#706f6c]">
                                                {c.paypal_section.finalizing}
                                            </div>
                                        )}
                                        {paypalDisplayError && (
                                            <p className="text-xs text-red-500">
                                                {paypalDisplayError}
                                            </p>
                                        )}
                                        <p className="text-[10px] text-[#706f6c]">
                                            {c.paypal_section.disclaimer}
                                        </p>
                                    </div>
                                )}

                                {paymentMethod === 'cryptomus' && (
                                    <div className="mt-4 space-y-2">
                                        <button
                                            type="button"
                                            onClick={handleCryptomusSubmit}
                                            disabled={cryptomusLoading}
                                            className="w-full rounded-lg bg-primary px-6 py-3 font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                        >
                                            {cryptomusLoading
                                                ? c.cryptomus_section.processing
                                                : c.cryptomus_section
                                                      .button_label}
                                        </button>
                                        {cryptomusError && (
                                            <p className="text-xs text-red-500">
                                                {cryptomusError}
                                            </p>
                                        )}
                                        <p className="text-[10px] text-[#706f6c]">
                                            {c.cryptomus_section.disclaimer}
                                        </p>
                                    </div>
                                )}

                                {!paypal.client_id && !cryptomus.configured && (
                                    <p className="mt-4 text-sm text-[#706f6c]">
                                        {payment.no_methods_message ??
                                            'No online payment method is currently configured.'}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}

function formatOptions(options: Record<string, string>): string {
    return Object.entries(options)
        .map(([key, value]) => {
            const label = key
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (l) => l.toUpperCase());

            return `${label}: ${value}`;
        })
        .join(', ');
}
