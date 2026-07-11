// Content sourced from `content/hardcoded-content.json` via useContent('blog_show_page').
import { Link, useForm } from '@inertiajs/react';
import {
    Facebook,
    Leaf,
    Link2,
    Linkedin,
    Twitter,
} from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Post {
    id: number;
    title: string;
    slug: string;
    body: string;
    featured_image: string | null;
    published_at: string;
    category: { id: number; name: string; slug: string };
    author: { id: number; name: string };
}

interface Props {
    post: Post;
    related: Post[];
}

/**
 * Estimate reading time from the rendered HTML body — strip tags and
 * count words at roughly 200 wpm, rounded up, never less than 1.
 */
function readingTimeMinutes(html: string): number {
    const words = html.replace(/<[^>]+>/g, ' ').trim().split(/\s+/).filter(Boolean).length;

    return Math.max(1, Math.ceil(words / 200));
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

const HUB_GREEN = '#1f3d2f';

export default function BlogShow({ post, related }: Props) {
    const c = useContent('blog_show_page');
    const minutes = readingTimeMinutes(post.body);
    const shareUrl = typeof window !== 'undefined' ? window.location.href : '';
    const shareTitle = encodeURIComponent(post.title);
    const shareEnc = encodeURIComponent(shareUrl);

    return (
        <StorefrontLayout activeCategory={c.active_category}>
            <SEO
                title={post.title}
                description={post.body.replace(/<[^>]+>/g, '').slice(0, 160)}
                type="article"
                image={post.featured_image ?? undefined}
                publishedAt={post.published_at}
                author={post.author.name}
            />

            <div className="flex flex-col bg-white text-neutral-900">
                <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10 md:py-14">
                    {/* Breadcrumbs */}
                    <nav
                        aria-label={c.breadcrumb_aria_label}
                        className="mb-8 text-sm text-neutral-500"
                    >
                        <ol className="flex flex-wrap items-center gap-2">
                            <li>
                                <Link
                                    href={c.breadcrumb_root_href}
                                    className="hover:text-neutral-900"
                                >
                                    {c.breadcrumb_root_label}
                                </Link>
                            </li>
                            <li aria-hidden className="text-neutral-400">
                                {c.breadcrumb_separator}
                            </li>
                            <li>
                                <Link
                                    href={`/blog?category=${post.category.slug}`}
                                    className="hover:text-neutral-900"
                                >
                                    {post.category.name}
                                </Link>
                            </li>
                        </ol>
                    </nav>

                    <article>
                        {/* Headline */}
                        <h1 className="max-w-4xl text-4xl font-extrabold leading-[1.1] tracking-tight text-neutral-900 md:text-5xl lg:text-[3.5rem]">
                            {post.title}
                        </h1>

                        {/* Metadata row: hub label · divider · author · read time */}
                        <div className="mt-8 flex flex-wrap items-center gap-x-5 gap-y-3 text-sm text-neutral-600">
                            <span className="inline-flex items-center gap-2 font-medium" style={{ color: HUB_GREEN }}>
                                <Leaf className="size-4" aria-hidden />
                                {c.brand_name}
                            </span>
                            <span
                                aria-hidden
                                className="hidden h-px w-16 sm:inline-block"
                                style={{ backgroundColor: HUB_GREEN }}
                            />
                            <span>
                                {c.metadata.by_prefix} <span className="font-medium text-neutral-900">{post.author.name}</span>
                            </span>
                            <span className="text-neutral-400">·</span>
                            <span>{c.metadata.read_time_template.replace('{minutes}', String(minutes))}</span>
                        </div>

                        {/* Social share */}
                        <div className="mt-4 flex items-center gap-2">
                            <ShareLink
                                label={c.share_buttons.twitter_label}
                                href={`https://twitter.com/intent/tweet?url=${shareEnc}&text=${shareTitle}`}
                                icon={<Twitter className="size-4" />}
                            />
                            <ShareLink
                                label={c.share_buttons.facebook_label}
                                href={`https://www.facebook.com/sharer/sharer.php?u=${shareEnc}`}
                                icon={<Facebook className="size-4" />}
                            />
                            <ShareLink
                                label={c.share_buttons.linkedin_label}
                                href={`https://www.linkedin.com/sharing/share-offsite/?url=${shareEnc}`}
                                icon={<Linkedin className="size-4" />}
                            />
                            <CopyLinkButton url={shareUrl} label={c.share_buttons.copy_link_label} />
                        </div>

                        {/* Hero image */}
                        {post.featured_image && (
                            <div className="mt-10 overflow-hidden rounded-2xl">
                                <img
                                    src={post.featured_image}
                                    alt={post.title}
                                    className="aspect-[16/8] w-full object-cover"
                                />
                            </div>
                        )}

                        {/* Intro hub label above the article body */}
                        <div className="mt-12 flex items-center gap-2 text-sm font-medium" style={{ color: HUB_GREEN }}>
                            <Leaf className="size-4" aria-hidden />
                            {c.brand_name}
                        </div>

                        {/* Article body — rendered HTML, styled to match the editorial feel */}
                        <div
                            className="prose prose-neutral mx-auto mt-6 max-w-3xl text-[17px] leading-[1.7] prose-headings:font-bold prose-headings:tracking-tight prose-h2:mt-12 prose-h2:text-2xl prose-h2:md:text-3xl prose-h3:mt-10 prose-h3:text-xl prose-p:my-5 prose-a:font-medium prose-a:no-underline hover:prose-a:underline prose-img:my-8 prose-img:rounded-xl prose-img:w-full"
                            // The article body comes from the CMS; render trusted HTML and
                            // recolour links to match the green hub accent.
                            style={
                                {
                                    // CSS var consumed by the prose-a override below.
                                    ['--tw-prose-links' as never]: HUB_GREEN,
                                } as React.CSSProperties
                            }
                            dangerouslySetInnerHTML={{ __html: post.body }}
                        />
                    </article>

                    {/* Contact form: "Find the card that gets you" */}
                    <FindYourCardForm />

                    {/* Newsletter block */}
                    <NewsletterCard />
                </main>

                {/* Related posts */}
                {related.length > 0 && (
                    <section className="mx-auto w-full max-w-6xl px-4 pb-16">
                        <h2 className="mb-8 text-2xl font-bold tracking-tight md:text-3xl">
                            {c.related_section.heading}
                        </h2>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {related.slice(0, 4).map((item) => (
                                <Link
                                    key={item.id}
                                    href={`/blog/${item.slug}`}
                                    className="group flex flex-col"
                                >
                                    <div className="aspect-[4/3] overflow-hidden rounded-2xl bg-neutral-100">
                                        {item.featured_image ? (
                                            <img
                                                src={item.featured_image}
                                                alt={item.title}
                                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                loading="lazy"
                                            />
                                        ) : (
                                            <div className="flex h-full w-full items-center justify-center text-neutral-300">
                                                <svg className="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 16.5V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v9a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 16.5z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 16.5L8.25 11.25l4.5 4.5 3-3L21 16.5" />
                                                </svg>
                                            </div>
                                        )}
                                    </div>
                                    <div className="mt-4">
                                        <div
                                            className="text-xs font-medium uppercase tracking-wide"
                                            style={{ color: HUB_GREEN }}
                                        >
                                            {item.category.name}
                                        </div>
                                        <h3 className="mt-2 text-lg font-bold leading-snug text-neutral-900 group-hover:underline">
                                            {item.title}
                                        </h3>
                                        <p className="mt-2 text-xs text-neutral-500">
                                            {formatDate(item.published_at)}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </StorefrontLayout>
    );
}

/* ------------------------- Sub-components ------------------------- */

function ShareLink({
    label,
    href,
    icon,
}: {
    label: string;
    href: string;
    icon: React.ReactNode;
}) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={label}
            className="inline-flex size-9 items-center justify-center rounded-full border border-neutral-300 text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1f3d2f]"
        >
            {icon}
        </a>
    );
}

function CopyLinkButton({ url, label }: { url: string; label: string }) {
    const onCopy = () => {
        if (!url || typeof navigator === 'undefined') {
return;
}

        navigator.clipboard?.writeText(url);
    };

    return (
        <button
            type="button"
            onClick={onCopy}
            aria-label={label}
            className="inline-flex size-9 items-center justify-center rounded-full border border-neutral-300 text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1f3d2f]"
        >
            <Link2 className="size-4" />
        </button>
    );
}

function FindYourCardForm() {
    const c = useContent('blog_show_page').find_your_card_form;
    const { data, setData, post, processing, wasSuccessful, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        email: '',
    });

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // The lead-capture endpoint is best-effort; if it doesn't exist yet,
        // Inertia will surface the error in `errors.email` rather than blowing up.
        post('/leads/find-your-card', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    // Pull labels in field-order from the JSON (first_name, last_name, email).
    const fieldByKey = Object.fromEntries(c.fields.map((f) => [f.id, f]));
    const fieldLabel = (id: string) => fieldByKey[id]?.label ?? id;

    return (
        <section className="mx-auto mt-20 max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight text-neutral-900 md:text-4xl">
                {c.heading}
            </h2>
            <p className="mx-auto mt-4 max-w-lg text-neutral-600">
                {c.description}
            </p>

            <form
                onSubmit={onSubmit}
                className="mx-auto mt-10 grid max-w-xl gap-4 text-left"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label htmlFor="first_name" className="text-sm font-medium text-neutral-700">
                            {fieldLabel('first_name')}
                        </label>
                        <input
                            id="first_name"
                            type="text"
                            required
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            className="mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-[#1f3d2f] focus:outline-none focus:ring-2 focus:ring-[#1f3d2f]/30"
                        />
                        {errors.first_name && (
                            <p className="mt-1 text-xs text-red-600">{errors.first_name}</p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="last_name" className="text-sm font-medium text-neutral-700">
                            {fieldLabel('last_name')}
                        </label>
                        <input
                            id="last_name"
                            type="text"
                            required
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            className="mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-[#1f3d2f] focus:outline-none focus:ring-2 focus:ring-[#1f3d2f]/30"
                        />
                        {errors.last_name && (
                            <p className="mt-1 text-xs text-red-600">{errors.last_name}</p>
                        )}
                    </div>
                </div>
                <div>
                    <label htmlFor="email" className="text-sm font-medium text-neutral-700">
                        {fieldLabel('email')}
                    </label>
                    <input
                        id="email"
                        type="email"
                        required
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-[#1f3d2f] focus:outline-none focus:ring-2 focus:ring-[#1f3d2f]/30"
                    />
                    {errors.email && (
                        <p className="mt-1 text-xs text-red-600">{errors.email}</p>
                    )}
                </div>

                <p className="text-xs text-neutral-500">
                    {c.legal_text.split(c.legal_link_text)[0]}
                    <Link href={c.legal_link_href} className="underline" style={{ color: HUB_GREEN }}>
                        {c.legal_link_text}
                    </Link>
                    {c.legal_text.split(c.legal_link_text)[1] ?? ''}
                </p>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:opacity-60"
                >
                    {processing ? c.submit_label_processing : c.submit_label}
                </button>

                {wasSuccessful && (
                    <p className="text-center text-sm text-[#1f3d2f]">
                        {c.success_message}
                    </p>
                )}
            </form>
        </section>
    );
}

function NewsletterCard() {
    const c = useContent('blog_show_page').newsletter_card;

    return (
        <section className="mx-auto mt-20 max-w-5xl">
            <div className="flex flex-col items-start justify-between gap-6 rounded-2xl bg-[#f5efe4] px-6 py-8 md:flex-row md:items-center md:gap-10 md:px-10 md:py-10">
                <div className="max-w-2xl">
                    <h3 className="text-2xl font-bold tracking-tight text-neutral-900 md:text-3xl">
                        {c.title}
                    </h3>
                    <p className="mt-2 text-neutral-700">
                        {c.description}
                    </p>
                </div>
                <Link
                    href={c.cta_href}
                    className="inline-flex shrink-0 items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold transition hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1f3d2f]"
                    style={{ color: HUB_GREEN }}
                >
                    {c.cta}
                </Link>
            </div>
        </section>
    );
}
