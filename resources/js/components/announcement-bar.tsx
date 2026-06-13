import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

export type Announcement = {
    text: string;
    href: string;
};

type Props = {
    messages?: Announcement[];
    intervalMs?: number;
    className?: string;
};

const DEFAULT_MESSAGES: Announcement[] = [
    { text: 'Free standard shipping on orders over $50', href: '/shipping' },
    { text: 'New: FSC® certified Business Cards now available', href: '/shop?cat=business-cards' },
    { text: '20% off your first order — use code WELCOME20', href: '/shop' },
    { text: 'Need help? Chat with our print experts 24/7', href: '/contact' },
];

/**
 * Slim global announcement bar that vertically slides through a list of
 * short marketing messages. Each tick the active message slides UP and
 * out the top while the next slides IN from the bottom edge. Each slide
 * is a clickable link.
 *
 * Only the active slide and the slide it's replacing animate; every
 * other slide is parked off-screen with no transition so it can't drift
 * back through the visible window between ticks.
 */
export function AnnouncementBar({
    messages = DEFAULT_MESSAGES,
    intervalMs = 4000,
    className,
}: Props) {
    const [index, setIndex] = useState(0);

    useEffect(() => {
        if (messages.length <= 1) return;
        const id = window.setInterval(() => {
            setIndex((i) => (i + 1) % messages.length);
        }, intervalMs);
        return () => window.clearInterval(id);
    }, [messages.length, intervalMs]);

    if (messages.length === 0) return null;

    return (
        <div
            className={cn(
                'relative overflow-hidden bg-[#0f4c3a] text-white',
                className,
            )}
            role="region"
            aria-label="Announcements"
        >
            <div className="mx-auto flex h-9 max-w-7xl items-center justify-center px-4">
                <div className="relative h-5 w-full overflow-hidden text-center text-xs font-medium tracking-wide sm:text-sm">
                    {messages.map((msg, i) => {
                        const isActive = i === index;
                        const wasActive =
                            messages.length > 1 &&
                            i === (index - 1 + messages.length) % messages.length;
                        return (
                            <Link
                                key={i}
                                href={msg.href}
                                aria-hidden={!isActive}
                                tabIndex={isActive ? 0 : -1}
                                className={cn(
                                    'absolute inset-0 flex items-center justify-center px-4 hover:underline',
                                    // Only the active and just-departed slides should
                                    // animate. Other slides snap to the bottom-rest
                                    // position so they never traverse the viewport.
                                    (isActive || wasActive) &&
                                        'transition-transform duration-700 ease-in-out',
                                    isActive && 'translate-y-0',
                                    wasActive && '-translate-y-full',
                                    !isActive && !wasActive && 'translate-y-full',
                                )}
                            >
                                {msg.text}
                            </Link>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

export default AnnouncementBar;
