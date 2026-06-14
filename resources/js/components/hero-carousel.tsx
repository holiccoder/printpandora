import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

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

const DEFAULT_SLIDES: HeroSlide[] = [
    {
        headline: 'Marketing Materials, make your brand stand out',
        subheadline:
            'We’re here to help your business stand out for your next big campaign.',
        ctaLabel: 'Shop now',
        ctaHref: '/shop?cat=marketing-materials',
        image: 'https://images.unsplash.com/photo-1607748862156-7c548e7e98f4?auto=format&fit=crop&w=1400&q=70',
        imageAlt:
            'A spread of colourful printed promotional flyers, business cards and stickers on a light wood table beside a red wire chair',
        bgClassName: 'bg-[#dfe5dc]',
    },
    {
        headline: 'Original Business Cards, designed to be remembered',
        subheadline:
            'Thicker than your average card, with finishes that turn first impressions into lasting ones.',
        ctaLabel: 'Shop business cards',
        ctaHref: '/shop?cat=business-cards',
        image: 'https://images.unsplash.com/photo-1606857521015-7f9fcf423740?auto=format&fit=crop&w=1400&q=70',
        imageAlt: 'Two minimalist business cards laid on textured brown packing paper',
        bgClassName: 'bg-[#e8e1d6]',
    },
    {
        headline: 'FSC® certified products that are kinder to the planet',
        subheadline:
            'Bring your brand to life on certified papers — Business Cards, Postcards, Flyers and Brochures.',
        ctaLabel: 'Discover the range',
        ctaHref: '/shop/fsc-certified',
        image: 'https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1400&q=70',
        imageAlt: 'Aerial view of a dense, vibrant green pine forest',
        bgClassName: 'bg-[#dde6df]',
    },
];

type Props = {
    slides?: HeroSlide[];
    /** Auto-advance interval; pass 0 to disable. */
    autoPlayMs?: number;
    className?: string;
};

/**
 * Storefront hero carousel: a left text panel (headline + CTA + slide
 * indicators) paired with a full-bleed product photograph on the right.
 * Auto-advances on a timer; the timer pauses while the user hovers and
 * resets whenever they navigate manually.
 */
export function HeroCarousel({
    slides = DEFAULT_SLIDES,
    autoPlayMs = 6000,
    className,
}: Props) {
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    const total = slides.length;

    useEffect(() => {
        if (autoPlayMs <= 0 || paused || total <= 1) return;
        const id = window.setInterval(() => {
            setIndex((i) => (i + 1) % total);
        }, autoPlayMs);
        return () => window.clearInterval(id);
    }, [autoPlayMs, paused, total, index]);

    if (total === 0) return null;

    const goTo = (i: number) => setIndex(((i % total) + total) % total);
    const prev = () => goTo(index - 1);
    const next = () => goTo(index + 1);

    return (
        <section
            className={cn('relative w-full overflow-hidden', className)}
            aria-roledescription="carousel"
            aria-label="Featured products"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
        >
            {/* Slide track — translates horizontally; each slide is full-width */}
            <div
                className="flex w-full transition-transform duration-700 ease-out"
                style={{ transform: `translateX(-${index * 100}%)` }}
            >
                {slides.map((slide, i) => (
                    <Slide key={i} slide={slide} hidden={i !== index} />
                ))}
            </div>

            {/* Prev / next buttons sit over the panels on the far edges */}
            {total > 1 && (
                <>
                    <button
                        type="button"
                        onClick={prev}
                        aria-label="Previous slide"
                        className="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-neutral-200 bg-white/90 p-2 text-neutral-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-[#0f4c3a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] md:left-5"
                    >
                        <ChevronLeft className="size-5" />
                    </button>
                    <button
                        type="button"
                        onClick={next}
                        aria-label="Next slide"
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
                        {slides.map((_, i) => (
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
            // Cap the desktop slide at 550px tall so a long headline/subheadline
            // can't push the carousel into a wall-of-pixels. On narrow screens
            // the two panels stack and we let them size to their content.
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
