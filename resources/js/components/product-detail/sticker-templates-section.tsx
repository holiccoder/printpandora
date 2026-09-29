import { Link } from '@inertiajs/react';
import type { PromotionalBannerContent } from '@/types/product-detail';

interface StickerTemplatesSectionProps {
    content: PromotionalBannerContent;
}

export default function StickerTemplatesSection({
    content,
}: StickerTemplatesSectionProps) {
    const { heading, body, cta_label, cta_href, image_url, image_alt } =
        content;

    return (
        <section className="bg-[#e9eae8] py-12 lg:py-16">
            <div className="product-detail-container mx-auto max-w-7xl px-4">
                <div className="relative aspect-[16/9] min-h-[25rem] overflow-hidden rounded-2xl shadow-sm sm:min-h-0">
                    <img
                        src={image_url}
                        alt={image_alt}
                        loading="lazy"
                        className="absolute inset-0 h-full w-full object-cover"
                    />
                    <div className="absolute inset-x-[12%] top-[23%] flex h-[49%] items-center justify-center sm:inset-x-[25%] lg:top-[23%] lg:right-[17%] lg:left-[38%] lg:-rotate-3">
                        <div className="max-w-[32rem] px-5 text-center sm:px-8 lg:px-10">
                            <h2 className="text-xl font-extrabold tracking-tight text-neutral-900 sm:text-3xl lg:text-4xl">
                                {heading}
                            </h2>
                            <p className="mt-3 text-sm leading-relaxed text-neutral-700 sm:mt-4 sm:text-base lg:text-lg">
                                {body}
                            </p>
                            <Link
                                href={cta_href}
                                className="mt-4 inline-block text-sm font-bold text-[#0f766e] underline-offset-4 transition-colors hover:text-[#115e59] hover:underline sm:mt-6 sm:text-base"
                            >
                                {cta_label}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
