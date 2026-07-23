import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useContent } from '@/hooks/use-content';

/**
 * "Popular products" section — eight category cards in a 4×2 grid on
 * desktop with a centered headline above and a "Shop all" CTA below.
 * Seven cards use lifestyle photography; the eighth ("Finish your
 * project") is a green line-art icon block.
 *
 * All labels, hrefs, and image URLs come from
 * `content/hardcoded-content.json` → `home_page.popular_products`.
 * Products with null `image_url` get the SVG illustration treatment.
 */

const ACCENT = '#1e3a5f';

/**
 * Finish your project illustration — a large document with two smaller
 * cards stacked over it; each shape has a teardrop accent.
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
            <rect x="20" y="14" width="80" height="100" rx="3" />
            <Teardrop cx={60} cy={64} />
            <rect x="60" y="34" width="60" height="44" rx="3" fill="white" />
            <Teardrop cx={90} cy={56} />
            <rect x="80" y="62" width="60" height="44" rx="3" fill="white" />
            <Teardrop cx={110} cy={84} />
        </svg>
    );
}

function Teardrop({ cx, cy }: { cx: number; cy: number }) {
    return (
        <path
            d={`M${cx} ${cy - 7} C ${cx + 5} ${cy - 2}, ${cx + 5} ${cy + 4}, ${cx} ${cy + 6} C ${cx - 5} ${cy + 4}, ${cx - 5} ${cy - 2}, ${cx} ${cy - 7} Z`}
        />
    );
}

export function PopularProducts() {
    const home = useContent('home_page');
    const pp = home.popular_products;

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
                        {pp.section_title}
                    </h2>
                    <p className="mt-3 text-sm text-neutral-600 sm:text-base">
                        {pp.section_description}
                    </p>
                </header>

                <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {pp.products.map((card) => (
                        <li key={card.name}>
                            <Link
                                href={card.href}
                                className="group flex h-full flex-col overflow-hidden rounded-md bg-white shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div className="relative aspect-[4/3] overflow-hidden bg-neutral-100">
                                    {card.image_url ? (
                                        <img
                                            src={card.image_url}
                                            alt={card.alt ?? ''}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                                        />
                                    ) : (
                                        <div
                                            className="flex h-full w-full items-center justify-center"
                                            style={{ color: ACCENT }}
                                        >
                                            <FinishProjectIllustration />
                                        </div>
                                    )}
                                </div>
                                <div className="flex flex-1 items-center justify-between gap-2 px-4 py-4">
                                    <div className="flex flex-col gap-0.5">
                                        <span
                                            className="text-sm font-semibold group-hover:underline"
                                            style={{ color: ACCENT }}
                                        >
                                            {card.name}
                                        </span>
                                        <span className="text-xs text-neutral-500">
                                            {card.cta}
                                        </span>
                                    </div>
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
                        href={pp.footer_cta_href}
                        className="inline-flex items-center justify-center rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        {pp.footer_cta_text}
                    </Link>
                </div>
            </div>
        </section>
    );
}

export default PopularProducts;
