import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ArrowRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';

import type { HeroSlide as ContentSlide } from '@/types/content';

export type HeroSlideFeature = {
    icon: string;
    title: string;
    description: string;
};

export type HeroSlide = {
    eyebrow?: string;
    headline: string;
    subheadline: string;
    ctaLabel: string;
    ctaHref: string;
    image: string;
    imageAlt: string;
    features?: HeroSlideFeature[];
};

type Props = {
    slides?: HeroSlide[];
    autoPlayMs?: number;
    className?: string;
};

function mapSlide(s: ContentSlide): HeroSlide {
    return {
        eyebrow: s.eyebrow,
        headline: s.headline,
        subheadline: s.subheadline,
        ctaLabel: s.cta_text,
        ctaHref: s.cta_href,
        image: s.image_url,
        imageAlt: s.alt,
        features: s.features as HeroSlideFeature[] | undefined,
    };
}

function FeatureIcon({ icon }: { icon: string }) {
    const cls = 'size-6';

    switch (icon) {
        case 'card':
            return (
                <svg
                    className={cls}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden
                >
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                    <path d="M2 10h20" />
                </svg>
            );
        case 'press':
            return (
                <svg
                    className={cls}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden
                >
                    <path d="M4 7V4h16v3" />
                    <path d="M2 7h20v13H2z" />
                    <path d="M12 7v13" />
                    <path d="M8 11h2" />
                    <path d="M14 11h2" />
                </svg>
            );
        case 'pencil':
            return (
                <svg
                    className={cls}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden
                >
                    <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                    <path d="m15 5 4 4" />
                </svg>
            );
        default:
            return null;
    }
}

export function HeroCarousel({ slides, autoPlayMs = 6000, className }: Props) {
    const home = useContent('home_page');
    const defaultSlides = home.hero_carousel.slides.map((s) => mapSlide(s));
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
            <div
                className="flex w-full transition-transform duration-700 ease-out"
                style={{ transform: `translateX(-${index * 100}%)` }}
            >
                {items.map((slide, i) => (
                    <Slide
                        key={i}
                        slide={slide}
                        hidden={i !== index}
                        active={i === index}
                    />
                ))}
            </div>

            {total > 1 && (
                <>
                    <button
                        type="button"
                        onClick={prev}
                        aria-label={home.hero_carousel.prev_button_label}
                        className="absolute top-1/2 left-3 z-10 -translate-y-1/2 rounded-full border border-neutral-200 bg-white/90 p-2 text-neutral-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-[#800020] focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:outline-none md:left-5"
                    >
                        <ChevronLeft className="size-5" />
                    </button>
                    <button
                        type="button"
                        onClick={next}
                        aria-label={home.hero_carousel.next_button_label}
                        className="absolute top-1/2 right-3 z-10 -translate-y-1/2 rounded-full border border-neutral-200 bg-white/90 p-2 text-neutral-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-[#800020] focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:outline-none md:right-5"
                    >
                        <ChevronRight className="size-5" />
                    </button>
                </>
            )}

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

/** Fade + slide-up; pair with inline transitionDelay for staggered enter. */
function enterClass(active: boolean) {
    return cn(
        'transition-all duration-700 ease-out will-change-transform',
        active ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0',
    );
}

function Slide({
    slide,
    hidden,
    active,
}: {
    slide: HeroSlide;
    hidden: boolean;
    active: boolean;
}) {
    const hasFeatures = slide.features && slide.features.length > 0;

    return (
        <div
            className="relative h-[710px] w-full shrink-0 overflow-hidden md:h-[710px]"
            aria-hidden={hidden}
        >
            {/* Full-width photo — subtle scale-in when active */}
            <img
                src={slide.image}
                alt={slide.imageAlt}
                className={cn(
                    'absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] ease-out',
                    active ? 'scale-100' : 'scale-105',
                )}
                loading="eager"
            />

            {/* Copy on the photo */}
            <div className="relative z-10 flex h-full flex-col">
                <div className="flex flex-1 flex-col justify-end px-6 md:justify-center md:px-12 lg:px-20">
                    <div className="max-w-md pb-6 md:pb-0">
                        {slide.eyebrow && (
                            <div
                                className={cn(
                                    'mb-4 flex items-center gap-3',
                                    enterClass(active),
                                )}
                                style={{
                                    transitionDelay: active ? '80ms' : '0ms',
                                }}
                            >
                                <p className="text-xs font-semibold tracking-[0.15em] text-[#C9A96A] uppercase">
                                    {slide.eyebrow}
                                </p>
                                <span
                                    className={cn(
                                        'h-px origin-left bg-[#C9A96A]/40 transition-transform duration-700 ease-out',
                                        active ? 'scale-x-100' : 'scale-x-0',
                                    )}
                                    style={{
                                        transitionDelay: active
                                            ? '200ms'
                                            : '0ms',
                                    }}
                                />
                            </div>
                        )}
                        <h2
                            className={cn(
                                'font-serif text-4xl leading-tight font-bold whitespace-pre-line text-[#800020] md:text-5xl lg:text-[3.25rem]',
                                enterClass(active),
                            )}
                            style={{
                                transitionDelay: active ? '180ms' : '0ms',
                            }}
                        >
                            {slide.headline}
                        </h2>
                        <p
                            className={cn(
                                'mt-4 text-base leading-relaxed text-[#2A2A28]/80 md:text-lg',
                                enterClass(active),
                            )}
                            style={{
                                transitionDelay: active ? '320ms' : '0ms',
                            }}
                        >
                            {slide.subheadline}
                        </p>
                        <div
                            className={cn(enterClass(active))}
                            style={{
                                transitionDelay: active ? '460ms' : '0ms',
                            }}
                        >
                            <Link
                                href={slide.ctaHref}
                                tabIndex={hidden ? -1 : undefined}
                                className="mt-8 inline-flex items-center justify-center gap-2 rounded-sm bg-[#800020] px-6 py-3 text-sm font-semibold tracking-wider text-white uppercase shadow-sm transition hover:bg-[#800020]/90 focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                {slide.ctaLabel}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Features strip */}
                {hasFeatures && (
                    <div
                        className={cn(
                            'hidden border-t border-[#800020]/10 md:block',
                            enterClass(active),
                        )}
                        style={{
                            transitionDelay: active ? '580ms' : '0ms',
                        }}
                    >
                        <div className="grid max-w-3xl grid-cols-3 divide-x divide-[#800020]/10">
                            {slide.features!.map((f, fi) => (
                                <div
                                    key={f.title}
                                    className={cn(
                                        'flex items-center gap-3 px-6 py-4 lg:px-8',
                                        enterClass(active),
                                    )}
                                    style={{
                                        transitionDelay: active
                                            ? `${620 + fi * 90}ms`
                                            : '0ms',
                                    }}
                                >
                                    <span className="shrink-0 text-[#800020]">
                                        <FeatureIcon icon={f.icon} />
                                    </span>
                                    <div>
                                        <p className="text-[11px] font-bold tracking-wider text-[#800020] uppercase">
                                            {f.title}
                                        </p>
                                        <p className="text-xs text-[#2A2A28]/70">
                                            {f.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

export default HeroCarousel;
