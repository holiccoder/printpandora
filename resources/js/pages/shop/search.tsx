import { Link } from '@inertiajs/react';
import { ArrowRight, Search as SearchIcon } from 'lucide-react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

type SearchProduct = {
    id: number;
    name: string;
    href: string;
    summary: string;
    featured_image: string | null;
    category: {
        name: string;
    };
};

type SearchResults = {
    data: SearchProduct[];
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    query: string;
    products: SearchResults;
};

export default function SearchPage({ query, products }: Props) {
    const heading = query ? `Search results for “${query}”` : 'All products';

    return (
        <StorefrontLayout>
            <SEO
                title={query ? `Search results for ${query}` : 'Search'}
                description="Find custom business cards, stickers, postcards and other print products from InkPavo."
                robots="noindex,follow"
            />

            <main className="bg-[#FDFDFC] py-12 text-neutral-900 sm:py-16">
                <div className="mx-auto max-w-7xl px-4 sm:px-6">
                    <div className="mx-auto max-w-3xl text-center">
                        <p className="text-xs font-semibold tracking-[0.18em] text-[#800020] uppercase">
                            InkPavo catalog
                        </p>
                        <h1 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                            Search products
                        </h1>
                        <form
                            action="/search"
                            method="get"
                            role="search"
                            className="relative mt-8 flex gap-2"
                        >
                            <label
                                htmlFor="search-page-query"
                                className="sr-only"
                            >
                                Search products
                            </label>
                            <SearchIcon className="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-neutral-400" />
                            <input
                                id="search-page-query"
                                name="q"
                                type="search"
                                defaultValue={query}
                                placeholder="Search business cards, stickers, postcards..."
                                className="h-12 min-w-0 flex-1 rounded-full border border-neutral-300 bg-white pr-4 pl-12 text-sm shadow-sm outline-none focus:border-[#800020] focus:ring-2 focus:ring-[#800020]/20"
                            />
                            <button
                                type="submit"
                                className="h-12 rounded-full bg-[#800020] px-5 text-sm font-semibold text-white transition hover:bg-[#650019]"
                            >
                                Search
                            </button>
                        </form>
                    </div>

                    <section
                        className="mt-12"
                        aria-labelledby="search-results-heading"
                    >
                        <div className="flex flex-col gap-2 border-b border-neutral-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
                            <h2
                                id="search-results-heading"
                                className="text-xl font-semibold sm:text-2xl"
                            >
                                {heading}
                            </h2>
                            <p className="text-sm text-neutral-500">
                                {products.total}{' '}
                                {products.total === 1 ? 'product' : 'products'}
                            </p>
                        </div>

                        {products.data.length === 0 ? (
                            <div className="py-16 text-center">
                                <h3 className="text-lg font-semibold">
                                    No products found
                                </h3>
                                <p className="mt-2 text-sm text-neutral-600">
                                    Try a broader search, such as “cards” or
                                    “stickers”.
                                </p>
                            </div>
                        ) : (
                            <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {products.data.map((product) => (
                                    <Link
                                        key={product.id}
                                        href={product.href}
                                        className="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white transition-shadow hover:shadow-lg"
                                    >
                                        <div className="aspect-[4/3] overflow-hidden bg-neutral-100">
                                            {product.featured_image ? (
                                                <img
                                                    src={product.featured_image}
                                                    alt={product.name}
                                                    loading="lazy"
                                                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex h-full items-center justify-center text-neutral-300">
                                                    <SearchIcon className="size-10" />
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex flex-1 flex-col p-5">
                                            {product.category.name && (
                                                <span className="self-start rounded-full bg-[#f3e8eb] px-2.5 py-1 text-[11px] font-semibold tracking-wide text-[#800020] uppercase">
                                                    {product.category.name}
                                                </span>
                                            )}
                                            <h3 className="mt-3 text-lg leading-snug font-semibold group-hover:text-[#800020]">
                                                {product.name}
                                            </h3>
                                            {product.summary && (
                                                <p className="mt-2 line-clamp-3 text-sm leading-relaxed text-neutral-600">
                                                    {product.summary}
                                                </p>
                                            )}
                                            <span className="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold text-[#800020]">
                                                View product
                                                <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                                            </span>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}

                        {(products.prev_page_url || products.next_page_url) && (
                            <nav
                                aria-label="Search results pagination"
                                className="mt-10 flex items-center justify-center gap-4"
                            >
                                {products.prev_page_url ? (
                                    <Link
                                        href={products.prev_page_url}
                                        className="rounded-full border border-[#800020] px-4 py-2 text-sm font-semibold text-[#800020] hover:bg-[#800020] hover:text-white"
                                    >
                                        Previous
                                    </Link>
                                ) : null}
                                <span className="text-sm text-neutral-500">
                                    Page {products.current_page} of{' '}
                                    {products.last_page}
                                </span>
                                {products.next_page_url ? (
                                    <Link
                                        href={products.next_page_url}
                                        className="rounded-full border border-[#800020] px-4 py-2 text-sm font-semibold text-[#800020] hover:bg-[#800020] hover:text-white"
                                    >
                                        Next
                                    </Link>
                                ) : null}
                            </nav>
                        )}
                    </section>
                </div>
            </main>
        </StorefrontLayout>
    );
}
