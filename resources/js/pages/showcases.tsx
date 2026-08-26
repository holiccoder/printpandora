import { Link } from '@inertiajs/react';
import { useState } from 'react';
import LightboxGallery from '@/components/product-detail/lightbox-gallery';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

type Showcase = {
    id: number;
    link: string | null;
    image_url: string;
};

type PaginatedShowcases = {
    data: Showcase[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    showcases: PaginatedShowcases;
};

export default function Showcases({ showcases }: Props) {
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);
    const images = showcases.data.map((showcase) => showcase.image_url);

    const openLightbox = (index: number) => {
        setLightboxIndex(index);
        setLightboxOpen(true);
    };

    return (
        <StorefrontLayout activeCategory="Showcases">
            <SEO
                title="Showcases"
                description="Explore our premium paper and print finishing showcase."
            />

            <section className="bg-[#FAF7F2] py-16 sm:py-20 lg:py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <header className="mx-auto max-w-2xl text-center">
                        <p className="text-xs font-semibold tracking-[0.24em] text-[#800020] uppercase">
                            InkPavo craftsmanship
                        </p>
                        <h1 className="mt-4 font-serif text-4xl font-bold tracking-tight text-neutral-900 sm:text-5xl">
                            Showcases
                        </h1>
                        <p className="mt-5 text-base leading-relaxed text-neutral-600 sm:text-lg">
                            Browse examples of our paper stocks, finishes, and
                            printed work. Select any image to view it in detail.
                        </p>
                    </header>

                    {showcases.data.length === 0 ? (
                        <div className="mx-auto mt-12 max-w-xl rounded-2xl border border-dashed border-neutral-300 bg-white px-6 py-16 text-center text-neutral-600">
                            No showcase images are available yet.
                        </div>
                    ) : (
                        <>
                            <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {showcases.data.map((showcase, index) => {
                                    const showcaseNumber =
                                        (showcases.current_page - 1) * 16 +
                                        index +
                                        1;
                                    const alt = `Showcase image ${showcaseNumber}`;

                                    return (
                                        <article
                                            key={showcase.id}
                                            className="overflow-hidden rounded-2xl border border-neutral-200/80 bg-white shadow-sm transition-shadow duration-300 hover:shadow-lg"
                                        >
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openLightbox(index)
                                                }
                                                className="group block w-full cursor-zoom-in text-left focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:ring-offset-2 focus-visible:outline-none"
                                                aria-label={`Open ${alt}`}
                                            >
                                                <div className="aspect-[4/3] overflow-hidden bg-neutral-100">
                                                    <img
                                                        src={showcase.image_url}
                                                        alt={alt}
                                                        loading="lazy"
                                                        decoding="async"
                                                        className="h-full w-full object-contain transition-transform duration-500 group-hover:scale-[1.03]"
                                                    />
                                                </div>
                                            </button>

                                            {showcase.link && (
                                                <div className="border-t border-neutral-100 px-4 py-3">
                                                    <a
                                                        href={showcase.link}
                                                        target={
                                                            /^https?:\/\//i.test(
                                                                showcase.link,
                                                            )
                                                                ? '_blank'
                                                                : undefined
                                                        }
                                                        rel={
                                                            /^https?:\/\//i.test(
                                                                showcase.link,
                                                            )
                                                                ? 'noopener noreferrer'
                                                                : undefined
                                                        }
                                                        className="text-xs font-semibold text-[#800020] hover:underline"
                                                    >
                                                        Visit related link
                                                    </a>
                                                </div>
                                            )}
                                        </article>
                                    );
                                })}
                            </div>

                            {showcases.last_page > 1 && (
                                <nav
                                    aria-label="Showcase pagination"
                                    className="mt-10 flex items-center justify-center gap-4"
                                >
                                    {showcases.prev_page_url ? (
                                        <Link
                                            href={showcases.prev_page_url}
                                            preserveScroll
                                            className="rounded-full border border-[#800020] px-4 py-2 text-sm font-semibold text-[#800020] transition-colors hover:bg-[#800020] hover:text-white"
                                        >
                                            Previous
                                        </Link>
                                    ) : (
                                        <span className="rounded-full border border-neutral-200 px-4 py-2 text-sm font-semibold text-neutral-300">
                                            Previous
                                        </span>
                                    )}

                                    <span className="text-sm text-neutral-600">
                                        Page {showcases.current_page} of{' '}
                                        {showcases.last_page}
                                    </span>

                                    {showcases.next_page_url ? (
                                        <Link
                                            href={showcases.next_page_url}
                                            preserveScroll
                                            className="rounded-full border border-[#800020] px-4 py-2 text-sm font-semibold text-[#800020] transition-colors hover:bg-[#800020] hover:text-white"
                                        >
                                            Next
                                        </Link>
                                    ) : (
                                        <span className="rounded-full border border-neutral-200 px-4 py-2 text-sm font-semibold text-neutral-300">
                                            Next
                                        </span>
                                    )}
                                </nav>
                            )}
                        </>
                    )}
                </div>
            </section>

            {lightboxOpen && images.length > 0 && (
                <LightboxGallery
                    open={lightboxOpen}
                    onClose={() => setLightboxOpen(false)}
                    images={images}
                    initialIndex={lightboxIndex}
                    showThumbnails={false}
                />
            )}
        </StorefrontLayout>
    );
}
