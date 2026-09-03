import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#800020';
const GOLD = '#C9A96A';

function CategoryIcon({ name }: { name: string }) {
    const common = {
        viewBox: '0 0 24 24',
        fill: 'none',
        stroke: 'currentColor',
        strokeWidth: 1.6,
        strokeLinecap: 'round' as const,
        strokeLinejoin: 'round' as const,
        className: 'size-6',
    };

    switch (name) {
        case 'truck':
            return (
                <svg {...common}>
                    <rect x="1" y="3" width="15" height="13" />
                    <polygon points="16 8 20 8 23 11 23 16 16 16" />
                    <circle cx="5.5" cy="18.5" r="2.5" />
                    <circle cx="19.5" cy="18.5" r="2.5" />
                </svg>
            );
        case 'palette':
            return (
                <svg {...common}>
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 6a6 6 0 0 0 0 12c1.5 0 3-1 3-2.5S13.5 13 12 13" />
                </svg>
            );
        case 'user':
            return (
                <svg {...common}>
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            );
        case 'shopping-bag':
        default:
            return (
                <svg {...common}>
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
            );
    }
}

export default function Help() {
    const c = useContent('help_center_page') as any;
    const [query, setQuery] = useState('');

    const filteredQuestions = useMemo(() => {
        const q = query.trim().toLowerCase();

        if (!q) {
            return c.popular_questions.questions;
        }

        return c.popular_questions.questions.filter(
            (item: any) =>
                item.question.toLowerCase().includes(q) ||
                item.answer.toLowerCase().includes(q),
        );
    }, [query, c.popular_questions.questions]);

    return (
        <StorefrontLayout activeCategory="Help & FAQs">
            <SEO title={c.seo.title} description={c.seo.description} />

            {/* Hero */}
            <section className="border-b border-neutral-100 bg-white">
                <div className="mx-auto max-w-4xl px-4 py-16 text-center lg:py-24">
                    <p
                        className="mb-3 text-xs font-semibold tracking-[0.15em] uppercase"
                        style={{ color: GOLD }}
                    >
                        {c.hero.eyebrow}
                    </p>
                    <h1 className="font-serif text-4xl font-bold tracking-tight text-[#800020] sm:text-5xl">
                        {c.hero.heading}
                    </h1>
                    <p className="mx-auto mt-4 max-w-2xl text-base text-neutral-600 sm:text-lg">
                        {c.hero.description}
                    </p>
                    <form
                        role="search"
                        className="mx-auto mt-8 flex max-w-xl flex-col gap-3 sm:flex-row"
                        onSubmit={(e) => e.preventDefault()}
                    >
                        <Input
                            type="search"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            placeholder={c.hero.search_placeholder}
                            className="h-12 flex-1"
                        />
                        <Button
                            type="submit"
                            className="h-12 bg-primary px-6 text-primary-foreground"
                        >
                            {c.hero.search_button_label}
                        </Button>
                    </form>
                </div>
            </section>

            {/* Categories */}
            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {c.categories.map((category: any) => (
                            <Link
                                key={category.id}
                                href={`/faq-and-help-center#${category.id}`}
                                className="group rounded-xl border border-neutral-200 bg-white p-6 transition-shadow hover:shadow-md"
                            >
                                <div
                                    className="mb-4 inline-flex rounded-lg p-3"
                                    style={{
                                        color: ACCENT,
                                        backgroundColor: `${ACCENT}10`,
                                    }}
                                >
                                    <CategoryIcon name={category.icon} />
                                </div>
                                <h2 className="text-lg font-bold text-neutral-900 group-hover:text-[#800020]">
                                    {category.title}
                                </h2>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                    {category.description}
                                </p>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            {/* Popular questions */}
            <section className="bg-white">
                <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                    <h2 className="text-center font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                        {c.popular_questions.heading}
                    </h2>

                    {filteredQuestions.length > 0 ? (
                        <ul className="mt-8 divide-y divide-neutral-100 rounded-xl border border-neutral-200 bg-white">
                            {filteredQuestions.map((item: any) => (
                                <li
                                    key={item.question}
                                    id={item.question
                                        .toLowerCase()
                                        .replace(/\s+/g, '-')
                                        .replace(/[^a-z0-9-]/g, '')}
                                    className="p-5"
                                >
                                    <h3 className="text-base font-bold text-neutral-900">
                                        {item.question}
                                    </h3>
                                    <div
                                        className="mt-2 space-y-2 text-sm leading-relaxed text-neutral-600 [&_hr]:my-4 [&_hr]:border-neutral-200 [&_ul]:my-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5"
                                        dangerouslySetInnerHTML={{
                                            __html: item.answer,
                                        }}
                                    />
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="mt-8 text-center text-sm text-neutral-500">
                            No questions match your search.
                        </p>
                    )}
                </div>
            </section>

            {/* Contact CTA */}
            <section className="border-t border-neutral-100 bg-neutral-50">
                <div className="mx-auto max-w-3xl px-4 py-12 text-center lg:py-16">
                    <h2 className="font-serif text-2xl font-bold text-[#800020] sm:text-3xl">
                        {c.contact_cta.heading}
                    </h2>
                    <p className="mt-3 text-sm text-neutral-600 sm:text-base">
                        {c.contact_cta.description}
                    </p>
                    <div className="mt-6">
                        <Button
                            asChild
                            className="bg-primary px-6 text-primary-foreground hover:bg-primary/90"
                        >
                            <Link href={c.contact_cta.button_href}>
                                {c.contact_cta.button_label}
                            </Link>
                        </Button>
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
