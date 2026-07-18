// Content (labels, copy, images, FAQs) sourced from `content/hardcoded-content.json`
// via useContent('product_detail_page'). Configurator state and price math stay
// local to the page; JSON drives the labels and option metadata.
import { Link, router } from '@inertiajs/react';
import { ChevronRight, Star } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import { computeDynamicTiers } from '@/lib/pricing';
import type { DynamicPricingData } from '@/lib/pricing';
import { findMatchingGallery } from '@/lib/product-options';
import type { ProductGallery } from '@/lib/product-options';

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: string;
    featured_image: string | null;
    category: { id: number; name: string; slug: string };
}

interface ProductOptions {
    subtitle?: string;
    starting_price_text?: string;
    sizes: Array<{
        name: string;
        width: string;
        height: string;
        swatch_image: string;
    }>;
    paper_finish: Array<{
        name: string;
        description: string;
        added_price: string;
        swatch_image: string;
    }>;
    corners: Array<{
        name: string;
        description: string;
        swatch_image: string;
        added_price: string;
    }>;
    special_finish: Array<{
        name: string;
        description: string;
        swatch_image: string;
    }>;
    print_code: Array<{
        name: string;
        description: string;
    }>;
    drill: Array<{
        name: string;
        swatch_image: string;
        price_add: string;
    }>;
    quantity_price_table: Array<{
        quantity: string;
        price_per_card: string;
        pack_price: string;
        pack_original_price: string;
        is_recommended: boolean;
    }>;
    galleries?: ProductGallery[];
    pricing_data?: DynamicPricingData;
}

interface Props {
    product: Product;
    related: Product[];
    productOptions?: ProductOptions;
}

/* -------------------------------------------------------------------------- */
/* Layout-only metadata (shapes, hrefs) — kept here, not in JSON              */
/* -------------------------------------------------------------------------- */

const sizeShapes: Record<string, 'rect' | 'square'> = {
    standard: 'rect',
    square: 'square',
};

const businessBlockHrefs = [
    '/shop?cat=business-services',
    '/shop?cat=business-services',
    '/contact',
];

/* -------------------------------------------------------------------------- */
/* Page                                                                       */
/* -------------------------------------------------------------------------- */

export default function ShopShow({ product, related, productOptions }: Props) {
    const c = useContent('product_detail_page') as any;
    const ACCENT = c.accent_color;

    const galleryThumbs: string[] = c.gallery_thumb_image_urls;
    const finishThumbs: string[] = c.finish_thumb_image_urls;

    const hasProductOptions = productOptions != null;

    const sizes = useMemo(
        () =>
            hasProductOptions
                ? productOptions.sizes.map((s) => ({
                      id: s.name.toLowerCase(),
                      label: s.name.charAt(0).toUpperCase() + s.name.slice(1),
                      dims: `${s.width}" x ${s.height}"`,
                      swatch: s.swatch_image,
                  }))
                : c.configurator_options.sizes,
        [hasProductOptions, productOptions, c.configurator_options.sizes],
    );

    const finishes = useMemo(
        () =>
            hasProductOptions
                ? productOptions.paper_finish.map((f) => ({
                      id: f.name.toLowerCase(),
                      label: f.name,
                      description: f.description,
                      thumb: f.swatch_image,
                  }))
                : c.configurator_options.finishes.map((f: any, i: number) => ({
                      ...f,
                      thumb: finishThumbs[i] ?? '',
                  })),
        [
            hasProductOptions,
            productOptions,
            c.configurator_options.finishes,
            finishThumbs,
        ],
    );

    const cornersList = useMemo(
        () =>
            hasProductOptions
                ? productOptions.corners.map((cn) => ({
                      id: cn.name.toLowerCase(),
                      label: cn.name,
                      swatch: cn.swatch_image,
                  }))
                : c.configurator_options.corners,
        [hasProductOptions, productOptions, c.configurator_options.corners],
    );

    const specialFinishes = useMemo(
        () =>
            hasProductOptions
                ? productOptions.special_finish
                      .filter((f) => f.name.toLowerCase() !== 'none')
                      .map((f) => ({
                          id: f.name.toLowerCase().replace(/\s+/g, '-'),
                          label:
                              f.name.charAt(0).toUpperCase() + f.name.slice(1),
                          description: f.description,
                          thumb: f.swatch_image,
                      }))
                : [],
        [hasProductOptions, productOptions],
    );

    const hasDynamicPricing =
        hasProductOptions &&
        product.slug === '300g-tongbangzhi-uv' &&
        productOptions.pricing_data != null;

    const dynamicStartQty = hasDynamicPricing
        ? productOptions.pricing_data!.rectangle.startQuantity
        : null;

    // "X cards from $Y" derived from data: X = startQuantity from the
    // pricing JSON, Y = subtotal (currentPrice) of the first row of the
    // quantity pricing table under the default option configuration.
    const startingPriceText = useMemo(() => {
        if (hasDynamicPricing) {
            const firstTier = computeDynamicTiers(
                productOptions.pricing_data!,
                0, // default size
                0, // default paper finish
                0, // default corners
                0, // default special finish
            )[0];

            if (firstTier) {
                return `${firstTier.qty} cards from $${firstTier.currentPrice}`;
            }
        }

        return productOptions?.starting_price_text;
    }, [hasDynamicPricing, productOptions]);

    const staticRecommendedQty = (() => {
        if (hasDynamicPricing) {
            return null;
        }

        const tiers = hasProductOptions
            ? productOptions.quantity_price_table.map((q) => ({
                  qty: parseInt(q.quantity, 10),
                  pricePerCard: parseFloat(q.price_per_card),
                  currentPrice: parseFloat(q.pack_price),
                  originalPrice: q.pack_original_price
                      ? parseFloat(q.pack_original_price)
                      : null,
                  recommended: q.is_recommended,
              }))
            : c.configurator_options.quantity_tiers.map((t: any) => {
                  const total =
                      parseFloat(product.price) * t.multiplier * (1 - t.save);

                  return {
                      qty: t.qty,
                      pricePerCard: total / t.qty,
                      currentPrice: total,
                      originalPrice:
                          t.save > 0
                              ? parseFloat(product.price) * t.multiplier
                              : null,
                      recommended: !!t.recommended,
                      badge: t.badge,
                  };
              });

        const rec = tiers.find((t: any) => t.recommended) ?? tiers[0];

        return rec?.qty ?? null;
    })();

    const RECOMMENDED_QTY = dynamicStartQty ?? staticRecommendedQty;

    const configuredGalleries = useMemo(
        () => productOptions?.galleries ?? [],
        [productOptions],
    );

    const fallbackGallery = useMemo<ProductGallery>(
        () => ({
            id: 'fallback',
            is_default: true,
            match: {},
            images: galleryThumbs,
        }),
        [galleryThumbs],
    );

    const [selectedSize, setSelectedSize] = useState<string | null>(
        hasDynamicPricing ? null : sizes[0].id,
    );
    const [selectedFinish, setSelectedFinish] = useState<string | null>(
        hasDynamicPricing ? null : finishes[0].id,
    );
    const [selectedCorners, setSelectedCorners] = useState<string | null>(
        hasDynamicPricing ? null : cornersList[0].id,
    );
    const [selectedSpecialFinish, setSelectedSpecialFinish] = useState<
        string | null
    >(hasDynamicPricing ? null : (specialFinishes[0]?.id ?? 'none'));
    const [selectedQty, setSelectedQty] = useState<number | null>(
        hasDynamicPricing ? null : RECOMMENDED_QTY,
    );
    const [selectedThumbnail, setSelectedThumbnail] = useState<string | null>(
        null,
    );
    const [added, setAdded] = useState(false);
    const [designModal, setDesignModal] = useState<
        'canva' | 'upload' | 'design-for-you' | null
    >(null);

    const hasSelection =
        selectedSize != null &&
        selectedFinish != null &&
        selectedCorners != null &&
        selectedSpecialFinish != null;

    const defaultOptions = useMemo<Record<string, string>>(
        () => ({
            sizes: sizes[0].id,
            paper_finish: finishes[0].id,
            corners: cornersList[0].id,
            special_finish: specialFinishes[0]?.id ?? 'none',
            quantity: String(RECOMMENDED_QTY ?? ''),
        }),
        [sizes, finishes, cornersList, specialFinishes, RECOMMENDED_QTY],
    );

    const selectedOptions = useMemo<Record<string, string>>(() => {
        if (!hasSelection) {
            return defaultOptions;
        }

        return {
            sizes: selectedSize,
            paper_finish: selectedFinish,
            corners: selectedCorners,
            special_finish: selectedSpecialFinish ?? 'none',
            quantity: String(selectedQty ?? RECOMMENDED_QTY),
        };
    }, [
        hasSelection,
        defaultOptions,
        selectedSize,
        selectedFinish,
        selectedCorners,
        selectedSpecialFinish,
        selectedQty,
        RECOMMENDED_QTY,
    ]);

    const activeGallery = useMemo(() => {
        if (configuredGalleries.length > 0) {
            const matched = findMatchingGallery(
                configuredGalleries,
                selectedOptions,
                defaultOptions,
            );

            if (matched) {
                return matched;
            }
        }

        return fallbackGallery;
    }, [configuredGalleries, selectedOptions, defaultOptions, fallbackGallery]);

    const activeImage = useMemo(() => {
        if (
            selectedThumbnail &&
            activeGallery.images.includes(selectedThumbnail)
        ) {
            return selectedThumbnail;
        }

        return (
            activeGallery.images[0] ??
            product.featured_image ??
            galleryThumbs[0]
        );
    }, [
        selectedThumbnail,
        activeGallery,
        product.featured_image,
        galleryThumbs,
    ]);

    const quantityTiers = useMemo(() => {
        if (hasDynamicPricing && hasSelection) {
            const sizeIndex = sizes.findIndex(
                (s: any) => s.id === selectedSize,
            );
            const finishIndex = finishes.findIndex(
                (f: any) => f.id === selectedFinish,
            );
            const cornersIndex = cornersList.findIndex(
                (cn: any) => cn.id === selectedCorners,
            );
            const specialIndex = specialFinishes.findIndex(
                (f: any) => f.id === selectedSpecialFinish,
            );

            return computeDynamicTiers(
                productOptions.pricing_data!,
                sizeIndex,
                finishIndex,
                cornersIndex,
                specialIndex >= 0 ? specialIndex : 0,
            );
        }

        return hasProductOptions
            ? productOptions.quantity_price_table.map((q) => ({
                  qty: parseInt(q.quantity, 10),
                  pricePerCard: parseFloat(q.price_per_card),
                  currentPrice: Math.round(parseFloat(q.pack_price)),
                  originalPrice: q.pack_original_price
                      ? Math.round(parseFloat(q.pack_original_price))
                      : null,
                  recommended: q.is_recommended,
              }))
            : c.configurator_options.quantity_tiers.map((t: any) => {
                  const total = Math.round(
                      parseFloat(product.price) * t.multiplier * (1 - t.save),
                  );

                  return {
                      qty: t.qty,
                      pricePerCard: total / t.qty,
                      currentPrice: total,
                      originalPrice:
                          t.save > 0
                              ? Math.round(
                                    parseFloat(product.price) * t.multiplier,
                                )
                              : null,
                      recommended: !!t.recommended,
                      badge: t.badge,
                  };
              });
    }, [
        hasDynamicPricing,
        hasSelection,
        hasProductOptions,
        selectedSize,
        selectedFinish,
        selectedCorners,
        selectedSpecialFinish,
        sizes,
        finishes,
        cornersList,
        specialFinishes,
        productOptions,
        product.price,
        c.configurator_options,
    ]);

    const tier = useMemo(() => {
        if (!hasSelection) {
            return null;
        }

        return (
            quantityTiers.find((t: any) => t.qty === selectedQty) ??
            quantityTiers[0] ??
            null
        );
    }, [selectedQty, quantityTiers, hasSelection]);

    const fullPrice = tier?.originalPrice ?? tier?.currentPrice ?? 0;
    const finalPrice = tier?.currentPrice ?? 0;

    function selectOption(
        group: 'sizes' | 'paper_finish' | 'corners' | 'special_finish',
        value: string,
    ) {
        if (!hasSelection) {
            setSelectedSize(sizes[0].id);
            setSelectedFinish(finishes[0].id);
            setSelectedCorners(cornersList[0].id);
            setSelectedSpecialFinish(specialFinishes[0]?.id ?? 'none');

            if (hasDynamicPricing) {
                setSelectedQty(dynamicStartQty);
            }
        }

        switch (group) {
            case 'sizes':
                setSelectedSize(value);
                break;
            case 'paper_finish':
                setSelectedFinish(value);
                break;
            case 'corners':
                setSelectedCorners(value);
                break;
            case 'special_finish':
                setSelectedSpecialFinish(value);
                break;
        }
    }

    const sizeLabel =
        sizes.find((s: any) => s.id === selectedSize)?.label ?? '';
    const finishLabel =
        finishes.find((f: any) => f.id === selectedFinish)?.label ?? '';
    const cornersLabel =
        cornersList.find((cn: any) => cn.id === selectedCorners)?.label ?? '';
    const specialFinishLabel =
        specialFinishes.find((f: any) => f.id === selectedSpecialFinish)
            ?.label ?? '';
    const showSpecialFinishInSummary = specialFinishes.length > 0;

    const addToCart = () => {
        setAdded(true);
        router.post(
            '/cart/add',
            {
                product_id: product.id,
                options: selectedOptions,
            },
            {
                preserveScroll: true,
                onFinish: () => setTimeout(() => setAdded(false), 2000),
            },
        );
    };

    const breadcrumbs: string[] = c.breadcrumbs;
    const featureChips: string[] = c.feature_chips;
    const summaryLabels: string[] = c.order_summary.labels;

    return (
        <StorefrontLayout>
            <SEO
                title={product.name}
                description={product.description
                    ?.replace(/<[^>]+>/g, '')
                    .slice(0, 160)}
                image={product.featured_image ?? undefined}
            />

            {/* breadcrumbs */}
            <nav
                aria-label="Breadcrumb"
                className="border-b border-neutral-100 bg-white"
            >
                <ol className="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 text-xs text-neutral-500">
                    <li>
                        <Link href="/" className="hover:text-neutral-900">
                            {breadcrumbs[0]}
                        </Link>
                    </li>
                    <ChevronRight className="size-3.5" />
                    <li>
                        <Link
                            href={`/shop?cat=${product.category.slug}`}
                            className="hover:text-neutral-900"
                        >
                            {product.category.name}
                        </Link>
                    </li>
                    <ChevronRight className="size-3.5" />
                    <li className="text-neutral-900">{product.name}</li>
                </ol>
            </nav>

            {/* 1. configurator */}
            <section className="bg-white">
                <div className="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 lg:grid-cols-2">
                    {/* gallery */}
                    <div className="lg:sticky lg:top-[172px] lg:self-start">
                        <div className="overflow-hidden rounded-lg bg-neutral-100">
                            <img
                                src={activeImage}
                                alt={product.name}
                                className="aspect-[5/4] w-full object-cover"
                            />
                        </div>
                        <div className="mt-3 grid grid-cols-4 gap-2">
                            {activeGallery.images.map((src) => (
                                <button
                                    key={src}
                                    type="button"
                                    onClick={() => setSelectedThumbnail(src)}
                                    className={`overflow-hidden rounded-md border-2 transition-colors ${
                                        activeImage === src
                                            ? 'border-[#0f4c3a]'
                                            : 'border-transparent hover:border-neutral-200'
                                    }`}
                                >
                                    <img
                                        src={src}
                                        alt=""
                                        className="aspect-square w-full object-cover"
                                    />
                                </button>
                            ))}
                        </div>
                        <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-neutral-700">
                            <FeatureChip
                                accent={ACCENT}
                                icon={
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.6"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        className="size-4"
                                    >
                                        <rect
                                            x="3"
                                            y="6"
                                            width="13"
                                            height="9"
                                            rx="1"
                                        />
                                        <rect
                                            x="8"
                                            y="11"
                                            width="13"
                                            height="9"
                                            rx="1"
                                        />
                                    </svg>
                                }
                                label={featureChips[0]}
                            />
                            <FeatureChip
                                accent={ACCENT}
                                icon={
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.6"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        className="size-4"
                                    >
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 3v18M3 12h18" />
                                    </svg>
                                }
                                label={featureChips[1]}
                            />
                        </div>
                    </div>

                    {/* options */}
                    <div>
                        <p className="mb-2 text-xs font-semibold tracking-wide text-neutral-500 uppercase">
                            {product.category.name}
                        </p>
                        <h1 className="text-3xl leading-tight font-bold text-neutral-900 lg:text-4xl">
                            {product.name}
                        </h1>
                        <div className="mt-3 flex items-center gap-3">
                            <Stars
                                value={4.6}
                                accent={ACCENT}
                                ariaLabel={String(
                                    c.stars_aria_label_template,
                                ).replace('{value}', '4.6')}
                            />
                            <span className="text-sm text-neutral-500">
                                {c.stars_rating_text}
                            </span>
                        </div>
                        <p className="mt-4 text-sm leading-relaxed text-neutral-700">
                            {productOptions?.subtitle ?? c.product_subtitle}
                        </p>
                        {startingPriceText && (
                            <p className="mt-2 text-sm font-semibold text-neutral-900">
                                {startingPriceText}
                            </p>
                        )}

                        <OptionGroup label={c.configurator_labels.size}>
                            <div className="grid grid-cols-2 gap-3">
                                {sizes.map((s: any) => {
                                    const shape = sizeShapes[s.id] ?? 'rect';
                                    const hasSwatch = !!s.swatch;

                                    return (
                                        <ChoiceTile
                                            key={s.id}
                                            active={selectedSize === s.id}
                                            onClick={() =>
                                                selectOption('sizes', s.id)
                                            }
                                        >
                                            <div className="flex h-16 items-center justify-center">
                                                {hasSwatch ? (
                                                    <img
                                                        src={s.swatch}
                                                        alt=""
                                                        className="h-full max-h-16 rounded-sm object-contain"
                                                    />
                                                ) : (
                                                    <span
                                                        className={`block rounded-sm border-2 ${
                                                            selectedSize ===
                                                            s.id
                                                                ? 'border-[#0f4c3a] bg-[#0f4c3a]/5'
                                                                : 'border-neutral-300 bg-neutral-50'
                                                        } ${shape === 'rect' ? 'h-8 w-14' : 'size-10'}`}
                                                    />
                                                )}
                                            </div>
                                            <p className="mt-2 text-sm font-semibold">
                                                {s.label}
                                            </p>
                                            <p className="text-xs text-neutral-500">
                                                {s.dims}
                                            </p>
                                        </ChoiceTile>
                                    );
                                })}
                            </div>
                        </OptionGroup>

                        <OptionGroup label={c.configurator_labels.paper_finish}>
                            <div className="grid grid-cols-2 gap-3">
                                {finishes.map((f: any) => (
                                    <ChoiceTile
                                        key={f.id}
                                        active={selectedFinish === f.id}
                                        onClick={() =>
                                            selectOption('paper_finish', f.id)
                                        }
                                    >
                                        <img
                                            src={f.thumb}
                                            alt=""
                                            className="aspect-[3/2] w-full rounded-sm object-cover"
                                        />
                                        <p className="mt-2 text-sm font-semibold">
                                            {f.label}
                                        </p>
                                        {f.description && (
                                            <p className="text-xs text-neutral-500">
                                                {f.description}
                                            </p>
                                        )}
                                    </ChoiceTile>
                                ))}
                            </div>
                        </OptionGroup>

                        <OptionGroup label={c.configurator_labels.corners}>
                            <div className="grid grid-cols-2 gap-3">
                                {cornersList.map((cn: any) => {
                                    const hasSwatch = !!cn.swatch;

                                    return (
                                        <ChoiceTile
                                            key={cn.id}
                                            active={selectedCorners === cn.id}
                                            onClick={() =>
                                                selectOption('corners', cn.id)
                                            }
                                        >
                                            <div className="flex h-16 items-center justify-center">
                                                {hasSwatch ? (
                                                    <div
                                                        className="h-12 w-12 text-neutral-700"
                                                        dangerouslySetInnerHTML={{
                                                            __html: cn.swatch,
                                                        }}
                                                    />
                                                ) : (
                                                    <span
                                                        className={`block h-8 w-14 border-2 ${
                                                            cn.id === 'rounded'
                                                                ? 'rounded-lg'
                                                                : 'rounded-sm'
                                                        } ${
                                                            selectedCorners ===
                                                            cn.id
                                                                ? 'border-[#0f4c3a] bg-[#0f4c3a]/5'
                                                                : 'border-neutral-300 bg-neutral-50'
                                                        }`}
                                                    />
                                                )}
                                            </div>
                                            <p className="mt-2 text-sm font-semibold">
                                                {cn.label}
                                            </p>
                                        </ChoiceTile>
                                    );
                                })}
                            </div>
                        </OptionGroup>

                        {specialFinishes.length > 0 && (
                            <OptionGroup label="Special finish">
                                <div className="grid grid-cols-2 gap-3">
                                    {specialFinishes.map((f: any) => (
                                        <ChoiceTile
                                            key={f.id}
                                            active={
                                                selectedSpecialFinish === f.id
                                            }
                                            onClick={() =>
                                                selectOption(
                                                    'special_finish',
                                                    f.id,
                                                )
                                            }
                                        >
                                            <img
                                                src={f.thumb}
                                                alt=""
                                                className="aspect-[3/2] w-full rounded-sm object-cover"
                                            />
                                            <p className="mt-2 text-sm font-semibold">
                                                {f.label}
                                            </p>
                                            {f.description && (
                                                <p className="text-xs text-neutral-500">
                                                    {f.description}
                                                </p>
                                            )}
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        <OptionGroup label={c.configurator_labels.quantity}>
                            {hasSelection ? (
                                <div className="overflow-hidden rounded-md border border-neutral-200">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="bg-neutral-50 text-left text-xs tracking-wide text-neutral-500 uppercase">
                                                <th className="px-4 py-2 font-medium">
                                                    {
                                                        c
                                                            .quantity_table_headers[0]
                                                    }
                                                </th>
                                                <th className="px-4 py-2 font-medium">
                                                    {
                                                        c
                                                            .quantity_table_headers[1]
                                                    }
                                                </th>
                                                <th className="px-4 py-2 font-medium">
                                                    {
                                                        c
                                                            .quantity_table_headers[2]
                                                    }
                                                </th>
                                                <th className="px-4 py-2 font-medium">
                                                    {
                                                        c
                                                            .quantity_table_headers[3]
                                                    }
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-neutral-100">
                                            {quantityTiers.map((t: any) => {
                                                const recommended =
                                                    !!t.recommended;
                                                const active =
                                                    selectedQty === t.qty;
                                                const was = t.originalPrice;
                                                const now = t.currentPrice;

                                                return (
                                                    <tr
                                                        key={t.qty}
                                                        onClick={() =>
                                                            setSelectedQty(
                                                                t.qty,
                                                            )
                                                        }
                                                        className={`cursor-pointer transition-colors ${
                                                            active
                                                                ? 'bg-[#0f4c3a]/5'
                                                                : recommended
                                                                  ? 'bg-amber-50/60 hover:bg-amber-50'
                                                                  : 'hover:bg-neutral-50'
                                                        }`}
                                                    >
                                                        <td className="px-4 py-3">
                                                            <label className="flex items-center gap-3">
                                                                <input
                                                                    type="radio"
                                                                    name="qty"
                                                                    checked={
                                                                        active
                                                                    }
                                                                    onChange={() =>
                                                                        setSelectedQty(
                                                                            t.qty,
                                                                        )
                                                                    }
                                                                    className="size-4 accent-[#0f4c3a]"
                                                                />
                                                                <span className="font-semibold text-neutral-900">
                                                                    {t.qty}
                                                                </span>
                                                                {recommended && (
                                                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-amber-800 uppercase">
                                                                        {t.badge ??
                                                                            'Recommended'}
                                                                    </span>
                                                                )}
                                                            </label>
                                                        </td>
                                                        <td className="px-4 py-3 text-neutral-500">
                                                            $
                                                            {t.pricePerCard.toFixed(
                                                                3,
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3 text-neutral-400">
                                                            {was != null &&
                                                            was > now ? (
                                                                <span className="line-through">
                                                                    $
                                                                    {Math.round(
                                                                        was,
                                                                    ).toFixed(
                                                                        0,
                                                                    )}
                                                                </span>
                                                            ) : (
                                                                <span>—</span>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3 font-semibold text-neutral-900">
                                                            $
                                                            {Math.round(
                                                                now,
                                                            ).toFixed(0)}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-sm text-neutral-500">
                                    Select an option above to see pricing.
                                </p>
                            )}
                        </OptionGroup>

                        {/* delivery callout */}
                        <div className="mt-6 flex items-start gap-3 rounded-md border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm">
                            <span style={{ color: ACCENT }}>
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.6"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="mt-0.5 size-5"
                                >
                                    <rect
                                        x="2"
                                        y="7"
                                        width="13"
                                        height="10"
                                        rx="1"
                                    />
                                    <path d="M15 10h4l3 3v4h-7" />
                                    <circle cx="6.5" cy="17.5" r="1.5" />
                                    <circle cx="17.5" cy="17.5" r="1.5" />
                                </svg>
                            </span>
                            <div>
                                <p className="font-semibold text-neutral-900">
                                    {c.delivery_callout.title}
                                </p>
                                <p className="text-neutral-600">
                                    {c.delivery_callout.subtitle}
                                </p>
                            </div>
                        </div>

                        {/* order summary */}
                        {hasSelection && tier && (
                            <div className="mt-6 rounded-md border border-neutral-200 bg-white px-4 py-4">
                                <p className="mb-3 text-xs font-semibold tracking-wide text-neutral-500 uppercase">
                                    {c.order_summary.heading}
                                </p>
                                <dl className="grid grid-cols-2 gap-y-1 text-sm">
                                    <dt className="text-neutral-500">
                                        {summaryLabels[0]}
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {finishLabel}
                                    </dd>
                                    <dt className="text-neutral-500">
                                        {summaryLabels[1]}
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {sizeLabel}
                                    </dd>
                                    <dt className="text-neutral-500">
                                        {summaryLabels[2]}
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {selectedQty}
                                    </dd>
                                    <dt className="text-neutral-500">
                                        {summaryLabels[3]}
                                    </dt>
                                    <dd className="text-right font-medium capitalize">
                                        {cornersLabel}
                                    </dd>
                                    {showSpecialFinishInSummary && (
                                        <>
                                            <dt className="text-neutral-500">
                                                Special finish
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {specialFinishLabel}
                                            </dd>
                                        </>
                                    )}
                                </dl>
                                <div className="mt-4 flex items-baseline justify-between border-t border-neutral-100 pt-4">
                                    <span className="text-sm text-neutral-500">
                                        {c.order_summary.total_label}
                                    </span>
                                    <div className="text-right">
                                        {tier.originalPrice != null &&
                                            tier.originalPrice >
                                                tier.currentPrice && (
                                                <span className="mr-2 text-sm text-neutral-400 line-through">
                                                    $
                                                    {Math.round(
                                                        fullPrice,
                                                    ).toFixed(0)}
                                                </span>
                                            )}
                                        <span className="text-2xl font-bold text-neutral-900">
                                            ${Math.round(finalPrice).toFixed(0)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* design CTA */}
                        <div className="mt-8">
                            <h2 className="mb-3 text-base font-bold text-neutral-900">
                                {c.design_cta.heading}
                            </h2>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <DesignChoice
                                    title={c.design_cta.options[0].title}
                                    body={c.design_cta.options[0].body}
                                    accent={ACCENT}
                                    onClick={() => setDesignModal('canva')}
                                    icon={
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.6"
                                            className="size-7"
                                        >
                                            <rect
                                                x="3"
                                                y="3"
                                                width="7"
                                                height="9"
                                                rx="1"
                                            />
                                            <rect
                                                x="14"
                                                y="3"
                                                width="7"
                                                height="5"
                                                rx="1"
                                            />
                                            <rect
                                                x="14"
                                                y="12"
                                                width="7"
                                                height="9"
                                                rx="1"
                                            />
                                            <rect
                                                x="3"
                                                y="16"
                                                width="7"
                                                height="5"
                                                rx="1"
                                            />
                                        </svg>
                                    }
                                />
                                <DesignChoice
                                    title={c.design_cta.options[1].title}
                                    body={c.design_cta.options[1].body}
                                    accent={ACCENT}
                                    onClick={() => setDesignModal('upload')}
                                    icon={
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.6"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className="size-7"
                                        >
                                            <path d="M3 17l6-6 4 4 8-8" />
                                            <path d="M17 7h4v4" />
                                        </svg>
                                    }
                                />
                                <DesignChoice
                                    title={c.design_cta.options[2].title}
                                    body={c.design_cta.options[2].body}
                                    accent={ACCENT}
                                    onClick={() =>
                                        setDesignModal('design-for-you')
                                    }
                                    icon={
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.6"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className="size-7"
                                        >
                                            <path d="M12 3v12" />
                                            <path d="M7 8l5-5 5 5" />
                                            <path d="M5 21h14" />
                                        </svg>
                                    }
                                />
                            </div>
                        </div>

                        {/* design modals */}
                        <CanvaDesignModal
                            open={designModal === 'canva'}
                            onOpenChange={(open) =>
                                setDesignModal(open ? 'canva' : null)
                            }
                        />
                        <DesignServiceFormModal
                            open={designModal === 'upload'}
                            onOpenChange={(open) =>
                                setDesignModal(open ? 'upload' : null)
                            }
                            title="Upload a full design (free)"
                            description="Send us your print-ready artwork and we'll prepare a free proof before printing."
                        />
                        <DesignServiceFormModal
                            open={designModal === 'design-for-you'}
                            onOpenChange={(open) =>
                                setDesignModal(open ? 'design-for-you' : null)
                            }
                            title="Design for you"
                            description="Tell us about your brand and what you need — our designers will create the artwork for you."
                        />

                        <Button
                            onClick={addToCart}
                            disabled={added || !hasSelection || !tier}
                            className={`mt-6 h-12 w-full text-base font-semibold text-primary-foreground ${added ? 'bg-primary/90' : 'bg-primary hover:bg-primary/90'}`}
                        >
                            {added
                                ? c.added_to_cart_button
                                : hasSelection && tier
                                  ? String(
                                        c.add_to_cart_button_template,
                                    ).replace(
                                        '{price}',
                                        Math.round(finalPrice).toFixed(0),
                                    )
                                  : 'Select options'}
                        </Button>
                    </div>
                </div>
            </section>

            {/* 2. design guidelines */}
            <section className="bg-neutral-100">
                <div className="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-12 lg:grid-cols-2 lg:py-16">
                    <div>
                        <h2 className="text-2xl font-bold text-neutral-900">
                            {c.design_guidelines.heading}
                        </h2>
                        <p className="mt-3 text-sm leading-relaxed text-neutral-700">
                            {c.design_guidelines.description}
                        </p>
                        <ul className="mt-6 space-y-2 text-sm text-neutral-700">
                            {c.design_guidelines.bullets.map(
                                (bullet: string) => (
                                    <li key={bullet} className="flex gap-2">
                                        <Bullet accent={ACCENT} /> {bullet}
                                    </li>
                                ),
                            )}
                        </ul>
                    </div>
                    <div className="relative flex items-center justify-center">
                        {/* Bleed/safe area diagram */}
                        <div className="relative aspect-[5/3] w-full max-w-md rounded-md border-4 border-pink-300 bg-white p-4">
                            <div className="h-full w-full rounded-sm border-2 border-dashed border-neutral-300 p-3">
                                <div className="flex h-full w-full items-center justify-center rounded-sm bg-neutral-50">
                                    <span className="text-xs tracking-wider text-neutral-400 uppercase">
                                        {c.design_guidelines.safe_area_label}
                                    </span>
                                </div>
                            </div>
                            <span className="absolute -top-3 left-3 bg-neutral-100 px-2 text-[10px] font-semibold tracking-wider text-pink-500 uppercase">
                                {c.design_guidelines.bleed_area_label}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            {/* 3. lifestyle banner */}
            <section
                className="relative h-[320px] bg-cover bg-center text-white sm:h-[420px]"
                style={{
                    backgroundImage: `linear-gradient(to right, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0.1) 60%), url(${c.lifestyle_banner.image_url})`,
                }}
            >
                <div className="mx-auto flex h-full max-w-7xl items-center px-4">
                    <div className="max-w-md">
                        <p className="text-xs font-semibold tracking-[0.25em] uppercase">
                            {c.lifestyle_banner.eyebrow}
                        </p>
                        <h2 className="mt-3 text-3xl leading-tight font-bold sm:text-4xl">
                            {c.lifestyle_banner.heading}
                        </h2>
                        <p className="mt-3 text-sm leading-relaxed opacity-90">
                            {c.lifestyle_banner.body}
                        </p>
                    </div>
                </div>
            </section>

            {/* 4. paper stocks */}
            <section className="bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <header className="mb-8 max-w-2xl">
                        <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                            {c.paper_stocks_section.heading}
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            {c.paper_stocks_section.subtitle}
                        </p>
                    </header>
                    <ul className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {c.paper_stocks_section.stocks.map((stock: any) => (
                            <li key={stock.id}>
                                <Link
                                    href={`/paper-stocks/${stock.id}`}
                                    className="group block"
                                >
                                    <div className="overflow-hidden rounded-md bg-neutral-100">
                                        <img
                                            src={stock.image_url}
                                            alt={stock.name}
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    </div>
                                    <h3 className="mt-3 text-base font-bold text-neutral-900">
                                        {stock.name}
                                    </h3>
                                    <p className="mt-1 text-sm text-neutral-600">
                                        {stock.blurb}
                                    </p>
                                    <span
                                        className="mt-2 inline-flex items-center gap-1 text-sm font-semibold group-hover:underline"
                                        style={{ color: ACCENT }}
                                    >
                                        {c.paper_stocks_section.cta}{' '}
                                        <ChevronRight className="size-3.5" />
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* 5. printfinity banner */}
            <section
                className="relative bg-cover bg-center"
                style={{
                    backgroundImage: `linear-gradient(rgba(15,76,58,0.55), rgba(15,76,58,0.55)), url(${c.printfinity_banner.image_url})`,
                }}
            >
                <div className="mx-auto max-w-7xl px-4 py-16 lg:py-24">
                    <div className="max-w-md rounded-lg bg-white p-6 shadow-md sm:p-8">
                        <p
                            className="text-xs font-semibold tracking-[0.25em] uppercase"
                            style={{ color: ACCENT }}
                        >
                            {c.printfinity_banner.eyebrow}
                        </p>
                        <h2 className="mt-3 text-2xl font-bold text-neutral-900 sm:text-3xl">
                            {c.printfinity_banner.heading}
                        </h2>
                        <p className="mt-3 text-sm leading-relaxed text-neutral-700">
                            {c.printfinity_banner.body}
                        </p>
                        <Link
                            href="/printfinity"
                            className="mt-4 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                            style={{ color: ACCENT }}
                        >
                            {c.printfinity_banner.cta}{' '}
                            <ChevronRight className="size-3.5" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* 6. team & business solutions */}
            <section className="bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <header className="mb-8 max-w-2xl">
                        <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                            {c.business_solutions_section.heading}
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            {c.business_solutions_section.subtitle}
                        </p>
                    </header>
                    <ul className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        {c.business_solutions_section.blocks.map(
                            (block: any, i: number) => (
                                <li
                                    key={block.title}
                                    className="overflow-hidden rounded-lg bg-white shadow-sm"
                                >
                                    <img
                                        src={block.image_url}
                                        alt=""
                                        loading="lazy"
                                        className="aspect-[5/3] w-full object-cover"
                                    />
                                    <div className="p-6">
                                        <h3 className="text-lg font-bold text-neutral-900">
                                            {block.title}
                                        </h3>
                                        <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                            {block.body}
                                        </p>
                                        <Link
                                            href={
                                                businessBlockHrefs[i] ??
                                                '/shop?cat=business-services'
                                            }
                                            className="mt-3 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                                            style={{ color: ACCENT }}
                                        >
                                            {block.cta}{' '}
                                            <ChevronRight className="size-3.5" />
                                        </Link>
                                    </div>
                                </li>
                            ),
                        )}
                    </ul>
                </div>
            </section>

            {/* 7. cross-sell */}
            <section className="bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <header className="mb-8 max-w-2xl">
                        <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                            {c.cross_sell_section.heading}
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            {c.cross_sell_section.subtitle}
                        </p>
                    </header>
                    {related.length > 0 ? (
                        <ul className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {related.slice(0, 3).map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={`/${item.slug}`}
                                        className="group block overflow-hidden rounded-md"
                                    >
                                        <div className="overflow-hidden bg-neutral-100">
                                            {item.featured_image ? (
                                                <img
                                                    src={item.featured_image}
                                                    alt={item.name}
                                                    className="aspect-[4/3] w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="flex aspect-[4/3] items-center justify-center text-neutral-400">
                                                    <svg
                                                        className="size-12"
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
                                        <div className="px-1 py-3">
                                            <h3 className="text-base font-bold text-neutral-900">
                                                {item.name}
                                            </h3>
                                            <span
                                                className="mt-1 inline-flex items-center gap-1 text-sm font-semibold group-hover:underline"
                                                style={{ color: ACCENT }}
                                            >
                                                {String(
                                                    c.cross_sell_section
                                                        .shop_label_template,
                                                ).replace(
                                                    '{name}',
                                                    item.name,
                                                )}{' '}
                                                <ChevronRight className="size-3.5" />
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-neutral-500">
                            {c.cross_sell_section.empty_state}
                        </p>
                    )}
                </div>
            </section>

            {/* 8. FAQs */}
            <section className="border-t border-neutral-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 lg:py-16">
                    <h2 className="mb-8 text-2xl font-bold text-neutral-900 sm:text-3xl">
                        FAQs — {product.name}
                    </h2>
                    <ul className="grid grid-cols-1 gap-x-10 gap-y-8 md:grid-cols-2 lg:grid-cols-3">
                        {c.faq.map((faq: any) => (
                            <li key={faq.question}>
                                <h3 className="text-sm font-bold text-neutral-900">
                                    {faq.question}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-600">
                                    {faq.answer}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>
        </StorefrontLayout>
    );
}

/* -------------------------------------------------------------------------- */
/* Sub-components                                                             */
/* -------------------------------------------------------------------------- */

function FeatureChip({
    icon,
    label,
    accent,
}: {
    icon: React.ReactNode;
    label: string;
    accent: string;
}) {
    return (
        <span className="inline-flex items-center gap-1.5">
            <span style={{ color: accent }}>{icon}</span>
            <span className="text-xs font-semibold tracking-wide uppercase">
                {label}
            </span>
        </span>
    );
}

function Stars({
    value,
    accent,
    ariaLabel,
}: {
    value: number;
    accent: string;
    ariaLabel: string;
}) {
    const full = Math.floor(value);

    return (
        <div className="flex items-center gap-0.5" aria-label={ariaLabel}>
            {Array.from({ length: 5 }).map((_, i) => (
                <Star
                    key={i}
                    className="size-4"
                    fill={i < full ? accent : 'transparent'}
                    stroke={i < full ? accent : '#cbd5d3'}
                    strokeWidth={1.5}
                />
            ))}
        </div>
    );
}

function OptionGroup({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <fieldset className="mt-6">
            <legend className="mb-3 text-sm font-bold text-neutral-900">
                {label}
            </legend>
            {children}
        </fieldset>
    );
}

function ChoiceTile({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-md border-2 p-3 text-left transition-colors ${
                active
                    ? 'border-[#0f4c3a] bg-[#0f4c3a]/5'
                    : 'border-neutral-200 hover:border-neutral-300'
            }`}
        >
            {children}
        </button>
    );
}

function DesignChoice({
    title,
    body,
    icon,
    accent,
    onClick,
}: {
    title: string;
    body: string;
    icon: React.ReactNode;
    accent: string;
    onClick?: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="flex h-full flex-col items-start gap-2 rounded-md border-2 border-neutral-200 bg-white p-4 text-left transition-colors hover:border-[#0f4c3a] hover:bg-[#0f4c3a]/5"
        >
            <span style={{ color: accent }}>{icon}</span>
            <p className="text-sm font-bold text-neutral-900">{title}</p>
            <p className="text-xs leading-relaxed text-neutral-600">{body}</p>
        </button>
    );
}

function CanvaDesignModal({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Design in Canva</DialogTitle>
                    <DialogDescription>
                        Watch the quick tutorial, design your card in Canva,
                        then come back and upload the file below.
                    </DialogDescription>
                </DialogHeader>

                <div className="aspect-video w-full overflow-hidden rounded-md bg-neutral-100">
                    <iframe
                        className="h-full w-full"
                        src="https://www.youtube.com/embed/r4n88m21kow?start=2"
                        title="Canva design tutorial"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowFullScreen
                    />
                </div>

                <form
                    className="mt-2 space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        toast.success(
                            'File received — we will attach it to your order.',
                        );
                        onOpenChange(false);
                    }}
                >
                    <h3 className="text-sm font-bold text-neutral-900">
                        Upload your Canva design file
                    </h3>
                    <Input
                        type="file"
                        accept=".pdf,.png,.jpg,.jpeg,.svg,.ai,.psd"
                        required
                    />
                    <Button type="submit" className="w-full sm:w-auto">
                        Upload file
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DesignServiceFormModal({
    open,
    onOpenChange,
    title,
    description,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                <form
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        toast.success(
                            'Thanks — our design team will contact you by email shortly.',
                        );
                        onOpenChange(false);
                    }}
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="ds-name">Full name *</Label>
                            <Input id="ds-name" name="name" required />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ds-email">Email *</Label>
                            <Input
                                id="ds-email"
                                name="email"
                                type="email"
                                required
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ds-phone">Phone</Label>
                            <Input id="ds-phone" name="phone" type="tel" />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ds-company">Company name</Label>
                            <Input id="ds-company" name="company" />
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="ds-file">Upload your file(s)</Label>
                        <Input
                            id="ds-file"
                            name="files"
                            type="file"
                            multiple
                            accept=".pdf,.png,.jpg,.jpeg,.svg,.ai,.psd,.eps"
                        />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="ds-notes">Design instructions</Label>
                        <textarea
                            id="ds-notes"
                            name="notes"
                            rows={4}
                            placeholder="Colours, style, text to include, references…"
                            className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>

                    <Button type="submit" className="w-full">
                        Submit
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Bullet({ accent }: { accent: string }) {
    return (
        <span
            aria-hidden
            className="mt-1.5 inline-block size-1.5 shrink-0 rounded-full"
            style={{ backgroundColor: accent }}
        />
    );
}
