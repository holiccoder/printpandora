import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useContent } from '@/hooks/use-content';

export type RecentPost = {
    id: number;
    title: string;
    slug: string;
    body: string;
    featured_image: string | null;
    published_at: string;
    category: { id: number; name: string; slug: string };
    author: { id: number; name: string };
};

type Props = {
    posts: RecentPost[];
    /** Override the section heading; defaults to JSON content. */
    heading?: string;
    /** Override the eyebrow label; defaults to JSON content. */
    eyebrow?: string;
};

function excerpt(body: string, length = 110): string {
    const text = body.replace(/<[^>]+>/g, '').trim();

    if (text.length <= length) {
return text;
}

    return text.slice(0, length).replace(/\s+\S*$/, '') + '…';
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

/**
 * Homepage section showcasing the latest blog posts as a 4-up card grid.
 * Renders nothing when no posts have been published yet.
 *
 * Section labels fall back to `content/hardcoded-content.json` →
 * `home_page.recent_posts` when no prop override is given.
 */
export default function RecentPosts({ posts, heading, eyebrow }: Props) {
    const rp = useContent('home_page').recent_posts;
    const h = heading ?? rp.section_title;
    const e = eyebrow ?? rp.eyebrow;

    if (posts.length === 0) {
return null;
}

    return (
        <section className="bg-white py-14 md:py-20">
            <div className="mx-auto w-full max-w-7xl px-4 md:px-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        {e && (
                            <p className="text-xs font-semibold uppercase tracking-wider text-[#0f4c3a]">
                                {e}
                            </p>
                        )}
                        <h2 className="mt-2 text-2xl font-bold tracking-tight text-neutral-900 md:text-3xl lg:text-4xl">
                            {h}
                        </h2>
                    </div>
                    <Link
                        href={rp.view_all_href}
                        className="inline-flex items-center gap-1 text-sm font-semibold text-[#0f4c3a] hover:underline"
                    >
                        {rp.view_all_cta} <ArrowRight className="size-4" />
                    </Link>
                </div>

                <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {posts.map((post) => (
                        <PostCard key={post.id} post={post} />
                    ))}
                </div>
            </div>
        </section>
    );
}

function PostCard({ post }: { post: RecentPost }) {
    return (
        <Link
            href={`/blog/${post.slug}`}
            className="group flex flex-col overflow-hidden rounded-lg border border-neutral-200 bg-white transition-shadow hover:shadow-md"
        >
            <div className="aspect-[4/3] overflow-hidden bg-neutral-100">
                {post.featured_image ? (
                    <img
                        src={post.featured_image}
                        alt={post.title}
                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                        loading="lazy"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center text-neutral-300">
                        <svg
                            className="size-10"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={1.5}
                                d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                            />
                        </svg>
                    </div>
                )}
            </div>
            <div className="flex flex-1 flex-col p-5">
                <span className="self-start rounded-full bg-[#e6efe9] px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[#0f4c3a]">
                    {post.category.name}
                </span>
                <h3 className="mt-3 text-base font-semibold leading-snug text-neutral-900 group-hover:text-[#0f4c3a]">
                    {post.title}
                </h3>
                <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-neutral-600">
                    {excerpt(post.body)}
                </p>
                <div className="mt-auto flex items-center gap-2 pt-4 text-xs text-neutral-500">
                    <span>{post.author.name}</span>
                    <span aria-hidden>·</span>
                    <time>{formatDate(post.published_at)}</time>
                </div>
            </div>
        </Link>
    );
}