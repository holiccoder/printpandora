import { Link } from '@inertiajs/react';
import { ArrowRight, ShoppingCart } from 'lucide-react';
import { useState  } from 'react';
import type {FormEvent} from 'react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#0f4c3a';

const businessCardListItems = [
    { label: 'Paper stocks', value: 'Original, Super, Luxe, Cotton' },
    { label: 'Sizes', value: 'PrintPandora, Square, Standard, Mini' },
    { label: 'Paper finishes', value: 'Matte, Gloss, Soft Touch' },
    { label: 'Special finishes', value: 'Gold Foil, Silver Foil, Spot UV' },
];

const allProductsListItems = [
    { label: 'Flyer sizes', value: 'Small Flyer, Square Flyer, Half Page' },
    { label: 'Flyer paper', value: 'Premium Gloss / Matte, Pearlescent' },
    { label: 'Postcard sizes', value: 'Medium, Rack' },
    { label: 'Postcard paper', value: 'Original Matte / Gloss, Super, Luxe' },
    { label: 'Business Cards', value: 'Cotton, Luxe, Super' },
    { label: 'Stickers', value: 'Round and Rectangular' },
    { label: 'Sticker paper', value: 'Vinyl Gloss, Vinyl Matte' },
];

const faqColumns = [
    [
        {
            q: 'How much does it cost to order a sample pack?',
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
    ],
    [
        {
            q: 'Can I order custom proofs?',
            a: 'Yes — if you need to see exactly how your design will look on a specific paper before committing to a full order, custom proofs are available for most products.',
        },
        {
            q: 'Do the samples have my design on them?',
            a: 'No — samples are pre-printed with our showcase artwork so you can focus on paper feel, print quality, and finishes.',
        },
        {
            q: 'How long does the sample pack take to arrive?',
            a: 'Most sample packs ship within 2 working days and arrive within 5–10 working days, depending on your destination.',
        },
    ],
    [
        {
            q: 'How many sample packs can I order?',
            a: 'One pack per household, please. If you need additional samples for a team, get in touch and we’ll help out.',
        },
        {
            q: 'Can I request specific paper samples?',
            a: 'Yes — use the chat widget at the bottom of the page to let us know what you’d like to see, and we’ll do our best to include it.',
        },
        {
            q: 'Do sample packs ship internationally?',
            a: 'Yes — free shipping worldwide applies to sample packs too. Note that delivery times vary by country.',
        },
    ],
];

/* -------------------------------------------------------------------------- */
/* UI Components                                                               */
/* -------------------------------------------------------------------------- */

function AddToCartButton() {
    return (
        <Link
            href="/cart"
            className="inline-flex items-center gap-2 rounded-md bg-primary px-8 py-3 text-sm font-bold text-primary-foreground transition-all hover:bg-primary/90 hover:shadow-md"
        >
            <ShoppingCart className="size-4" />
            Add to cart
        </Link>
    );
}

function BulletList({ items }: { items: { label: string; value: string }[] }) {
    return (
        <ul className="mt-6 space-y-2">
            {items.map((item) => (
                <li key={item.label} className="flex items-baseline gap-3 text-sm">
                    <span
                        className="size-1.5 shrink-0 rounded-full"
                        style={{ backgroundColor: ACCENT }}
                    />
                    <span>
                        <strong className="font-bold text-neutral-900">{item.label}:</strong>{' '}
                        <span className="text-neutral-600">{item.value}</span>
                    </span>
                </li>
            ))}
        </ul>
    );
}

function FeatureBlock({
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
            <div className="overflow-hidden rounded-md bg-neutral-100">
                <img
                    src={image}
                    alt={title}
                    loading="lazy"
                    className="aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
            </div>
            <h3 className="mt-5 text-base font-bold text-neutral-900 sm:text-lg">{title}</h3>
            <p className="mt-2 text-sm leading-relaxed text-neutral-600">{description}</p>
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

/* -------------------------------------------------------------------------- */
/* Page                                                                       */
/* -------------------------------------------------------------------------- */

export default function SamplePacks() {
    const [email, setEmail] = useState('');
    const [submitted, setSubmitted] = useState(false);

    const onSubscribe = (e: FormEvent) => {
        e.preventDefault();

        if (!email.trim()) {
return;
}

        setSubmitted(true);
        setEmail('');
    };

    return (
        <StorefrontLayout>
            <SEO
                title="Free Sample Packs"
                description="Get a feel for the full PrintPandora range with a free sample pack — papers, finishes, cards and stickers, posted to your door."
            />

            {/* 1. Hero — Lifestyle photo with text overlay ------------------------------------------------------ */}
            <section className="relative min-h-[560px] bg-neutral-100">
                <img
                    src="https://picsum.photos/seed/sample-pack-hero/1920/1080"
                    alt="Hands opening a PrintPandora sample folder on a wooden desk"
                    className="absolute inset-0 size-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-white/90 via-white/70 to-transparent" />
                <div className="relative mx-auto flex max-w-7xl items-center px-4 py-24">
                    <div className="max-w-xl">
                        <h1 className="text-4xl font-bold tracking-tight text-neutral-900 sm:text-5xl md:text-6xl">
                            We&apos;ll let the paper do the talking
                        </h1>
                        <p className="mt-5 text-lg text-neutral-700 sm:text-xl">
                            Get a feel for the full PrintPandora range, with a free sample pack.
                        </p>
                    </div>
                </div>
            </section>

            {/* 2. Business Card Sample Pack — Split layout ------------------------------------------------ */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto grid max-w-7xl items-stretch gap-10 px-4 py-20 md:grid-cols-2 md:gap-16 md:py-24">
                    {/* Left: Large main image */}
                    <div className="flex flex-col justify-center">
                        <div className="overflow-hidden rounded-md bg-neutral-50">
                            <img
                                src="https://picsum.photos/seed/bc-sample-open/1000/800"
                                alt="Open sample folder showcasing business card designs"
                                loading="lazy"
                                className="w-full object-cover"
                            />
                        </div>
                    </div>

                    {/* Right: 3 small thumbs + text + list + CTA */}
                    <div className="flex flex-col justify-center">
                        <div className="grid grid-cols-3 gap-3">
                            <img
                                src="https://picsum.photos/seed/texture-linen/300/200"
                                alt="Close-up of textured linen paper"
                                loading="lazy"
                                className="aspect-[3/2] w-full rounded-md bg-neutral-50 object-cover"
                            />
                            <img
                                src="https://picsum.photos/seed/card-hold/300/200"
                                alt="Hand holding a premium business card"
                                loading="lazy"
                                className="aspect-[3/2] w-full rounded-md bg-neutral-50 object-cover"
                            />
                            <img
                                src="https://picsum.photos/seed/foil-detail/300/200"
                                alt="Gold foil detail on card edge"
                                loading="lazy"
                                className="aspect-[3/2] w-full rounded-md bg-neutral-50 object-cover"
                            />
                        </div>

                        <h2 className="mt-8 text-3xl font-bold text-neutral-900 sm:text-4xl">
                            Business Card Sample Pack
                        </h2>
                        <p className="mt-4 text-base leading-relaxed text-neutral-600 sm:text-lg">
                            Buy your hands on our full range of Business Cards. Feel the paper,
                            check the print quality, and decide which stock makes the right first
                            impression.
                        </p>

                        <BulletList items={businessCardListItems} />

                        <div className="mt-8">
                            <AddToCartButton />
                        </div>
                    </div>
                </div>
            </section>

            {/* 3. All Products Sample Pack — Reversed split layout ---------------------------------------- */}
            <section className="border-t border-neutral-100 bg-neutral-50">
                <div className="mx-auto grid max-w-7xl items-stretch gap-10 px-4 py-20 md:grid-cols-2 md:gap-16 md:py-24">
                    {/* Left: 3 small thumbs + text + list + CTA */}
                    <div className="flex flex-col justify-center md:order-1">
                        <div className="grid grid-cols-3 gap-3">
                            <img
                                src="https://picsum.photos/seed/green-booklet/300/200"
                                alt="Green sample pack booklet"
                                loading="lazy"
                                className="aspect-[3/2] w-full rounded-md bg-white object-cover"
                            />
                            <img
                                src="https://picsum.photos/seed/flipping-pages/300/200"
                                alt="Flipping through sample materials"
                                loading="lazy"
                                className="aspect-[3/2] w-full rounded-md bg-white object-cover"
                            />
                            <img
                                src="https://picsum.photos/seed/sticker-sheet/300/200"
                                alt="Sample sticker sheets"
                                loading="lazy"
                                className="aspect-[3/2] w-full rounded-md bg-white object-cover"
                            />
                        </div>

                        <h2 className="mt-8 text-3xl font-bold text-neutral-900 sm:text-4xl">
                            All Products Sample Pack
                        </h2>
                        <p className="mt-4 text-base leading-relaxed text-neutral-600 sm:text-lg">
                            Papers you&apos;ll want to stroke. Finishes you&apos;ll hold up to the
                            light. This is the world of PrintPandora print, in one pack.
                        </p>

                        <BulletList items={allProductsListItems} />

                        <div className="mt-8">
                            <AddToCartButton />
                        </div>
                    </div>

                    {/* Right: Large main image — colorful spread */}
                    <div className="flex flex-col justify-center md:order-2">
                        <div className="overflow-hidden rounded-md bg-white">
                            <img
                                src="https://picsum.photos/seed/rainbow-samples/1000/800"
                                alt="Vibrant array of sample materials: yellow, blue, orange cards and stickers"
                                loading="lazy"
                                className="w-full object-cover"
                            />
                        </div>
                    </div>
                </div>
            </section>

            {/* 4. Secondary Features — "There's more where those came from" 2-card grid -------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-20 md:py-24">
                    <h2 className="text-center text-3xl font-bold italic text-neutral-800 sm:text-4xl">
                        There&apos;s more where those came from
                    </h2>

                    <div className="mt-12 grid grid-cols-1 gap-10 md:grid-cols-2 md:gap-10">
                        <FeatureBlock
                            image="https://picsum.photos/seed/letterpress-hands/800/500"
                            title="Letterpress Business Cards"
                            description="Twelve debossed designs, pressed into layers of textured Mohawk Superfine paper. The kind of card that fingers can't leave alone."
                            linkText="Get in touch"
                            linkHref="/contact"
                        />
                        <FeatureBlock
                            image="https://picsum.photos/seed/business-collab/800/500"
                            title="PrintPandora business plans"
                            description="Pro designers, special discounts, and more — on tap. Everything you need to look the part, without doing it all yourself."
                            linkText="More plans"
                            linkHref="/contact"
                        />
                    </div>
                </div>
            </section>

            {/* 5. FAQs + Newsletter — 3-column static grid + signup bar ------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-20 md:py-24">
                    <h2 className="text-center text-4xl font-bold text-neutral-900 sm:text-5xl">
                        FAQs
                    </h2>

                    <div className="mt-12 grid grid-cols-1 gap-10 md:grid-cols-3 md:gap-8">
                        {faqColumns.map((column, colIndex) => (
                            <div key={colIndex} className="space-y-8">
                                {column.map((faq) => (
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
                        ))}
                    </div>
                </div>

                {/* Newsletter signup — Full-width horizontal bar */}
                <div className="border-t border-neutral-200" style={{ backgroundColor: ACCENT }}>
                    <div className="mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-12 md:flex-row md:justify-between md:py-10">
                        <h3 className="max-w-xl text-center text-xl font-bold text-white md:text-left md:text-2xl">
                            Sign up to the PrintPandora newsletter for special offers, news and
                            inspiration
                        </h3>
                        {submitted ? (
                            <p className="shrink-0 text-sm font-semibold text-white/90" role="status">
                                Thanks — you&apos;re on the list.
                            </p>
                        ) : (
                            <form
                                onSubmit={onSubscribe}
                                className="flex w-full max-w-md shrink-0 items-center gap-0 md:w-auto"
                                noValidate
                            >
                                <label htmlFor="faq-newsletter-email" className="sr-only">
                                    Your email address
                                </label>
                                <input
                                    id="faq-newsletter-email"
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="Your email address"
                                    className="flex-1 rounded-l-md border-0 bg-white px-5 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-white md:w-72"
                                />
                                <button
                                    type="submit"
                                    className="rounded-r-md border-2 border-l border-white/20 bg-white/10 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-white/20"
                                    aria-label="Submit email"
                                >
                                    <ArrowRight className="size-5" />
                                </button>
                            </form>
                        )}
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
