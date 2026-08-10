import { Link } from '@inertiajs/react';
import { ArrowRight, Cookie, Gift, ShieldCheck } from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

export function AffiliateProgram() {
    const c = useContent('affiliate_program_page');

    return (
        <StorefrontLayout>
            <SEO title={c.seo.title} description={c.seo.description} />

            {/* Hero Section */}
            <div className="relative isolate min-h-[32rem] overflow-hidden bg-neutral-950 text-white lg:min-h-[36rem]">
                <img
                    src="/images/affiliate/hero-banner.png"
                    alt=""
                    aria-hidden="true"
                    className="absolute inset-0 h-full w-full object-cover object-center"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-neutral-950/80 via-neutral-950/50 to-neutral-950/70" />
                <div className="absolute inset-0 [background-image:radial-gradient(#800020_1px,transparent_1px)] [background-size:16px_16px] opacity-20" />
                <div className="relative mx-auto flex min-h-[32rem] max-w-5xl items-center justify-center px-4 py-20 text-center lg:min-h-[36rem]">
                    <div className="max-w-3xl">
                        <span className="text-xs font-bold tracking-widest text-amber-400 uppercase">
                            InkPavo Partnerships
                        </span>
                        <h1 className="mt-4 font-serif text-4xl font-extrabold tracking-tight md:text-5xl lg:text-6xl">
                            {c.hero.title}
                        </h1>
                        <p className="mx-auto mt-6 max-w-2xl text-lg font-medium text-neutral-300 md:text-xl">
                            {c.hero.subtitle}
                        </p>
                        <p className="mt-4 text-xs text-neutral-400 italic">
                            {c.hero.terms_disclaimer}{' '}
                            <Link
                                href="/affiliate-program-terms-and-conditions"
                                className="underline hover:text-white"
                            >
                                Read Terms
                            </Link>
                        </p>
                        <div className="mt-10 flex justify-center">
                            <Link
                                href={c.content.cta_button_href}
                                className="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-6 py-3.5 text-sm font-bold tracking-wider text-neutral-900 uppercase shadow-md transition hover:bg-amber-400"
                            >
                                {c.content.cta_button_text}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Content Section */}
            <div className="bg-white py-16 lg:py-24">
                <div className="mx-auto max-w-4xl px-4">
                    <div className="grid grid-cols-1 items-center gap-12 md:grid-cols-2">
                        <div className="space-y-6">
                            <h2 className="font-serif text-3xl leading-tight font-bold text-neutral-900">
                                How it works
                            </h2>
                            <p className="text-base leading-relaxed text-neutral-700">
                                {c.content.main_text}
                            </p>
                            <div className="flex items-start gap-4 rounded-lg border border-neutral-100 bg-neutral-50 p-5">
                                <Cookie className="mt-0.5 size-6 shrink-0 text-[#800020]" />
                                <div>
                                    <h3 className="font-bold text-neutral-900">
                                        Cookie Tracking
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-600">
                                        {c.content.cookie_text}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Right part: Features */}
                        <div className="space-y-6 rounded-2xl border border-neutral-100 bg-neutral-50 p-8">
                            <div className="flex items-start gap-4">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                                    <Gift className="size-5" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-neutral-900">
                                        20% Cash Commission
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-600">
                                        Earn a massive 20% on the first purchase
                                        made by anyone you refer, with no limit
                                        on your earnings.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                                    <Cookie className="size-5" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-neutral-900">
                                        30-Day Cookie Validity
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-600">
                                        As long as your referred users purchase
                                        within 30 days of clicking your link,
                                        you get paid.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                                    <ShieldCheck className="size-5" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-neutral-900">
                                        Professional Support
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-600">
                                        Get access to professional banners,
                                        graphics, and custom tools to boost your
                                        referral conversions.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}

export default AffiliateProgram;
