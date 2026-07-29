import { Link } from '@inertiajs/react';
import { ArrowRight, Check, ShoppingCart } from 'lucide-react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#800020';
const GOLD = '#C9A96A';
const WARM_BG = '#FAF7F2';

const includedItems = [
    {
        title: 'Paper stocks',
        description:
            'Original, Super, Luxe, Cotton — feel the weight of every option.',
    },
    {
        title: 'Sizes',
        description: 'InkPavo, Square, Standard and Mini formats side by side.',
    },
    {
        title: 'Paper finishes',
        description:
            'Matte, Gloss and Soft Touch, so you can pick the right feel.',
    },
    {
        title: 'Special finishes',
        description:
            'Gold Foil, Silver Foil and Spot UV samples to see the shine.',
    },
];

const faqs = [
    {
        q: 'How much does it cost to order a Business Card Sample Pack?',
        a: 'It’s absolutely free. We even cover the shipping — our way of letting the paper speak for itself.',
    },
    {
        q: 'Are Greeting Cards in the sample pack?',
        a: 'No. At the moment we are unable to offer pre-printed samples of Greeting Cards.',
    },
    {
        q: 'Can I get a sample of your custom products?',
        a: 'No. Sorry, we don’t currently offer samples of custom products such as Drinkware, Notebooks or Planners.',
    },
    {
        q: 'Can I order custom proofs?',
        a: 'Yes — if you need to see exactly how your design will look on a specific paper before committing to a full order, custom proofs are available for most products.',
    },
    {
        q: 'Do the samples have my design on them?',
        a: 'No — samples are pre-printed with our showcase artwork so you can focus on paper feel, print quality, and finishes.',
    },
    {
        q: 'How many sample packs can I order?',
        a: 'One pack per household, please. If you need additional samples for a team, get in touch and we’ll help out.',
    },
];

function FeatureCard({
    image,
    title,
    description,
    linkText,
    linkHref,
}: {
    image: string;
    title: string;
    description: string;
    linkText: string;
    linkHref: string;
}) {
    return (
        <div className="group">
            <div className="overflow-hidden rounded-xl bg-neutral-100">
                <img
                    src={image}
                    alt={title}
                    loading="lazy"
                    className="aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
            </div>
            <h3 className="mt-5 text-lg font-bold text-neutral-900">{title}</h3>
            <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                {description}
            </p>
            <Link
                href={linkHref}
                className="mt-4 inline-flex items-center gap-1 text-sm font-bold"
                style={{ color: ACCENT }}
            >
                {linkText} <ArrowRight className="size-3.5" />
            </Link>
        </div>
    );
}

export default function BusinessCardSamplePack() {
    return (
        <StorefrontLayout activeCategory="Business Cards">
            <SEO
                title="Free Business Card Sample Pack"
                description="Order a free InkPavo Business Card Sample Pack. Feel every paper stock, finish and size before you buy — shipping included."
            />

            {/* 1. Hero ------------------------------------------------------ */}
            <section style={{ backgroundColor: WARM_BG }}>
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 lg:grid-cols-2 lg:gap-12 lg:py-24">
                    <div className="flex flex-col justify-center">
                        <p
                            className="mb-3 text-xs font-semibold tracking-[0.15em] uppercase"
                            style={{ color: GOLD }}
                        >
                            Try before you buy
                        </p>
                        <h1 className="font-serif text-4xl leading-tight font-bold tracking-tight text-[#800020] sm:text-5xl lg:text-6xl">
                            Order a free Business Card Sample Pack
                        </h1>
                        <p className="mt-5 max-w-lg text-base text-neutral-700 sm:text-lg">
                            See and feel every paper stock, finish and size
                            before you commit. We’ll cover the shipping — you
                            just bring the curiosity.
                        </p>
                        <div className="mt-8 flex flex-wrap items-center gap-4">
                            <Link
                                href="/cart"
                                className="inline-flex items-center gap-2 rounded-md bg-primary px-8 py-3.5 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90"
                            >
                                <ShoppingCart className="size-4" />
                                Add free sample pack to cart
                            </Link>
                            <Link
                                href="/sample-packs"
                                className="inline-flex items-center gap-1 text-sm font-bold"
                                style={{ color: ACCENT }}
                            >
                                See all sample packs
                                <ArrowRight className="size-3.5" />
                            </Link>
                        </div>
                        <p className="mt-4 text-xs text-neutral-500">
                            Inspired by{' '}
                            <a
                                href="https://www.moo.com/us/sample-packs/free-sample-business-cards"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="underline"
                            >
                                MOO’s free Business Card Sample Pack
                            </a>
                            .
                        </p>
                    </div>
                    <div className="overflow-hidden rounded-xl shadow-md">
                        <img
                            src="/images/home/sample-pack-banner.png"
                            alt="Person pulling a sample business card from a white InkPavo sample folder"
                            className="aspect-[4/3] w-full object-cover"
                        />
                    </div>
                </div>
            </section>

            {/* 2. What’s included ------------------------------------------ */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="font-serif text-3xl font-bold text-[#800020] sm:text-4xl">
                            What’s inside
                        </h2>
                        <p className="mt-4 text-neutral-600">
                            A curated set of pre-printed business cards so you
                            can compare every option we offer.
                        </p>
                    </div>

                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {includedItems.map((item) => (
                            <div
                                key={item.title}
                                className="rounded-xl border border-neutral-100 bg-white p-6 shadow-sm"
                            >
                                <span
                                    className="flex h-8 w-8 items-center justify-center rounded-full"
                                    style={{ backgroundColor: ACCENT }}
                                >
                                    <Check className="size-4 text-white" />
                                </span>
                                <h3 className="mt-4 font-bold text-neutral-900">
                                    {item.title}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                    {item.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* 3. Cross-sell features --------------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <h2 className="text-center text-3xl font-bold text-neutral-800 italic sm:text-4xl">
                        There’s more where those came from
                    </h2>

                    <div className="mt-12 grid gap-10 md:grid-cols-2">
                        <FeatureCard
                            image="https://picsum.photos/seed/letterpress-hands/800/500"
                            title="Letterpress Business Cards"
                            description="Twelve debossed designs, pressed into layers of textured paper. The kind of card that fingers can’t leave alone."
                            linkText="Explore letterpress"
                            linkHref="/luxe-business-cards"
                        />
                        <FeatureCard
                            image="https://picsum.photos/seed/business-collab/800/500"
                            title="InkPavo business plans"
                            description="Pro designers, special discounts, and more — on tap. So you can look the part, without doing it all yourself."
                            linkText="More plans"
                            linkHref="/contact"
                        />
                    </div>
                </div>
            </section>

            {/* 4. FAQs ------------------------------------------------------ */}
            <section
                className="border-t border-neutral-100"
                style={{ backgroundColor: WARM_BG }}
            >
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <h2 className="text-center font-serif text-3xl font-bold text-[#800020] sm:text-4xl">
                        Frequently asked questions
                    </h2>

                    <div className="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        {faqs.map((faq) => (
                            <div key={faq.q}>
                                <h3 className="text-base font-bold text-neutral-900">
                                    {faq.q}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                    {faq.a}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
