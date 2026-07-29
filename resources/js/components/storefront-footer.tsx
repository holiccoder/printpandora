import { Link } from '@inertiajs/react';
import { useContent } from '@/hooks/use-content';
import type { FooterSocialLink, NavLink } from '@/types/content';

/**
 * Storefront footer — clean white background, dark column headers, and
 * burgundy link text. Mirrors the InkPavo footer reference: Products (split
 * into two link lists), Paper Stocks, About us, Help/Useful links, social
 * icons, and a legal/utility bar at the bottom.
 *
 * All link lists, column headings, social links, and the copyright/legal
 * links come from `content/hardcoded-content.json` → `global_chrome.footer`.
 */

const TEAL = 'text-[#800020] hover:underline';

function ColumnHeading({ children }: { children: React.ReactNode }) {
    return (
        <h3 className="mb-4 text-sm font-bold text-neutral-900">{children}</h3>
    );
}

function LinkList({ links }: { links: NavLink[] }) {
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

/* Brand SVGs (lucide has no brand icons). Paths are simplified official marks. */
function SocialIcon({ network }: { network: string }) {
    const common = {
        viewBox: '0 0 24 24',
        className: 'size-5 shrink-0',
        fill: 'currentColor',
        'aria-hidden': true as const,
        focusable: false as const,
    };

    switch (network) {
        case 'facebook':
            return (
                <svg {...common}>
                    <path d="M22 12.07C22 6.48 17.52 2 11.93 2S1.86 6.48 1.86 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.02H7.9v-2.91h2.4V9.84c0-2.37 1.4-3.69 3.56-3.69 1.03 0 2.11.19 2.11.19v2.32h-1.19c-1.17 0-1.54.73-1.54 1.48v1.77h2.62l-.42 2.91h-2.2V22c4.78-.75 8.44-4.91 8.44-9.93z" />
                </svg>
            );
        case 'pinterest':
            return (
                <svg {...common}>
                    <path d="M12.04 2C6.52 2 2.5 5.96 2.5 11.2c0 3.76 2.25 7.02 5.5 8.28-.08-.7-.14-1.78.03-2.55.15-.68.99-4.2.99-4.2s-.25-.51-.25-1.26c0-1.18.69-2.07 1.54-2.07.73 0 1.08.55 1.08 1.2 0 .73-.47 1.83-.71 2.84-.2.85.43 1.54 1.27 1.54 1.52 0 2.55-1.95 2.55-4.26 0-1.76-1.19-3.07-3.35-3.07-2.44 0-3.96 1.82-3.96 3.85 0 .7.21 1.19.53 1.57.15.17.17.24.12.44l-.18.72c-.06.23-.19.28-.43.17-1.2-.5-1.76-1.83-1.76-3.33 0-2.47 2.09-5.43 6.23-5.43 3.33 0 5.52 2.41 5.52 5 0 3.42-1.9 5.97-4.7 5.97-.94 0-1.83-.51-2.13-1.09l-.58 2.22c-.18.7-.56 1.4-.88 1.91.78.23 1.6.36 2.46.36 5.52 0 9.54-3.96 9.54-9.2C21.58 5.96 17.56 2 12.04 2z" />
                </svg>
            );
        case 'instagram':
            return (
                <svg {...common}>
                    <path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.64-.07-4.85s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zm0-2.16C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95C23.73 2.7 21.31.27 16.95.07 15.67.01 15.26 0 12 0zm0 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84zM12 16a4 4 0 1 1 4-4 4 4 0 0 1-4 4zm6.41-11.85a1.44 1.44 0 1 0 1.44 1.44 1.44 1.44 0 0 0-1.44-1.44z" />
                </svg>
            );
        case 'x':
            return (
                <svg {...common}>
                    <path d="M18.24 2H21.5l-7.19 8.22L22.5 22h-6.59l-5.16-6.74L4.9 22H1.62l7.69-8.79L1.5 2h6.75l4.66 6.17L18.24 2zm-1.16 18h1.81L7.01 3.9H5.07L17.08 20z" />
                </svg>
            );
        case 'youtube':
            return (
                <svg {...common}>
                    <path d="M23.5 6.19a3.02 3.02 0 0 0-2.12-2.14C19.54 3.5 12 3.5 12 3.5s-7.54 0-9.38.55A3.02 3.02 0 0 0 .5 6.19 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.81 3.02 3.02 0 0 0 2.12 2.14c1.84.55 9.38.55 9.38.55s7.54 0 9.38-.55a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.81zM9.75 15.57V8.43L15.84 12l-6.09 3.57z" />
                </svg>
            );
        default:
            return null;
    }
}

function SocialLinks({ links }: { links: FooterSocialLink[] }) {
    if (!links?.length) {
        return null;
    }

    return (
        <ul
            className="flex shrink-0 items-center justify-center gap-1 md:order-none"
            aria-label="Social media"
        >
            {links.map((link) => (
                <li key={link.network}>
                    <a
                        href={link.href}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label={link.label}
                        title={link.label}
                        className="inline-flex size-10 items-center justify-center rounded-full text-[#800020] transition-colors hover:bg-[#800020]/10 hover:text-[#5c0018]"
                    >
                        <SocialIcon network={link.network} />
                    </a>
                </li>
            ))}
        </ul>
    );
}

export function StorefrontFooter() {
    const chrome = useContent('global_chrome');
    const f = chrome.footer;
    const socialLinks = f.social_links ?? [];

    return (
        <footer className="border-t border-neutral-100 bg-white">
            <div className="mx-auto max-w-7xl px-4 py-12 lg:px-6 lg:py-16">
                {/* Main columns */}
                <div className="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
                    {/* Brand column */}
                    <div className="md:col-span-2 lg:col-span-1">
                        <Link
                            href="/"
                            className="flex min-h-[100px] items-center justify-center"
                        >
                            <img
                                src={f.brand.logo_url}
                                alt="InkPavo"
                                className="h-auto max-h-[100px] w-auto object-contain"
                            />
                        </Link>
                        <p className="mt-4 text-sm leading-relaxed text-neutral-600">
                            {f.brand.intro}
                        </p>
                    </div>

                    {/* Products */}
                    <div>
                        <ColumnHeading>
                            {f.column_headings.products}
                        </ColumnHeading>
                        <LinkList links={f.products} />
                    </div>

                    {/* Essential Links */}
                    <div>
                        <ColumnHeading>
                            {f.column_headings.essential_links}
                        </ColumnHeading>
                        <LinkList links={f.essential_links} />
                    </div>

                    {/* Social / Other */}
                    <div className="flex flex-col items-start">
                        <ColumnHeading>Follow us</ColumnHeading>
                        <SocialLinks links={socialLinks} />
                    </div>
                </div>

                {/* Faint divider before bottom bar */}
                <div
                    className="mt-12 border-t border-neutral-200"
                    aria-hidden
                />

                {/* Legal links (left) + copyright hint (right) */}
                <div className="flex flex-col items-center gap-4 pt-8 sm:flex-row sm:justify-between">
                    <ul className="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs sm:justify-start">
                        {f.legal_bar.legal_links.map((link) => (
                            <li key={link.label}>
                                <Link href={link.href} className={TEAL}>
                                    {link.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                    <p className="text-xs text-neutral-500">
                        {f.legal_bar.copyright_text}
                    </p>
                </div>
            </div>
        </footer>
    );
}

export default StorefrontFooter;
