import {
    ArrowRight,
    ChevronRight,
    FileUp,
    LayoutTemplate,
    Leaf,
    Palette,
    ShieldCheck,
    Truck,
} from 'lucide-react';
import { useState } from 'react';
import SEO from '@/components/seo';
import UploadFilesModal from '@/components/upload-files-modal';
import StorefrontLayout from '@/layouts/storefront-layout';

/* -------------------------------------------------------------------------- */
/* Types (JSON-driven; loose enough for three category configs)               */
/* -------------------------------------------------------------------------- */

export interface CategorySeriesItem {
    name: string;
    blurb: string;
    price_from: string;
    image_url: string;
    href: string;
}

export interface CategoryDesignPath {
    name: string;
    blurb: string;
    cta: string;
    href: string;
}

export interface CategoryPerk {
    title: string;
    description: string;
}

export interface CategoryFaq {
    question: string;
    answer: string;
}

export interface CategoryLandingContent {
    active_category?: string;
    accent_color?: string;
    warm_bg?: string;
    gold?: string;
    seo: { title: string; description: string };
    hero: {
        eyebrow?: string;
        heading: string;
        body: string;
        cta: string;
        cta_href: string;
        image_url: string;
        image_alt: string;
    };
    series: {
        eyebrow?: string;
        heading: string;
        subtitle?: string;
        items: CategorySeriesItem[];
    };
    design_cta: {
        eyebrow?: string;
        heading: string;
        subtitle?: string;
        paths: CategoryDesignPath[];
    };
    perks: {
        heading?: string;
        items: CategoryPerk[];
    };
    faq: CategoryFaq[];
}

const DEFAULT_ACCENT = '#800020';
const DEFAULT_WARM = '#FAF7F2';
const DEFAULT_GOLD = '#C9A96A';

const designIcons = [FileUp, LayoutTemplate, Palette];
const perkIcons = [ShieldCheck, Leaf, Truck, Palette];

/* -------------------------------------------------------------------------- */
/* Component                                                                  */
/* -------------------------------------------------------------------------- */

export default function CategoryLanding({
    content,
}: {
    content: CategoryLandingContent;
}) {
    const ACCENT = content.accent_color ?? DEFAULT_ACCENT;
    const WARM_BG = content.warm_bg ?? DEFAULT_WARM;
    const GOLD = content.gold ?? DEFAULT_GOLD;
    const { hero, series, design_cta, perks, faq } = content;
    const [uploadModalOpen, setUploadModalOpen] = useState(false);

    const openUploadModal = () => {
        setUploadModalOpen(true);
    };

    return (
        <StorefrontLayout activeCategory={content.active_category}>
            <SEO
                title={content.seo.title}
                description={content.seo.description}
            />

            {/* ① Hero ------------------------------------------------------- */}
            <section style={{ backgroundColor: WARM_BG }}>
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 lg:grid-cols-2 lg:gap-12 lg:py-24">
                    <div className="flex flex-col justify-center">
                        {hero.eyebrow && (
                            <p
                                className="mb-3 text-xs font-semibold tracking-[0.15em] uppercase"
                                style={{ color: GOLD }}
                            >
                                {hero.eyebrow}
                            </p>
                        )}
                        <h1 className="font-serif text-4xl leading-tight font-bold tracking-tight text-[#800020] sm:text-5xl lg:text-6xl">
                            {hero.heading}
                        </h1>
                        <p className="mt-5 max-w-lg text-base text-neutral-700 sm:text-lg">
                            {hero.body}
                        </p>
                        <div className="mt-8">
                            <button
                                type="button"
                                onClick={openUploadModal}
                                className="inline-flex items-center gap-2 rounded-md bg-primary px-7 py-3.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                            >
                                {hero.cta}
                                <ArrowRight className="size-4" />
                            </button>
                        </div>
                    </div>
                    <div className="overflow-hidden rounded-xl shadow-md">
                        <img
                            src={hero.image_url}
                            alt={hero.image_alt}
                            className="aspect-[4/3] w-full object-cover"
                        />
                    </div>
                </div>
            </section>

            {/* ② Product series grid ---------------------------------------- */}
            <section
                id="series"
                className="scroll-mt-20 border-t border-neutral-100 bg-white"
            >
                <div className="mx-auto max-w-7xl px-4 py-14 lg:py-20">
                    <header className="mb-10 max-w-2xl">
                        {series.eyebrow && (
                            <p
                                className="mb-2 text-xs font-semibold tracking-[0.15em] uppercase"
                                style={{ color: GOLD }}
                            >
                                {series.eyebrow}
                            </p>
                        )}
                        <h2 className="font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                            {series.heading}
                        </h2>
                        {series.subtitle && (
                            <p className="mt-2 text-sm text-neutral-600 sm:text-base">
                                {series.subtitle}
                            </p>
                        )}
                    </header>
                    <ul className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {series.items.map((item) => (
                            <li key={item.name} className="group">
                                <button
                                    type="button"
                                    onClick={openUploadModal}
                                    className="flex h-full w-full flex-col overflow-hidden rounded-lg border border-neutral-200 bg-white text-left transition-all hover:-translate-y-0.5 hover:shadow-md"
                                >
                                    <div className="overflow-hidden bg-neutral-100">
                                        <img
                                            src={item.image_url}
                                            alt={item.name}
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    </div>
                                    <div className="flex flex-1 flex-col p-5">
                                        <h3 className="text-base font-bold text-neutral-900">
                                            {item.name}
                                        </h3>
                                        <p className="mt-1.5 flex-1 text-sm text-neutral-600">
                                            {item.blurb}
                                        </p>
                                        <div className="mt-4 flex items-center justify-between">
                                            <span
                                                className="text-sm font-semibold"
                                                style={{ color: ACCENT }}
                                            >
                                                {item.price_from}
                                            </span>
                                            <span
                                                className="inline-flex items-center gap-0.5 text-sm font-semibold group-hover:underline"
                                                style={{ color: ACCENT }}
                                            >
                                                Shop
                                                <ChevronRight className="size-3.5" />
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* ③ Design service CTA (conversion core) ----------------------- */}
            <section style={{ backgroundColor: WARM_BG }}>
                <div className="mx-auto max-w-7xl px-4 py-14 lg:py-20">
                    <header className="mb-10 max-w-2xl">
                        {design_cta.eyebrow && (
                            <p
                                className="mb-2 text-xs font-semibold tracking-[0.15em] uppercase"
                                style={{ color: GOLD }}
                            >
                                {design_cta.eyebrow}
                            </p>
                        )}
                        <h2 className="font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                            {design_cta.heading}
                        </h2>
                        {design_cta.subtitle && (
                            <p className="mt-2 text-sm text-neutral-600 sm:text-base">
                                {design_cta.subtitle}
                            </p>
                        )}
                    </header>
                    <ul className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        {design_cta.paths.map((path, i) => {
                            const Icon = designIcons[i] ?? Palette;

                            return (
                                <li key={path.name}>
                                    <button
                                        type="button"
                                        onClick={openUploadModal}
                                        className="group flex h-full w-full flex-col items-start rounded-lg border border-neutral-200 bg-white p-6 text-left transition-all hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        <span
                                            className="flex size-12 items-center justify-center rounded-full"
                                            style={{
                                                backgroundColor: `${ACCENT}1a`,
                                                color: ACCENT,
                                            }}
                                        >
                                            <Icon className="size-5" />
                                        </span>
                                        <h3 className="mt-5 text-lg font-bold text-neutral-900">
                                            {path.name}
                                        </h3>
                                        <p className="mt-2 flex-1 text-sm text-neutral-600">
                                            {path.blurb}
                                        </p>
                                        <span
                                            className="mt-5 inline-flex items-center gap-1 text-sm font-semibold group-hover:underline"
                                            style={{ color: ACCENT }}
                                        >
                                            {path.cta}
                                            <ChevronRight className="size-3.5" />
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>

            {/* ④ Quality perks ---------------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-14 lg:py-16">
                    {perks.heading && (
                        <h2 className="mb-10 text-center font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                            {perks.heading}
                        </h2>
                    )}
                    <ul className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        {perks.items.map((perk, i) => {
                            const Icon = perkIcons[i] ?? ShieldCheck;

                            return (
                                <li
                                    key={perk.title}
                                    className="flex flex-col items-center text-center"
                                >
                                    <span
                                        className="flex size-12 items-center justify-center rounded-full"
                                        style={{
                                            backgroundColor: `${GOLD}22`,
                                            color: GOLD,
                                        }}
                                    >
                                        <Icon className="size-5" />
                                    </span>
                                    <h3 className="mt-4 text-base font-bold text-neutral-900">
                                        {perk.title}
                                    </h3>
                                    <p className="mt-1.5 text-sm text-neutral-600">
                                        {perk.description}
                                    </p>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>

            {/* ⑤ FAQ -------------------------------------------------------- */}
            {faq.length > 0 && (
                <section
                    className="border-t border-neutral-100"
                    style={{ backgroundColor: WARM_BG }}
                >
                    <div className="mx-auto max-w-3xl px-4 py-14 lg:py-20">
                        <h2 className="mb-8 text-center font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                            Frequently asked questions
                        </h2>
                        <dl className="space-y-6">
                            {faq.map((item) => (
                                <div
                                    key={item.question}
                                    className="rounded-lg border border-neutral-200 bg-white p-5 sm:p-6"
                                >
                                    <dt className="text-base font-semibold text-neutral-900">
                                        {item.question}
                                    </dt>
                                    <dd className="mt-2 text-sm leading-relaxed text-neutral-600">
                                        {item.answer}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </section>
            )}

            <UploadFilesModal
                open={uploadModalOpen}
                onOpenChange={setUploadModalOpen}
            />
        </StorefrontLayout>
    );
}
