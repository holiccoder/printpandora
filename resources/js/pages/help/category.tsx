import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
}

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    featured_image: string | null;
    published_at: string;
}

interface Faq {
    id: number;
    question: string;
    answer: string;
}

interface Props {
    category: Category;
    articles: Article[];
    faqs: Faq[];
}

function excerpt(body: string | null, length = 160): string {
    if (!body) return '';
    const text = body
        .replace(/<[^\u003e]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    return text.length > length ? text.slice(0, length) + '...' : text;
}

export default function HelpCategory({ category, articles, faqs }: Props) {
    const pageDescription =
        category.description ?? `Browse ${category.name} articles and FAQs.`;

    return (
        <StorefrontLayout activeCategory="Help & FAQs">
            <SEO
                title={`${category.name} — Help Center`}
                description={pageDescription}
            />

            <section className="border-b border-neutral-100 bg-white">
                <div className="mx-auto max-w-4xl px-4 py-12 text-center lg:py-20">
                    <div className="mb-4 text-xs font-semibold tracking-[0.15em] text-[#C9A96A] uppercase">
                        Help Center
                    </div>
                    <h1 className="font-serif text-3xl font-bold tracking-tight text-[#800020] sm:text-4xl lg:text-5xl">
                        {category.name}
                    </h1>
                    {category.description && (
                        <p className="mx-auto mt-4 max-w-2xl text-base text-neutral-600 sm:text-lg">
                            {category.description}
                        </p>
                    )}
                    <div className="mt-6">
                        <Button asChild variant="outline">
                            <Link href="/faq-and-help-center">
                                ← Back to Help Center
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <h2 className="mb-8 font-serif text-2xl font-bold text-[#800020]">
                        Articles
                    </h2>

                    {articles.length > 0 ? (
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {articles.map((article) => (
                                <Link
                                    key={article.id}
                                    href={`/faq-and-help-center/articles/${article.slug}`}
                                    className="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition-shadow hover:shadow-md"
                                >
                                    {article.featured_image && (
                                        <div className="aspect-video overflow-hidden bg-neutral-100">
                                            <img
                                                src={article.featured_image}
                                                alt={article.title}
                                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                        </div>
                                    )}
                                    <div className="flex flex-1 flex-col p-6">
                                        <h3 className="text-lg font-bold text-neutral-900 group-hover:text-[#800020]">
                                            {article.title}
                                        </h3>
                                        <p className="mt-2 flex-1 text-sm leading-relaxed text-neutral-600">
                                            {excerpt(article.excerpt)}
                                        </p>
                                        <span className="mt-4 text-xs font-medium text-[#800020]">
                                            Read article →
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <p className="text-neutral-600">
                            No articles in this category yet.
                        </p>
                    )}
                </div>
            </section>

            {faqs.length > 0 && (
                <section className="bg-white">
                    <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                        <h2 className="mb-8 text-center font-serif text-2xl font-bold text-[#800020]">
                            Frequently asked questions
                        </h2>
                        <ul className="divide-y divide-neutral-100 rounded-xl border border-neutral-200 bg-white">
                            {faqs.map((item) => (
                                <li key={item.id} className="p-5">
                                    <h3 className="text-base font-bold text-neutral-900">
                                        {item.question}
                                    </h3>
                                    <div
                                        className="help-faq-content mt-2 max-w-none"
                                        dangerouslySetInnerHTML={{
                                            __html: item.answer,
                                        }}
                                    />
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            )}
        </StorefrontLayout>
    );
}
