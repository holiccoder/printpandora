import { Link } from '@inertiajs/react';
import { useContent } from '@/hooks/use-content';
import type { NavLink } from '@/types/content';

/**
 * Storefront footer — clean white background, dark column headers, and
 * teal/green link text. Mirrors the PrintPandora footer reference: Products (split
 * into two link lists), Paper Stocks, About us, Help/Useful links, and a
 * legal/utility bar at the bottom.
 *
 * All link lists, column headings, and the copyright/legal links come
 * from `content/hardcoded-content.json` → `global_chrome.footer`.
 */

const TEAL = 'text-[#0f4c3a] hover:underline';

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

export function StorefrontFooter() {
    const chrome = useContent('global_chrome');
    const f = chrome.footer;

    return (
        <footer className="border-t border-neutral-100 bg-white">
            <div className="mx-auto max-w-7xl px-4 py-12 lg:px-6 lg:py-16">
                {/* Main columns */}
                <div className="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-5">
                    {/* Products — spans two of the five columns and renders two side-by-side lists */}
                    <div className="lg:col-span-2">
                        <ColumnHeading>{f.column_headings.products}</ColumnHeading>
                        <div className="grid grid-cols-1 gap-8 sm:grid-cols-2">
                            <LinkList links={f.products_left_column} />
                            <LinkList links={f.products_right_column} />
                        </div>
                    </div>

                    <div>
                        <ColumnHeading>{f.column_headings.paper_stocks}</ColumnHeading>
                        <LinkList links={f.paper_stocks} />
                    </div>

                    <div>
                        <ColumnHeading>{f.column_headings.about_us}</ColumnHeading>
                        <LinkList links={f.about_us} />
                    </div>

                    <div>
                        <ColumnHeading>{f.column_headings.help}</ColumnHeading>
                        <LinkList links={f.help_links} />
                    </div>
                </div>

                {/* Faint divider before the legal bar */}
                <div className="mt-12 border-t border-neutral-200" aria-hidden />

                {/* Legal/utility bar */}
                <div className="flex flex-col gap-4 pt-6 text-xs md:flex-row md:items-center md:justify-between">
                    <div className="flex items-center gap-2 text-neutral-600">
                        <TeardropIcon />
                        {/* {YEAR} token is substituted by useContent() at read time. */}
                        <span>{f.legal_bar.copyright_text}</span>
                    </div>
                    <ul className="flex flex-wrap items-center gap-x-5 gap-y-2">
                        {f.legal_bar.legal_links.map((link) => (
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
