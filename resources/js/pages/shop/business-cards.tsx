// Content (copy, blurbs, images) sourced from `content/hardcoded-content.json`
// via useContent('business_cards_landing_page'). Hrefs and icon mappings
// remain in this file because the JSON does not declare them.
import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    ChevronRight,
    Globe,
    Leaf,
    Pencil,
    ShieldCheck,
    Truck,
    Upload,
} from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

/* -------------------------------------------------------------------------- */
/* Layout-only data (hrefs, icons, positional CSS) — not in the JSON          */
/* -------------------------------------------------------------------------- */

const heroCollageClasses = [
    'top-4 left-2 w-44 rotate-[-9deg] sm:w-52 lg:w-60',
    'top-2 right-4 w-44 rotate-[6deg] sm:w-52 lg:w-60',
    'bottom-2 left-1/2 w-48 -translate-x-1/2 rotate-[2deg] sm:w-56 lg:w-64',
    'bottom-6 left-4 w-40 rotate-[-3deg] sm:w-48 lg:w-52',
    'bottom-10 right-2 w-36 rotate-[10deg] sm:w-44 lg:w-48',
];

const paperHrefs = [
    '/shop/original-business-cards',
    '/shop/super-business-cards',
    '/shop/luxe-business-cards',
    '/shop/cotton-business-cards',
];

const sizeHrefs = [
    '/shop/original-business-cards',
    '/shop/square-business-cards',
    '/shop/square-business-cards',
];

const finishHref = '/shop?cat=business-cards';
const templateHref = '/shop?cat=business-cards';
const designHref = '/shop?cat=business-cards';
const designIcons = [Pencil, Upload, Globe];
const perkIcons = [ShieldCheck, Leaf, Truck];
const crossSellHrefs = [
    '/shop/notebooks-journals',
    '/shop/greeting-cards',
    '/shop?cat=stickers-labels',
    '/shop/business-card-holders',
];

/* -------------------------------------------------------------------------- */
/* Shared section helpers (kept local to the page)                            */
/* -------------------------------------------------------------------------- */

function SectionHeader({
    eyebrow,
    title,
    subtitle,
    accent,
}: {
    eyebrow?: string;
    title: string;
    subtitle?: string;
    accent: string;
}) {
    return (
        <header className="mb-10 max-w-2xl">
            {eyebrow && (
                <p
                    className="mb-2 text-xs font-semibold tracking-widest uppercase"
                    style={{ color: accent }}
                >
                    {eyebrow}
                </p>
            )}
            <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">{title}</h2>
            {subtitle && (
                <p className="mt-2 text-sm text-neutral-600 sm:text-base">{subtitle}</p>
            )}
        </header>
    );
}

function ShopLink({ children, href, accent }: { children: React.ReactNode; href: string; accent: string }) {
    return (
        <Link
            href={href}
            className="mt-3 inline-flex items-center gap-1 text-sm font-semibold group-hover:underline"
            style={{ color: accent }}
        >
            {children} <ChevronRight className="size-3.5" />
        </Link>
    );
}

/* -------------------------------------------------------------------------- */
/* Page                                                                       */
/* -------------------------------------------------------------------------- */

export default function BusinessCardsLanding() {
    const c = useContent('business_cards_landing_page') as any;
    const ACCENT = c.accent_color;
    const WARM_BG = c.warm_bg;
    const sections = c.sections;

    const formatCta = (template: string, name: string) =>
        String(template).replace('{name}', name);

    return (
        <StorefrontLayout activeCategory="Business Cards">
            <SEO
                title={c.seo.title}
                description={c.seo.description}
            />

            {/* 1. Hero ------------------------------------------------------ */}
            <section style={{ backgroundColor: WARM_BG }}>
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 lg:grid-cols-2 lg:gap-8 lg:py-24">
                    <div className="flex flex-col justify-center">
                        <h1 className="text-4xl leading-tight font-bold tracking-tight text-neutral-900 sm:text-5xl lg:text-6xl">
                            {c.hero.heading}
                        </h1>
                        <p className="mt-5 max-w-lg text-base text-neutral-700 sm:text-lg">
                            {c.hero.body}
                        </p>
                        <div className="mt-8">
                            <Link
                                href="/shop?cat=business-cards"
                                className="inline-flex items-center gap-2 rounded-md bg-primary px-7 py-3.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                            >
                                {c.hero.cta}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>

                    {/* Collage panel — absolutely-positioned, rotated cards */}
                    <div className="relative h-[420px] sm:h-[520px] lg:h-[560px]">
                        {c.hero_collage.map((card: any, i: number) => (
                            <img
                                key={card.alt}
                                src={card.image_url}
                                alt={card.alt}
                                loading="lazy"
                                className={`absolute rounded-md shadow-xl ring-1 ring-black/5 ${heroCollageClasses[i] ?? ''}`}
                            />
                        ))}
                    </div>
                </div>
            </section>

            {/* 2. Shop by Paper -------------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <SectionHeader
                        title={sections.shop_by_paper.heading}
                        subtitle={sections.shop_by_paper.subtitle}
                        accent={ACCENT}
                    />
                    <ul className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        {sections.shop_by_paper.items.map((paper: any, i: number) => {
                            const href = paperHrefs[i] ?? '/shop?cat=business-cards';

                            return (
                                <li key={paper.name} className="group">
                                    <Link href={href} className="block">
                                        <div className="overflow-hidden rounded-md bg-neutral-100">
                                            <img
                                                src={paper.image_url}
                                                alt={paper.name}
                                                loading="lazy"
                                                className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                        </div>
                                        <h3 className="mt-4 text-base font-bold text-neutral-900">
                                            {paper.name}
                                        </h3>
                                        <p className="mt-1 text-sm text-neutral-600">
                                            {paper.blurb}
                                        </p>
                                        <ShopLink href={href} accent={ACCENT}>
                                            {formatCta(sections.shop_by_paper.cta_template, paper.name)}
                                        </ShopLink>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>

            {/* 3. Shop by Size --------------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <SectionHeader
                        title={sections.shop_by_size.heading}
                        subtitle={sections.shop_by_size.subtitle}
                        accent={ACCENT}
                    />
                    <ul className="grid grid-cols-1 gap-8 sm:grid-cols-3">
                        {sections.shop_by_size.items.map((size: any, i: number) => {
                            const href = sizeHrefs[i] ?? '/shop?cat=business-cards';

                            return (
                                <li key={size.name} className="group">
                                    <Link href={href} className="block">
                                        <div className="overflow-hidden rounded-md bg-neutral-100">
                                            <img
                                                src={size.image_url}
                                                alt={size.name}
                                                loading="lazy"
                                                className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                        </div>
                                        <h3 className="mt-4 text-base font-bold text-neutral-900">
                                            {size.name}
                                        </h3>
                                        <p className="mt-1 text-sm text-neutral-600">{size.blurb}</p>
                                        <ShopLink href={href} accent={ACCENT}>
                                            {formatCta(sections.shop_by_size.cta_template, size.name)}
                                        </ShopLink>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>

            {/* 4. Shop by Special Finish ----------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <SectionHeader
                        title={sections.shop_by_finish.heading}
                        subtitle={sections.shop_by_finish.subtitle}
                        accent={ACCENT}
                    />
                    <ul className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {sections.shop_by_finish.items.map((finish: any) => (
                            <li key={finish.name} className="group">
                                <Link href={finishHref} className="block">
                                    <div
                                        className={`overflow-hidden rounded-md ${
                                            finish.emphasised ? 'bg-neutral-900' : 'bg-neutral-100'
                                        }`}
                                    >
                                        <img
                                            src={finish.image_url}
                                            alt={finish.name}
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    </div>
                                    <h3 className="mt-4 text-base font-bold text-neutral-900">
                                        {finish.name}
                                    </h3>
                                    {finish.blurb && (
                                        <p className="mt-1 text-sm text-neutral-600">
                                            {finish.blurb}
                                        </p>
                                    )}
                                    <ShopLink href={finishHref} accent={ACCENT}>
                                        {finish.cta}
                                    </ShopLink>
                                </Link>
                            </li>
                        ))}
                    </ul>

                    {/* Inset free-shipping promo banner */}
                    <div
                        className="mt-12 grid overflow-hidden rounded-lg sm:grid-cols-2"
                        style={{ backgroundColor: WARM_BG }}
                    >
                        <div className="flex flex-col justify-center px-6 py-10 sm:px-12 sm:py-14">
                            <p
                                className="text-xs font-semibold tracking-widest uppercase"
                                style={{ color: ACCENT }}
                            >
                                {sections.shop_by_finish.free_shipping_promo.eyebrow}
                            </p>
                            <h3 className="mt-2 text-2xl font-bold text-neutral-900 sm:text-3xl">
                                {sections.shop_by_finish.free_shipping_promo.heading}
                            </h3>
                            <p className="mt-3 text-sm text-neutral-700 sm:text-base">
                                {sections.shop_by_finish.free_shipping_promo.body}
                            </p>
                            <Link
                                href="/shop?cat=business-cards"
                                className="mt-5 inline-flex w-fit items-center gap-2 rounded-md border border-primary px-5 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                            >
                                {sections.shop_by_finish.free_shipping_promo.cta}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                        <img
                            src={sections.shop_by_finish.free_shipping_promo.image_url}
                            alt={sections.shop_by_finish.free_shipping_promo.image_alt}
                            loading="lazy"
                            className="h-64 w-full object-cover sm:h-full"
                        />
                    </div>
                </div>
            </section>

            {/* 5. Templates ------------------------------------------------ */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <SectionHeader
                        title={sections.templates.heading}
                        subtitle={sections.templates.subtitle}
                        accent={ACCENT}
                    />
                    <ul className="grid grid-cols-2 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {sections.templates.items.map((tpl: any) => (
                            <li key={tpl.name} className="group">
                                <Link href={templateHref} className="block">
                                    <div className="overflow-hidden rounded-md bg-neutral-100">
                                        <img
                                            src={tpl.image_url}
                                            alt={tpl.name}
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    </div>
                                    <h3 className="mt-3 text-sm font-semibold text-neutral-900">
                                        {tpl.name}
                                    </h3>
                                </Link>
                            </li>
                        ))}
                    </ul>
                    <div className="mt-10 flex justify-center">
                        <Link
                            href="/shop?cat=business-cards"
                            className="inline-flex items-center gap-2 rounded-md bg-primary px-7 py-3.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            {sections.templates.cta}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* 6. Make your own ------------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <SectionHeader
                        title={sections.make_your_own.heading}
                        subtitle={sections.make_your_own.subtitle}
                        accent={ACCENT}
                    />
                    <ul className="grid grid-cols-1 gap-8 sm:grid-cols-3">
                        {sections.make_your_own.items.map((entry: any, i: number) => {
                            const Icon = designIcons[i] ?? Pencil;

                            return (
                                <li key={entry.name}>
                                    <Link
                                        href={designHref}
                                        className="group flex h-full flex-col items-start rounded-lg border border-neutral-200 bg-white p-6 transition-all hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        <span
                                            className="flex size-12 items-center justify-center rounded-full"
                                            style={{ backgroundColor: `${ACCENT}1a`, color: ACCENT }}
                                        >
                                            <Icon className="size-5" />
                                        </span>
                                        <h3 className="mt-5 text-lg font-bold text-neutral-900">
                                            {entry.name}
                                        </h3>
                                        <p className="mt-2 text-sm text-neutral-600">{entry.blurb}</p>
                                        <span
                                            className="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold group-hover:underline"
                                            style={{ color: ACCENT }}
                                        >
                                            {entry.cta} <ChevronRight className="size-3.5" />
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>

            {/* 7. Why PrintPandora? --------------------------------------- */}
            <section style={{ backgroundColor: WARM_BG }}>
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 lg:grid-cols-[1fr_2fr] lg:gap-16 lg:py-20">
                    <div className="flex flex-col justify-center">
                        <h2 className="text-3xl font-bold text-neutral-900 sm:text-4xl">
                            {sections.why_printpandora.heading}
                        </h2>
                        <p className="mt-3 text-sm text-neutral-700 sm:text-base">
                            {sections.why_printpandora.body}
                        </p>
                        <Link
                            href="/about"
                            className="mt-6 inline-flex w-fit items-center gap-2 rounded-md border border-primary px-5 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                        >
                            {sections.why_printpandora.cta}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>
                    <ul className="grid grid-cols-1 gap-8 sm:grid-cols-3">
                        {sections.why_printpandora.perks.map((perk: any, i: number) => {
                            const Icon = perkIcons[i] ?? ShieldCheck;

                            return (
                                <li key={perk.name}>
                                    <span
                                        className="flex size-11 items-center justify-center rounded-full"
                                        style={{ backgroundColor: 'white', color: ACCENT }}
                                    >
                                        <Icon className="size-5" />
                                    </span>
                                    <h3 className="mt-4 text-base font-bold text-neutral-900">
                                        {perk.name}
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-700">{perk.blurb}</p>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>

            {/* 8. How to use Business Cards -------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <SectionHeader
                        title={sections.how_to_use.heading}
                        subtitle={sections.how_to_use.subtitle}
                        accent={ACCENT}
                    />
                    <ul className="grid grid-cols-1 gap-8 sm:grid-cols-3">
                        {sections.how_to_use.items.map((tip: any) => (
                            <li key={tip.title}>
                                <div className="overflow-hidden rounded-md bg-neutral-100">
                                    <img
                                        src={tip.image_url}
                                        alt={tip.title}
                                        loading="lazy"
                                        className="aspect-[4/3] w-full object-cover"
                                    />
                                </div>
                                <h3 className="mt-4 text-base font-bold text-neutral-900">
                                    {tip.title}
                                </h3>
                                <p className="mt-1 text-sm text-neutral-600">{tip.blurb}</p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* 9. FAQ ------------------------------------------------------ */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <h2 className="mb-8 text-2xl font-bold text-neutral-900 sm:text-3xl">
                        FAQs — Business Cards
                    </h2>
                    <ul className="grid grid-cols-1 gap-x-10 gap-y-8 md:grid-cols-2">
                        {c.faq.map((faq: any) => (
                            <li key={faq.question}>
                                <h3 className="text-sm font-bold text-neutral-900">{faq.question}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                    {faq.answer}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* 10. Cross-sell ---------------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <SectionHeader
                        title={sections.cross_sell.heading}
                        subtitle={sections.cross_sell.subtitle}
                        accent={ACCENT}
                    />
                    <ul className="grid grid-cols-2 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {sections.cross_sell.items.map((item: any, i: number) => {
                            const href = crossSellHrefs[i] ?? '/shop';

                            return (
                                <li key={item.name} className="group">
                                    <Link href={href} className="block">
                                        <div className="overflow-hidden rounded-md bg-neutral-100">
                                            <img
                                                src={item.image_url}
                                                alt={item.name}
                                                loading="lazy"
                                                className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                        </div>
                                        <h3 className="mt-3 text-base font-bold text-neutral-900">
                                            {item.name}
                                        </h3>
                                        <ShopLink href={href} accent={ACCENT}>
                                            {formatCta(sections.cross_sell.cta_template, item.name)}
                                        </ShopLink>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>
        </StorefrontLayout>
    );
}
