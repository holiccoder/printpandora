import { ReactNode } from 'react';
import AnnouncementBar, { Announcement } from '@/components/announcement-bar';
import StorefrontHeader from '@/components/storefront-header';
import StorefrontFooter from '@/components/storefront-footer';

type Props = {
    children: ReactNode;
    /** Pass the visible label of the active top-nav category (e.g. "Business Cards"). */
    activeCategory?: string;
    /** Optional override for the announcement messages slideshow. */
    announcements?: Announcement[];
};

/**
 * Global storefront layout. Wraps any public/marketing page with:
 *   1. a thin animated announcement bar at the top
 *   2. the main storefront header (logo + mega-dropdown nav + actions)
 *   3. the page contents
 *   4. the shared storefront footer (link columns + legal bar)
 */
export default function StorefrontLayout({
    children,
    activeCategory,
    announcements,
}: Props) {
    return (
        <div className="flex min-h-screen flex-col bg-white text-neutral-900">
            <AnnouncementBar messages={announcements} />
            <StorefrontHeader activeCategory={activeCategory} />
            <main className="flex-1">{children}</main>
            <StorefrontFooter />
        </div>
    );
}
