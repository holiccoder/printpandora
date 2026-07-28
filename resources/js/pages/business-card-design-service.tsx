import { Link } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import DesignServiceForm from '@/components/design-service-form';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#800020';
const GOLD = '#C9A96A';
const WARM_BG = '#FAF7F2';

export default function BusinessCardDesignService() {
    const c = useContent('design_service_page') as any;
    const tiers: any[] = c.pricing_tiers ?? [];
    const supplemental: any[] = c.supplemental_services ?? [];

    return (
        <StorefrontLayout>
            <SEO
                title={c.seo.title ?? 'Business Card Design Service'}
                description={c.seo.description}
            />

            {/* 1. Hero ------------------------------------------------------ */}
            <section style={{ backgroundColor: WARM_BG }}>
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 lg:grid-cols-2 lg:gap-12 lg:py-24">
                    <div className="flex flex-col justify-center">
                        {c.hero?.eyebrow && (
                            <p
                                className="mb-3 text-xs font-semibold tracking-[0.15em] uppercase"
                                style={{ color: GOLD }}
                            >
                                {c.hero.eyebrow}
                            </p>
                        )}
                        <h1 className="font-serif text-4xl leading-tight font-bold tracking-tight text-[#800020] sm:text-5xl lg:text-6xl">
                            {c.hero?.heading ?? c.heading}
                        </h1>
                        <p className="mt-5 max-w-lg text-base text-neutral-700 sm:text-lg">
                            {c.hero?.body ?? c.intro}
                        </p>
                        {c.hero?.cta && (
                            <div className="mt-8">
                                <a
                                    href="#design-form"
                                    className="inline-flex items-center gap-2 rounded-md bg-primary px-7 py-3.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                                >
                                    {c.hero.cta}
                                    <ArrowRight className="size-4" />
                                </a>
                            </div>
                        )}
                    </div>
                    {c.hero?.image_url && (
                        <div className="overflow-hidden rounded-xl shadow-md">
                            <img
                                src={c.hero.image_url}
                                alt={c.hero.image_alt ?? 'Business card design'}
                                className="aspect-[4/3] w-full object-cover"
                            />
                        </div>
                    )}
                </div>
            </section>

            {/* 2. Four pricing tiers ---------------------------------------- */}
            {tiers.length > 0 && (
                <section className="border-t border-neutral-100 bg-white">
                    <div className="mx-auto max-w-7xl px-4 py-14 lg:py-20">
                        <header className="mb-10 max-w-2xl">
                            {c.tiers_eyebrow && (
                                <p
                                    className="mb-2 text-xs font-semibold tracking-[0.15em] uppercase"
                                    style={{ color: GOLD }}
                                >
                                    {c.tiers_eyebrow}
                                </p>
                            )}
                            <h2 className="font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                                {c.tiers_heading ?? 'Choose your design path'}
                            </h2>
                            {c.tiers_subtitle && (
                                <p className="mt-2 text-sm text-neutral-600 sm:text-base">
                                    {c.tiers_subtitle}
                                </p>
                            )}
                        </header>

                        <ul className="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                            {tiers.map((tier) => (
                                <li
                                    key={tier.name}
                                    className={`relative flex h-full flex-col rounded-xl border bg-white p-6 transition-shadow hover:shadow-md ${
                                        tier.badge
                                            ? 'border-[#800020] shadow-sm'
                                            : 'border-neutral-200'
                                    }`}
                                >
                                    {tier.badge && (
                                        <span
                                            className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full px-3 py-0.5 text-xs font-semibold text-white"
                                            style={{ backgroundColor: ACCENT }}
                                        >
                                            {tier.badge}
                                        </span>
                                    )}

                                    <h3 className="text-lg font-bold text-neutral-900">
                                        {tier.name}
                                    </h3>

                                    <div className="mt-3 flex items-baseline gap-1">
                                        <span
                                            className="text-3xl font-bold"
                                            style={{ color: ACCENT }}
                                        >
                                            {tier.price}
                                        </span>
                                        {tier.price_unit && (
                                            <span className="text-sm text-neutral-500">
                                                {tier.price_unit}
                                            </span>
                                        )}
                                    </div>
                                    {tier.price_detail && (
                                        <p className="mt-1 text-xs text-neutral-500">
                                            {tier.price_detail}
                                        </p>
                                    )}

                                    <p className="mt-4 text-sm text-neutral-600">
                                        {tier.audience}
                                    </p>

                                    {Array.isArray(tier.flow) && (
                                        <ul className="mt-5 flex-1 space-y-2">
                                            {tier.flow.map(
                                                (step: string, i: number) => (
                                                    <li
                                                        key={i}
                                                        className="flex gap-2 text-sm text-neutral-700"
                                                    >
                                                        <Check
                                                            className="mt-0.5 size-4 shrink-0"
                                                            style={{
                                                                color: GOLD,
                                                            }}
                                                        />
                                                        <span>{step}</span>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}

                                    {tier.note && (
                                        <p className="mt-4 rounded-md bg-neutral-50 px-3 py-2 text-xs leading-relaxed text-neutral-500">
                                            {tier.note}
                                        </p>
                                    )}

                                    <a
                                        href={tier.cta_href ?? '#design-form'}
                                        className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-semibold transition-colors"
                                        style={
                                            tier.badge
                                                ? {
                                                      backgroundColor: ACCENT,
                                                      color: 'white',
                                                  }
                                                : {
                                                      border: `1px solid ${ACCENT}`,
                                                      color: ACCENT,
                                                  }
                                        }
                                    >
                                        {tier.cta}
                                        <ArrowRight className="size-4" />
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            )}

            {/* 3. Supplemental services ------------------------------------- */}
            {supplemental.length > 0 && (
                <section
                    className="border-t border-neutral-100"
                    style={{ backgroundColor: WARM_BG }}
                >
                    <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                        <h2 className="font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                            {c.supplemental_heading ?? 'Additional services'}
                        </h2>
                        <ul className="mt-6 space-y-4">
                            {supplemental.map((item: any, i: number) => (
                                <li
                                    key={i}
                                    className="rounded-lg border border-neutral-200 bg-white p-5"
                                >
                                    <h3 className="text-base font-semibold text-neutral-900">
                                        {item.title}
                                    </h3>
                                    <p className="mt-1.5 text-sm leading-relaxed text-neutral-600">
                                        {item.body}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            )}

            {/* 4. Design Process -------------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                    <h2 className="font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                        {c.process_heading}
                    </h2>
                    <ol className="mt-6 space-y-4">
                        {c.process_steps.map((step: string, i: number) => (
                            <li key={i} className="flex gap-4">
                                <span
                                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                                    style={{ backgroundColor: ACCENT }}
                                >
                                    {i + 1}
                                </span>
                                <p className="text-sm leading-relaxed text-neutral-700">
                                    {step}
                                </p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            {/* 5. Design Notes / Terms -------------------------------------- */}
            <section
                className="border-t border-neutral-100"
                style={{ backgroundColor: WARM_BG }}
            >
                <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                    <h2 className="font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                        {c.notes_heading}
                    </h2>
                    <ol className="mt-6 space-y-4">
                        {c.notes.map((note: string, i: number) => (
                            <li key={i} className="flex gap-4">
                                <span
                                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                                    style={{ backgroundColor: ACCENT }}
                                >
                                    {i + 1}
                                </span>
                                <p className="text-sm leading-relaxed text-neutral-700">
                                    {note}
                                </p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            {/* 6. Form ------------------------------------------------------ */}
            <section
                id="design-form"
                className="scroll-mt-20 border-t border-neutral-100 bg-white"
            >
                <div className="mx-auto max-w-4xl px-4 py-12 lg:py-16">
                    <div className="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm sm:p-8 lg:p-10">
                        <h2 className="text-xl font-bold text-neutral-900 sm:text-2xl">
                            {c.form_heading}
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            {c.form_description}
                        </p>
                        <DesignServiceForm
                            productOptions={c.form_product_options}
                            submitLabel={c.form_submit_label}
                            className="mt-6"
                        />
                    </div>
                    <p className="mt-6 text-center text-sm text-neutral-500">
                        Prefer to browse first?{' '}
                        <Link
                            href="/business-cards"
                            className="font-semibold underline-offset-2 hover:underline"
                            style={{ color: ACCENT }}
                        >
                            Explore our business cards
                        </Link>
                    </p>
                </div>
            </section>
        </StorefrontLayout>
    );
}
