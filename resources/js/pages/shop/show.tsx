import { Link, router } from '@inertiajs/react';
import { ChevronRight, Star } from 'lucide-react';
import { useMemo, useState } from 'react';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#0f4c3a';

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    featured_image: string | null;
    category: { id: number; name: string; slug: string };
}

interface Props {
    product: Product;
    related: Product[];
}

/* -------------------------------------------------------------------------- */
/* Configurator option data                                                   */
/* -------------------------------------------------------------------------- */

const galleryThumbs = [
    'https://images.unsplash.com/photo-1606857521015-7f9fcf423740?auto=format&fit=crop&w=400&q=70',
    'https://images.unsplash.com/photo-1583912267550-d6c2ac3196c0?auto=format&fit=crop&w=400&q=70',
    'https://images.unsplash.com/photo-1517242810446-cc8951b2be40?auto=format&fit=crop&w=400&q=70',
    'https://images.unsplash.com/photo-1542435503-956c469947f6?auto=format&fit=crop&w=400&q=70',
];

const sizes = [
    { id: 'standard', label: 'Standard', dims: '85 x 55 mm', shape: 'rect' as const },
    { id: 'square', label: 'Square', dims: '65 x 65 mm', shape: 'square' as const },
];

const finishes = [
    {
        id: 'matte',
        label: 'Matte',
        thumb: 'https://images.unsplash.com/photo-1583912267550-d6c2ac3196c0?auto=format&fit=crop&w=200&q=70',
    },
    {
        id: 'gloss',
        label: 'Gloss',
        thumb: 'https://images.unsplash.com/photo-1606857521015-7f9fcf423740?auto=format&fit=crop&w=200&q=70',
    },
];

const corners = [
    { id: 'standard', label: 'Standard' },
    { id: 'rounded', label: 'Rounded' },
];

/** Quantity tiers — base price × multiplier with a “discount” story.
 *  The 200 row is highlighted as the recommended option. */
const quantityTiers = [
    { qty: 50, multiplier: 1, save: 0 },
    { qty: 100, multiplier: 1.6, save: 0.1 },
    { qty: 200, multiplier: 2.4, save: 0.2 },
    { qty: 400, multiplier: 4, save: 0.3 },
];

const RECOMMENDED_QTY = 200;

const paperStocks = [
    {
        id: 'matte',
        name: 'Matte',
        blurb: 'Smooth, modern, and versatile. Our most popular choice for everyday print.',
        image: 'https://images.unsplash.com/photo-1583912267550-d6c2ac3196c0?auto=format&fit=crop&w=600&q=70',
    },
    {
        id: 'soft-touch',
        name: 'Soft Touch',
        blurb: 'A velvety, tactile finish that feels as good as it looks.',
        image: 'https://images.unsplash.com/photo-1606857521015-7f9fcf423740?auto=format&fit=crop&w=600&q=70',
    },
    {
        id: 'cotton',
        name: 'Cotton',
        blurb: '100% cotton — eco-friendly, surprisingly soft, and tree-free.',
        image: 'https://images.unsplash.com/photo-1517242810446-cc8951b2be40?auto=format&fit=crop&w=600&q=70',
    },
    {
        id: 'luxe',
        name: 'Luxe',
        blurb: 'Triple layered with a coloured seam — the ultimate first impression.',
        image: 'https://images.unsplash.com/photo-1611162616475-46b635cb6868?auto=format&fit=crop&w=600&q=70',
    },
];

const businessBlocks = [
    {
        title: 'Order for the whole team',
        body: 'Need cards for multiple employees? Manage everyone’s details and order in one go.',
        image: 'https://images.unsplash.com/photo-1542435503-956c469947f6?auto=format&fit=crop&w=600&q=70',
        cta: 'Learn more',
        href: '/shop?cat=business-services',
    },
    {
        title: 'Manage brand templates',
        body: 'Lock down your brand fonts, colours and logos so every order stays on-brand.',
        image: 'https://images.unsplash.com/photo-1611162616475-46b635cb6868?auto=format&fit=crop&w=600&q=70',
        cta: 'Discover Brand Manager',
        href: '/shop?cat=business-services',
    },
    {
        title: 'Dedicated account manager',
        body: 'Larger orders get a dedicated rep to handle quoting, proofing and shipping.',
        image: 'https://images.unsplash.com/photo-1517242810446-cc8951b2be40?auto=format&fit=crop&w=600&q=70',
        cta: 'Talk to an expert',
        href: '/contact',
    },
];

const faqs = [
    {
        q: 'What are the exact dimensions?',
        a: 'Standard: 85 × 55 mm. Square: 65 × 65 mm. MiniCard: 70 × 28 mm.',
    },
    {
        q: 'How thick is the paper?',
        a: 'Original Business Cards are printed on 350 gsm uncoated stock — substantial without being bulky.',
    },
    {
        q: 'Can I print on both sides?',
        a: 'Yes. All Original Business Cards are printed full-colour on both sides at no extra cost.',
    },
    {
        q: 'Will my colours print accurately?',
        a: 'We print in CMYK. Designs created in RGB will be auto-converted, which can shift bright tones slightly.',
    },
    {
        q: 'What file format should I upload?',
        a: 'PDF (preferred), PNG, or JPG at 300 dpi or higher. Include a 2 mm bleed on every edge.',
    },
    {
        q: 'How long does delivery take?',
        a: 'Standard: 3–5 working days. Next Day Delivery is available on selected products before 12pm.',
    },
    {
        q: 'Can I order a sample first?',
        a: 'Yes — order a free Business Card Sample Pack to feel every paper stock before you buy.',
    },
    {
        q: 'Do you offer Printfinity?',
        a: 'Yes. Print a different design on the back of every card in your pack at no extra cost.',
    },
    {
        q: 'Is there a money-back guarantee?',
        a: 'The MOO Promise — if you’re not 100% happy with your order, we’ll reprint it or refund you.',
    },
];

/* -------------------------------------------------------------------------- */
/* Page                                                                       */
/* -------------------------------------------------------------------------- */

export default function ShopShow({ product, related }: Props) {
    const basePrice = parseFloat(product.price);

    const [selectedSize, setSelectedSize] = useState(sizes[0].id);
    const [selectedFinish, setSelectedFinish] = useState(finishes[0].id);
    const [selectedCorners, setSelectedCorners] = useState(corners[0].id);
    const [selectedQty, setSelectedQty] = useState<number>(RECOMMENDED_QTY);
    const [activeImage, setActiveImage] = useState(
        product.featured_image ?? galleryThumbs[0],
    );
    const [added, setAdded] = useState(false);

    const tier = useMemo(
        () => quantityTiers.find((t) => t.qty === selectedQty) ?? quantityTiers[0],
        [selectedQty],
    );

    const fullPrice = basePrice * tier.multiplier;
    const finalPrice = fullPrice * (1 - tier.save);

    const sizeLabel = sizes.find((s) => s.id === selectedSize)?.label ?? '';
    const finishLabel = finishes.find((f) => f.id === selectedFinish)?.label ?? '';

    const addToCart = () => {
        setAdded(true);
        router.post(
            '/cart/add',
            {
                product_id: product.id,
                quantity: selectedQty,
            },
            {
                preserveScroll: true,
                onFinish: () => setTimeout(() => setAdded(false), 2000),
            },
        );
    };

    return (
        <StorefrontLayout>
            <SEO
                title={product.name}
                description={product.description?.replace(/<[^>]+>/g, '').slice(0, 160)}
                image={product.featured_image ?? undefined}
            />

            {/* breadcrumbs */}
            <nav
                aria-label="Breadcrumb"
                className="border-b border-neutral-100 bg-white"
            >
                <ol className="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 text-xs text-neutral-500">
                    <li>
                        <Link href="/" className="hover:text-neutral-900">
                            Home
                        </Link>
                    </li>
                    <ChevronRight className="size-3.5" />
                    <li>
                        <Link href="/shop" className="hover:text-neutral-900">
                            Shop
                        </Link>
                    </li>
                    <ChevronRight className="size-3.5" />
                    <li>
                        <Link
                            href={`/shop?cat=${product.category.slug}`}
                            className="hover:text-neutral-900"
                        >
                            {product.category.name}
                        </Link>
                    </li>
                    <ChevronRight className="size-3.5" />
                    <li className="text-neutral-900">{product.name}</li>
                </ol>
            </nav>

            {/* 1. configurator */}
            <section className="bg-white">
                <div className="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-10 lg:grid-cols-2 lg:py-14">
                    {/* gallery */}
                    <div>
                        <div className="overflow-hidden rounded-lg bg-neutral-100">
                            <img
                                src={activeImage}
                                alt={product.name}
                                className="aspect-[4/3] w-full object-cover"
                            />
                        </div>
                        <div className="mt-3 grid grid-cols-4 gap-2">
                            {galleryThumbs.map((src) => (
                                <button
                                    key={src}
                                    type="button"
                                    onClick={() => setActiveImage(src)}
                                    className={`overflow-hidden rounded-md border-2 transition-colors ${
                                        activeImage === src
                                            ? 'border-[#0f4c3a]'
                                            : 'border-transparent hover:border-neutral-200'
                                    }`}
                                >
                                    <img src={src} alt="" className="aspect-square w-full object-cover" />
                                </button>
                            ))}
                        </div>
                        <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-neutral-700">
                            <FeatureChip
                                icon={
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.6"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        className="size-4"
                                    >
                                        <rect x="3" y="6" width="13" height="9" rx="1" />
                                        <rect x="8" y="11" width="13" height="9" rx="1" />
                                    </svg>
                                }
                                label="Double-sided"
                            />
                            <FeatureChip
                                icon={
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.6"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        className="size-4"
                                    >
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 3v18M3 12h18" />
                                    </svg>
                                }
                                label="Full colour"
                            />
                        </div>
                    </div>

                    {/* options */}
                    <div>
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-500">
                            {product.category.name}
                        </p>
                        <h1 className="text-3xl font-bold leading-tight text-neutral-900 lg:text-4xl">
                            {product.name}
                        </h1>
                        <div className="mt-3 flex items-center gap-3">
                            <Stars value={4.6} />
                            <span className="text-sm text-neutral-500">4.6 · 12,431 reviews</span>
                        </div>
                        <p className="mt-4 text-sm leading-relaxed text-neutral-700">
                            Thicker than your average card. Printed on 350 gsm uncoated paper —
                            substantial in the hand without bulking up your wallet.
                        </p>

                        <OptionGroup label="Size">
                            <div className="grid grid-cols-2 gap-3">
                                {sizes.map((s) => (
                                    <ChoiceTile
                                        key={s.id}
                                        active={selectedSize === s.id}
                                        onClick={() => setSelectedSize(s.id)}
                                    >
                                        <div className="flex h-16 items-center justify-center">
                                            <span
                                                className={`block rounded-sm border-2 ${
                                                    selectedSize === s.id
                                                        ? 'border-[#0f4c3a] bg-[#0f4c3a]/5'
                                                        : 'border-neutral-300 bg-neutral-50'
                                                } ${s.shape === 'rect' ? 'h-8 w-14' : 'size-10'}`}
                                            />
                                        </div>
                                        <p className="mt-2 text-sm font-semibold">{s.label}</p>
                                        <p className="text-xs text-neutral-500">{s.dims}</p>
                                    </ChoiceTile>
                                ))}
                            </div>
                        </OptionGroup>

                        <OptionGroup label="Paper finish">
                            <div className="grid grid-cols-2 gap-3">
                                {finishes.map((f) => (
                                    <ChoiceTile
                                        key={f.id}
                                        active={selectedFinish === f.id}
                                        onClick={() => setSelectedFinish(f.id)}
                                    >
                                        <img
                                            src={f.thumb}
                                            alt=""
                                            className="aspect-[3/2] w-full rounded-sm object-cover"
                                        />
                                        <p className="mt-2 text-sm font-semibold">{f.label}</p>
                                    </ChoiceTile>
                                ))}
                            </div>
                        </OptionGroup>

                        <OptionGroup label="Corners">
                            <div className="grid grid-cols-2 gap-3">
                                {corners.map((c) => (
                                    <ChoiceTile
                                        key={c.id}
                                        active={selectedCorners === c.id}
                                        onClick={() => setSelectedCorners(c.id)}
                                    >
                                        <div className="flex h-16 items-center justify-center">
                                            <span
                                                className={`block h-8 w-14 border-2 ${
                                                    c.id === 'rounded' ? 'rounded-lg' : 'rounded-sm'
                                                } ${
                                                    selectedCorners === c.id
                                                        ? 'border-[#0f4c3a] bg-[#0f4c3a]/5'
                                                        : 'border-neutral-300 bg-neutral-50'
                                                }`}
                                            />
                                        </div>
                                        <p className="mt-2 text-sm font-semibold">{c.label}</p>
                                    </ChoiceTile>
                                ))}
                            </div>
                        </OptionGroup>

                        <OptionGroup label="Quantity">
                            <div className="overflow-hidden rounded-md border border-neutral-200">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-neutral-50 text-left text-xs uppercase tracking-wide text-neutral-500">
                                            <th className="px-4 py-2 font-medium">Quantity</th>
                                            <th className="px-4 py-2 font-medium">Was</th>
                                            <th className="px-4 py-2 font-medium">Now</th>
                                            <th className="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {quantityTiers.map((t) => {
                                            const recommended = t.qty === RECOMMENDED_QTY;
                                            const active = selectedQty === t.qty;
                                            const was = basePrice * t.multiplier;
                                            const now = was * (1 - t.save);
                                            return (
                                                <tr
                                                    key={t.qty}
                                                    onClick={() => setSelectedQty(t.qty)}
                                                    className={`cursor-pointer transition-colors ${
                                                        active
                                                            ? 'bg-[#0f4c3a]/5'
                                                            : recommended
                                                              ? 'bg-amber-50/60 hover:bg-amber-50'
                                                              : 'hover:bg-neutral-50'
                                                    }`}
                                                >
                                                    <td className="px-4 py-3">
                                                        <label className="flex items-center gap-3">
                                                            <input
                                                                type="radio"
                                                                name="qty"
                                                                checked={active}
                                                                onChange={() => setSelectedQty(t.qty)}
                                                                className="size-4 accent-[#0f4c3a]"
                                                            />
                                                            <span className="font-semibold text-neutral-900">
                                                                {t.qty}
                                                            </span>
                                                            {recommended && (
                                                                <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-800">
                                                                    Best value
                                                                </span>
                                                            )}
                                                        </label>
                                                    </td>
                                                    <td className="px-4 py-3 text-neutral-400">
                                                        {t.save > 0 ? (
                                                            <span className="line-through">${was.toFixed(2)}</span>
                                                        ) : (
                                                            <span>—</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 font-semibold text-neutral-900">
                                                        ${now.toFixed(2)}
                                                    </td>
                                                    <td className="px-4 py-3 text-right text-xs text-neutral-500">
                                                        {t.save > 0 && `Save ${Math.round(t.save * 100)}%`}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </OptionGroup>

                        {/* delivery callout */}
                        <div
                            className="mt-6 flex items-start gap-3 rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm"
                        >
                            <span style={{ color: ACCENT }}>
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.6"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="mt-0.5 size-5"
                                >
                                    <rect x="2" y="7" width="13" height="10" rx="1" />
                                    <path d="M15 10h4l3 3v4h-7" />
                                    <circle cx="6.5" cy="17.5" r="1.5" />
                                    <circle cx="17.5" cy="17.5" r="1.5" />
                                </svg>
                            </span>
                            <div>
                                <p className="font-semibold text-neutral-900">Get it by Tue, 16 Jun</p>
                                <p className="text-neutral-600">
                                    Order before 12pm Mon–Fri for next-day delivery on selected products.
                                </p>
                            </div>
                        </div>

                        {/* order summary */}
                        <div className="mt-6 rounded-md border border-neutral-200 bg-white px-4 py-4">
                            <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-neutral-500">
                                Your selection
                            </p>
                            <dl className="grid grid-cols-2 gap-y-1 text-sm">
                                <dt className="text-neutral-500">Paper</dt>
                                <dd className="text-right font-medium">{finishLabel}</dd>
                                <dt className="text-neutral-500">Size</dt>
                                <dd className="text-right font-medium">{sizeLabel}</dd>
                                <dt className="text-neutral-500">Quantity</dt>
                                <dd className="text-right font-medium">{selectedQty}</dd>
                                <dt className="text-neutral-500">Corners</dt>
                                <dd className="text-right font-medium capitalize">{selectedCorners}</dd>
                            </dl>
                            <div className="mt-4 flex items-baseline justify-between border-t border-neutral-100 pt-4">
                                <span className="text-sm text-neutral-500">Total</span>
                                <div className="text-right">
                                    {tier.save > 0 && (
                                        <span className="mr-2 text-sm text-neutral-400 line-through">
                                            ${fullPrice.toFixed(2)}
                                        </span>
                                    )}
                                    <span className="text-2xl font-bold text-neutral-900">
                                        ${finalPrice.toFixed(2)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* design CTA */}
                        <div className="mt-8">
                            <h2 className="mb-3 text-base font-bold text-neutral-900">
                                How would you like to design?
                            </h2>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <DesignChoice
                                    title="Use a template"
                                    body="Browse hundreds of designs and personalise yours in minutes."
                                    icon={
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" className="size-7">
                                            <rect x="3" y="3" width="7" height="9" rx="1" />
                                            <rect x="14" y="3" width="7" height="5" rx="1" />
                                            <rect x="14" y="12" width="7" height="9" rx="1" />
                                            <rect x="3" y="16" width="7" height="5" rx="1" />
                                        </svg>
                                    }
                                />
                                <DesignChoice
                                    title="Design online"
                                    body="Start from scratch in our easy drag-and-drop designer."
                                    icon={
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="size-7">
                                            <path d="M3 17l6-6 4 4 8-8" />
                                            <path d="M17 7h4v4" />
                                        </svg>
                                    }
                                />
                                <DesignChoice
                                    title="Upload a full design"
                                    body="Already have artwork? Upload your own PDF or PNG."
                                    icon={
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" className="size-7">
                                            <path d="M12 3v12" />
                                            <path d="M7 8l5-5 5 5" />
                                            <path d="M5 21h14" />
                                        </svg>
                                    }
                                />
                            </div>
                        </div>

                        <Button
                            onClick={addToCart}
                            disabled={added}
                            className="mt-6 h-12 w-full text-base font-semibold text-white"
                            style={{ backgroundColor: added ? '#0c3d2f' : ACCENT }}
                        >
                            {added ? 'Added to cart' : `Add to cart · $${finalPrice.toFixed(2)}`}
                        </Button>
                    </div>
                </div>
            </section>

            {/* 2. design guidelines */}
            <section className="bg-neutral-100">
                <div className="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-12 lg:grid-cols-2 lg:py-16">
                    <div>
                        <h2 className="text-2xl font-bold text-neutral-900">
                            Designing your card?
                        </h2>
                        <p className="mt-3 text-sm leading-relaxed text-neutral-700">
                            Set up your file with a 2 mm bleed, keep important text and logos
                            inside the safe area, and export at 300 dpi in CMYK. The pink stripe
                            shows where we trim — anything inside the dashed line will print.
                        </p>
                        <ul className="mt-6 space-y-2 text-sm text-neutral-700">
                            <li className="flex gap-2">
                                <Bullet /> 2 mm bleed on every edge
                            </li>
                            <li className="flex gap-2">
                                <Bullet /> 300 dpi minimum image resolution
                            </li>
                            <li className="flex gap-2">
                                <Bullet /> CMYK colour space (RGB will auto-convert)
                            </li>
                            <li className="flex gap-2">
                                <Bullet /> Embedded fonts or outlined text
                            </li>
                        </ul>
                    </div>
                    <div className="relative flex items-center justify-center">
                        {/* Bleed/safe area diagram */}
                        <div className="relative aspect-[5/3] w-full max-w-md rounded-md border-4 border-pink-300 bg-white p-4">
                            <div className="h-full w-full rounded-sm border-2 border-dashed border-neutral-300 p-3">
                                <div className="flex h-full w-full items-center justify-center rounded-sm bg-neutral-50">
                                    <span className="text-xs uppercase tracking-wider text-neutral-400">
                                        Safe area
                                    </span>
                                </div>
                            </div>
                            <span className="absolute -top-3 left-3 bg-neutral-100 px-2 text-[10px] font-semibold uppercase tracking-wider text-pink-500">
                                Bleed area
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            {/* 3. lifestyle banner */}
            <section
                className="relative h-[320px] bg-cover bg-center text-white sm:h-[420px]"
                style={{
                    backgroundImage:
                        'linear-gradient(to right, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0.1) 60%), url(https://images.unsplash.com/photo-1499951360447-b19be8fe80f5?auto=format&fit=crop&w=1600&q=70)',
                }}
            >
                <div className="mx-auto flex h-full max-w-7xl items-center px-4">
                    <div className="max-w-md">
                        <p className="text-xs font-semibold uppercase tracking-[0.25em]">
                            Make a first impression
                        </p>
                        <h2 className="mt-3 text-3xl font-bold leading-tight sm:text-4xl">
                            Premium cards for the moments that matter.
                        </h2>
                        <p className="mt-3 text-sm leading-relaxed opacity-90">
                            From boardroom intros to coffee-shop hand-offs, our Original Business
                            Cards land right every time.
                        </p>
                    </div>
                </div>
            </section>

            {/* 4. paper stocks */}
            <section className="bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <header className="mb-8 max-w-2xl">
                        <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                            Discover our other paper stocks
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            Every paper has its own personality. Order a free sample pack to feel
                            them all.
                        </p>
                    </header>
                    <ul className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {paperStocks.map((stock) => (
                            <li key={stock.id}>
                                <Link
                                    href={`/paper-stocks/${stock.id}`}
                                    className="group block"
                                >
                                    <div className="overflow-hidden rounded-md bg-neutral-100">
                                        <img
                                            src={stock.image}
                                            alt={stock.name}
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    </div>
                                    <h3 className="mt-3 text-base font-bold text-neutral-900">
                                        {stock.name}
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-600">{stock.blurb}</p>
                                    <span
                                        className="mt-2 inline-flex items-center gap-1 text-sm font-semibold group-hover:underline"
                                        style={{ color: ACCENT }}
                                    >
                                        Learn more <ChevronRight className="size-3.5" />
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* 5. printfinity banner */}
            <section
                className="relative bg-cover bg-center"
                style={{
                    backgroundImage:
                        'linear-gradient(rgba(15,76,58,0.55), rgba(15,76,58,0.55)), url(https://images.unsplash.com/photo-1611162616475-46b635cb6868?auto=format&fit=crop&w=1600&q=70)',
                }}
            >
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <div className="max-w-md rounded-lg bg-white p-6 shadow-md sm:p-8">
                        <p
                            className="text-xs font-semibold uppercase tracking-[0.25em]"
                            style={{ color: ACCENT }}
                        >
                            Printfinity
                        </p>
                        <h2 className="mt-3 text-2xl font-bold text-neutral-900 sm:text-3xl">
                            A different design on every card. For free.
                        </h2>
                        <p className="mt-3 text-sm leading-relaxed text-neutral-700">
                            Showcase your full portfolio, your team, or every product you make —
                            print a unique design on the back of every card in the pack at no
                            extra cost.
                        </p>
                        <Link
                            href="/printfinity"
                            className="mt-4 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                            style={{ color: ACCENT }}
                        >
                            Discover Printfinity <ChevronRight className="size-3.5" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* 6. team & business solutions */}
            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <header className="mb-8 max-w-2xl">
                        <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                            Built for teams and businesses
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            Whether you’re kitting out two staff or two thousand, we’ve got the
                            tools to make it painless.
                        </p>
                    </header>
                    <ul className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        {businessBlocks.map((block) => (
                            <li
                                key={block.title}
                                className="overflow-hidden rounded-lg bg-white shadow-sm"
                            >
                                <img
                                    src={block.image}
                                    alt=""
                                    loading="lazy"
                                    className="aspect-[5/3] w-full object-cover"
                                />
                                <div className="p-6">
                                    <h3 className="text-lg font-bold text-neutral-900">
                                        {block.title}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                        {block.body}
                                    </p>
                                    <Link
                                        href={block.href}
                                        className="mt-3 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                                        style={{ color: ACCENT }}
                                    >
                                        {block.cta} <ChevronRight className="size-3.5" />
                                    </Link>
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* 7. cross-sell */}
            <section className="bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <header className="mb-8 max-w-2xl">
                        <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                            Even more good stuff
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            Round out your kit with these customer favourites.
                        </p>
                    </header>
                    {related.length > 0 ? (
                        <ul className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {related.slice(0, 3).map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={`/shop/${item.slug}`}
                                        className="group block overflow-hidden rounded-md"
                                    >
                                        <div className="overflow-hidden bg-neutral-100">
                                            {item.featured_image ? (
                                                <img
                                                    src={item.featured_image}
                                                    alt={item.name}
                                                    className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex aspect-[4/3] items-center justify-center text-neutral-400">
                                                    <svg className="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>
                                        <div className="px-1 py-3">
                                            <h3 className="text-base font-bold text-neutral-900">
                                                {item.name}
                                            </h3>
                                            <span
                                                className="mt-1 inline-flex items-center gap-1 text-sm font-semibold group-hover:underline"
                                                style={{ color: ACCENT }}
                                            >
                                                Shop {item.name} <ChevronRight className="size-3.5" />
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-neutral-500">
                            More products coming soon.
                        </p>
                    )}
                </div>
            </section>

            {/* 8. FAQs */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <h2 className="mb-8 text-2xl font-bold text-neutral-900 sm:text-3xl">
                        FAQs — {product.name}
                    </h2>
                    <ul className="grid grid-cols-1 gap-x-10 gap-y-8 md:grid-cols-2 lg:grid-cols-3">
                        {faqs.map((faq) => (
                            <li key={faq.q}>
                                <h3 className="text-sm font-bold text-neutral-900">{faq.q}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                    {faq.a}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>
        </StorefrontLayout>
    );
}

/* -------------------------------------------------------------------------- */
/* Sub-components                                                             */
/* -------------------------------------------------------------------------- */

function FeatureChip({ icon, label }: { icon: React.ReactNode; label: string }) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span style={{ color: ACCENT }}>{icon}</span>
            <span className="text-xs font-semibold uppercase tracking-wide">{label}</span>
        </span>
    );
}

function Stars({ value }: { value: number }) {
    const full = Math.floor(value);
    return (
        <div className="flex items-center gap-0.5" aria-label={`${value} out of 5`}>
            {Array.from({ length: 5 }).map((_, i) => (
                <Star
                    key={i}
                    className="size-4"
                    fill={i < full ? ACCENT : 'transparent'}
                    stroke={i < full ? ACCENT : '#cbd5d3'}
                    strokeWidth={1.5}
                />
            ))}
        </div>
    );
}

function OptionGroup({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <fieldset className="mt-6">
            <legend className="mb-3 text-sm font-bold text-neutral-900">{label}</legend>
            {children}
        </fieldset>
    );
}

function ChoiceTile({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-md border-2 p-3 text-left transition-colors ${
                active
                    ? 'border-[#0f4c3a] bg-[#0f4c3a]/5'
                    : 'border-neutral-200 hover:border-neutral-300'
            }`}
        >
            {children}
        </button>
    );
}

function DesignChoice({
    title,
    body,
    icon,
}: {
    title: string;
    body: string;
    icon: React.ReactNode;
}) {
    return (
        <button
            type="button"
            className="flex h-full flex-col items-start gap-2 rounded-md border-2 border-neutral-200 bg-white p-4 text-left transition-colors hover:border-[#0f4c3a] hover:bg-[#0f4c3a]/5"
        >
            <span style={{ color: ACCENT }}>{icon}</span>
            <p className="text-sm font-bold text-neutral-900">{title}</p>
            <p className="text-xs leading-relaxed text-neutral-600">{body}</p>
        </button>
    );
}

function Bullet() {
    return (
        <span
            aria-hidden
            className="mt-1.5 inline-block size-1.5 shrink-0 rounded-full"
            style={{ backgroundColor: ACCENT }}
        />
    );
}
