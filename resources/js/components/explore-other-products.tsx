import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export function ExploreOtherProducts() {
    const cards = [
        {
            title: 'shop business cards',
            href: '/business-cards',
            image_url: '/images/home/product-business-cards.png',
        },
        {
            title: 'shop postcards',
            href: '/postcards',
            image_url: '/images/home/product-postcards.png',
        },
        {
            title: 'shop flyers and brochures',
            href: '/flyers-and-brochures',
            image_url: '/images/home/product-flyers.png',
        },
        {
            title: 'shop stickers and labels',
            href: '/stickers-and-labels',
            image_url: '/images/home/product-stickers-labels.png',
        },
    ];

    return (
        <section
            aria-labelledby="explore-other-products-heading"
            className="bg-white"
        >
            <div className="mx-auto max-w-7xl px-4 py-16 lg:py-20">
                <header className="mx-auto mb-10 max-w-2xl text-center">
                    <h2
                        id="explore-other-products-heading"
                        className="font-serif text-3xl leading-tight font-bold text-[#800020] md:text-4xl capitalize"
                    >
                        explore other products
                    </h2>
                </header>

                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <Link
                            key={card.title}
                            href={card.href}
                            className="group relative flex aspect-[4/5] flex-col overflow-hidden rounded-lg bg-neutral-100 shadow-sm transition-shadow hover:shadow-md"
                        >
                            <img
                                src={card.image_url}
                                alt={card.title}
                                loading="lazy"
                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                            />
                            {/* Overlay */}
                            <div className="absolute inset-0 bg-gradient-to-t from-neutral-900/70 via-neutral-900/20 to-transparent" />

                            {/* Card Content */}
                            <div className="absolute inset-x-0 bottom-0 flex items-center justify-between p-6">
                                <h3 className="text-base font-semibold text-white uppercase tracking-wider">
                                    {card.title}
                                </h3>
                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#800020] text-white transition-transform group-hover:scale-110">
                                    <ArrowRight className="size-4" />
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </section>
    );
}

export default ExploreOtherProducts;
