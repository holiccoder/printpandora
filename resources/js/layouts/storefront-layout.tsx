import type { ReactNode } from 'react';
import AnnouncementBar from '@/components/announcement-bar';
import StorefrontFooter from '@/components/storefront-footer';
import StorefrontHeader from '@/components/storefront-header';

type Props = {
    children: ReactNode;
    /** Pass the visible label of the active top-nav category (e.g. "Business Cards"). */
    activeCategory?: string;
};

/**
 * Global storefront layout. Wraps any public/marketing page with:
 *   1. a thin animated announcement bar at the top (content from JSON)
 *   2. the main storefront header (logo + mega-dropdown nav + actions)
 *   3. the page contents
 *   4. the shared storefront footer (link columns + legal bar)
 *
 * Announcement bar and footer content are read internally via
 * `useContent('global_chrome')` so they stay in sync with
 * `content/hardcoded-content.json` without any props from here.
 */
export default function StorefrontLayout({
    children,
    activeCategory,
}: Props) {
    return (
        <div className="flex min-h-screen flex-col bg-white text-neutral-900">
            <AnnouncementBar />
            <StorefrontHeader activeCategory={activeCategory} />
            <main className="flex-1">{children}</main>
            <StorefrontFooter />
        </div>
    );
}