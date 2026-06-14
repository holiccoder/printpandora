import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type Props = {
    headline?: string;
    description?: string;
    ctaLabel?: string;
    ctaHref?: string;
    /** Replace the default lifestyle photograph (right column). */
    image?: string;
    imageAlt?: string;
    className?: string;
};

/**
 * Two-column promo banner: a dark, moody text panel on the left with a
 * white ghost-button CTA, paired with a brightly-lit lifestyle photo on
 * the right. Designed to sit full-bleed underneath other hero content.
 */
export default function SamplePackBanner({
    headline = 'Free sample',
    description = 'New to PrintPandora? See our full print range in all the possible variations of shape, paper stock & finishes for free.',
    ctaLabel = 'Get your sample pack',
    ctaHref = '/shop/sample-pack',
    image = 'https://images.unsplash.com/photo-1551836022-deb4988cc6c0?auto=format&fit=crop&w=1400&q=70',
    imageAlt = 'Person in a denim jacket pulling a yellow card from a white sample pack folder on a light wood tabletop',
    className,
}: Props) {
    return (
        <section
            className={cn(
                'relative grid w-full grid-cols-1 overflow-hidden bg-[#1d130f] md:grid-cols-2 md:h-[420px]',
                className,
            )}
            aria-label={headline}
        >
            {/* Left: dark text panel. The radial gradient mimics the soft
                spotlight on a moody studio backdrop, so flat black doesn't
                feel cheap on large displays. */}
            <div
                className="relative flex flex-col justify-center px-6 py-12 text-white md:px-12 md:py-16 lg:px-20"
                style={{
                    backgroundImage:
                        'radial-gradient(ellipse at 30% 50%, #3a2a22 0%, #1d130f 70%)',
                }}
            >
                <div className="relative z-10 max-w-md">
                    <h2 className="text-3xl font-bold leading-tight md:text-4xl lg:text-[2.5rem]">
                        {headline}
                    </h2>
                    <p className="mt-3 text-sm leading-relaxed text-white/80 md:text-base">
                        {description}
                    </p>
                    <Link
                        href={ctaHref}
                        className="mt-6 inline-flex items-center justify-center rounded-sm border border-white px-6 py-3 text-sm font-semibold text-white transition hover:bg-white hover:text-[#1d130f] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#1d130f]"
                    >
                        {ctaLabel}
                    </Link>
                </div>
            </div>

            {/* Right: lifestyle photo */}
            <div className="relative h-64 overflow-hidden bg-neutral-100 md:h-full">
                <img
                    src={image}
                    alt={imageAlt}
                    className="h-full w-full object-cover"
                    loading="lazy"
                />
            </div>
        </section>
    );
}
