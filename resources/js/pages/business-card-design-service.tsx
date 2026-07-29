import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import DesignServiceForm from '@/components/design-service-form';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#800020';
const GOLD = '#C9A96A';
const WARM_BG = '#FAF7F2';

export default function BusinessCardDesignService() {
    const c = useContent('design_service_page') as any;

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

            {/* 2. Design Process -------------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 lg:grid-cols-2 lg:gap-12 lg:py-16">
                    {c.process_image_url && (
                        <div className="overflow-hidden rounded-xl shadow-md">
                            <img
                                src={c.process_image_url}
                                alt="Design process"
                                className="aspect-[4/3] w-full object-cover"
                            />
                        </div>
                    )}
                    <div>
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
                </div>
            </section>

            {/* 3. Terms & Notes + Form -------------------------------------- */}
            <section
                id="design-form"
                className="scroll-mt-20 border-t border-neutral-100"
                style={{ backgroundColor: WARM_BG }}
            >
                <div className="mx-auto grid max-w-7xl items-start gap-10 px-4 py-12 lg:grid-cols-2 lg:gap-12 lg:py-16">
                    <div>
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
                </div>

                <p className="mx-auto max-w-7xl px-4 pb-12 text-center text-sm text-neutral-500 lg:pb-16">
                    Prefer to browse first?{' '}
                    <Link
                        href="/business-cards"
                        className="font-semibold underline-offset-2 hover:underline"
                        style={{ color: ACCENT }}
                    >
                        Explore our business cards
                    </Link>
                </p>
            </section>
        </StorefrontLayout>
    );
}
