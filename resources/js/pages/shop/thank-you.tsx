import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Props {
    order: {
        id: number;
        shipping_method: string;
        carrier: string;
        shipping_estimate: string;
        dispatch_date: string;
        delivery_date: string;
    };
}

export default function ThankYou({ order }: Props) {
    const c = useContent('shop_thank_you_page');
    const [secondsRemaining, setSecondsRemaining] = useState(5);

    useEffect(() => {
        const redirectTimer = window.setTimeout(() => {
            router.visit('/orders');
        }, 5000);
        const countdownTimer = window.setInterval(() => {
            setSecondsRemaining((seconds) => Math.max(seconds - 1, 0));
        }, 1000);

        return () => {
            window.clearTimeout(redirectTimer);
            window.clearInterval(countdownTimer);
        };
    }, []);

    return (
        <>
            <SEO title={c.seo_title} />

            <StorefrontLayout>
                <section className="bg-[#FAF7F2] px-4 py-16 sm:py-20">
                    <div className="mx-auto max-w-3xl">
                        <div className="text-center">
                            <div className="mx-auto flex size-16 items-center justify-center rounded-full bg-[#800020] text-white shadow-sm">
                                <svg
                                    className="size-8"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    aria-hidden="true"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="m5 12 4 4L19 6"
                                    />
                                </svg>
                            </div>
                            <p className="mt-6 text-sm font-semibold tracking-[0.2em] text-[#800020] uppercase">
                                {c.eyebrow}
                            </p>
                            <h1 className="mt-3 text-4xl font-semibold tracking-tight text-neutral-900 sm:text-5xl">
                                {c.heading}
                            </h1>
                            <p className="mx-auto mt-5 max-w-xl text-lg leading-8 text-neutral-600">
                                {c.intro}
                            </p>
                            <p className="mt-4 font-semibold text-neutral-900">
                                {c.order_label_prefix}
                                {order.id}
                            </p>
                        </div>

                        <div className="mt-12 rounded-2xl border border-[#e3d9cf] bg-white p-6 shadow-sm sm:p-8">
                            <h2 className="text-xl font-semibold text-neutral-900">
                                {c.delivery_heading}
                            </h2>

                            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                <div className="rounded-xl bg-[#FAF7F2] p-5">
                                    <p className="text-sm text-neutral-600">
                                        {c.dispatch_label}
                                    </p>
                                    <p className="mt-2 text-xl font-semibold text-neutral-900">
                                        {order.dispatch_date}
                                    </p>
                                </div>
                                <div className="rounded-xl bg-[#FAF7F2] p-5">
                                    <p className="text-sm text-neutral-600">
                                        {c.delivery_label}
                                    </p>
                                    <p className="mt-2 text-xl font-semibold text-neutral-900">
                                        {order.delivery_date}
                                    </p>
                                </div>
                            </div>

                            <dl className="mt-6 grid gap-3 border-t border-[#e3d9cf] pt-6 text-sm sm:grid-cols-3">
                                <div>
                                    <dt className="text-neutral-500">
                                        {c.method_label}
                                    </dt>
                                    <dd className="mt-1 font-medium text-neutral-900">
                                        {order.shipping_method}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-neutral-500">
                                        {c.carrier_label}
                                    </dt>
                                    <dd className="mt-1 font-medium text-neutral-900">
                                        {order.carrier}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-neutral-500">
                                        {c.transit_label}
                                    </dt>
                                    <dd className="mt-1 font-medium text-neutral-900">
                                        {order.shipping_estimate}
                                    </dd>
                                </div>
                            </dl>

                            <p className="mt-6 text-sm leading-6 text-neutral-500">
                                {c.estimate_note}
                            </p>
                        </div>

                        <div className="mt-6 rounded-2xl border border-[#e3d9cf] bg-white p-6 text-center sm:p-8">
                            <h2 className="text-xl font-semibold text-neutral-900">
                                {c.contact_heading}
                            </h2>
                            <p className="mt-2 text-neutral-600">
                                {c.contact_body}
                            </p>
                            <Link
                                href="/contact-us"
                                className="mt-5 inline-flex items-center justify-center rounded-md bg-[#800020] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#5c0018]"
                            >
                                {c.contact_link}
                            </Link>
                        </div>

                        <div className="mt-8 text-center text-sm text-neutral-500">
                            <p>
                                {c.redirect_prefix}{' '}
                                <span className="font-semibold text-neutral-900">
                                    {secondsRemaining}
                                </span>{' '}
                                {c.redirect_suffix}
                            </p>
                            <Link
                                href="/orders"
                                className="mt-3 inline-block font-semibold text-[#800020] hover:underline"
                            >
                                {c.orders_link}
                            </Link>
                        </div>
                    </div>
                </section>
            </StorefrontLayout>
        </>
    );
}
