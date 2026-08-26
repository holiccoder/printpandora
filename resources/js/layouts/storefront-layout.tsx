import type { ReactNode } from 'react';
import AiChatWidget from '@/components/ai-chat-widget';
import StorefrontFooter from '@/components/storefront-footer';
import StorefrontHeader from '@/components/storefront-header';

type Props = {
    children: ReactNode;
    /** Pass the visible label of the active top-nav category (e.g. "Business Cards"). */
    activeCategory?: string;
};

/**
 * Global storefront layout. Wraps any public/marketing page with:
 *   1. the main storefront header (announcement bar + logo + mega-dropdown nav + actions)
 *   2. the page contents
 *   3. the shared storefront footer (link columns + legal bar)
 *   4. the AI support chat widget (floating launcher, streams from /ai/chat)
 *
 * Header and footer content are read internally via
 * `useContent('global_chrome')` so they stay in sync with
 * `content/hardcoded-content.json` without any props from here.
 */
export default function StorefrontLayout({ children, activeCategory }: Props) {
    return (
        <div className="flex min-h-screen flex-col bg-white text-neutral-900">
            <StorefrontHeader activeCategory={activeCategory} />
            <main className="flex-1">{children}</main>
            <StorefrontFooter />
            <AiChatWidget />
        </div>
    );
}
