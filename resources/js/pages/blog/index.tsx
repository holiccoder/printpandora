import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';
import { home } from '@/routes';

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
    posts: {
        data: Post[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}

function excerpt(body: string, length = 160): string {
    const text = body.replace(/<[^>]+>/g, '');

    return text.length > length ? text.slice(0, length) + '...' : text;
}

export default function BlogIndex({ posts }: Props) {
    return (
        <>
            <SEO
                title="Blog"
                description="Read our latest articles and updates."
                type="website"
            />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4">
                        <Link
                            href={home()}
                            className="text-lg font-semibold tracking-tight"
                        >
                            PrintPandora
                        </Link>
                        <Link
                            href={home()}
                            className="rounded-sm px-4 py-1.5 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            Home
                        </Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">Blog</h1>

                    {posts.data.length === 0 ? (
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">No posts yet.</p>
                    ) : (
                        <>
                            <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                                {posts.data.map((post) => (
                                    <Link
                                        key={post.id}
                                        href={`/blog/${post.slug}`}
                                        className="group block overflow-hidden rounded-lg border border-[#e3e3e0] bg-white transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                                    >
                                        {post.featured_image ? (
                                            <div className="aspect-video overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                                <img
                                                    src={post.featured_image}
                                                    alt={post.title}
                                                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                />
                                            </div>
                                        ) : (
                                            <div className="flex aspect-video items-center justify-center bg-neutral-100 text-neutral-400 dark:bg-neutral-800 dark:text-neutral-500">
                                                <svg className="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                            </div>
                                        )}
                                        <div className="p-5">
                                            <span className="mb-2 inline-block rounded-sm bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                                {post.category.name}
                                            </span>
                                            <h2 className="mb-2 text-lg font-semibold leading-snug group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                                {post.title}
                                            </h2>
                                            <p className="mb-3 text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                                                {excerpt(post.body)}
                                            </p>
                                            <div className="flex items-center gap-3 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                <span>{post.author.name}</span>
                                                <span>·</span>
                                                <time>
                                                    {new Date(post.published_at).toLocaleDateString('en-US', {
                                                        year: 'numeric',
                                                        month: 'long',
                                                        day: 'numeric',
                                                    })}
                                                </time>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>

                            {(posts.prev_page_url || posts.next_page_url) && (
                                <div className="mt-10 flex items-center justify-center gap-4">
                                    {posts.prev_page_url ? (
                                        <Link
                                            href={posts.prev_page_url}
                                            className="rounded-sm border border-[#e3e3e0] px-4 py-2 text-sm hover:bg-neutral-50 dark:border-[#3E3E3A] dark:hover:bg-neutral-900"
                                        >
                                            Previous
                                        </Link>
                                    ) : (
                                        <span className="rounded-sm border border-transparent px-4 py-2 text-sm text-neutral-300 dark:text-neutral-700">
                                            Previous
                                        </span>
                                    )}
                                    <span className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        Page {posts.current_page} of {posts.last_page}
                                    </span>
                                    {posts.next_page_url ? (
                                        <Link
                                            href={posts.next_page_url}
                                            className="rounded-sm border border-[#e3e3e0] px-4 py-2 text-sm hover:bg-neutral-50 dark:border-[#3E3E3A] dark:hover:bg-neutral-900"
                                        >
                                            Next
                                        </Link>
                                    ) : (
                                        <span className="rounded-sm border border-transparent px-4 py-2 text-sm text-neutral-300 dark:text-neutral-700">
                                            Next
                                        </span>
                                    )}
                                </div>
                            )}
                        </>
                    )}
                </main>

                <footer className="border-t border-[#e3e3e0] bg-white py-6 text-center text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A]">
                    &copy; {new Date().getFullYear()} PrintPandora. All rights reserved.
                </footer>
            </div>
        </>
    );
}
