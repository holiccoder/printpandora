import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import SEO from '@/components/seo';
import { home } from '@/routes';

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    featured_image: string | null;
    category: { id: number; name: string; slug: string };
}

interface Props {
    product: Product;
    related: Product[];
}

export default function ShopShow({ product, related }: Props) {
    const [added, setAdded] = useState(false);

    const addToCart = () => {
        setAdded(true);
        router.post('/cart/add', {
            product_id: product.id,
            quantity: 1,
        });
        setTimeout(() => setAdded(false), 2000);
    };

    return (
        <>
            <SEO
                title={product.name}
                description={product.description?.replace(/<[^>]+>/g, '').slice(0, 160)}
                image={product.featured_image ?? undefined}
            />

            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full border-b border-[#e3e3e0] bg-white dark:border-[#3E3E3A] dark:bg-[#161615]">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                        <Link href={home()} className="text-lg font-semibold tracking-tight">
                            PrintPandora
                        </Link>
                        <nav className="flex items-center gap-4 text-sm">
                            <Link href="/shop" className="text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]">Shop</Link>
                            <Link href="/cart" className="rounded-sm bg-amber-600 px-3 py-1.5 text-white hover:bg-amber-700">Cart</Link>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-12">
                    <Link href="/shop" className="mb-6 inline-flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#EDEDEC]">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to shop
                    </Link>

                    <div className="grid gap-10 lg:grid-cols-2">
                        <div className="overflow-hidden rounded-lg border border-[#e3e3e0] bg-neutral-100 dark:border-[#3E3E3A] dark:bg-neutral-800">
                            {product.featured_image ? (
                                <img src={product.featured_image} alt={product.name} className="h-full w-full object-cover" />
                            ) : (
                                <div className="flex aspect-square items-center justify-center text-neutral-400">
                                    <svg className="h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                            )}
                        </div>

                        <div>
                            <span className="mb-2 inline-block rounded-sm bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                {product.category.name}
                            </span>
                            <h1 className="mb-4 text-3xl font-bold leading-tight">{product.name}</h1>
                            <p className="mb-6 text-3xl font-bold text-amber-600 dark:text-amber-400">
                                ${parseFloat(product.price).toFixed(2)}
                            </p>

                            {product.description && (
                                <div
                                    className="prose prose-neutral mb-8 max-w-none dark:prose-invert"
                                    dangerouslySetInnerHTML={{ __html: product.description }}
                                />
                            )}

                            <button
                                onClick={addToCart}
                                className={`w-full rounded-lg px-6 py-3 font-semibold text-white transition-colors ${
                                    added ? 'bg-green-600' : 'bg-amber-600 hover:bg-amber-700'
                                }`}
                            >
                                {added ? 'Added!' : 'Add to Cart'}
                            </button>
                        </div>
                    </div>

                    {related.length > 0 && (
                        <section className="mt-16 border-t border-[#e3e3e0] pt-10 dark:border-[#3E3E3A]">
                            <h2 className="mb-6 text-xl font-semibold">Related products</h2>
                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                {related.map((item) => (
                                    <Link
                                        key={item.id}
                                        href={`/shop/${item.slug}`}
                                        className="group block overflow-hidden rounded-lg border border-[#e3e3e0] bg-white transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
                                    >
                                        <div className="aspect-square overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                            {item.featured_image ? (
                                                <img src={item.featured_image} alt={item.name} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                            ) : (
                                                <div className="flex h-full items-center justify-center text-neutral-400">
                                                    <svg className="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>
                                        <div className="p-4">
                                            <h3 className="mb-1 text-sm font-semibold group-hover:text-amber-600 dark:group-hover:text-amber-400">{item.name}</h3>
                                            <p className="text-sm font-bold text-amber-600 dark:text-amber-400">${parseFloat(item.price).toFixed(2)}</p>
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
