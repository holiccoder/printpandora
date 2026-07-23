import { Link } from '@inertiajs/react';
import { useContent } from '@/hooks/use-content';

/**
 * Value-prop strip shown on the home page beneath the hero carousel.
 * Each tile is a navy line-art icon, a bold headline, and a short blurb.
 * Icons stay hardcoded; title/description per perk come from JSON.
 * When a perk has no href, it renders as a plain <div> (non-clickable).
 */

const ACCENT = '#1e3a5f';

const ICONS: Array<() => React.ReactElement> = [
    ProductVarietyIcon,
    CraftsmanshipVarietyIcon,
    MaterialVarietyIcon,
    GreatValueIcon,
    DesignServiceIcon,
    GlobalPartnerIcon,
];

export function HomePerks() {
    const home = useContent('home_page');
    const perks = home.perks;

    return (
        <section
            aria-label={perks.section_aria_label}
            className="border-y border-neutral-100 bg-white"
        >
            <div className="mx-auto max-w-7xl px-4 py-10 lg:py-14">
                <ul className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    {perks.items.map((perk, i) => {
                        const Icon = ICONS[i] ?? ProductVarietyIcon;
                        const C = perk.href ? Link : 'div';
                        const linkProps = perk.href
                            ? {
                                  href: perk.href,
                                  className:
                                      'group flex flex-col items-center text-center',
                              }
                            : {
                                  className:
                                      'flex flex-col items-center text-center',
                              };

                        return (
                            <li key={perk.title}>
                                <C {...linkProps}>
                                    <span className="mb-4 inline-flex size-16 items-center justify-center text-[#1e3a5f]">
                                        <Icon />
                                    </span>
                                    <h3 className="mb-2 text-base font-bold text-neutral-900 group-hover:text-[#1e3a5f]">
                                        {perk.title}
                                    </h3>
                                    <p className="max-w-xs text-sm leading-snug text-neutral-600">
                                        {perk.description}
                                    </p>
                                </C>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </section>
    );
}

/* --------------------------------- icons --------------------------------- */
/* Navy line-art, drawn at 64×64 with currentColor stroke so a parent text
   colour drives them. Stroke width is 1.5, round cap/join. */

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

function ProductVarietyIcon() {
    return (
        <svg {...STROKE}>
            <path d="M8 42h48" />
            <rect x="14" y="22" width="12" height="20" rx="2" />
            <rect x="30" y="28" width="10" height="14" rx="1.5" />
            <circle cx="54" cy="34" r="7" />
        </svg>
    );
}

function CraftsmanshipVarietyIcon() {
    return (
        <svg {...STROKE}>
            <path d="M32 10l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" />
            <path d="M20 38h24" />
            <rect x="18" y="38" width="28" height="16" rx="2" />
            <path d="M28 38v16M36 38v16" />
        </svg>
    );
}

function MaterialVarietyIcon() {
    return (
        <svg {...STROKE}>
            <rect x="14" y="12" width="38" height="12" rx="1.5" />
            <rect x="12" y="22" width="42" height="12" rx="1.5" />
            <rect x="10" y="32" width="46" height="12" rx="1.5" />
            <rect x="8" y="42" width="50" height="12" rx="1.5" />
            <path d="M52 12l2 2v40H8V12" />
        </svg>
    );
}

function GreatValueIcon() {
    return (
        <svg {...STROKE}>
            <path d="M16 12l8-4 32 32-8 8-32-32z" />
            <path d="M16 12l4 4" />
            <path d="M28 24l12 12" />
            <circle cx="24" cy="28" r="3" fill="currentColor" />
        </svg>
    );
}

function DesignServiceIcon() {
    return (
        <svg {...STROKE}>
            <path d="M44 14c7 0 12 5 12 12s-5 12-12 12h-2l-8 6 2-8c-5-2-8-6-8-10 0-7 5-12 12-12z" />
            <path d="M8 52l24-24 8 8-24 24z" />
            <path d="M32 28l8 8" />
            <path d="M16 44l4 4" />
        </svg>
    );
}

function GlobalPartnerIcon() {
    return (
        <svg {...STROKE}>
            <circle cx="32" cy="32" r="22" />
            <ellipse cx="32" cy="32" rx="13" ry="22" />
            <path d="M10 32h44" />
            <path d="M32 10c-6 8-6 24 0 32" />
            <path d="M32 10c6 8 6 24 0 32" />
        </svg>
    );
}

export default HomePerks;
