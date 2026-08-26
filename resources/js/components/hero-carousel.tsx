import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ArrowRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';

import type {
    HeroMemberOffer,
    HeroSlide as ContentSlide,
} from '@/types/content';

export type HeroSlideFeature = {
    icon: string;
    title: string;
    description: string;
};

export type HeroTextTone = 'light' | 'dark';

export type HeroSlide = {
    eyebrow?: string;
    headline: string;
    subheadline: string;
    ctaLabel: string;
    ctaHref: string;
    image: string;
    imageAlt: string;
    features?: HeroSlideFeature[];
    textTone?: HeroTextTone;
    offer?: HeroMemberOffer;
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
        textTone: s.text_tone ?? 'dark',
        offer: s.offer,
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

function MemberOfferIcon({ icon }: { icon: string }) {
    const cls = 'size-5';

    switch (icon) {
        case 'user-plus':
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
                    <path d="M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="8.5" cy="7" r="4" />
                    <path d="M19 8v6M22 11h-6" />
                </svg>
            );
        case 'arrow-right':
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
                    <path d="M5 12h14" />
                    <path d="m13 6 6 6-6 6" />
                </svg>
            );
        case 'tag':
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
                    <path d="M20.59 13.41 11 3.83V3H4v7h.83l9.59 9.59a2 2 0 0 0 2.83 0l3.34-3.35a2 2 0 0 0 0-2.83Z" />
                    <circle
                        cx="7.5"
                        cy="6.5"
                        r=".75"
                        fill="currentColor"
                        stroke="none"
                    />
                </svg>
            );
        default:
            return null;
    }
}

function MemberOfferCopy({
    offer,
    active,
    hidden,
}: {
    offer: HeroMemberOffer;
    active: boolean;
    hidden: boolean;
}) {
    return (
        <div className="max-w-xl pb-7 md:pb-0">
            <p
                className={cn(
                    'text-xs font-semibold tracking-[0.2em] text-[#C9A96A] uppercase',
                    enterClass(active),
                )}
                style={{ transitionDelay: active ? '80ms' : '0ms' }}
            >
                {offer.pretitle}
            </p>

            <h2
                className={cn(
                    'mt-3 font-sans text-4xl leading-tight font-semibold tracking-[0.08em] text-white drop-shadow-sm sm:text-5xl',
                    enterClass(active),
                )}
                style={{ transitionDelay: active ? '160ms' : '0ms' }}
            >
                {offer.headline}
            </h2>

            <div
                className={cn(
                    'mt-3 flex items-baseline gap-3 text-white',
                    enterClass(active),
                )}
                style={{ transitionDelay: active ? '240ms' : '0ms' }}
            >
                <span className="text-xl font-medium">
                    {offer.discount_label}
                </span>
                <span className="text-4xl leading-none font-bold tracking-tight text-[#C9A96A] sm:text-5xl">
                    {offer.discount}
                </span>
            </div>

            <div
                className={cn(
                    'mt-5 border-t border-[#C9A96A]/80 pt-4',
                    enterClass(active),
                )}
                style={{ transitionDelay: active ? '320ms' : '0ms' }}
            >
                <p className="text-sm leading-relaxed text-white/90 sm:text-base">
                    {offer.description}
                </p>
            </div>

            <div
                className={cn('mt-5', enterClass(active))}
                style={{ transitionDelay: active ? '400ms' : '0ms' }}
            >
                {offer.steps.map((step, stepIndex) => (
                    <div key={step.number} className="relative pb-2 last:pb-0">
                        <div className="relative z-10 flex min-h-9 items-center gap-3">
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg border border-[#C9A96A]/80 text-[#C9A96A]">
                                <MemberOfferIcon icon={step.icon} />
                            </span>
                            <p className="text-sm leading-relaxed text-white sm:text-base">
                                <span className="mr-3 text-xs font-semibold tracking-[0.12em] text-[#C9A96A]">
                                    {step.number}
                                </span>
                                {step.label}
                            </p>
                        </div>
                        {stepIndex < offer.steps.length - 1 && (
                            <span
                                aria-hidden="true"
                                className="absolute top-9 bottom-0 left-[1.125rem] w-px bg-[#C9A96A]/55"
                            />
                        )}
                    </div>
                ))}
            </div>

            <p
                className={cn('mt-4 text-xs text-white/75', enterClass(active))}
                style={{ transitionDelay: active ? '520ms' : '0ms' }}
            >
                {offer.terms}
            </p>

            <div
                className={cn(enterClass(active))}
                style={{ transitionDelay: active ? '600ms' : '0ms' }}
            >
                <Link
                    href={offer.cta_href}
                    tabIndex={hidden ? -1 : undefined}
                    className="mt-5 inline-flex items-center justify-center rounded-md bg-[#C9A96A] px-7 py-3 text-sm font-bold text-[#2A2114] shadow-sm transition hover:bg-[#D8BB82] focus-visible:ring-2 focus-visible:ring-[#C9A96A] focus-visible:ring-offset-2 focus-visible:ring-offset-[#2A2114] focus-visible:outline-none"
                >
                    {offer.cta_text}
                </Link>
            </div>
        </div>
    );
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
    const activeTextTone = items[index]?.textTone ?? 'dark';

    return (
        <section
            className={cn(
                'home-hero-carousel relative w-full overflow-hidden',
                className,
            )}
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
                        priority={i === 0}
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
                <div className="pointer-events-none absolute right-0 bottom-6 z-10 flex w-full justify-end md:w-1/2">
                    <div className="pointer-events-auto flex items-center gap-3 px-6 md:pr-12 lg:pr-20">
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
                                        ? cn(
                                              'w-10',
                                              activeTextTone === 'light'
                                                  ? 'bg-white'
                                                  : 'bg-neutral-900',
                                          )
                                        : cn(
                                              'w-8',
                                              activeTextTone === 'light'
                                                  ? 'bg-white/60 hover:bg-white'
                                                  : 'bg-neutral-400/70 hover:bg-neutral-600',
                                          ),
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
        'transition-[opacity,transform] duration-700 ease-out',
        active ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0',
    );
}

function Slide({
    slide,
    hidden,
    active,
    priority,
}: {
    slide: HeroSlide;
    hidden: boolean;
    active: boolean;
    priority: boolean;
}) {
    const hasFeatures =
        !slide.offer && slide.features && slide.features.length > 0;
    const lightText = slide.textTone === 'light';

    return (
        <div
            className={cn(
                'relative w-full shrink-0 overflow-hidden',
                slide.offer
                    ? 'min-h-[560px] sm:aspect-[16/10] sm:min-h-0 lg:aspect-[12/5]'
                    : 'aspect-[3/4] sm:aspect-[16/10] lg:aspect-[12/5]',
            )}
            aria-hidden={hidden}
        >
            {/* Full-width photo — subtle scale-in when active. From lg up the
                container matches the 3840x1600 (12:5) source ratio, so the image
                is never cropped and just scales with the viewport. On small
                screens a taller ratio leaves room for the copy; object-position
                keeps the right-of-center subject in frame when cropping. */}
            <img
                src={slide.image}
                alt={slide.imageAlt}
                className={cn(
                    'absolute inset-0 h-full w-full object-cover object-[68%_center] transition-transform duration-[1200ms] ease-out',
                    active ? 'scale-100' : 'scale-105',
                )}
                loading={priority ? 'eager' : 'lazy'}
                fetchPriority={priority ? 'high' : 'low'}
                decoding="async"
            />

            {!slide.offer && (
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-y-0 left-0 z-0 w-[85%] md:w-[60%]"
                    style={{
                        background: lightText
                            ? 'linear-gradient(to right, rgba(0, 0, 0, 0.62) 0%, rgba(0, 0, 0, 0.28) 58%, transparent 100%)'
                            : 'linear-gradient(to right, rgba(255, 255, 255, 0.72) 0%, rgba(255, 255, 255, 0.32) 58%, transparent 100%)',
                    }}
                />
            )}

            {/* Copy on the photo */}
            <div className="relative z-10 flex h-full flex-col">
                <div className="flex flex-1 flex-col justify-end px-6 md:justify-center md:px-12 lg:px-20">
                    {slide.offer ? (
                        <MemberOfferCopy
                            offer={slide.offer}
                            active={active}
                            hidden={hidden}
                        />
                    ) : (
                        <div className="max-w-md pb-6 md:pb-0">
                            {slide.eyebrow && (
                                <div
                                    className={cn(
                                        'mb-4 flex items-center gap-3',
                                        enterClass(active),
                                    )}
                                    style={{
                                        transitionDelay: active
                                            ? '80ms'
                                            : '0ms',
                                    }}
                                >
                                    <p
                                        className={cn(
                                            'text-xs font-semibold tracking-[0.15em] uppercase',
                                            lightText
                                                ? 'text-[#F4D7A0]'
                                                : 'text-[#800020]',
                                        )}
                                    >
                                        {slide.eyebrow}
                                    </p>
                                    <span
                                        className={cn(
                                            'h-px origin-left transition-transform duration-700 ease-out',
                                            lightText
                                                ? 'bg-[#F4D7A0]/50'
                                                : 'bg-[#800020]/30',
                                            active
                                                ? 'scale-x-100'
                                                : 'scale-x-0',
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
                                    'font-serif text-4xl leading-tight font-bold whitespace-pre-line md:text-5xl lg:text-[3.25rem]',
                                    lightText
                                        ? 'text-white drop-shadow-sm'
                                        : 'text-[#800020]',
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
                                    'mt-4 text-base leading-relaxed md:text-lg',
                                    lightText
                                        ? 'text-white/85'
                                        : 'text-[#2A2A28]/80',
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
                    )}
                </div>

                {/* Features strip */}
                {hasFeatures && (
                    <div
                        className={cn(
                            'hidden border-t md:block',
                            lightText
                                ? 'border-white/20'
                                : 'border-[#800020]/10',
                            enterClass(active),
                        )}
                        style={{
                            transitionDelay: active ? '580ms' : '0ms',
                        }}
                    >
                        <div
                            className={cn(
                                'grid max-w-3xl grid-cols-3 divide-x',
                                lightText
                                    ? 'divide-white/20'
                                    : 'divide-[#800020]/10',
                            )}
                        >
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
                                    <span
                                        className={cn(
                                            'shrink-0',
                                            lightText
                                                ? 'text-white'
                                                : 'text-[#800020]',
                                        )}
                                    >
                                        <FeatureIcon icon={f.icon} />
                                    </span>
                                    <div>
                                        <p
                                            className={cn(
                                                'text-[11px] font-bold tracking-wider uppercase',
                                                lightText
                                                    ? 'text-white'
                                                    : 'text-[#800020]',
                                            )}
                                        >
                                            {f.title}
                                        </p>
                                        <p
                                            className={cn(
                                                'text-xs',
                                                lightText
                                                    ? 'text-white/75'
                                                    : 'text-[#2A2A28]/70',
                                            )}
                                        >
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
