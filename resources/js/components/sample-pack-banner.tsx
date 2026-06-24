import { Link } from '@inertiajs/react';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';

type Props = {
    /** Overrides for callers that want bespoke copy. Defaults come from JSON. */
    headline?: string;
    description?: string;
    ctaLabel?: string;
    ctaHref?: string;
    image?: string;
    imageAlt?: string;
    className?: string;
};

/**
 * Two-column promo banner: a dark, moody text panel on the left with a
 * white ghost-button CTA, paired with a brightly-lit lifestyle photo on
 * the right. Designed to sit full-bleed underneath other hero content.
 *
 * Defaults come from `content/hardcoded-content.json` →
 * `home_page.sample_pack_banner`.
 */
export default function SamplePackBanner({
    headline,
    description,
    ctaLabel,
    ctaHref,
    image,
    imageAlt,
    className,
}: Props) {
    const b = useContent('home_page').sample_pack_banner;
    const h = headline ?? b.title;
    const desc = description ?? b.description;
    const ctaText = ctaLabel ?? b.cta_text;
    const ctaUrl = ctaHref ?? b.cta_href;
    const img = image ?? b.image_url;
    const alt = imageAlt ?? b.alt;

    return (
        <section
            className={cn(
                'relative grid w-full grid-cols-1 overflow-hidden bg-[#1d130f] md:grid-cols-2 md:h-[420px]',
                className,
            )}
            aria-label={h}
        >
            {/* Left: dark text panel. */}
            <div
                className="relative flex flex-col justify-center px-6 py-12 text-white md:px-12 md:py-16 lg:px-20"
                style={{
                    backgroundImage:
                        'radial-gradient(ellipse at 30% 50%, #3a2a22 0%, #1d130f 70%)',
                }}
            >
                <div className="relative z-10 max-w-md">
                    <h2 className="text-3xl font-bold leading-tight md:text-4xl lg:text-[2.5rem]">
                        {h}
                    </h2>
                    <p className="mt-3 text-sm leading-relaxed text-white/80 md:text-base">
                        {desc}
                    </p>
                    <Link
                        href={ctaUrl}
                        className="mt-6 inline-flex items-center justify-center rounded-sm border border-white px-6 py-3 text-sm font-semibold text-white transition hover:bg-white hover:text-[#1d130f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#1d130f]"
                    >
                        {ctaText}
                    </Link>
                </div>
            </div>

            {/* Right: lifestyle photo */}
            <div className="relative h-64 overflow-hidden bg-neutral-100 md:h-full">
                <img
                    src={img}
                    alt={alt}
                    className="h-full w-full object-cover"
                    loading="lazy"
                />
            </div>
        </section>
    );
}
