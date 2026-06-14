import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

/**
 * "Popular products" section — eight category cards in a 4×2 grid on
 * desktop with a centered headline above and a "Shop all" CTA below.
 * Seven cards use lifestyle photography; the eighth ("Finish your
 * project") is a green line-art icon block.
 */

const ACCENT = '#0f4c3a';

type ProductCard = {
    title: string;
    /** External Unsplash photo URL — swap with real product imagery later. */
    image?: string;
    imageAlt?: string;
    linkLabel: string;
    href: string;
    /** Render an SVG instead of a photo (used by the last card). */
    illustration?: () => React.ReactElement;
};

const cards: ProductCard[] = [
    {
        title: 'Business Cards',
        image: 'https://images.unsplash.com/photo-1606857521015-7f9fcf423740?auto=format&fit=crop&w=800&q=70',
        imageAlt: 'A hand tucking a peach-coloured business card into a back pocket',
        linkLabel: 'Shop Business Cards',
        href: '/shop?cat=business-cards',
    },
    {
        title: 'Postcards',
        image: 'https://images.unsplash.com/photo-1517242810446-cc8951b2be40?auto=format&fit=crop&w=800&q=70',
        imageAlt: 'A person in a green jacket holds a pink postcard up against a blue sky',
        linkLabel: 'Shop Postcards',
        href: '/shop?cat=postcards',
    },
    {
        title: 'Stickers & Labels',
        image: 'https://images.unsplash.com/photo-1611162616475-46b635cb6868?auto=format&fit=crop&w=800&q=70',
        imageAlt: 'A “Hello my name is” sticker on a denim jacket',
        linkLabel: 'Shop Stickers & Labels',
        href: '/shop?cat=stickers-labels',
    },
    {
        title: 'Flyers',
        image: 'https://images.unsplash.com/photo-1542435503-956c469947f6?auto=format&fit=crop&w=800&q=70',
        imageAlt: 'Person holding a tall stack of printed flyers on their lap',
        linkLabel: 'Shop Flyers',
        href: '/shop?cat=flyers',
    },
    {
        title: 'Invitations',
        image: 'https://images.unsplash.com/photo-1607344645866-009c320b63e0?auto=format&fit=crop&w=800&q=70',
        imageAlt: 'Overhead view of invitation cards scattered with a bowl of cereal',
        linkLabel: 'Shop Invitations',
        href: '/shop?cat=invitations',
    },
    {
        title: 'Greeting Cards',
        image: 'https://images.unsplash.com/photo-1512909006721-3d6018887383?auto=format&fit=crop&w=800&q=70',
        imageAlt: 'A hand-drawn floral “Thanks for being you” greeting card',
        linkLabel: 'Shop Greeting Cards',
        href: '/shop/greeting-cards',
    },
    {
        title: 'Luxe Notecards',
        image: 'https://images.unsplash.com/photo-1583912267550-d6c2ac3196c0?auto=format&fit=crop&w=800&q=70',
        imageAlt: 'Hands sliding a Luxe notecard out of a white envelope on a beige coat',
        linkLabel: 'Shop Luxe Notecards',
        href: '/shop/luxe-notecards',
    },
    {
        title: 'Finish your project',
        linkLabel: 'Finish your project',
        href: '/shop',
        illustration: FinishProjectIllustration,
    },
];

export function PopularProducts() {
    return (
        <section
            aria-labelledby="popular-products-heading"
            className="bg-neutral-100"
        >
            <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                <header className="mx-auto mb-10 max-w-2xl text-center">
                    <h2
                        id="popular-products-heading"
                        className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl"
                    >
                        Popular products
                    </h2>
                    <p className="mt-3 text-sm text-neutral-600 sm:text-base">
                        Call it the MOO Hall of Fame. Our most tried and trusted favourites.
                    </p>
                </header>

                <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <li key={card.title}>
                            <Link
                                href={card.href}
                                className="group flex h-full flex-col overflow-hidden rounded-md bg-white shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div className="relative aspect-[4/3] overflow-hidden bg-neutral-100">
                                    {card.image ? (
                                        <img
                                            src={card.image}
                                            alt={card.imageAlt ?? ''}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                                        />
                                    ) : card.illustration ? (
                                        <div
                                            className="flex h-full w-full items-center justify-center"
                                            style={{ color: ACCENT }}
                                        >
                                            <card.illustration />
                                        </div>
                                    ) : null}
                                </div>
                                <div className="flex flex-1 items-center justify-between gap-2 px-4 py-4">
                                    <span
                                        className="text-sm font-semibold group-hover:underline"
                                        style={{ color: ACCENT }}
                                    >
                                        {card.linkLabel}
                                    </span>
                                    <ChevronRight
                                        className="size-4 transition-transform group-hover:translate-x-0.5"
                                        style={{ color: ACCENT }}
                                    />
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>

                <div className="mt-10 flex justify-center">
                    <Link
                        href="/shop"
                        className="inline-flex items-center justify-center rounded-md px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#0c3d2f]"
                        style={{ backgroundColor: ACCENT }}
                    >
                        Shop All MOO Products
                    </Link>
                </div>
            </div>
        </section>
    );
}

/**
 * Green line-art illustration for the "Finish your project" tile — a
 * large document with two smaller cards stacked over it; each shape has
 * a teardrop accent in the centre.
 */
function FinishProjectIllustration() {
    const stroke: React.SVGProps<SVGSVGElement> = {
        xmlns: 'http://www.w3.org/2000/svg',
        viewBox: '0 0 160 120',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 2,
        strokeLinecap: 'round',
        strokeLinejoin: 'round',
        'aria-hidden': true,
        className: 'h-3/5 w-3/5',
    };
    return (
        <svg {...stroke}>
            {/* Back document */}
            <rect x="20" y="14" width="80" height="100" rx="3" />
            <Teardrop cx={60} cy={64} />
            {/* Middle card */}
            <rect x="60" y="34" width="60" height="44" rx="3" fill="white" />
            <Teardrop cx={90} cy={56} />
            {/* Front card */}
            <rect x="80" y="62" width="60" height="44" rx="3" fill="white" />
            <Teardrop cx={110} cy={84} />
        </svg>
    );
}

function Teardrop({ cx, cy }: { cx: number; cy: number }) {
    // small teardrop pointing up — outline only, drawn at the requested centre
    return (
        <path
            d={`M${cx} ${cy - 7} C ${cx + 5} ${cy - 2}, ${cx + 5} ${cy + 4}, ${cx} ${cy + 6} C ${cx - 5} ${cy + 4}, ${cx - 5} ${cy - 2}, ${cx} ${cy - 7} Z`}
        />
    );
}

export default PopularProducts;
