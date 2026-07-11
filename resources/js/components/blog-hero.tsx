import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, FileText, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';

/**
 * Hero section for the blog index — the "The Drop" headline, a row of
 * pill-shaped category tabs with a trailing search field, and a single
 * featured-article carousel with image-left / copy-right layout.
 *
 * All default content (title, categories, slides, aria-labels, search
 * placeholder, date/read-time template) comes from
 * `content/hardcoded-content.json` → `blog_index_page.blog_hero`.
 */

export type BlogHeroCategory = {
    label: string;
    href: string;
    /** When true, the pill is rendered in the dark-green filled style. */
    active?: boolean;
};

export type BlogHeroSlide = {
    image: string;
    imageAlt: string;
    category: string;
    headline: string;
    excerpt: string;
    /** ISO or already-formatted publication date. */
    date: string;
    readMinutes: number;
    href: string;
    /**
     * Optional decorative sticker overlaid on the image. When present, the
     * underlying photo is blurred and a die-cut speech-bubble shape with this
     * text is rendered in sharp focus on top of it.
     */
    sticker?: { text: string };
};

type Props = {
    categories?: BlogHeroCategory[];
    slides?: BlogHeroSlide[];
    initialSlide?: number;
    /** Auto-advance interval in ms; pass 0 to disable. */
    autoPlayMs?: number;
    className?: string;
};

export function BlogHero({
    categories,
    slides,
    initialSlide = 4,
    autoPlayMs = 0,
    className,
}: Props) {
    const c = useContent('blog_index_page').blog_hero;

    const cats: BlogHeroCategory[] =
        categories ?? c.default_categories.map((x) => ({ label: x.label, href: x.href, active: x.active }));

    const slideItems: BlogHeroSlide[] =
        slides ??
        c.default_slides.map((s) => ({
            image: s.image_url,
            imageAlt: s.alt,
            category: s.category,
            headline: s.headline,
            excerpt: s.excerpt,
            date: s.date,
            readMinutes: s.read_minutes,
            href: s.href,
            sticker: s.sticker_text ? { text: s.sticker_text } : undefined,
        }));

    const total = slideItems.length;
    const [index, setIndex] = useState(
        Math.min(Math.max(initialSlide, 0), Math.max(total - 1, 0)),
    );
    const [paused, setPaused] = useState(false);

    useEffect(() => {
        if (autoPlayMs <= 0 || paused || total <= 1) {
return;
}

        const id = window.setInterval(() => {
            setIndex((i) => (i + 1) % total);
        }, autoPlayMs);

        return () => window.clearInterval(id);
    }, [autoPlayMs, paused, total]);

    if (total === 0) {
return null;
}

    const goTo = (i: number) => setIndex(((i % total) + total) % total);
    const prev = () => goTo(index - 1);
    const next = () => goTo(index + 1);
    const slide = slideItems[index];

    const readTime = (c.read_time_template ?? '{date} · {minutes} min read')
        .replace('{date}', slide.date)
        .replace('{minutes}', String(slide.readMinutes));

    return (
        <section
            className={cn('w-full bg-white', className)}
            aria-labelledby="blog-hero-title"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
        >
            <div className="mx-auto w-full max-w-6xl px-4 pt-10 pb-6 md:pt-14">
                {/* Title */}
                <h1
                    id="blog-hero-title"
                    className="text-center text-5xl font-extrabold tracking-tight text-neutral-900 md:text-6xl"
                >
                    {c.title}
                </h1>

                {/* Navigation pills + search */}
                <nav
                    aria-label={c.categories_aria_label}
                    className="mt-8 flex flex-wrap items-center justify-center gap-2 md:gap-3"
                >
                    {cats.map((cat) => (
                        <Link
                            key={cat.label}
                            href={cat.href}
                            aria-current={cat.active ? 'page' : undefined}
                            className={cn(
                                'inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] focus-visible:ring-offset-2',
                                cat.active
                                    ? 'bg-[#1f3d2f] text-white hover:bg-[#173024]'
                                    : 'border border-neutral-300 text-neutral-900 hover:border-neutral-400 hover:bg-neutral-50',
                            )}
                        >
                            {cat.label}
                        </Link>
                    ))}

                    {/* Search pill */}
                    <form
                        role="search"
                        onSubmit={(e) => e.preventDefault()}
                        className="ml-0 md:ml-2"
                    >
                        <label className="sr-only" htmlFor="blog-hero-search">
                            {c.search_label}
                        </label>
                        <div className="flex items-center gap-2 rounded-full border border-neutral-300 bg-white px-4 py-2 text-sm text-neutral-600 focus-within:border-neutral-400 focus-within:ring-2 focus-within:ring-[#0f4c3a]/30">
                            <Search className="size-4 text-neutral-500" aria-hidden />
                            <input
                                id="blog-hero-search"
                                type="search"
                                placeholder={c.search_placeholder}
                                className="w-32 border-0 bg-transparent p-0 text-sm placeholder:text-neutral-500 focus:outline-none focus:ring-0 md:w-40"
                            />
                        </div>
                    </form>
                </nav>
            </div>

            {/* Carousel */}
            <div className="relative mx-auto w-full max-w-6xl px-4 pb-12 md:pb-16">
                {total > 1 && (
                    <>
                        <button
                            type="button"
                            onClick={prev}
                            aria-label={c.prev_aria_label}
                            className="absolute left-0 top-1/2 z-10 -translate-y-1/2 -translate-x-1 rounded-full p-2 text-neutral-400 transition hover:text-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] md:-translate-x-3"
                        >
                            <ChevronLeft className="size-7" />
                        </button>
                        <button
                            type="button"
                            onClick={next}
                            aria-label={c.next_aria_label}
                            className="absolute right-0 top-1/2 z-10 -translate-y-1/2 translate-x-1 rounded-full p-2 text-neutral-400 transition hover:text-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] md:translate-x-3"
                        >
                            <ChevronRight className="size-7" />
                        </button>
                    </>
                )}

                <div className="grid grid-cols-1 gap-8 md:grid-cols-2 md:gap-12 md:items-center">
                    {/* Left — featured image */}
                    <Link
                        href={slide.href}
                        aria-label={slide.headline}
                        className="relative block overflow-hidden rounded-2xl bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0f4c3a] focus-visible:ring-offset-2"
                    >
                        <div className="aspect-[4/3] w-full overflow-hidden">
                            <img
                                key={slide.image}
                                src={slide.image}
                                alt={slide.imageAlt}
                                className={cn(
                                    'h-full w-full object-cover transition-transform duration-700 ease-out',
                                    slide.sticker && 'scale-105 blur-[2px]',
                                )}
                                loading="eager"
                            />
                        </div>
                        {slide.sticker && <StickerOverlay text={slide.sticker.text} />}
                    </Link>

                    {/* Right — article copy */}
                    <div className="flex flex-col">
                        <div className="flex items-center gap-2 text-sm text-neutral-400">
                            <FileText className="size-4" aria-hidden />
                            <span>{slide.category}</span>
                        </div>

                        <Link href={slide.href} className="group mt-4">
                            <h2 className="text-3xl font-bold leading-tight text-neutral-900 group-hover:text-[#1f3d2f] md:text-[2.5rem] md:leading-[1.15]">
                                {slide.headline}
                            </h2>
                        </Link>

                        <p className="mt-4 max-w-prose text-base text-neutral-500">
                            {slide.excerpt}
                        </p>

                        <p className="mt-6 text-sm text-neutral-400">{readTime}</p>

                        {/* Pagination dots */}
                        {total > 1 && (
                            <div
                                className="mt-8 flex items-center gap-2"
                                role="tablist"
                                aria-label={c.tablist_aria_label}
                            >
                                {slideItems.map((_, i) => (
                                    <button
                                        key={i}
                                        type="button"
                                        role="tab"
                                        aria-selected={i === index}
                                        aria-label={(c.go_to_article_template ?? 'Go to article {n}').replace('{n}', String(i + 1))}
                                        onClick={() => goTo(i)}
                                        className={cn(
                                            'size-2.5 rounded-full transition-colors',
                                            i === index
                                                ? 'bg-[#1f3d2f]'
                                                : 'bg-neutral-300 hover:bg-neutral-400',
                                        )}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

export default BlogHero;

function StickerOverlay({ text }: { text: string }) {
    return (
        <div
            className="pointer-events-none absolute inset-0 flex items-center justify-center"
            aria-hidden
        >
            <svg
                viewBox="0 0 320 240"
                className="w-[58%] max-w-[260px] drop-shadow-[0_18px_24px_rgba(0,0,0,0.25)]"
            >
                <path
                    d="M40 30 Q160 0 280 30 Q320 70 300 130 Q310 170 280 200 Q160 230 80 200 Q40 220 30 180 Q0 130 20 80 Q10 50 40 30 Z"
                    fill="#c6f24a"
                    stroke="#ffffff"
                    strokeWidth="6"
                />
                <text
                    x="160"
                    y="100"
                    textAnchor="middle"
                    fill="#0e2a18"
                    fontFamily="'Helvetica Neue', Arial, sans-serif"
                    fontSize="34"
                    fontWeight="800"
                    letterSpacing="-0.5"
                >
                    {text.split(' ')[0]}
                </text>
                <text
                    x="160"
                    y="160"
                    textAnchor="middle"
                    fill="#0e2a18"
                    fontFamily="'Brush Script MT', 'Lucida Handwriting', cursive"
                    fontSize="44"
                    fontStyle="italic"
                    fontWeight="600"
                >
                    {text.split(' ').slice(1).join(' ')}
                </text>
            </svg>
        </div>
    );
}
