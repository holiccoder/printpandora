import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    ShoppingCart,
    Star,
    ShieldCheck,
    Mail,
} from 'lucide-react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#800020';
const GOLD = '#C9A96A';
const WARM_BG = '#FAF7F2';

const paperStocks = [
    {
        title: 'Original Paper',
        weight: '16pt / 350gsm',
        feel: 'Smooth, standard excellence',
        description:
            'Thicker than your average card, with a choice of crisp Matte or vibrant Gloss finishes. The classic business card, refined.',
    },
    {
        title: 'Super Paper',
        weight: '19pt / 400gsm',
        feel: 'Sturdy, ultra-durable, premium',
        description:
            'Non-bendy, water-resistant, and silky-smooth. Perfectly suited for high-impact Special Finishes like Gold Foil or Spot Gloss.',
    },
    {
        title: 'Luxe Paper',
        weight: '32pt / 600gsm',
        feel: 'Extraordinary, triple-thick, colored seam',
        description:
            'Made from four layers of archival-quality paper with a distinctive colored seam running through the middle. A card that gets talked about.',
    },
    {
        title: 'Cotton Paper',
        weight: '18pt / 298gsm',
        feel: 'Tactile, textured, 100% recycled',
        description:
            'Crafted from 100% recycled t-shirt cotton fibers. Exceptionally tactile, naturally textured, and completely tree-free.',
    },
];

const specialFinishes = [
    {
        title: 'Gold Foil',
        description:
            'Elegant, brilliant metallic gold accents heat-pressed onto your design to catch the light.',
    },
    {
        title: 'Silver Foil',
        description:
            'Crisp, contemporary metallic silver highlighting that adds a sharp, modern luster to text or logos.',
    },
    {
        title: 'Spot Gloss (UV)',
        description:
            'A glossy, raised finish layered over select areas to create contrast with the surrounding matte paper.',
    },
];

const faqs = [
    {
        q: 'How much does the sample pack cost?',
        a: 'Absolutely nothing. The Business Card Sample Pack is completely free, and we even cover the shipping so you can experience our quality first-hand.',
    },
    {
        q: 'Do the samples feature my custom designs?',
        a: 'No — our sample packs are pre-printed with our showcase branding and design layouts. This allows us to ship them immediately and lets you focus on comparing the tactile paper weight, textures, and finish qualities.',
    },
    {
        q: 'How long will it take to arrive?',
        a: 'Most free sample packs are dispatched within 2 business days. Delivery times typically range between 5 to 10 business days depending on your location.',
    },
    {
        q: 'How many sample packs can I order?',
        a: 'We limit orders to one free sample pack per customer/household. If you are a larger agency or team requiring multiple samples, please contact our support team and we will be happy to accommodate you.',
    },
];

export default function FreeSamplePack() {
    return (
        <StorefrontLayout activeCategory="Business Cards">
            <SEO
                title="Free Business Card Sample Pack"
                description="Experience the quality of InkPavo before you buy. Order a free business card sample pack featuring our paper stocks, weights, and premium finishes — free worldwide shipping included."
            />

            {/* 1. Premium Hero Section ------------------------------------ */}
            <section
                style={{ backgroundColor: WARM_BG }}
                className="relative overflow-hidden"
            >
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 lg:grid-cols-12 lg:gap-16 lg:py-24">
                    <div className="flex flex-col justify-center lg:col-span-6">
                        <div className="mb-3 flex items-center gap-2">
                            <span className="h-px w-8 bg-neutral-300" />
                            <p
                                className="text-xs font-semibold tracking-[0.2em] uppercase"
                                style={{ color: GOLD }}
                            >
                                Try Before You Buy
                            </p>
                        </div>
                        <h1 className="font-serif text-4xl leading-tight font-bold tracking-tight text-[#800020] sm:text-5xl lg:text-6xl">
                            Free Business Card Sample Pack
                        </h1>
                        <p className="mt-6 text-base leading-relaxed text-neutral-700 sm:text-lg">
                            See, feel, and compare every premium paper stock,
                            weight, and stunning finish we offer before placing
                            your order. We’ll even ship it to your door for
                            free.
                        </p>

                        <div className="mt-8 flex flex-wrap items-center gap-4">
                            <Link
                                href="/cart"
                                className="inline-flex items-center gap-2 rounded-md bg-primary px-8 py-4 text-sm font-bold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 hover:shadow-md"
                            >
                                <ShoppingCart className="size-4" />
                                Add Free Sample Pack to Cart
                            </Link>
                            <Link
                                href="/sample-packs"
                                className="inline-flex items-center gap-1.5 text-sm font-bold transition-all hover:translate-x-1"
                                style={{ color: ACCENT }}
                            >
                                See All Sample Packs
                                <ArrowRight className="size-3.5" />
                            </Link>
                        </div>

                        <div className="mt-8 grid grid-cols-2 gap-4 border-t border-neutral-200 pt-6 text-xs text-neutral-500">
                            <div className="flex items-center gap-2">
                                <Check className="size-4 text-emerald-600" />
                                <span>100% Free Pack & Shipping</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Check className="size-4 text-emerald-600" />
                                <span>Ships in 2 Business Days</span>
                            </div>
                        </div>
                    </div>
                    <div className="lg:col-span-6">
                        <div className="overflow-hidden rounded-xl border border-neutral-100 shadow-lg">
                            <img
                                src="/images/home/sample-pack-banner.png"
                                alt="Pre-printed premium card samples displayed on beautiful craft paper"
                                className="aspect-[16/11] w-full object-cover"
                            />
                        </div>
                    </div>
                </div>
            </section>

            {/* 2. Paper Stocks Spotlight ----------------------------------- */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <div className="mx-auto max-w-3xl text-center">
                        <h2 className="font-serif text-3xl font-bold text-[#800020] sm:text-4xl">
                            Feel the Difference in Every Stock
                        </h2>
                        <p className="mt-4 text-base text-neutral-600">
                            From natural cotton textures to ultra-thick colored
                            cores, our paper is sourced to make your brand
                            impossible to ignore.
                        </p>
                    </div>

                    <div className="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        {paperStocks.map((stock) => (
                            <div
                                key={stock.title}
                                className="flex flex-col justify-between rounded-xl border border-neutral-100 bg-neutral-50/30 p-6 shadow-xs transition-all duration-350 hover:shadow-sm"
                            >
                                <div>
                                    <span className="inline-block rounded-full bg-[#800020]/10 px-3 py-1 text-xs font-semibold text-[#800020]">
                                        {stock.weight}
                                    </span>
                                    <h3 className="mt-4 font-serif text-xl font-bold text-neutral-900">
                                        {stock.title}
                                    </h3>
                                    <p className="mt-1 text-xs font-medium text-neutral-500 italic">
                                        {stock.feel}
                                    </p>
                                    <p className="mt-3 text-sm leading-relaxed text-neutral-600">
                                        {stock.description}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* 3. Special Finishes spotlight -------------------------------- */}
            <section
                style={{ backgroundColor: WARM_BG }}
                className="border-neutral-150 border-t border-b"
            >
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <div className="mx-auto mb-14 max-w-3xl text-center">
                        <h2 className="font-serif text-3xl font-bold text-[#800020] sm:text-4xl">
                            Add a Touch of Brilliant Shine
                        </h2>
                        <p className="mt-4 text-base text-neutral-600">
                            Our sample pack includes pre-printed specimens
                            highlighting premium tactile coatings and
                            light-catching metallic foils.
                        </p>
                    </div>

                    <div className="grid gap-8 md:grid-cols-3">
                        {specialFinishes.map((finish, idx) => (
                            <div
                                key={finish.title}
                                className="rounded-xl border border-neutral-200 bg-white p-6 shadow-xs transition-all duration-300 hover:border-[#800020]/30"
                            >
                                <div className="flex items-center gap-3">
                                    <span
                                        className="border-neutral-150 flex h-8 w-8 items-center justify-center rounded-full border bg-[#FAF7F2] text-xs font-semibold"
                                        style={{ color: GOLD }}
                                    >
                                        0{idx + 1}
                                    </span>
                                    <h3 className="font-serif text-lg font-bold text-neutral-900">
                                        {finish.title}
                                    </h3>
                                </div>
                                <p className="mt-4 text-sm leading-relaxed text-neutral-600">
                                    {finish.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* 4. Trust Banner --------------------------------------------- */}
            <section className="bg-white">
                <div className="mx-auto max-w-5xl px-4 py-16 text-center">
                    <div className="mb-4 flex justify-center gap-1.5 text-amber-500">
                        <Star className="size-5 fill-current" />
                        <Star className="size-5 fill-current" />
                        <Star className="size-5 fill-current" />
                        <Star className="size-5 fill-current" />
                        <Star className="size-5 fill-current" />
                    </div>
                    <blockquote className="mx-auto max-w-3xl font-serif text-xl leading-relaxed text-neutral-800 italic md:text-2xl">
                        "InkPavo's sample pack gave me the absolute confidence
                        to order our company's rebranding materials. Seeing the
                        spot gloss and feeling the Luxe color seam in person is
                        a complete game changer."
                    </blockquote>
                    <p className="mt-4 text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                        — Sarah K., Principal Creative Director
                    </p>
                </div>
            </section>

            {/* 5. FAQs ------------------------------------------------------ */}
            <section className="border-t border-neutral-100 bg-[#FAF7F2]/40">
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <div className="mx-auto mb-14 max-w-3xl text-center">
                        <h2 className="font-serif text-3xl font-bold text-[#800020] sm:text-4xl">
                            Frequently Asked Questions
                        </h2>
                        <p className="mt-3 text-neutral-600">
                            Got questions? We've got answers.
                        </p>
                    </div>

                    <div className="mx-auto grid max-w-5xl gap-8 md:grid-cols-2">
                        {faqs.map((faq) => (
                            <div
                                key={faq.q}
                                className="border-neutral-150 rounded-xl border bg-white p-6 shadow-xs"
                            >
                                <h3 className="text-base font-bold text-neutral-900">
                                    {faq.q}
                                </h3>
                                <p className="mt-3 text-sm leading-relaxed text-neutral-600">
                                    {faq.a}
                                </p>
                            </div>
                        ))}
                    </div>

                    <div className="mx-auto mt-16 max-w-xl rounded-xl border border-neutral-200 bg-white p-8 text-center shadow-xs">
                        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                            <Mail className="size-5" />
                        </div>
                        <h3 className="text-lg font-bold text-neutral-900">
                            Still have questions?
                        </h3>
                        <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                            Need specific advice on card design or paper options
                            for an upcoming launch? We are here to help you get
                            it exactly right.
                        </p>
                        <Link
                            href="/contact-us"
                            className="mt-5 inline-flex items-center gap-1.5 text-sm font-bold"
                            style={{ color: ACCENT }}
                        >
                            Get in Touch with Us{' '}
                            <ArrowRight className="size-3.5" />
                        </Link>
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
