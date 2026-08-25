import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Article {
    id: number;
    title: string;
    slug: string;
    body: string;
    excerpt: string | null;
    featured_image: string | null;
    published_at: string;
    category: {
        id: number;
        name: string;
        slug: string;
    };
}

interface RelatedArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    published_at: string;
}

interface Props {
    article: Article;
    related: RelatedArticle[];
}

function stripHtml(html: string): string {
    return html
        .replace(/<[^\u003e]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function readingTimeMinutes(html: string): number {
    const words = stripHtml(html).split(/\s+/).filter(Boolean).length;
    return Math.max(1, Math.ceil(words / 200));
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

export default function HelpArticle({ article, related }: Props) {
    const description = stripHtml(article.body).slice(0, 160);
    const minutes = readingTimeMinutes(article.body);

    return (
        <StorefrontLayout activeCategory="Help & FAQs">
            <SEO
                title={`${article.title} — Help Center`}
                description={description}
                type="article"
                image={article.featured_image ?? undefined}
                publishedAt={article.published_at}
            />

            <article className="bg-white">
                <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                    {/* Breadcrumb */}
                    <nav
                        aria-label="Breadcrumb"
                        className="mb-6 text-sm text-neutral-500"
                    >
                        <ol className="flex flex-wrap items-center gap-2">
                            <li>
                                <Link
                                    href="/help"
                                    className="hover:text-neutral-900"
                                >
                                    Help Center
                                </Link>
                            </li>
                            <li aria-hidden="true">/</li>
                            <li>
                                <Link
                                    href={`/help/categories/${article.category.slug}`}
                                    className="hover:text-neutral-900"
                                >
                                    {article.category.name}
                                </Link>
                            </li>
                            <li aria-hidden="true">/</li>
                            <li className="text-neutral-900">
                                {article.title}
                            </li>
                        </ol>
                    </nav>

                    <header>
                        <span className="text-xs font-semibold tracking-[0.15em] text-[#C9A96A] uppercase">
                            {article.category.name}
                        </span>
                        <h1 className="mt-3 font-serif text-3xl font-bold tracking-tight text-[#800020] sm:text-4xl lg:text-5xl">
                            {article.title}
                        </h1>
                        <p className="mt-4 text-sm text-neutral-500">
                            {formatDate(article.published_at)} · {minutes} min
                            read
                        </p>
                    </header>

                    {article.featured_image && (
                        <div className="mt-8 overflow-hidden rounded-2xl">
                            <img
                                src={article.featured_image}
                                alt={article.title}
                                className="aspect-[16/8] w-full object-cover"
                            />
                        </div>
                    )}

                    <div
                        className="help-article-content mt-10 max-w-none"
                        dangerouslySetInnerHTML={{ __html: article.body }}
                    />

                    <div className="mt-10">
                        <Button asChild variant="outline">
                            <Link
                                href={`/help/categories/${article.category.slug}`}
                            >
                                ← Back to {article.category.name}
                            </Link>
                        </Button>
                    </div>
                </div>
            </article>

            {related.length > 0 && (
                <section className="border-t border-neutral-100 bg-neutral-50">
                    <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                        <h2 className="mb-8 font-serif text-2xl font-bold text-[#800020]">
                            Related articles
                        </h2>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {related.map((item) => (
                                <Link
                                    key={item.id}
                                    href={`/help/articles/${item.slug}`}
                                    className="group block rounded-xl border border-neutral-200 bg-white p-5 transition-shadow hover:shadow-md"
                                >
                                    <h3 className="text-base font-bold text-neutral-900 group-hover:text-[#800020]">
                                        {item.title}
                                    </h3>
                                    <p className="mt-2 text-xs text-neutral-500">
                                        {formatDate(item.published_at)}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </StorefrontLayout>
    );
}
