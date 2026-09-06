import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgePercent,
    Building2,
    Check,
    ClipboardCheck,
    FileCheck2,
    Handshake,
    Layers3,
    Palette,
    Printer,
    Scissors,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { DesignerPartnerPageContent } from '@/types/content';

const BENEFIT_ICONS: Record<string, LucideIcon> = {
    'badge-percent': BadgePercent,
    printer: Printer,
    'file-check': FileCheck2,
    layers: Layers3,
    handshake: Handshake,
};

const AUDIENCE_ICONS: LucideIcon[] = [
    Palette,
    Palette,
    Palette,
    Scissors,
    ClipboardCheck,
    Building2,
    Building2,
    Building2,
    Building2,
    Handshake,
];

export default function DesignerPartnerProgram() {
    const c = useContent('designer_partner_page') as DesignerPartnerPageContent;

    return (
        <StorefrontLayout activeCategory="Design Service">
            <SEO
                title={c.seo.title ?? c.hero.heading}
                description={c.seo.description}
                image={c.hero.image_url}
            />

            <Hero content={c} />
            <AnchorNavigation links={c.anchor_links} />
            <BenefitsSection content={c} />
            <PricingSection content={c} />
            <SupportSection content={c} />
            <EligibilitySection content={c} />
            <ProcessSection content={c} />
            <MaterialsSection content={c} />
            <ApplySection content={c} />
        </StorefrontLayout>
    );
}

function Hero({ content }: { content: DesignerPartnerPageContent }) {
    const { hero } = content;

    return (
        <section className="overflow-hidden border-b border-[#eadfce] bg-[#fbf6ee]">
            <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16 lg:px-8 lg:py-24">
                <div className="max-w-xl">
                    <p className="text-xs font-semibold tracking-[0.18em] text-[#800020] uppercase">
                        {hero.eyebrow}
                    </p>
                    <h1 className="mt-5 font-serif text-4xl leading-[1.05] font-bold tracking-tight text-[#800020] sm:text-5xl lg:text-6xl">
                        {hero.heading}
                    </h1>
                    <p className="mt-6 max-w-lg text-base leading-relaxed text-neutral-700 sm:text-lg">
                        {hero.body}
                    </p>

                    <div className="mt-8 flex flex-wrap items-center gap-4">
                        <a
                            href={hero.cta_href}
                            className="inline-flex items-center gap-2 rounded-md bg-[#800020] px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#650019] focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            {hero.cta}
                            <ArrowRight className="size-4" />
                        </a>
                        <span className="text-sm text-neutral-500">
                            Built for client-facing print projects
                        </span>
                    </div>

                    <div className="mt-10 flex items-start gap-4 border-l-2 border-[#c9a96a] pl-4">
                        <BadgePercent
                            className="mt-0.5 size-6 shrink-0 text-[#800020]"
                            strokeWidth={1.7}
                        />
                        <div>
                            <p className="font-serif text-xl font-bold text-neutral-900">
                                {hero.highlight}
                            </p>
                            <p className="mt-1 text-sm leading-relaxed text-neutral-600">
                                {hero.highlight_body}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="relative overflow-hidden rounded-2xl bg-neutral-200 shadow-xl shadow-[#800020]/10">
                    <img
                        src={hero.image_url}
                        alt={hero.image_alt}
                        className="aspect-[4/3] w-full object-cover"
                    />
                    <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/55 to-transparent px-6 pt-16 pb-5">
                        <p className="text-xs font-semibold tracking-[0.18em] text-white/90 uppercase">
                            InkPavo print partnership
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

function AnchorNavigation({
    links,
}: {
    links: { label: string; href: string }[];
}) {
    return (
        <nav
            aria-label="On this page"
            className="border-b border-neutral-200 bg-white"
        >
            <div className="mx-auto max-w-7xl overflow-x-auto px-4 sm:px-6 lg:px-8">
                <ul className="flex min-w-max items-center gap-1 py-3">
                    {links.map((link) => (
                        <li key={link.href}>
                            <a
                                href={link.href}
                                className="inline-flex rounded-full px-4 py-2 text-sm font-medium whitespace-nowrap text-neutral-600 transition hover:bg-[#fbf6ee] hover:text-[#800020] focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:outline-none"
                            >
                                {link.label}
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
        </nav>
    );
}

function BenefitsSection({ content }: { content: DesignerPartnerPageContent }) {
    const { benefits } = content;

    return (
        <section id="benefits" className="scroll-mt-32 bg-white py-16 lg:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionIntro
                    eyebrow={benefits.eyebrow}
                    heading={benefits.heading}
                    body={benefits.body}
                />

                <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                    {benefits.items.map((item, index) => {
                        const Icon = BENEFIT_ICONS[item.icon] ?? Check;

                        return (
                            <article
                                key={item.title}
                                className="flex flex-col rounded-xl border border-neutral-200 bg-[#fbfaf6] p-6 transition hover:-translate-y-0.5 hover:border-[#c9a96a] hover:shadow-md"
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex size-11 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                                        <Icon
                                            className="size-5"
                                            strokeWidth={1.7}
                                        />
                                    </div>
                                    <span className="font-serif text-2xl font-bold text-[#c9a96a]">
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                </div>
                                <h3 className="mt-6 font-serif text-xl leading-tight font-bold text-neutral-900">
                                    {item.title}
                                </h3>
                                <p className="mt-3 text-sm leading-relaxed text-neutral-600">
                                    {item.description}
                                </p>
                            </article>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function PricingSection({ content }: { content: DesignerPartnerPageContent }) {
    const { pricing } = content;

    return (
        <section
            id="pricing"
            className="scroll-mt-32 bg-[#fbf6ee] py-16 lg:py-24"
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionIntro
                    eyebrow={pricing.eyebrow}
                    heading={pricing.heading}
                    body={pricing.body}
                />

                <div className="mt-12 grid gap-5 lg:grid-cols-3">
                    {pricing.tiers.map((tier, index) => (
                        <article
                            key={tier.name}
                            className={`flex flex-col rounded-2xl border bg-white p-7 shadow-sm sm:p-8 ${
                                index === pricing.tiers.length - 1
                                    ? 'border-[#800020] shadow-lg shadow-[#800020]/10'
                                    : 'border-[#eadfce]'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-4">
                                <h3 className="font-serif text-2xl font-bold text-[#800020]">
                                    {tier.name}
                                </h3>
                                <BadgePercent className="size-6 shrink-0 text-[#c9a96a]" />
                            </div>
                            <p className="mt-6 font-serif text-4xl font-bold tracking-tight text-neutral-900">
                                {tier.discount}
                            </p>
                            <p className="mt-4 text-sm leading-relaxed text-neutral-600">
                                {tier.description}
                            </p>
                            <div className="mt-auto border-t border-neutral-200 pt-5">
                                <p className="text-xs font-semibold tracking-[0.12em] text-neutral-500 uppercase">
                                    Best for
                                </p>
                                <p className="mt-2 text-sm leading-relaxed font-medium text-neutral-800">
                                    {tier.audience}
                                </p>
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

function SupportSection({ content }: { content: DesignerPartnerPageContent }) {
    const { support } = content;

    return (
        <section id="support" className="scroll-mt-32 bg-white py-16 lg:py-24">
            <div className="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-start lg:gap-20 lg:px-8">
                <div>
                    <SectionIntro
                        eyebrow={support.eyebrow}
                        heading={support.heading}
                        body={support.body}
                        align="left"
                    />
                </div>

                <div className="rounded-2xl border border-neutral-200 bg-[#fbfaf6] p-6 sm:p-8">
                    <div className="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                        {support.checks.map((check) => (
                            <div key={check} className="flex items-start gap-3">
                                <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                                    <Check
                                        className="size-3.5"
                                        strokeWidth={2.5}
                                    />
                                </span>
                                <p className="text-sm leading-relaxed text-neutral-700">
                                    {check}
                                </p>
                            </div>
                        ))}
                    </div>
                    <p className="mt-8 border-t border-neutral-200 pt-6 text-sm leading-relaxed text-neutral-700">
                        {support.closing}
                    </p>
                    <p className="mt-4 text-xs leading-relaxed text-neutral-500">
                        {support.note}
                    </p>
                </div>
            </div>
        </section>
    );
}

function EligibilitySection({
    content,
}: {
    content: DesignerPartnerPageContent;
}) {
    const { eligibility } = content;

    return (
        <section
            id="eligibility"
            className="scroll-mt-32 bg-[#fbfaf6] py-16 lg:py-24"
        >
            <div className="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div>
                    <SectionIntro
                        eyebrow={eligibility.eyebrow}
                        heading={eligibility.heading}
                        body={eligibility.body}
                        align="left"
                    />

                    <div className="mt-8 grid gap-3 sm:grid-cols-2">
                        {eligibility.audiences.map((audience, index) => {
                            const Icon = AUDIENCE_ICONS[index] ?? Handshake;

                            return (
                                <div
                                    key={audience}
                                    className="flex items-center gap-3 rounded-lg border border-neutral-200 bg-white px-4 py-3"
                                >
                                    <Icon
                                        className="size-4 shrink-0 text-[#800020]"
                                        strokeWidth={1.8}
                                    />
                                    <span className="text-sm font-medium text-neutral-800">
                                        {audience}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>

                <div className="rounded-2xl bg-[#800020] p-7 text-white sm:p-10">
                    <ClipboardCheck
                        className="size-8 text-[#e5c98f]"
                        strokeWidth={1.5}
                    />
                    <h3 className="mt-6 font-serif text-3xl font-bold">
                        {eligibility.requirements_heading}
                    </h3>
                    <p className="mt-4 text-sm leading-relaxed text-white/75">
                        {eligibility.requirements_intro}
                    </p>
                    <ul className="mt-8 space-y-4">
                        {eligibility.requirements.map((requirement) => (
                            <li
                                key={requirement}
                                className="flex items-start gap-3"
                            >
                                <Check
                                    className="mt-0.5 size-4 shrink-0 text-[#e5c98f]"
                                    strokeWidth={2.2}
                                />
                                <span className="text-sm leading-relaxed text-white/90">
                                    {requirement}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}

function ProcessSection({ content }: { content: DesignerPartnerPageContent }) {
    const { process } = content;

    return (
        <section
            id="process"
            className="scroll-mt-32 bg-[#800020] py-16 text-white lg:py-24"
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionIntro
                    eyebrow={process.eyebrow}
                    heading={process.heading}
                    body=""
                    tone="dark"
                />

                <ol className="mt-12 grid gap-8 md:grid-cols-5 md:gap-0">
                    {process.steps.map((step, index) => (
                        <li
                            key={step.number}
                            className="relative border-l border-white/25 pl-5 md:border-t md:border-l-0 md:px-5 md:pt-7 md:first:pl-0 md:last:pr-0"
                        >
                            <span className="absolute top-0 -left-3 flex size-6 items-center justify-center rounded-full bg-[#e5c98f] text-xs font-bold text-[#800020] md:top-[-13px] md:left-5 md:first:left-0">
                                {index + 1}
                            </span>
                            <p className="text-xs font-semibold tracking-[0.16em] text-[#e5c98f] uppercase">
                                {step.number}
                            </p>
                            <h3 className="mt-3 font-serif text-xl leading-tight font-bold">
                                {step.title}
                            </h3>
                            <p className="mt-3 text-sm leading-relaxed text-white/70">
                                {step.description}
                            </p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}

function MaterialsSection({
    content,
}: {
    content: DesignerPartnerPageContent;
}) {
    const { materials } = content;

    return (
        <section
            id="materials"
            className="scroll-mt-32 bg-white py-16 lg:py-24"
        >
            <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div className="order-2 lg:order-1">
                    <SectionIntro
                        eyebrow={materials.eyebrow}
                        heading={materials.heading}
                        body={materials.body}
                        align="left"
                    />

                    <ul className="mt-8 grid gap-4 sm:grid-cols-2">
                        {materials.items.map((item) => (
                            <li key={item} className="flex items-start gap-3">
                                <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                                    <Check
                                        className="size-3.5"
                                        strokeWidth={2.5}
                                    />
                                </span>
                                <span className="text-sm leading-relaxed text-neutral-700">
                                    {item}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="order-1 overflow-hidden rounded-2xl bg-neutral-100 shadow-lg lg:order-2">
                    <img
                        src={materials.image_url}
                        alt={materials.image_alt}
                        className="aspect-[4/3] w-full object-cover"
                    />
                </div>
            </div>
        </section>
    );
}

function ApplySection({ content }: { content: DesignerPartnerPageContent }) {
    const { apply } = content;

    return (
        <section
            id="apply"
            className="scroll-mt-32 bg-[#fbf6ee] py-16 lg:py-24"
        >
            <div className="mx-auto grid max-w-5xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-[1fr_auto] lg:px-8">
                <div>
                    <p className="text-xs font-semibold tracking-[0.18em] text-[#800020] uppercase">
                        {apply.eyebrow}
                    </p>
                    <h2 className="mt-4 max-w-2xl font-serif text-3xl leading-tight font-bold text-[#800020] sm:text-4xl lg:text-5xl">
                        {apply.heading}
                    </h2>
                    <p className="mt-5 max-w-2xl text-base leading-relaxed text-neutral-700">
                        {apply.body}
                    </p>
                </div>

                <Link
                    href={apply.cta_href}
                    className="inline-flex items-center justify-center gap-2 rounded-md bg-[#800020] px-7 py-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#650019] focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    {apply.cta}
                    <ArrowRight className="size-4" />
                </Link>
            </div>
        </section>
    );
}

function SectionIntro({
    eyebrow,
    heading,
    body,
    align = 'center',
    tone = 'light',
}: {
    eyebrow: string;
    heading: string;
    body: string;
    align?: 'left' | 'center';
    tone?: 'light' | 'dark';
}) {
    const isDark = tone === 'dark';
    const isLeft = align === 'left';

    return (
        <header
            className={isLeft ? 'max-w-xl' : 'mx-auto max-w-2xl text-center'}
        >
            <p
                className={`text-xs font-semibold tracking-[0.18em] uppercase ${
                    isDark ? 'text-[#e5c98f]' : 'text-[#800020]'
                }`}
            >
                {eyebrow}
            </p>
            <h2
                className={`mt-4 font-serif text-3xl leading-tight font-bold sm:text-4xl ${
                    isDark ? 'text-white' : 'text-[#800020]'
                }`}
            >
                {heading}
            </h2>
            {body && (
                <p
                    className={`mt-5 text-base leading-relaxed ${
                        isDark ? 'text-white/70' : 'text-neutral-600'
                    }`}
                >
                    {body}
                </p>
            )}
        </header>
    );
}
