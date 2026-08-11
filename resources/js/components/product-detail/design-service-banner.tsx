import React from 'react';
import { ChevronRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { DesignServiceBannerContent } from '@/types/product-detail';

interface DesignServiceBannerProps {
    content: DesignServiceBannerContent;
}

export default function DesignServiceBanner({
    content,
}: DesignServiceBannerProps) {
    const { heading, body, cta_label, cta_href, image_url, image_alt } =
        content;

    return (
        <section className="overflow-hidden bg-[#F5F0E8] py-12 lg:py-16">
            <div className="mx-auto max-w-7xl px-4">
                <div className="grid grid-cols-1 items-center gap-10 lg:grid-cols-2">
                    {/* Left: Content */}
                    <div className="mx-auto max-w-xl space-y-5 text-center lg:mx-0 lg:text-left">
                        <h2 className="text-3xl font-extrabold tracking-tight text-[#800020] sm:text-4xl">
                            {heading}
                        </h2>
                        <p className="text-base leading-relaxed text-neutral-700">
                            {body}
                        </p>
                        <div className="pt-2">
                            <Link
                                href={cta_href}
                                className="inline-flex items-center gap-1.5 rounded-full bg-[#800020] px-6 py-3 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#800020]/90 hover:shadow"
                                aria-label={`${cta_label} — Learn about our design service plans`}
                            >
                                {cta_label}
                                <ChevronRight className="size-4" />
                            </Link>
                        </div>
                    </div>

                    {/* Right: Concept Mockup Image */}
                    <div className="relative flex justify-center lg:justify-end">
                        <div className="relative aspect-[4/3] w-full max-w-md overflow-hidden rounded-2xl border border-white/40 shadow-lg">
                            <img
                                src={image_url}
                                alt={image_alt}
                                loading="lazy"
                                className="h-full w-full transform object-cover transition-transform duration-500 hover:scale-105"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
