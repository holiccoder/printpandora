import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: string;
    featured_image: string | null;
    category: { id: number; name: string; slug: string };
}

interface Category {
    id: number;
    name: string;
    slug: string;
    products_count: number;
}

interface Props {
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    categories: Category[];
    selectedCategory?: string | null;
}

export default function ShopIndex({
    products,
    categories,
    selectedCategory: initialCategory,
}: Props) {
    const c = useContent('shop_index_page');
    const selectedCategory = initialCategory ?? null;
    const totalProductCount = categories.reduce(
        (total, category) => total + category.products_count,
        0,
    );

    const filtered = products.data;

    return (
        <StorefrontLayout activeCategory="Business Cards">
            <SEO
                title={c.seo.title ?? 'Shop'}
                description={c.seo.description}
            />

            <div className="flex flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-12">
                    <h1 className="mb-8 text-3xl font-semibold tracking-tight">
                        {c.page_heading}
                    </h1>

                    <div className="mb-8 flex flex-wrap gap-2">
                        <Link
                            href="/shop"
                            className={`rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
                                selectedCategory === null
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-neutral-100 text-[#706f6c] hover:bg-neutral-200 dark:bg-neutral-800 dark:text-[#A1A09A]'
                            }`}
                        >
                            {c.all_button_label.replace(
                                '{count}',
                                String(totalProductCount),
                            )}
                        </Link>
                        {categories.map((cat) => (
                            <Link
                                key={cat.id}
                                href={`/shop?cat=${encodeURIComponent(cat.slug)}`}
                                className={`rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
                                    selectedCategory === cat.slug
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-neutral-100 text-[#706f6c] hover:bg-neutral-200 dark:bg-neutral-800 dark:text-[#A1A09A]'
                                }`}
                            >
                                {cat.name} ({cat.products_count})
                            </Link>
                        ))}
                    </div>

                    {filtered.length === 0 ? (
                        <p className="text-[#706f6c] dark:text-[#A1A09A]">
                            {c.empty_state}
                        </p>
                    ) : (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {filtered.map((product) => (
                                <Link
                                    key={product.id}
                                    href={`/${product.slug}`}
                                    className="group block overflow-hidden rounded-lg border border-[#e3e3e0] bg-white transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                                >
                                    <div className="aspect-square overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                        {product.featured_image ? (
                                            <img
                                                src={product.featured_image}
                                                alt={product.name}
                                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                        ) : (
                                            <div className="flex h-full items-center justify-center text-neutral-400">
                                                <svg
                                                    className="h-12 w-12"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth={1.5}
                                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                                    />
                                                </svg>
                                            </div>
                                        )}
                                    </div>
                                    <div className="p-4">
                                        <p className="mb-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                            {product.category.name}
                                        </p>
                                        <h2 className="mb-2 text-sm leading-snug font-semibold group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                            {product.name}
                                        </h2>
                                        <p className="text-lg font-bold text-amber-600 dark:text-amber-400">
                                            $
                                            {parseFloat(product.price).toFixed(
                                                2,
                                            )}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </main>
            </div>
        </StorefrontLayout>
    );
}
