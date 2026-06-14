import { Link } from '@inertiajs/react';

/**
 * Storefront footer — clean white background, dark column headers, and
 * teal/green link text. Mirrors the MOO footer reference: Products (split
 * into two link lists), Paper Stocks, About us, Help/Useful links, and a
 * legal/utility bar at the bottom.
 */

type FooterLink = {
    label: string;
    href: string;
};

const productsLeft: FooterLink[] = [
    { label: 'All Products', href: '/shop' },
    { label: 'Business Cards', href: '/shop?cat=business-cards' },
    { label: 'Square Business Cards', href: '/shop/square-business-cards' },
    { label: 'MiniCards', href: '/shop/minicards' },
    { label: 'Letterpress Business Cards', href: '/shop/letterpress-business-cards' },
    { label: 'Postcards', href: '/shop?cat=postcards' },
    { label: 'Stickers and Labels', href: '/shop?cat=stickers-labels' },
    { label: 'Flyers', href: '/shop?cat=flyers' },
];

const productsRight: FooterLink[] = [
    { label: 'Letterhead', href: '/shop/letterhead' },
    { label: 'Greeting Cards', href: '/shop/greeting-cards' },
    { label: 'Luxe Notecards', href: '/shop/luxe-notecards' },
    { label: 'Envelopes', href: '/shop/envelopes' },
    { label: 'Display Boxes', href: '/shop/display-boxes' },
    { label: 'Business Card Holders', href: '/shop/business-card-holders' },
    { label: 'Notebooks & Journals', href: '/shop/notebooks-journals' },
    { label: 'Planners', href: '/shop/planners' },
];

const paperStocks: FooterLink[] = [
    { label: 'Paper Stocks', href: '/paper-stocks' },
    { label: 'MOO Luxe', href: '/paper-stocks/luxe' },
    { label: 'MOO Super', href: '/paper-stocks/super' },
    { label: 'MOO Cotton', href: '/paper-stocks/cotton' },
    { label: 'MOO Original', href: '/paper-stocks/original' },
    { label: 'MOO Letterpress', href: '/paper-stocks/letterpress' },
    { label: 'Sample Packs', href: '/paper-stocks/sample-packs' },
    { label: 'FSC® Certified Paper', href: '/paper-stocks/fsc-certified' },
    { label: 'Luxe Collection', href: '/paper-stocks/luxe-collection' },
];

const aboutUs: FooterLink[] = [
    { label: 'About PrintPandora', href: '/about' },
    { label: 'Media resources', href: '/about/media' },
    { label: 'People, products and the planet', href: '/about/sustainability' },
    { label: 'Who we are', href: '/about' },
    { label: 'Careers', href: '/careers' },
    { label: 'The Drop', href: '/the-drop' },
    { label: 'Business Services', href: '/shop?cat=business-services' },
    { label: 'Reseller', href: '/reseller' },
    { label: 'Printfinity', href: '/printfinity' },
    { label: 'The PrintPandora Promise', href: '/promise' },
    { label: 'Packaging', href: '/packaging' },
    { label: 'Partner with PrintPandora', href: '/partner' },
];

const helpLinks: FooterLink[] = [
    { label: 'Contact us', href: '/contact' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'Next Day Delivery', href: '/next-day-delivery' },
    { label: 'FAQs', href: '/help' },
    { label: 'Artwork guidelines', href: '/help/artwork-guidelines' },
    { label: 'Affiliates', href: '/affiliates' },
    { label: 'Refer and Earn', href: '/refer' },
    { label: 'Vulnerability Disclosure', href: '/vulnerability-disclosure' },
];

const legalLinks: FooterLink[] = [
    { label: 'Terms & Conditions', href: '/terms' },
    { label: 'Privacy Policy', href: '/privacy' },
    { label: 'Fonts', href: '/fonts' },
    { label: 'Sitemap', href: '/sitemap' },
    { label: 'Company information', href: '/company-information' },
];

const TEAL = 'text-[#0f4c3a] hover:underline';

function ColumnHeading({ children }: { children: React.ReactNode }) {
    return (
        <h3 className="mb-4 text-sm font-bold text-neutral-900">{children}</h3>
    );
}

function LinkList({ links }: { links: FooterLink[] }) {
    return (
        <ul className="space-y-2">
            {links.map((link) => (
                <li key={link.label}>
                    <Link href={link.href} className={`text-sm ${TEAL}`}>
                        {link.label}
                    </Link>
                </li>
            ))}
        </ul>
    );
}

export function StorefrontFooter() {
    return (
        <footer className="border-t border-neutral-100 bg-white">
            <div className="mx-auto max-w-7xl px-4 py-12 lg:px-6 lg:py-16">
                {/* Main columns */}
                <div className="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-5">
                    {/* Products — spans two of the five columns and renders two side-by-side lists */}
                    <div className="lg:col-span-2">
                        <ColumnHeading>Products</ColumnHeading>
                        <div className="grid grid-cols-1 gap-8 sm:grid-cols-2">
                            <LinkList links={productsLeft} />
                            <LinkList links={productsRight} />
                        </div>
                    </div>

                    <div>
                        <ColumnHeading>Paper Stocks</ColumnHeading>
                        <LinkList links={paperStocks} />
                    </div>

                    <div>
                        <ColumnHeading>About us</ColumnHeading>
                        <LinkList links={aboutUs} />
                    </div>

                    <div>
                        <ColumnHeading>Help/Useful links</ColumnHeading>
                        <LinkList links={helpLinks} />
                    </div>
                </div>

                {/* Faint divider before the legal bar */}
                <div className="mt-12 border-t border-neutral-200" aria-hidden />

                {/* Legal/utility bar */}
                <div className="flex flex-col gap-4 pt-6 text-xs md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-2 text-neutral-600">
                        <TeardropIcon />
                        <span>
                            © {new Date().getFullYear()} PrintPandora — print-on-demand for the
                            small businesses, designers, and creators we love.
                        </span>
                    </div>
                    <ul className="flex flex-wrap items-center gap-x-5 gap-y-2">
                        {legalLinks.map((link) => (
                            <li key={link.label}>
                                <Link href={link.href} className={TEAL}>
                                    {link.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </footer>
    );
}

function TeardropIcon() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 24 24"
            className="size-4 shrink-0 text-[#0f4c3a]"
            fill="currentColor"
        >
            <path d="M12 2C12 2 5 10 5 15a7 7 0 0 0 14 0c0-5-7-13-7-13z" />
        </svg>
    );
}

export default StorefrontFooter;
