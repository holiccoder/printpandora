import { Link } from '@inertiajs/react';

/**
 * Four-up value-prop strip shown on the home page beneath the hero
 * carousel. Each tile is a teal line-art icon, a bold headline, and a
 * short blurb. Mirrors the MOO marketing strip: Next Day Delivery,
 * The MOO Promise, Business Perks, Printfinity.
 */

const ACCENT = '#0f4c3a';

type Perk = {
    title: string;
    description: string;
    href: string;
    icon: () => React.ReactElement;
};

const perks: Perk[] = [
    {
        title: 'Next day delivery!',
        description: 'Available on selected products. Order before 12pm Mon-Fri.*',
        href: '/next-day-delivery',
        icon: ShippingBoxIcon,
    },
    {
        title: 'The MOO Promise',
        description: 'We move heaven and earth so you’re happy with your order!',
        href: '/promise',
        icon: PromiseSealIcon,
    },
    {
        title: 'More perks for your business',
        description: 'Get more for your print with MOO Business Services',
        href: '/shop?cat=business-services',
        icon: BusinessShapesIcon,
    },
    {
        title: 'Printfinity',
        description:
            'Enjoy a different design on the back of every card – or every Sticker – for free!',
        href: '/printfinity',
        icon: PrintfinityCardsIcon,
    },
];

export function HomePerks() {
    return (
        <section
            aria-label="Why shop with PrintPandora"
            className="border-y border-neutral-100 bg-white"
        >
            <div className="mx-auto max-w-7xl px-4 py-10 lg:py-14">
                <ul className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {perks.map((perk) => (
                        <li key={perk.title}>
                            <Link
                                href={perk.href}
                                className="group flex flex-col items-center text-center"
                            >
                                <span className="mb-4 inline-flex size-16 items-center justify-center text-[#0f4c3a]">
                                    <perk.icon />
                                </span>
                                <h3 className="mb-2 text-base font-bold text-neutral-900 group-hover:text-[#0f4c3a]">
                                    {perk.title}
                                </h3>
                                <p className="max-w-xs text-sm leading-snug text-neutral-600">
                                    {perk.description}
                                </p>
                            </Link>
                        </li>
                    ))}
                </ul>

                {/* Trustpilot strip — peeks out beneath the perks the same way the
                    reference shows it cut off at the very bottom. Linked to the
                    public Trustpilot profile placeholder; swap when we know the real one. */}
                <div className="mt-10 flex items-center justify-center">
                    <a
                        href="https://www.trustpilot.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 text-sm font-semibold"
                        style={{ color: ACCENT }}
                    >
                        <TrustpilotStar />
                        <span>Trustpilot</span>
                    </a>
                </div>
            </div>
        </section>
    );
}

/* --------------------------------- icons --------------------------------- */
/* Teal line-art, drawn at 64×64 with currentColor stroke so a parent text
   colour drives them. Stroke width is 1.5 to match the airy MOO style. */

const STROKE: React.SVGProps<SVGSVGElement> = {
    xmlns: 'http://www.w3.org/2000/svg',
    viewBox: '0 0 64 64',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.5,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': true,
    className: 'size-full',
};

function ShippingBoxIcon() {
    return (
        <svg {...STROKE}>
            {/* tilted box (parallelogram-ish) with speed lines behind */}
            <path d="M6 22h6M4 30h7M6 38h6" />
            <path d="M22 18l24-4 8 8-4 26-24 4-8-8 4-26z" />
            <path d="M22 18l8 8 24-4" />
            <path d="M30 26l-4 26" />
            {/* tape line */}
            <path d="M34 16l8 8" strokeDasharray="2 3" />
        </svg>
    );
}

function PromiseSealIcon() {
    return (
        <svg {...STROKE}>
            {/* 12-point starburst seal */}
            <path d="M32 6l3.5 4 5-2 1 5 5 1-2 5 4 3.5-4 3.5 2 5-5 1-1 5-5-2-3.5 4-3.5-4-5 2-1-5-5-1 2-5-4-3.5 4-3.5-2-5 5-1 1-5 5 2 3.5-4z" />
            {/* heart inside */}
            <path d="M32 40s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 39 30c0 5.5-7 10-7 10z" />
        </svg>
    );
}

function BusinessShapesIcon() {
    return (
        <svg {...STROKE}>
            {/* striped rectangle */}
            <rect x="6" y="10" width="20" height="14" rx="2" />
            <path
                d="M6 14h20M6 18h20M6 22h20"
                strokeDasharray="2 3"
            />
            {/* dotted circle */}
            <circle cx="46" cy="18" r="9" />
            <circle cx="42" cy="16" r="0.8" fill="currentColor" />
            <circle cx="50" cy="16" r="0.8" fill="currentColor" />
            <circle cx="46" cy="20" r="0.8" fill="currentColor" />
            <circle cx="42" cy="22" r="0.8" fill="currentColor" />
            <circle cx="50" cy="22" r="0.8" fill="currentColor" />
            {/* plain square */}
            <rect x="8" y="34" width="14" height="14" rx="1.5" />
            {/* striped tall rectangle */}
            <rect x="28" y="34" width="10" height="20" rx="1.5" />
            <path d="M28 39h10M28 44h10M28 49h10" />
            {/* small circle bottom-right */}
            <circle cx="50" cy="44" r="7" />
        </svg>
    );
}

function PrintfinityCardsIcon() {
    return (
        <svg {...STROKE}>
            {/* three fanned cards, back to front */}
            <g transform="translate(8 14) rotate(-12 16 12)">
                <rect x="0" y="0" width="32" height="22" rx="2" />
                <path d="M2 4l28 14M2 10l28 14M2 16l24 12" />
            </g>
            <g transform="translate(14 18) rotate(-2 16 12)">
                <rect x="0" y="0" width="32" height="22" rx="2" />
            </g>
            <g transform="translate(20 22) rotate(8 16 12)">
                <rect x="0" y="0" width="32" height="22" rx="2" />
                <circle cx="6" cy="6" r="0.9" fill="currentColor" />
                <circle cx="12" cy="6" r="0.9" fill="currentColor" />
                <circle cx="18" cy="6" r="0.9" fill="currentColor" />
                <circle cx="24" cy="6" r="0.9" fill="currentColor" />
                <circle cx="6" cy="12" r="0.9" fill="currentColor" />
                <circle cx="12" cy="12" r="0.9" fill="currentColor" />
                <circle cx="18" cy="12" r="0.9" fill="currentColor" />
                <circle cx="24" cy="12" r="0.9" fill="currentColor" />
                <circle cx="6" cy="18" r="0.9" fill="currentColor" />
                <circle cx="12" cy="18" r="0.9" fill="currentColor" />
                <circle cx="18" cy="18" r="0.9" fill="currentColor" />
                <circle cx="24" cy="18" r="0.9" fill="currentColor" />
            </g>
        </svg>
    );
}

function TrustpilotStar() {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className="size-4"
            fill="currentColor"
            aria-hidden
        >
            <path d="M12 2l2.9 6.9L22 10l-5.5 4.8L18.1 22 12 18.3 5.9 22l1.6-7.2L2 10l7.1-1.1L12 2z" />
        </svg>
    );
}

export default HomePerks;
