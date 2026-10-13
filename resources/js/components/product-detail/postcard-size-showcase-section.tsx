import { computeDynamicTiers } from '@/lib/pricing';
import type { DynamicPricingData } from '@/lib/pricing';

interface PostcardSize {
    code?: string;
    width?: string;
    height?: string;
    area_sq_m?: string | number;
}

interface PostcardSizeShowcaseItem {
    id: string;
    title: string;
    aspectRatio: string;
    imageUrl: string;
}

const POSTCARD_SIZE_ITEMS: PostcardSizeShowcaseItem[] = [
    {
        id: 'standard',
        title: 'Standard Postcards',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/standard.png',
    },
    {
        id: 'square',
        title: 'Square Postcards',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/square.png',
    },
    {
        id: 'rack',
        title: 'Rack Cards',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/rack.png',
    },
    {
        id: 'half-page',
        title: 'Half Page Postcards',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/half-page.png',
    },
    {
        id: 'small',
        title: 'Small Postcards',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/small.png',
    },
    {
        id: 'medium',
        title: 'Medium Postcards',
        aspectRatio: '4 / 3',
        imageUrl: '/images/products/postcards/sizes/medium.png',
    },
    {
        id: 'large',
        title: 'Large Postcards',
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

function PostcardSizeCard({
    item,
}: {
    item: PostcardSizeShowcaseItem & { price?: string; dimensions: string };
}) {
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
                {item.price && (
                    <p className="mt-1.5 text-sm text-[#374151]">{item.price}</p>
                )}
                <p className="mt-1 text-sm text-[#6b7280]">{item.dimensions}</p>
            </div>
        </article>
    );
}

export default function PostcardSizeShowcaseSection({
    sizes,
    pricingData,
}: {
    sizes: PostcardSize[];
    pricingData: DynamicPricingData;
}) {
    const items = POSTCARD_SIZE_ITEMS.flatMap((item) => {
        const size = sizes.find(
            (value) => value.code?.replaceAll('_', '-') === item.id,
        );

        if (!size?.width || !size.height) {
            return [];
        }

        const area = Number(size.area_sq_m);
        const firstTier = computeDynamicTiers(pricingData, 0, 0, 0, 0, {
            sizes: size.code ?? item.id,
            corners: 'square',
            paper_area: String(area),
        })[0];

        return [{
            ...item,
            dimensions: `${size.width}" x ${size.height}"`,
            price: firstTier && area > 0
                ? `${firstTier.qty} postcards from $${firstTier.currentPrice.toFixed(2)}`
                : undefined,
        }];
    });
    const firstRow = items.slice(0, 4);
    const secondRow = items.slice(4);

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
