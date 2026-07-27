import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useContent } from '@/hooks/use-content';

export function PopularProducts() {
    const home = useContent('home_page');
    const pp = home.popular_products;

    return (
        <section className="bg-[#FAF7F2]">
            <div className="mx-auto max-w-7xl px-4 py-16 lg:py-20">
                <div className="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
                    {/* Left column: text */}
                    <div className="flex flex-col justify-center lg:col-span-4">
                        <p className="text-xs font-semibold tracking-[0.15em] text-[#C9A96A] uppercase">
                            {pp.eyebrow}
                        </p>
                        <h2 className="mt-4 font-serif text-3xl leading-tight font-bold text-[#800020] md:text-4xl">
                            {pp.headline}
                        </h2>
                        <p className="mt-4 text-sm leading-relaxed text-[#2A2A28]/70">
                            {pp.description}
                        </p>
                        <Link
                            href={pp.cta_href}
                            className="mt-6 inline-flex items-center gap-2 text-sm font-bold tracking-wider text-[#800020] transition hover:text-[#800020]/70 uppercase"
                        >
                            {pp.cta_text}
                            <ArrowRight className="size-4" />
                        </Link>
                    </div>

                    {/* Right column: 4 product cards */}
                    <div className="lg:col-span-8">
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                            {pp.cards.map((card) => (
                                <Link
                                    key={card.title}
                                    href={card.href}
                                    className="group relative flex flex-col overflow-hidden rounded-lg bg-white shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div className="relative aspect-[4/3] overflow-hidden bg-neutral-100">
                                        <img
                                            src={card.image_url}
                                            alt={card.title}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                                        />
                                    </div>
                                    <div className="flex items-start justify-between gap-3 px-5 py-4">
                                        <div>
                                            <h3 className="text-sm font-semibold text-neutral-900 group-hover:text-[#800020]">
                                                {card.title}
                                            </h3>
                                            <p className="mt-1 text-xs text-neutral-500">
                                                {card.description}
                                            </p>
                                        </div>
                                        <span className="mt-1 flex size-8 shrink-0 items-center justify-center rounded-full bg-[#800020] text-white transition-transform group-hover:scale-110">
                                            <ArrowRight className="size-4" />
                                        </span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

export default PopularProducts;
