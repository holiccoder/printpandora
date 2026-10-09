interface PostcardSizeShowcaseItem {
    id: string;
    title: string;
    price: string;
    dimensions: string;
    aspectRatio: string;
    imageUrl: string;
}

const POSTCARD_SIZE_ITEMS: PostcardSizeShowcaseItem[] = [
    {
        id: 'standard',
        title: 'Standard Postcards',
        price: '50 postcards from $24.00',
        dimensions: '4" x 6"',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/standard.png',
    },
    {
        id: 'square',
        title: 'Square Postcards',
        price: '50 postcards from $23.00',
        dimensions: '4.72" x 4.72"',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/square.png',
    },
    {
        id: 'rack',
        title: 'Rack Cards',
        price: '50 postcards from $26.00',
        dimensions: '3.67" x 8.5"',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/rack.png',
    },
    {
        id: 'half-page',
        title: 'Half Page Postcards',
        price: '50 postcards from $36.00',
        dimensions: '5.5" x 8.5"',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/half-page.png',
    },
    {
        id: 'small',
        title: 'Small Postcards',
        price: '50 postcards from $24.00',
        dimensions: '4.13" x 5.83"',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/small.png',
    },
    {
        id: 'medium',
        title: 'Medium Postcards',
        price: '50 postcards from $27.00',
        dimensions: '5" x 7"',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/medium.png',
    },
    {
        id: 'large',
        title: 'Large Postcards',
        price: '50 postcards from $36.00',
        dimensions: '6" x 9"',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/large.png',
    },
];

function previewWidthForAspectRatio(aspectRatio: string): number {
    const [width, height] = aspectRatio
        .split('/')
        .map((value) => Number(value.trim()));

    if (
        !Number.isFinite(width) ||
        !Number.isFinite(height) ||
        width <= 0 ||
        height <= 0
    ) {
        return 220;
    }

    return Math.min(220, (220 * width) / height);
}

function PostcardSizeCard({ item }: { item: PostcardSizeShowcaseItem }) {
    return (
        <article className="group flex h-full flex-col items-center text-center">
            <div className="flex h-[220px] w-full items-center justify-center">
                <div
                    className="overflow-hidden rounded-[2px] shadow-[0_4px_12px_rgba(0,0,0,0.08)] transition-transform duration-200 group-hover:-translate-y-1"
                    style={{
                        width: `${previewWidthForAspectRatio(item.aspectRatio)}px`,
                        aspectRatio: item.aspectRatio,
                    }}
                >
                    <img
                        src={item.imageUrl}
                        alt={`${item.title} preview`}
                        loading="lazy"
                        className="h-full w-full object-cover"
                    />
                </div>
            </div>

            <div className="flex flex-1 flex-col items-center">
                <h3 className="mt-4 min-h-10 text-base leading-5 font-semibold text-[#111827]">
                    {item.title}
                </h3>
                <p className="mt-1.5 text-sm text-[#374151]">{item.price}</p>
                <p className="mt-1 text-sm text-[#6b7280]">{item.dimensions}</p>
            </div>
        </article>
    );
}

export default function PostcardSizeShowcaseSection() {
    const firstRow = POSTCARD_SIZE_ITEMS.slice(0, 4);
    const secondRow = POSTCARD_SIZE_ITEMS.slice(4);

    return (
        <section className="bg-white py-16 lg:py-20">
            <div className="mx-auto max-w-[1200px] px-6 lg:px-8">
                <header className="mx-auto mb-12 max-w-3xl text-center">
                    <h2 className="text-[28px] leading-tight font-bold tracking-tight text-[#111827] sm:text-[32px]">
                        Check out our other Postcard sizes
                    </h2>
                    <p className="mt-2 text-base text-[#6b7280]">
                        Our Postcards now come in more sizes than ever.
                    </p>
                </header>

                <div className="space-y-12 lg:space-y-14">
                    <div className="grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-4 lg:gap-x-10">
                        {firstRow.map((item) => (
                            <PostcardSizeCard key={item.id} item={item} />
                        ))}
                    </div>

                    <div className="mx-auto grid max-w-4xl grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 lg:gap-x-10">
                        {secondRow.map((item) => (
                            <PostcardSizeCard key={item.id} item={item} />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
