import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';

import type { HeroSlide as ContentSlide } from '@/types/content';

export type HeroSlide = {
    eyebrow?: string;
    headline: string;
    subheadline: string;
    ctaLabel: string;
    ctaHref: string;
    image: string;
    imageAlt: string;
    /**
     * Tailwind background utility (or arbitrary value) for the left text panel.
     * Defaults to a soft pale gray-green that matches the storefront chrome.
     */
    bgClassName?: string;
};

type Props = {
    /** Override slides from content JSON. Rarely used. */
    slides?: HeroSlide[];
    /** Auto-advance interval; pass 0 to disable. */
    autoPlayMs?: number;
    className?: string;
};

const SLIDE_BG = ['bg-[#dfe5dc]', 'bg-[#e8e1d6]', 'bg-[#dde6df]'];

function mapSlide(s: ContentSlide, i: number): HeroSlide {
    return {
        headline: s.headline,
        subheadline: s.subheadline,
        ctaLabel: s.cta_text,
        ctaHref: s.cta_href,
        image: s.image_url,
        imageAlt: s.alt,
        bgClassName: SLIDE_BG[i % SLIDE_BG.length],
    };
}

/**
 * Storefront hero carousel: a left text panel (headline + CTA + slide
 * indicators) paired with a full-bleed product photograph on the right.
 *
 * Slides and labels come from `content/hardcoded-content.json` →
 * `home_page.hero_carousel` by default.
 */
export function HeroCarousel({ slides, autoPlayMs = 6000, className }: Props) {
    const home = useContent('home_page');
    const defaultSlides = home.hero_carousel.slides.map((s, i) => mapSlide(s, i));
    const items = slides ?? defaultSlides;

    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    const total = items.length;

    useEffect(() => {
        if (autoPlayMs <= 0 || paused || total <= 1) {
return;
}

        const id = window.setInterval(() => {
            setIndex((i) => (i + 1) % total);
        }, autoPlayMs);

        return () => window.clearInterval(id);
    }, [autoPlayMs, paused, total, index]);

    if (total === 0) {
return null;
}

    const goTo = (i: number) => setIndex(((i % total) + total) % total);
    const prev = () => goTo(index - 1);
    const next = () => goTo(index + 1);

    return (
        <section
            className={cn('relative w-full overflow-hidden', className)}
            aria-roledescription="carousel"
            aria-label={home.hero_carousel.aria_label}
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
        >
            {/* Slide track — translates horizontally; each slide is full-width */}
            <div
                className="flex w-full transition-transform duration-700 ease-out"
                style={{ transform: `translateX(-${index * 100}%)` }}
            >
                {items.map((slide, i) => (
                    <Slide key={i} slide={slide} hidden={i !== index} />
                ))}
            </div>

            {/* Prev / next buttons sit over the panels on the far edges */}
            {total > 1 && (
                <>
                    <button
                        type="button"
                        onClick={prev}
                        aria-label={home.hero_carousel.prev_button_label}
                        className="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-neutral-200 bg-white/90 p-2 text-neutral-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-[#0f4c3a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] md:left-5"
                    >
                        <ChevronLeft className="size-5" />
                    </button>
                    <button
                        type="button"
                        onClick={next}
                        aria-label={home.hero_carousel.next_button_label}
                        className="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-neutral-200 bg-white/90 p-2 text-neutral-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-[#0f4c3a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] md:right-5"
                    >
                        <ChevronRight className="size-5" />
                    </button>
                </>
            )}

            {/* Indicators sit pinned to the bottom of the left text panel */}
            {total > 1 && (
                <div className="pointer-events-none absolute bottom-6 left-0 z-10 flex w-full md:w-1/2">
                    <div className="pointer-events-auto mx-auto flex items-center gap-3 px-6 md:mx-0 md:pl-12 lg:pl-20">
                        {items.map((_, i) => (
                            <button
                                key={i}
                                type="button"
                                onClick={() => goTo(i)}
                                aria-label={`Go to slide ${i + 1}`}
                                aria-current={i === index}
                                className={cn(
                                    'h-[3px] rounded-full transition-all',
                                    i === index
                                        ? 'w-10 bg-neutral-900'
                                        : 'w-8 bg-neutral-400/70 hover:bg-neutral-600',
                                )}
                            />
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}

function Slide({ slide, hidden }: { slide: HeroSlide; hidden: boolean }) {
    return (
        <div
            className="grid w-full shrink-0 grid-cols-1 md:h-[550px] md:max-h-[550px] md:grid-cols-2"
            aria-hidden={hidden}
        >
            {/* Left: text panel */}
            <div
                className={cn(
                    'flex h-full min-h-[20rem] flex-col justify-center px-6 py-10 md:min-h-0 md:px-12 md:py-12 lg:px-20',
                    slide.bgClassName ?? 'bg-[#dfe5dc]',
                )}
            >
                <div className="max-w-md">
                    {slide.eyebrow && (
                        <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-600">
                            {slide.eyebrow}
                        </p>
                    )}
                    <h2 className="text-3xl font-bold leading-tight text-neutral-900 md:text-4xl lg:text-[2.5rem]">
                        {slide.headline}
                    </h2>
                    <p className="mt-3 text-base text-neutral-700 md:text-lg">
                        {slide.subheadline}
                    </p>
                    <Link
                        href={slide.ctaHref}
                        className="mt-6 inline-flex items-center justify-center rounded-sm bg-[#0f4c3a] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0d3f30] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] focus-visible:ring-offset-2"
                    >
                        {slide.ctaLabel}
                    </Link>
                </div>
            </div>

            {/* Right: photo */}
            <div className="relative h-full min-h-[14rem] overflow-hidden bg-neutral-100 md:min-h-0">
                <img
                    src={slide.image}
                    alt={slide.imageAlt}
                    className="h-full w-full object-cover"
                    loading="eager"
                />
            </div>
        </div>
    );
}

export default HeroCarousel;