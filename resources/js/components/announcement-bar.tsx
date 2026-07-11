import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';

export type Announcement = {
    text: string;
    href: string;
};

type Props = {
    /** Override the messages from content JSON. Rarely needed. */
    messages?: Announcement[];
    /** Override the tick interval. Defaults to the JSON value. */
    intervalMs?: number;
    className?: string;
};

/**
 * Slim global announcement bar that vertically slides through a list of
 * short marketing messages. Each tick the active message slides UP and
 * out the top while the next slides IN from the bottom edge. Each slide
 * is a clickable link.
 *
 * Defaults (messages, interval, aria-label) come from
 * `content/hardcoded-content.json` → `global_chrome.announcement_bar`.
 *
 * Only the active slide and the slide it's replacing animate; every
 * other slide is parked off-screen with no transition so it can't drift
 * back through the visible window between ticks.
 */
export function AnnouncementBar({ messages, intervalMs, className }: Props) {
    const chrome = useContent('global_chrome');
    const bar = chrome.announcement_bar;
    const items = messages ?? bar.default_messages;
    const tick = intervalMs ?? bar.default_interval_ms;

    const [index, setIndex] = useState(0);

    useEffect(() => {
        if (items.length <= 1) {
return;
}

        const id = window.setInterval(() => {
            setIndex((i) => (i + 1) % items.length);
        }, tick);

        return () => window.clearInterval(id);
    }, [items.length, tick]);

    if (items.length === 0) {
return null;
}

    return (
        <div
            className={cn(
                'relative overflow-hidden bg-[#0f4c3a] text-white',
                className,
            )}
            role="region"
            aria-label={bar.region_aria_label}
        >
            <div className="mx-auto flex h-9 max-w-7xl items-center justify-center px-4">
                <div className="relative h-5 w-full overflow-hidden text-center text-xs font-medium tracking-wide sm:text-sm">
                    {items.map((msg, i) => {
                        const isActive = i === index;
                        const wasActive =
                            items.length > 1 &&
                            i === (index - 1 + items.length) % items.length;

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
