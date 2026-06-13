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
    post: Post;
    related: Post[];
}

export default function BlogShow({ post, related }: Props) {
    return (
        <>
            <SEO
                title={post.title}
                description={post.body.replace(/<[^>]+>/g, '').slice(0, 160)}
                type="article"
                image={post.featured_image ?? undefined}
                publishedAt={post.published_at}
                author={post.author.name}
            />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                        <Link
                            href={home()}
                            className="text-lg font-semibold tracking-tight"
                        >
                            PrintPandora
                        </Link>
                        <Link
                            href="/blog"
                            className="rounded-sm px-4 py-1.5 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                        >
                            Blog
                        </Link>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-12">
                    <Link
                        href="/blog"
                        className="mb-6 inline-flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        All posts
                    </Link>

                    <article>
                        <span className="mb-3 inline-block rounded-sm bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                            {post.category.name}
                        </span>

                        <h1 className="mb-4 text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                            {post.title}
                        </h1>

                        <div className="mb-8 flex items-center gap-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <span className="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                                {post.author.name}
                            </span>
                            <span>·</span>
                            <time>
                                {new Date(post.published_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                })}
                            </time>
                        </div>

                        {post.featured_image && (
                            <div className="mb-10 overflow-hidden rounded-lg">
                                <img
                                    src={post.featured_image}
                                    alt={post.title}
                                    className="w-full object-cover"
                                />
                            </div>
                        )}

                        <div
                            className="prose prose-neutral max-w-none dark:prose-invert prose-headings:font-semibold prose-a:text-amber-600 prose-img:rounded-lg prose-pre:bg-neutral-100 dark:prose-pre:bg-neutral-900"
                            dangerouslySetInnerHTML={{ __html: post.body }}
                        />
                    </article>

                    {related.length > 0 && (
                        <section className="mt-16 border-t border-[#e3e3e0] pt-10 dark:border-[#3E3E3A]">
                            <h2 className="mb-6 text-xl font-semibold">Related posts</h2>
                            <div className="grid gap-6 sm:grid-cols-3">
                                {related.map((item) => (
                                    <Link
                                        key={item.id}
                                        href={`/blog/${item.slug}`}
                                        className="group block overflow-hidden rounded-lg border border-[#e3e3e0] bg-white transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                                    >
                                        {item.featured_image ? (
                                            <div className="aspect-video overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                                <img
                                                    src={item.featured_image}
                                                    alt={item.title}
                                                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                />
                                            </div>
                                        ) : (
                                            <div className="flex aspect-video items-center justify-center bg-neutral-100 text-neutral-400 dark:bg-neutral-800 dark:text-neutral-500">
                                                <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                            </div>
                                        )}
                                        <div className="p-4">
                                            <span className="mb-1.5 inline-block rounded-sm bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                                {item.category.name}
                                            </span>
                                            <h3 className="text-sm font-semibold leading-snug group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                                {item.title}
                                            </h3>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </section>
                    )}
                </main>

                <footer className="border-t border-[#e3e3e0] bg-white py-6 text-center text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A]">
                    &copy; {new Date().getFullYear()} PrintPandora. All rights reserved.
                </footer>
            </div>
        </>
    );
}
