// Content (labels, copy, images, FAQs) sourced from `content/hardcoded-content.json`
// via useContent('product_detail_page'). Configurator state and price math stay
// local to the page; JSON drives the labels and option metadata.
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Fragment, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import DesignServiceForm from '@/components/design-service-form';
import type { DesignServiceOption } from '@/components/design-service-form';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import { computeDynamicTiers } from '@/lib/pricing';
import type { DynamicPricingData } from '@/lib/pricing';
import type { PricingRule } from '@/lib/pricing';
import { findMatchingGallery } from '@/lib/product-options';
import type { ProductGallery } from '@/lib/product-options';

const COLD_FOIL_OPTIONS = [
    {
        id: 'cold_red_gold',
        label: 'Cold Red Gold',
        thumb: '/images/product-options/business-cards/swatches/cold/red-gold.png',
        description: 'Vibrant cold red foil',
    },
    {
        id: 'cold_blue_gold',
        label: 'Cold Blue Gold',
        thumb: '/images/product-options/business-cards/swatches/cold/blue-gold.png',
        description: 'Elegant cold blue foil',
    },
    {
        id: 'cold_bright_gold',
        label: 'Cold Bright Gold',
        thumb: '/images/product-options/business-cards/swatches/cold/bright-gold.png',
        description: 'Glistening cold gold foil',
    },
    {
        id: 'cold_bright_silver',
        label: 'Cold Bright Silver',
        thumb: '/images/product-options/business-cards/swatches/cold/bright-silver.png',
        description: 'Shining cold silver foil',
    },
    {
        id: 'cold_green_gold',
        label: 'Cold Green Gold',
        thumb: '/images/product-options/business-cards/swatches/cold/green-gold.png',
        description: 'Rich cold green gold foil',
    },
    {
        id: 'cold_matte_gold',
        label: 'Cold Matte Gold',
        thumb: '/images/product-options/business-cards/swatches/cold/matte-gold.png',
        description: 'Sophisticated matte gold foil',
    },
    {
        id: 'cold_matte_silver',
        label: 'Cold Matte Silver',
        thumb: '/images/product-options/business-cards/swatches/cold/matte-silver.png',
        description: 'Elegant matte silver foil',
    },
];
import DesignSpecificationsSection from '@/components/product-detail/design-specifications-section';
import DesignServiceBanner from '@/components/product-detail/design-service-banner';
import PaperStockComparisonSection from '@/components/product-detail/paper-stock-comparison-section';
import MoreGoodStuffSection from '@/components/product-detail/more-good-stuff-section';
import ProductFaqSection from '@/components/product-detail/product-faq-section';
import LightboxGallery from '@/components/product-detail/lightbox-gallery';
import type { ProductDetailSections } from '@/types/product-detail';

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    description_title: string | null;
    bullet_points: string[] | null;
    price: string;
    featured_image: string | null;
    category: { id: number; name: string; slug: string };
}

interface ProductOptionValue {
    code?: string;
    name: string;
    description?: string;
    swatch_image?: string;
    width?: string;
    height?: string;
}

interface ProductOptionGroup {
    key: string;
    label: string;
    type: 'select' | 'multi_select';
    required?: boolean;
    default?: string;
    values: ProductOptionValue[];
}

interface ProductOptions {
    dynamic_options?: boolean;
    option_groups?: ProductOptionGroup[];
    subtitle?: string;
    starting_price_text?: string;
    sizes?: Array<{
        code?: string;
        name: string;
        width: string;
        height: string;
        swatch_image: string;
    }>;
    paper_finish?: Array<{
        code?: string;
        name: string;
        description: string;
        added_price: string;
        swatch_image: string;
    }>;
    corners?: Array<{
        code?: string;
        name: string;
        description: string;
        swatch_image: string;
        added_price: string;
    }>;
    texture?: Array<{
        code?: string;
        name: string;
        description: string;
        swatch_image: string;
        added_price: Array<{
            pack_size: number;
            price_per_card: number;
        }>;
    }>;
    special_finish: Array<{
        code?: string;
        name: string;
        description: string;
        swatch_image: string;
    }>;
    print_code: Array<{
        code?: string;
        name: string;
        description: string;
    }>;
    drill: Array<{
        code?: string;
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
    pricing_rules?: PricingRule[];
    detail_sections?: ProductDetailSections;
}

interface Props {
    product: Product;
    productOptions?: ProductOptions;
    fallbackGalleryImages?: string[];
}

/* -------------------------------------------------------------------------- */
/* Layout-only metadata (shapes, hrefs) — kept here, not in JSON              */
/* -------------------------------------------------------------------------- */

const sizeShapes: Record<string, 'rect' | 'square'> = {
    standard: 'rect',
    square: 'square',
};

const generatedSwatchBase = '/images/product-options/business-cards/generated';

const generatedSizeSwatches: Record<string, string> = {
    standard: `${generatedSwatchBase}/standard.png`,
    square: `${generatedSwatchBase}/square.png`,
};

const generatedFinishSwatches: Record<string, string> = {
    matte: `${generatedSwatchBase}/matte.png`,
    gloss: `${generatedSwatchBase}/gloss.png`,
    cotton: `${generatedSwatchBase}/cotton.png`,
    pvc: `${generatedSwatchBase}/pvc.png`,
};

const generatedCornerSwatches: Record<string, string> = {
    square: `${generatedSwatchBase}/square-corners.png`,
    standard: `${generatedSwatchBase}/square-corners.png`,
    rounded: `${generatedSwatchBase}/rounded-corners.png`,
};

// Display-only mirror of the server-side fee map
// (App\Models\DesignServiceRequest::DESIGN_SERVICE_FEES). The server
// re-computes the fee from the code; clients never submit amounts.
const designServiceFees: Record<string, number> = {
    card_layout: 29,
    card_design: 79,
};

const businessBlockHrefs = [
    '/shop?cat=business-services',
    '/shop?cat=business-services',
    '/contact',
];

function optionValueCode(value: ProductOptionValue): string {
    return (
        value.code?.trim() ||
        value.name
            .trim()
            .toLowerCase()
            .replace(/[\s_]+/g, '-')
    );
}

/* -------------------------------------------------------------------------- */
/* Page                                                                       */
/* -------------------------------------------------------------------------- */

export default function ShopShow({
    product,
    productOptions,
    fallbackGalleryImages,
}: Props) {
    const c = useContent('product_detail_page') as any;
    const ACCENT = c.accent_color;

    const galleryThumbs: string[] = c.gallery_thumb_image_urls;
    const finishThumbs: string[] = c.finish_thumb_image_urls;

    const hasProductOptions = productOptions != null;
    const isCottonBusinessCards =
        product.category?.slug === 'cotton-business-cards';
    const supportsColdFoil = ![
        'classic-standard-business-cards',
        'classic-special-business-cards',
    ].includes(product.slug);

    const usesDynamicOptions = productOptions?.dynamic_options === true;
    const dynamicOptionGroups = useMemo(
        () =>
            usesDynamicOptions && Array.isArray(productOptions?.option_groups)
                ? productOptions.option_groups
                : [],
        [usesDynamicOptions, productOptions],
    );

    const dynamicOptionDefaults = useMemo<
        Record<string, string | string[]>
    >(() => {
        const defaults: Record<string, string | string[]> = {};

        for (const group of dynamicOptionGroups) {
            const valueCodes = group.values.map(optionValueCode);
            const firstCode = valueCodes[0] ?? '';
            const configuredDefault = group.default?.trim() ?? '';
            const defaultCode = valueCodes.includes(configuredDefault)
                ? configuredDefault
                : firstCode;

            defaults[group.key] =
                group.type === 'multi_select'
                    ? defaultCode
                        ? [defaultCode]
                        : []
                    : defaultCode;
        }

        return defaults;
    }, [dynamicOptionGroups]);

    const sizes = useMemo(
        () =>
            hasProductOptions && Array.isArray(productOptions.sizes)
                ? productOptions.sizes.map((s) => ({
                      id: s.code ?? s.name.toLowerCase(),
                      label: s.name.charAt(0).toUpperCase() + s.name.slice(1),
                      dims: `${s.width}" x ${s.height}"`,
                      swatch:
                          generatedSizeSwatches[s.name.toLowerCase()] ??
                          s.swatch_image,
                  }))
                : c.configurator_options.sizes.map((s: any) => ({
                      ...s,
                      swatch:
                          generatedSizeSwatches[s.id.toLowerCase()] ?? s.swatch,
                  })),
        [hasProductOptions, productOptions, c.configurator_options.sizes],
    );

    const finishes = useMemo(
        () =>
            hasProductOptions && Array.isArray(productOptions.paper_finish)
                ? productOptions.paper_finish.map((f) => ({
                      id: f.code ?? f.name.toLowerCase(),
                      label: f.name,
                      description: f.description,
                      thumb:
                          generatedFinishSwatches[f.name.toLowerCase()] ??
                          f.swatch_image,
                  }))
                : c.configurator_options.finishes.map((f: any, i: number) => ({
                      ...f,
                      thumb:
                          generatedFinishSwatches[f.id.toLowerCase()] ??
                          finishThumbs[i] ??
                          '',
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
            hasProductOptions && Array.isArray(productOptions.corners)
                ? productOptions.corners.map((cn) => ({
                      id: cn.code ?? cn.name.toLowerCase(),
                      label: cn.name,
                      swatch:
                          generatedCornerSwatches[cn.name.toLowerCase()] ??
                          cn.swatch_image,
                  }))
                : c.configurator_options.corners.map((cn: any) => ({
                      ...cn,
                      swatch:
                          generatedCornerSwatches[cn.id.toLowerCase()] ??
                          cn.swatch,
                  })),
        [hasProductOptions, productOptions, c.configurator_options.corners],
    );

    const textures = useMemo(
        () =>
            hasProductOptions && Array.isArray(productOptions.texture)
                ? productOptions.texture.map((t) => ({
                      id: t.code ?? t.name.toLowerCase().replace(/\s+/g, '-'),
                      label: t.name,
                      description: t.description,
                      thumb: t.swatch_image,
                  }))
                : [],
        [hasProductOptions, productOptions],
    );

    const specialFinishes = useMemo(
        () =>
            hasProductOptions && Array.isArray(productOptions.special_finish)
                ? productOptions.special_finish
                      .filter((f) => f.name.toLowerCase() !== 'none')
                      .map((f) => ({
                          id: f.code ?? f.name.toLowerCase().replace(/\s+/g, '-'),
                          label:
                              f.name.charAt(0).toUpperCase() + f.name.slice(1),
                          description: f.description,
                          thumb: f.swatch_image,
                      }))
                : [],
        [hasProductOptions, productOptions],
    );

    const specialFinishOnSidesList = useMemo(
        () =>
            hasProductOptions && (productOptions as any).special_finish_on_sides
                ? (productOptions as any).special_finish_on_sides.map((s: any) => ({
                      id: s.code ?? s.name.toLowerCase().replace(/\s+/g, '-'),
                      label: s.name,
                      description: s.description,
                      thumb: s.swatch_image,
                  }))
                : [],
        [hasProductOptions, productOptions],
    );

    // Dynamic pricing is attached by the controller only for products
    // that have pricing JSON configured (see loadDynamicPricingData).
    const hasDynamicPricing =
        hasProductOptions &&
        (productOptions.pricing_data != null ||
            (productOptions.pricing_rules?.length ?? 0) > 0);

    const dynamicStartQty = hasDynamicPricing
        ? (productOptions.pricing_data?.rectangle?.startQuantity ??
          productOptions.pricing_rules?.[0]?.pricing.startQuantity ??
          null)
        : null;

    // "X cards from $Y" derived from data: X = startQuantity from the
    // pricing JSON, Y = subtotal (currentPrice) of the first row of the
    // quantity pricing table under the default option configuration.
    const startingPriceText = useMemo(() => {
        if (hasDynamicPricing && productOptions.pricing_data) {
            const firstTier = computeDynamicTiers(
                {
                    ...productOptions.pricing_data,
                    rules: productOptions.pricing_rules,
                },
                0, // default size
                0, // default paper finish
                0, // default corners
                0, // default special finish
                {},
            )[0];

            if (firstTier) {
                return `${firstTier.qty} cards from $${firstTier.currentPrice}`;
            }
        }

        if (hasDynamicPricing && productOptions.pricing_rules?.[0]?.pricing) {
            const pricing = productOptions.pricing_rules[0].pricing;
            const total = Math.round(
                pricing.startQuantity * pricing.basePrice,
            );

            return `${pricing.startQuantity} cards from $${total}`;
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
            images:
                fallbackGalleryImages && fallbackGalleryImages.length > 0
                    ? fallbackGalleryImages
                    : galleryThumbs,
        }),
        [fallbackGalleryImages, galleryThumbs],
    );

    const [selectedSize, setSelectedSize] = useState<string | null>(null);
    const [selectedFinish, setSelectedFinish] = useState<string | null>(null);
    const [selectedCorners, setSelectedCorners] = useState<string | null>(null);
    const [selectedDynamicOptions, setSelectedDynamicOptions] = useState<
        Record<string, string | string[]>
    >(dynamicOptionDefaults);
    const [selectedTexture, setSelectedTexture] = useState<string | null>(
        textures.length > 0 ? null : 'none',
    );
    const [selectedSpecialFinish, setSelectedSpecialFinish] = useState<
        string | null
    >(specialFinishes.length > 0 ? null : 'none');

    const [foilTab, setFoilTab] = useState<'hot' | 'cold'>(() => {
        if (
            supportsColdFoil &&
            selectedSpecialFinish &&
            selectedSpecialFinish.startsWith('cold_')
        ) {
            return 'cold';
        }
        return 'hot';
    });

    const handleFoilTabChange = (tab: 'hot' | 'cold') => {
        if (tab === 'cold' && !supportsColdFoil) {
            return;
        }

        setFoilTab(tab);
        if (tab === 'hot') {
            if (selectedSpecialFinish && selectedSpecialFinish.startsWith('cold_')) {
                const firstHot = specialFinishes[0]?.id ?? 'no-special-finish';
                setSelectedSpecialFinish(firstHot);
            }
        } else {
            if (!selectedSpecialFinish || !selectedSpecialFinish.startsWith('cold_')) {
                setSelectedSpecialFinish('cold_bright_gold');
            }
        }
    };
    const embossingList = useMemo(() => {
        if (!hasProductOptions || !(productOptions as any).embossing) {
            return [];
        }
        return (productOptions as any).embossing.map((item: any) => ({
            id: item.code,
            label: item.name,
            description: item.description,
        }));
    }, [hasProductOptions, productOptions]);

    const embossingOrSignaturePanelList = useMemo(() => {
        if (!hasProductOptions || !(productOptions as any).embossing_or_signature_panel) {
            return [];
        }
        return (productOptions as any).embossing_or_signature_panel.map((item: any) => ({
            id: item.code,
            label: item.name,
            description: item.description,
        }));
    }, [hasProductOptions, productOptions]);

    const [selectedEmbossing, setSelectedEmbossing] = useState<string | null>(() => {
        return embossingList.length > 0 ? embossingList[0].id : 'none';
    });

    const [selectedEmbossingOrSignaturePanel, setSelectedEmbossingOrSignaturePanel] = useState<string | null>(() => {
        return embossingOrSignaturePanelList.length > 0 ? embossingOrSignaturePanelList[0].id : 'none';
    });

    const [selectedSpecialFinishOnSides, setSelectedSpecialFinishOnSides] = useState<
        string | null
    >(specialFinishOnSidesList.length > 0 ? null : 'none');
    const [selectedQty, setSelectedQty] = useState<number | null>(
        RECOMMENDED_QTY,
    );
    const [selectedThumbnail, setSelectedThumbnail] = useState<string | null>(
        null,
    );
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);
    const [added, setAdded] = useState(false);
    const [designModal, setDesignModal] = useState<
        'canva' | 'upload' | 'design-for-you' | null
    >(null);
    const [selectedDesignService, setSelectedDesignService] = useState<
        string | null
    >(null);

    const hasInteractedRef = useRef(false);

    const hasSelection = usesDynamicOptions
        ? dynamicOptionGroups.every((group) => {
              const selected = selectedDynamicOptions[group.key];

              return group.type === 'multi_select'
                  ? Array.isArray(selected) && selected.length > 0
                  : typeof selected === 'string' && selected !== '';
          })
        : (sizes.length === 0 || selectedSize != null) &&
          (finishes.length === 0 || selectedFinish != null) &&
          (cornersList.length === 0 || selectedCorners != null) &&
          (textures.length === 0 || selectedTexture != null) &&
          (specialFinishes.length === 0 || selectedSpecialFinish != null) &&
          (specialFinishOnSidesList.length === 0 || selectedSpecialFinishOnSides != null) &&
          (embossingList.length === 0 || selectedEmbossing != null) &&
          (embossingOrSignaturePanelList.length === 0 || selectedEmbossingOrSignaturePanel != null);

    const defaultOptions = useMemo<Record<string, string | string[]>>(
        () => {
            const opts: Record<string, string | string[]> = {
                quantity: String(RECOMMENDED_QTY ?? ''),
            };

            if (usesDynamicOptions) {
                for (const group of dynamicOptionGroups) {
                    const value = dynamicOptionDefaults[group.key];

                    if (typeof value === 'string' && value !== '') {
                        opts[group.key] = value;
                    } else if (Array.isArray(value) && value.length > 0) {
                        opts[group.key] = value;
                    }
                }

                return opts;
            }

            if (sizes.length > 0) opts['sizes'] = sizes[0]?.id;
            if (finishes.length > 0) opts['paper_finish'] = finishes[0]?.id;
            if (cornersList.length > 0) opts['corners'] = cornersList[0]?.id;
            if (textures.length > 0) opts['texture'] = textures[0]?.id ?? 'none';
            if (specialFinishes.length > 0) opts['special_finish'] = specialFinishes[0]?.id ?? 'none';
            if (specialFinishOnSidesList.length > 0) opts['special_finish_on_sides'] = specialFinishOnSidesList[0]?.id ?? 'none';
            if (embossingList.length > 0) opts['embossing'] = embossingList[0]?.id ?? 'none';
            if (embossingOrSignaturePanelList.length > 0) opts['embossing_or_signature_panel'] = embossingOrSignaturePanelList[0]?.id ?? 'none';
            return opts;
        },
        [
            sizes,
            finishes,
            cornersList,
            textures,
            specialFinishes,
            specialFinishOnSidesList,
            embossingList,
            embossingOrSignaturePanelList,
            RECOMMENDED_QTY,
            usesDynamicOptions,
            dynamicOptionGroups,
            dynamicOptionDefaults,
        ],
    );

    const selectedOptions = useMemo<Record<string, string | string[]>>(() => {
        if (usesDynamicOptions) {
            const opts: Record<string, string | string[]> = {
                quantity: String(selectedQty ?? RECOMMENDED_QTY),
            };

            for (const group of dynamicOptionGroups) {
                const selected = selectedDynamicOptions[group.key];

                if (group.type === 'multi_select') {
                    opts[group.key] = Array.isArray(selected) ? selected : [];
                } else {
                    opts[group.key] =
                        typeof selected === 'string' && selected !== ''
                            ? selected
                            : dynamicOptionDefaults[group.key] ?? '';
                }
            }

            return opts;
        }

        if (!hasSelection) {
            return defaultOptions;
        }

        const opts: Record<string, string | string[]> = {
            quantity: String(selectedQty ?? RECOMMENDED_QTY),
        };
        if (sizes.length > 0 && selectedSize) opts['sizes'] = selectedSize;
        if (finishes.length > 0 && selectedFinish) opts['paper_finish'] = selectedFinish;
        if (cornersList.length > 0 && selectedCorners) opts['corners'] = selectedCorners;
        if (textures.length > 0 && selectedTexture) opts['texture'] = selectedTexture;
        if (specialFinishes.length > 0 && selectedSpecialFinish) opts['special_finish'] = selectedSpecialFinish;
        if (specialFinishOnSidesList.length > 0 && selectedSpecialFinishOnSides) opts['special_finish_on_sides'] = selectedSpecialFinishOnSides;
        if (embossingList.length > 0 && selectedEmbossing) opts['embossing'] = selectedEmbossing;
        if (embossingOrSignaturePanelList.length > 0 && selectedEmbossingOrSignaturePanel) opts['embossing_or_signature_panel'] = selectedEmbossingOrSignaturePanel;
        return opts;
    }, [
        hasSelection,
        defaultOptions,
        selectedSize,
        selectedFinish,
        selectedCorners,
        selectedTexture,
        selectedSpecialFinish,
        selectedSpecialFinishOnSides,
        selectedEmbossing,
        selectedEmbossingOrSignaturePanel,
        selectedQty,
        RECOMMENDED_QTY,
        sizes,
        finishes,
        cornersList,
        textures,
        specialFinishes,
        specialFinishOnSidesList,
        embossingList,
        embossingOrSignaturePanelList,
        usesDynamicOptions,
        dynamicOptionGroups,
        selectedDynamicOptions,
        dynamicOptionDefaults,
    ]);

    const activeGallery = useMemo(() => {
        if (configuredGalleries.length > 0) {
            const matched = findMatchingGallery(
                configuredGalleries,
                selectedOptions,
            );

            if (matched) {
                return matched;
            }
        }

        return fallbackGallery;
    }, [configuredGalleries, selectedOptions, fallbackGallery]);

    const defaultGallery = useMemo(
        () => configuredGalleries.find((g) => g.is_default) ?? fallbackGallery,
        [configuredGalleries, fallbackGallery],
    );

    const displayImages = useMemo(() => {
        const hero = activeGallery.images[0] ?? defaultGallery.images[0];
        const persistent = (defaultGallery.images ?? []).slice(1);

        return hero ? [hero, ...persistent] : persistent;
    }, [activeGallery, defaultGallery]);

    const activeImage = useMemo(() => {
        if (selectedThumbnail && displayImages.includes(selectedThumbnail)) {
            return selectedThumbnail;
        }

        return displayImages[0] ?? product.featured_image ?? galleryThumbs[0];
    }, [
        selectedThumbnail,
        displayImages,
        product.featured_image,
        galleryThumbs,
    ]);

    const quantityTiers = useMemo(() => {
        if (hasDynamicPricing) {
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
                {
                    ...(productOptions.pricing_data ?? {}),
                    rules: productOptions.pricing_rules,
                },
                Math.max(0, sizeIndex),
                Math.max(0, finishIndex),
                Math.max(0, cornersIndex),
                Math.max(0, specialIndex),
                selectedOptions,
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
        hasProductOptions,
        selectedSize,
        selectedFinish,
        selectedCorners,
        selectedSpecialFinish,
        selectedOptions,
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

    // One-time design service fee on top of the printing price. The quantity
    // table intentionally stays printing-only.
    const designFee = selectedDesignService
        ? (designServiceFees[selectedDesignService] ?? 0)
        : 0;

    const fullPrice =
        (tier?.originalPrice ?? tier?.currentPrice ?? 0) + designFee;
    const finalPrice = (tier?.currentPrice ?? 0) + designFee;

    function selectDynamicOption(groupKey: string, value: string) {
        const group = dynamicOptionGroups.find((item) => item.key === groupKey);

        if (!group) {
            return;
        }

        setSelectedDynamicOptions((current) => {
            if (group.type !== 'multi_select') {
                return { ...current, [groupKey]: value };
            }

            const selected = Array.isArray(current[groupKey])
                ? current[groupKey]
                : [];

            return {
                ...current,
                [groupKey]: selected.includes(value)
                    ? selected.filter((item) => item !== value)
                    : [...selected, value],
            };
        });
    }

    function selectOption(
        group:
            | 'sizes'
            | 'paper_finish'
            | 'corners'
            | 'texture'
            | 'special_finish'
            | 'special_finish_on_sides'
            | 'embossing'
            | 'embossing_or_signature_panel',
        value: string,
    ) {
        if (!hasInteractedRef.current) {
            hasInteractedRef.current = true;
            setSelectedSize(sizes[0]?.id ?? null);
            setSelectedFinish(finishes[0]?.id ?? null);
            setSelectedCorners(cornersList[0]?.id ?? null);
            setSelectedTexture(textures[0]?.id ?? 'none');
            setSelectedSpecialFinish(specialFinishes[0]?.id ?? 'none');
            setSelectedSpecialFinishOnSides(specialFinishOnSidesList[0]?.id ?? 'none');
            setSelectedEmbossing(embossingList[0]?.id ?? 'none');
            setSelectedEmbossingOrSignaturePanel(embossingOrSignaturePanelList[0]?.id ?? 'none');
        }

        switch (group) {
            case 'sizes':
                setSelectedSize(value);
                break;
            case 'paper_finish':
                setSelectedFinish(value);

                // Gloss only allows "no special finish" — reset if needed
                if (
                    value === 'gloss' &&
                    selectedSpecialFinish != null &&
                    selectedSpecialFinish !== 'no-special-finish'
                ) {
                    setSelectedSpecialFinish('no-special-finish');
                }

                break;
            case 'corners':
                setSelectedCorners(value);
                break;
            case 'texture':
                setSelectedTexture(value);
                break;
            case 'special_finish':
                setSelectedSpecialFinish(value);
                break;
            case 'special_finish_on_sides':
                setSelectedSpecialFinishOnSides(value);
                break;
            case 'embossing':
                setSelectedEmbossing(value);
                break;
            case 'embossing_or_signature_panel':
                setSelectedEmbossingOrSignaturePanel(value);
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
        specialFinishes.find((f: any) => f.id === selectedSpecialFinish)?.label ??
        (supportsColdFoil
            ? COLD_FOIL_OPTIONS.find((f: any) => f.id === selectedSpecialFinish)
                  ?.label
            : undefined) ??
        '';
    const textureLabel =
        textures.find((t: any) => t.id === selectedTexture)?.label ?? '';
    const embossingLabel =
        embossingList.find((e: any) => e.id === selectedEmbossing)?.label ?? '';
    const embossingOrSignaturePanelLabel =
        embossingOrSignaturePanelList.find((e: any) => e.id === selectedEmbossingOrSignaturePanel)?.label ?? '';

    const showSpecialFinishInSummary = specialFinishes.length > 0;
    const showTextureInSummary = textures.length > 0;
    const showEmbossingInSummary = embossingList.length > 0;
    const showEmbossingOrSignaturePanelInSummary = embossingOrSignaturePanelList.length > 0;

    const addToCart = () => {
        setAdded(true);
        router.post(
            '/cart/add',
            {
                product_id: product.id,
                // Only the code is submitted; the server resolves the fee.
                options: selectedDesignService
                    ? {
                          ...selectedOptions,
                          design_service: selectedDesignService,
                      }
                    : selectedOptions,
            },
            {
                preserveScroll: true,
                onFinish: () => setTimeout(() => setAdded(false), 2000),
            },
        );
    };

    const breadcrumbs: string[] = c.breadcrumbs;
    const featureChips: string[] = c.feature_chips;
    const featureChipDescriptions: string[] = c.feature_chip_descriptions;
    const gangRunTooltip: any = c.gang_run_printing_tooltip;
    const turnaroundTooltip: any = c.turnaround_tooltip;
    const summaryLabels: string[] = c.order_summary.labels;
    const designServicesConfig: any = c.design_services;
    const designFeeLabel: string = c.design_fee_label;
    const pageUrl = usePage().url;

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
            <nav aria-label="Breadcrumb" className="bg-white">
                <ol className="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 text-sm text-neutral-500">
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
                    <div className="lg:sticky lg:top-[10px] lg:self-start">
                        <div 
                            className="overflow-hidden rounded-lg bg-neutral-100 cursor-zoom-in hover:opacity-95 transition-all duration-300"
                            onClick={() => {
                                const index = displayImages.indexOf(activeImage);
                                setLightboxIndex(index !== -1 ? index : 0);
                                setLightboxOpen(true);
                            }}
                            title="Click to view fullscreen gallery"
                        >
                            <img
                                src={activeImage}
                                alt={product.name}
                                className="h-[420px] w-full object-cover sm:h-[540px] lg:h-[600px] transform hover:scale-[1.02] transition-transform duration-500"
                            />
                        </div>
                        <div className="mt-3 grid grid-cols-4 gap-2">
                            {displayImages.map((src) => (
                                <button
                                    key={src}
                                    type="button"
                                    onClick={() => setSelectedThumbnail(src)}
                                    className={`overflow-hidden rounded-md border-2 transition-colors ${
                                        activeImage === src
                                            ? 'border-[#800020]'
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
                        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <div
                                            tabIndex={0}
                                            className="rounded-lg focus-visible:ring-2 focus-visible:ring-[#800020]/40 focus-visible:outline-none"
                                        >
                                            <FeatureChip
                                                icon={
                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="1.6"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        className="size-10 text-[#800020]"
                                                    >
                                                        <circle
                                                            cx="12"
                                                            cy="12"
                                                            r="10"
                                                        />
                                                        <polyline points="12 6 12 12 16 14" />
                                                    </svg>
                                                }
                                                label={featureChips[0]}
                                                description={
                                                    featureChipDescriptions[0]
                                                }
                                            />
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        side="top"
                                        className="max-w-[min(20rem,calc(100vw-2rem))] bg-neutral-900 text-white"
                                    >
                                        <div className="space-y-2 p-1">
                                            <p className="font-semibold">
                                                {turnaroundTooltip.title}
                                            </p>
                                            {turnaroundTooltip.sections.map(
                                                (section: any) => (
                                                    <p key={section.heading}>
                                                        <span className="font-semibold">
                                                            {section.heading}
                                                        </span>
                                                        <br />
                                                        {section.body}
                                                    </p>
                                                ),
                                            )}
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <div>
                                            <FeatureChip
                                                icon={
                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="1.6"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        className="size-10 text-[#800020]"
                                                    >
                                                        <rect
                                                            x="3"
                                                            y="6"
                                                            width="18"
                                                            height="14"
                                                            rx="2"
                                                        />
                                                        <path d="M3 10h18" />
                                                        <path d="M7 6V4h10v2" />
                                                        <circle
                                                            cx="7"
                                                            cy="14"
                                                            r="1"
                                                        />
                                                        <circle
                                                            cx="11"
                                                            cy="14"
                                                            r="1"
                                                        />
                                                        <circle
                                                            cx="15"
                                                            cy="14"
                                                            r="1"
                                                        />
                                                    </svg>
                                                }
                                                label={featureChips[1]}
                                                description={
                                                    featureChipDescriptions[1]
                                                }
                                            />
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        side="top"
                                        className="max-w-xs bg-neutral-900 text-white"
                                    >
                                        <div className="space-y-2 p-1">
                                            <p className="font-semibold">
                                                {gangRunTooltip.title}
                                            </p>
                                            <p>{gangRunTooltip.intro}</p>
                                            <p className="font-semibold">
                                                {gangRunTooltip.pros_title}
                                            </p>
                                            <ul className="list-disc space-y-1 pl-4">
                                                {gangRunTooltip.pros.map(
                                                    (pro: string) => (
                                                        <li key={pro}>{pro}</li>
                                                    ),
                                                )}
                                            </ul>
                                            <p className="font-semibold">
                                                {gangRunTooltip.cons_title}
                                            </p>
                                            <ul className="list-disc space-y-1 pl-4">
                                                {gangRunTooltip.cons.map(
                                                    (con: string) => (
                                                        <li key={con}>{con}</li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
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
                        <div
                            className="mt-4 text-sm leading-relaxed text-neutral-700 [&_a]:underline [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-0 [&_p+p]:mt-2 [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5"
                            dangerouslySetInnerHTML={{
                                __html:
                                    productOptions?.subtitle ??
                                    c.product_subtitle,
                            }}
                        />
                        {startingPriceText && (
                            <p className="mt-2 text-sm font-semibold text-neutral-900">
                                {startingPriceText}
                            </p>
                        )}

                        {(product.description_title ||
                            product.description ||
                            product.bullet_points?.length) && (
                            <div className="mt-6">
                                {product.description_title && (
                                    <h2 className="text-lg font-bold text-neutral-900">
                                        {product.description_title}
                                    </h2>
                                )}
                                {product.description && (
                                    <div
                                        className="mt-2 text-sm leading-relaxed text-neutral-700"
                                        dangerouslySetInnerHTML={{
                                            __html: product.description,
                                        }}
                                    />
                                )}
                                {product.bullet_points &&
                                    product.bullet_points.length > 0 && (
                                        <ul className="mt-3 space-y-1.5 text-sm text-neutral-700">
                                            {product.bullet_points.map(
                                                (bullet: string) => (
                                                    <li
                                                        key={bullet}
                                                        className="flex gap-2"
                                                    >
                                                        <Bullet
                                                            accent={ACCENT}
                                                        />{' '}
                                                        {bullet}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}
                            </div>
                        )}

                        {c.description_block && (
                            <div className="mt-6">
                                <h2 className="text-lg font-bold text-neutral-900">
                                    {c.description_block.title}
                                </h2>
                                <p className="mt-2 text-sm leading-relaxed text-neutral-700">
                                    {c.description_block.description}
                                </p>
                                <ul className="mt-3 space-y-1.5 text-sm text-neutral-700">
                                    {c.description_block.bullets.map(
                                        (bullet: string) => (
                                            <li
                                                key={bullet}
                                                className="flex gap-2"
                                            >
                                                <Bullet accent={ACCENT} />{' '}
                                                {bullet}
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </div>
                        )}

                        {usesDynamicOptions && (
                            <DynamicOptionGroups
                                groups={dynamicOptionGroups}
                                selected={selectedDynamicOptions}
                                onSelect={selectDynamicOption}
                            />
                        )}

                        {!usesDynamicOptions && sizes.length > 0 && !isCottonBusinessCards && (
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
                                                                ? 'border-[#800020] bg-[#800020]/5'
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
                        )}

                        {!usesDynamicOptions && !isCottonBusinessCards && (
                            <OptionGroup
                                label={c.configurator_labels.paper_finish}
                            >
                            <div
                                className={`grid gap-3 ${
                                    finishes.length % 3 === 0 || finishes.length > 2
                                        ? 'grid-cols-1 sm:grid-cols-3'
                                        : 'grid-cols-2'
                                }`}
                            >
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
                        )}

                        {!usesDynamicOptions && cornersList.length > 0 && (
                            <OptionGroup label={c.configurator_labels.corners}>
                                <div className="grid grid-cols-2 gap-3">
                                    {cornersList.map((cn: any) => {
                                        const isImageSwatch =
                                            typeof cn.swatch === 'string' &&
                                            /^(https?:)?\//.test(cn.swatch);
                                        const isSvgSwatch =
                                            typeof cn.swatch === 'string' &&
                                            cn.swatch
                                                .trimStart()
                                                .startsWith('<svg');

                                        return (
                                            <ChoiceTile
                                                key={cn.id}
                                                active={selectedCorners === cn.id}
                                                onClick={() =>
                                                    selectOption('corners', cn.id)
                                                }
                                            >
                                                <div className="flex h-16 items-center justify-center">
                                                    {isImageSwatch ? (
                                                        <img
                                                            src={cn.swatch}
                                                            alt=""
                                                            className="h-full max-h-16 w-full rounded-sm object-contain"
                                                        />
                                                    ) : isSvgSwatch ? (
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
                                                                    ? 'border-[#800020] bg-[#800020]/5'
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
                        )}

                        {!usesDynamicOptions && textures.length > 0 && (
                            <OptionGroup label="Texture">
                                <div className="grid grid-cols-3 gap-3">
                                    {textures.map((t: any) => (
                                        <ChoiceTile
                                            key={t.id}
                                            active={selectedTexture === t.id}
                                            onClick={() =>
                                                selectOption('texture', t.id)
                                            }
                                        >
                                            {t.thumb ? (
                                                <img
                                                    src={t.thumb}
                                                    alt=""
                                                    className="aspect-square w-full rounded-sm bg-neutral-50 object-contain"
                                                />
                                            ) : (
                                                <div className="flex aspect-square w-full items-center justify-center rounded-sm bg-neutral-50">
                                                    <span className="text-xs text-neutral-400">
                                                        Texture
                                                    </span>
                                                </div>
                                            )}
                                            <p className="mt-2 text-sm font-semibold">
                                                {t.label}
                                            </p>
                                            {t.description && (
                                                <p className="text-xs text-neutral-500">
                                                    {t.description}
                                                </p>
                                            )}
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        {!usesDynamicOptions && specialFinishes.length > 0 && (
                            <div className="mt-6">
                                {isCottonBusinessCards ? (
                                    <OptionGroup label="With NFC">
                                        <div className="grid grid-cols-2 gap-3">
                                            {specialFinishes.map((f: any) => (
                                                <ChoiceTile
                                                    key={f.id}
                                                    active={selectedSpecialFinish === f.id}
                                                    onClick={() => selectOption('special_finish', f.id)}
                                                >
                                                    <div className="flex aspect-square w-full items-center justify-center rounded-sm bg-neutral-50 p-2">
                                                        {f.thumb ? (
                                                            <img
                                                                src={f.thumb}
                                                                alt=""
                                                                className="h-full w-full rounded-sm object-contain"
                                                            />
                                                        ) : (
                                                            <span className="text-xs text-neutral-400">
                                                                {f.id === 'nfc_card'
                                                                    ? 'NFC CHIP'
                                                                    : 'NO CHIP'}
                                                            </span>
                                                        )}
                                                    </div>
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
                                ) : (
                                    <>
                                        <div className="mb-3 flex items-center justify-between border-b border-neutral-100 pb-2">
                                            <span className="text-sm font-bold text-neutral-900">
                                                Special Finish
                                            </span>
                                            <div className="flex rounded-md bg-neutral-100 p-0.5">
                                                <button
                                                    type="button"
                                                    onClick={() => handleFoilTabChange('hot')}
                                                    className={`rounded-[4px] px-3 py-1 text-xs font-semibold transition-all ${
                                                        foilTab === 'hot'
                                                            ? 'bg-white text-[#800020] shadow-sm'
                                                            : 'text-neutral-500 hover:text-neutral-800'
                                                    }`}
                                                >
                                                    Hot Foil
                                                </button>
                                                {supportsColdFoil && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            handleFoilTabChange(
                                                                'cold',
                                                            )
                                                        }
                                                        className={`rounded-[4px] px-3 py-1 text-xs font-semibold transition-all ${
                                                            foilTab === 'cold'
                                                                ? 'bg-white text-[#800020] shadow-sm'
                                                                : 'text-neutral-500 hover:text-neutral-800'
                                                        }`}
                                                    >
                                                        Cold Foil
                                                    </button>
                                                )}
                                            </div>
                                        </div>

                                        {foilTab === 'hot' || !supportsColdFoil ? (
                                            <div className="grid grid-cols-3 gap-3">
                                                {specialFinishes.map((f: any) => {
                                                    const glossLimited =
                                                        selectedFinish === 'gloss' &&
                                                        f.id !== 'no-special-finish';

                                                    return (
                                                        <ChoiceTile
                                                            key={f.id}
                                                            active={
                                                                selectedSpecialFinish ===
                                                                f.id
                                                            }
                                                            disabled={glossLimited}
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
                                                                className="aspect-square w-full rounded-sm bg-neutral-50 object-contain"
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
                                                    );
                                                })}
                                            </div>
                                        ) : (
                                            <div className="grid grid-cols-3 gap-3">
                                                {COLD_FOIL_OPTIONS.map((f: any) => (
                                                    <ChoiceTile
                                                        key={f.id}
                                                        active={
                                                            selectedSpecialFinish ===
                                                            f.id
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
                                                            className="aspect-square w-full rounded-sm bg-neutral-50 object-contain"
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
                                        )}
                                    </>
                                )}
                            </div>
                        )}

                        {!usesDynamicOptions && specialFinishOnSidesList.length > 0 && (
                            <OptionGroup label="Special finish on sides">
                                <div className="grid grid-cols-2 gap-3">
                                    {specialFinishOnSidesList.map((s: any) => (
                                        <ChoiceTile
                                            key={s.id}
                                            active={selectedSpecialFinishOnSides === s.id}
                                            onClick={() =>
                                                selectOption('special_finish_on_sides', s.id)
                                            }
                                        >
                                            {s.thumb ? (
                                                <img
                                                    src={s.thumb}
                                                    alt=""
                                                    className="aspect-square w-full rounded-sm bg-neutral-50 object-contain"
                                                />
                                            ) : (
                                                <div className="flex aspect-square w-full items-center justify-center rounded-sm bg-neutral-50">
                                                    <span className="text-xs text-neutral-400">
                                                        Finish on sides
                                                    </span>
                                                </div>
                                            )}
                                            <p className="mt-2 text-sm font-semibold">
                                                {s.label}
                                            </p>
                                            {s.description && (
                                                <p className="text-xs text-neutral-500">
                                                    {s.description}
                                                </p>
                                            )}
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        {!usesDynamicOptions && embossingList.length > 0 && (
                            <OptionGroup label="Embossing">
                                <div className="grid grid-cols-2 gap-3">
                                    {embossingList.map((e: any) => (
                                        <ChoiceTile
                                            key={e.id}
                                            active={selectedEmbossing === e.id}
                                            onClick={() => selectOption('embossing', e.id)}
                                        >
                                            <div className="flex aspect-square w-full items-center justify-center rounded-sm bg-neutral-50 p-4">
                                                {e.id === 'embossing' ? (
                                                    <span className="text-xs font-bold text-[#800020]">EMBOSSED TEXT</span>
                                                ) : (
                                                    <span className="text-xs text-neutral-400">FLAT TEXT</span>
                                                )}
                                            </div>
                                            <p className="mt-2 text-sm font-semibold">
                                                {e.label}
                                            </p>
                                            {e.description && (
                                                <p className="text-xs text-neutral-500">
                                                    {e.description}
                                                </p>
                                            )}
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        {!usesDynamicOptions && embossingOrSignaturePanelList.length > 0 && (
                            <OptionGroup label="Embossing or Signature Panel">
                                <div className="grid grid-cols-3 gap-3">
                                    {embossingOrSignaturePanelList.map((e: any) => (
                                        <ChoiceTile
                                            key={e.id}
                                            active={selectedEmbossingOrSignaturePanel === e.id}
                                            onClick={() => selectOption('embossing_or_signature_panel', e.id)}
                                        >
                                            <div className="flex aspect-square w-full items-center justify-center rounded-sm bg-neutral-50 p-4">
                                                {e.id === 'embossing' ? (
                                                    <span className="text-xs font-bold text-[#800020]">EMBOSSING</span>
                                                ) : e.id === 'signature_panel' ? (
                                                    <span className="text-xs font-bold text-[#800020]">SIGNATURE</span>
                                                ) : (
                                                    <span className="text-xs text-neutral-400">NONE</span>
                                                )}
                                            </div>
                                            <p className="mt-2 text-sm font-semibold">
                                                {e.label}
                                            </p>
                                            {e.description && (
                                                <p className="text-xs text-neutral-500">
                                                    {e.description}
                                                </p>
                                            )}
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        <OptionGroup label={c.configurator_labels.quantity}>
                            <div className="overflow-hidden rounded-md border border-neutral-200">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-neutral-50 text-left text-xs tracking-wide text-neutral-500 uppercase">
                                            <th className="px-4 py-2 font-medium">
                                                {c.quantity_table_headers[0]}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {c.quantity_table_headers[1]}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {c.quantity_table_headers[2]}
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                {c.quantity_table_headers[3]}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {quantityTiers.map((t: any) => {
                                            const recommended = !!t.recommended;
                                            const active =
                                                selectedQty === t.qty;
                                            const was = t.originalPrice;
                                            const now = t.currentPrice;

                                            return (
                                                <tr
                                                    key={t.qty}
                                                    onClick={() =>
                                                        setSelectedQty(t.qty)
                                                    }
                                                    className={`cursor-pointer transition-colors ${
                                                        active
                                                            ? 'bg-[#800020]/5'
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
                                                                checked={active}
                                                                onChange={() =>
                                                                    setSelectedQty(
                                                                        t.qty,
                                                                    )
                                                                }
                                                                className="size-4 accent-[#800020]"
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
                                                                ).toFixed(0)}
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
                                    {usesDynamicOptions ? (
                                        <>
                                            {dynamicOptionGroups.map((group) => {
                                                const selected = selectedOptions[group.key];
                                                const selectedCodes = Array.isArray(selected)
                                                    ? selected
                                                    : selected
                                                      ? [selected]
                                                      : [];
                                                const labels = group.values
                                                    .filter((value) =>
                                                        selectedCodes.includes(
                                                            optionValueCode(value),
                                                        ),
                                                    )
                                                    .map((value) => value.name);

                                                return (
                                                    <Fragment key={group.key}>
                                                        <dt className="text-neutral-500">
                                                            {group.label}
                                                        </dt>
                                                        <dd className="text-right font-medium">
                                                            {labels.join(', ')}
                                                        </dd>
                                                    </Fragment>
                                                );
                                            })}
                                            <dt className="text-neutral-500">
                                                {summaryLabels[2]}
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {selectedQty}
                                            </dd>
                                            {selectedDesignService && (
                                                <>
                                                    <dt className="text-neutral-500">
                                                        {designFeeLabel}
                                                    </dt>
                                                    <dd className="text-right font-medium">
                                                        ${designFee}
                                                    </dd>
                                                </>
                                            )}
                                        </>
                                    ) : (
                                        <>
                                    <dt className="text-neutral-500">
                                        {summaryLabels[0]}
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {finishLabel}
                                    </dd>
                                    {sizes.length > 0 && (
                                        <>
                                            <dt className="text-neutral-500">
                                                {summaryLabels[1]}
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {sizeLabel}
                                            </dd>
                                        </>
                                    )}
                                    <dt className="text-neutral-500">
                                        {summaryLabels[2]}
                                    </dt>
                                    <dd className="text-right font-medium">
                                        {selectedQty}
                                    </dd>
                                    {cornersList.length > 0 && (
                                        <>
                                            <dt className="text-neutral-500">
                                                {summaryLabels[3]}
                                            </dt>
                                            <dd className="text-right font-medium capitalize">
                                                {cornersLabel}
                                            </dd>
                                        </>
                                    )}
                                    {showTextureInSummary && (
                                        <>
                                            <dt className="text-neutral-500">
                                                Texture
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {textureLabel}
                                            </dd>
                                        </>
                                    )}
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
                                    {showEmbossingInSummary && (
                                        <>
                                            <dt className="text-neutral-500">
                                                Embossing
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {embossingLabel}
                                            </dd>
                                        </>
                                    )}
                                    {showEmbossingOrSignaturePanelInSummary && (
                                        <>
                                            <dt className="text-neutral-500">
                                                Embossing / Signature
                                            </dt>
                                            <dd className="text-right font-medium">
                                                {embossingOrSignaturePanelLabel}
                                            </dd>
                                        </>
                                    )}
                                    {selectedDesignService && (
                                        <>
                                            <dt className="text-neutral-500">
                                                {designFeeLabel}
                                            </dt>
                                            <dd className="text-right font-medium">
                                                ${designFee}
                                            </dd>
                                        </>
                                    )}
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
                            productOptions={c.design_form_product_options}
                        />
                        <DesignServiceFormModal
                            open={designModal === 'design-for-you'}
                            onOpenChange={(open) =>
                                setDesignModal(open ? 'design-for-you' : null)
                            }
                            title="Design for you"
                            description="Tell us about your brand and what you need — our designers will create the artwork for you."
                            productOptions={c.design_form_product_options}
                            designServices={designServicesConfig?.options}
                            designServicesHeading={
                                designServicesConfig?.heading
                            }
                            designServicesRequiredError={
                                designServicesConfig?.required_error
                            }
                            designServicesNote={designServicesConfig?.note}
                            returnTo={pageUrl}
                            onDesignServiceSaved={(code) =>
                                setSelectedDesignService(code)
                            }
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
            {productOptions?.detail_sections && (
                <>
                    {productOptions.detail_sections.design_specifications && (
                        <DesignSpecificationsSection
                            content={
                                productOptions.detail_sections.design_specifications
                            }
                        />
                    )}
                    {productOptions.detail_sections.design_service_banner && (
                        <DesignServiceBanner
                            content={
                                productOptions.detail_sections.design_service_banner
                            }
                        />
                    )}
                    {productOptions.detail_sections.paper_stocks && (
                        <PaperStockComparisonSection
                            content={productOptions.detail_sections.paper_stocks}
                        />
                    )}
                    {productOptions.detail_sections.more_good_stuff && (
                        <MoreGoodStuffSection
                            content={productOptions.detail_sections.more_good_stuff}
                        />
                    )}
                    {productOptions.detail_sections.faq && (
                        <ProductFaqSection
                            content={productOptions.detail_sections.faq}
                        />
                    )}
                </>
            )}
            
            {lightboxOpen && (
                <LightboxGallery
                    open={lightboxOpen}
                    onClose={() => setLightboxOpen(false)}
                    images={displayImages}
                    initialIndex={lightboxIndex}
                />
            )}
        </StorefrontLayout>
    );
}

/* -------------------------------------------------------------------------- */
/* Sub-components                                                             */
/* -------------------------------------------------------------------------- */

function FeatureChip({
    icon,
    label,
    description,
}: {
    icon: React.ReactNode;
    label: string;
    description?: string;
}) {
    return (
        <div className="flex gap-3 rounded-lg border border-neutral-200 bg-white p-4">
            <span className="mt-0.5 shrink-0">{icon}</span>
            <div>
                <p className="text-sm font-bold text-neutral-900">{label}</p>
                {description && (
                    <p className="mt-1 text-xs leading-relaxed text-neutral-500">
                        {description}
                    </p>
                )}
            </div>
        </div>
    );
}

function DynamicOptionGroups({
    groups,
    selected,
    onSelect,
}: {
    groups: ProductOptionGroup[];
    selected: Record<string, string | string[]>;
    onSelect: (groupKey: string, value: string) => void;
}) {
    return (
        <>
            {groups.map((group) => (
                <OptionGroup key={group.key} label={group.label}>
                    <div className="grid grid-cols-3 gap-3">
                        {group.values.map((value) => {
                            const code = optionValueCode(value);
                            const selectedValue = selected[group.key];
                            const active =
                                group.type === 'multi_select'
                                    ? Array.isArray(selectedValue) &&
                                      selectedValue.includes(code)
                                    : selectedValue === code;
                            const swatch = value.swatch_image;
                            const isSvg =
                                typeof swatch === 'string' &&
                                swatch.trimStart().startsWith('<svg');

                            return (
                                <ChoiceTile
                                    key={code}
                                    active={active}
                                    onClick={() => onSelect(group.key, code)}
                                >
                                    <div className="flex min-h-16 items-center justify-center">
                                        {isSvg ? (
                                            <div
                                                className="h-16 w-full text-neutral-700"
                                                dangerouslySetInnerHTML={{
                                                    __html: swatch as string,
                                                }}
                                            />
                                        ) : swatch ? (
                                            <img
                                                src={swatch}
                                                alt=""
                                                className="h-auto w-full rounded-sm object-contain"
                                            />
                                        ) : (
                                            <span className="text-xs text-neutral-400">
                                                {group.type === 'multi_select'
                                                    ? 'Select option'
                                                    : 'Option'}
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-2 text-base font-semibold">
                                        {value.name}
                                    </p>
                                    {value.description && (
                                        <p className="text-xs text-neutral-500">
                                            {value.description}
                                        </p>
                                    )}
                                </ChoiceTile>
                            );
                        })}
                    </div>
                </OptionGroup>
            ))}
        </>
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
            <legend className="mb-3 text-base font-bold text-neutral-900">
                {label}
            </legend>
            {children}
        </fieldset>
    );
}

function ChoiceTile({
    active,
    disabled,
    onClick,
    children,
}: {
    active: boolean;
    disabled?: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={`rounded-md border-2 p-3 text-left transition-colors ${
                disabled
                    ? 'cursor-not-allowed border-neutral-100 bg-neutral-50 opacity-50'
                    : active
                      ? 'border-[#800020] bg-[#800020]/5'
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
            className="flex h-full flex-col items-start gap-2 rounded-md border-2 border-neutral-200 bg-white p-4 text-left transition-colors hover:border-[#800020] hover:bg-[#800020]/5"
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
    productOptions,
    designServices,
    designServicesHeading,
    designServicesRequiredError,
    designServicesNote,
    returnTo,
    onDesignServiceSaved,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    productOptions?: string[];
    designServices?: DesignServiceOption[];
    designServicesHeading?: string;
    designServicesRequiredError?: string;
    designServicesNote?: string;
    returnTo?: string;
    onDesignServiceSaved?: (code: string) => void;
}) {
    const ds = useContent('design_service_page') as {
        notes_heading?: string;
        notes?: string[];
    };
    const [designServiceCode, setDesignServiceCode] = useState('');
    const [designServiceError, setDesignServiceError] = useState<string | null>(
        null,
    );
    const hasDesignServices = (designServices?.length ?? 0) > 0;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-7xl">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                <div className="mt-4">
                    <div className="grid grid-cols-1 gap-10 md:grid-cols-12">
                        {/* Left: Terms & Notes + Design Service Selection */}
                        <div className="md:col-span-7 space-y-8">
                            <div>
                                <h3 className="font-serif text-xl font-bold text-[#800020]">
                                    {ds.notes_heading ?? 'Terms & notes'}
                                </h3>
                                <ol className="mt-4 space-y-3">
                                    {(ds.notes ?? []).map((note, i) => (
                                        <li key={i} className="flex gap-3">
                                            <span
                                                className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                                style={{ backgroundColor: '#800020' }}
                                            >
                                                {i + 1}
                                            </span>
                                            <p className="text-sm leading-relaxed text-neutral-700">
                                                {note}
                                            </p>
                                        </li>
                                    ))}
                                </ol>
                            </div>

                            {hasDesignServices && (
                                <div className="border-t border-neutral-100 pt-6">
                                    <h3 className="text-base font-bold text-neutral-900">
                                        {designServicesHeading ?? 'Choose a design service'}
                                    </h3>
                                    <div className="mt-3 grid grid-cols-1 gap-3">
                                        {designServices!.map((option) => {
                                            const active =
                                                designServiceCode === option.code;

                                            return (
                                                <label
                                                    key={option.code}
                                                    className={`flex cursor-pointer items-start gap-3 rounded-md border-2 p-4 transition-colors ${
                                                        active
                                                            ? 'border-[#800020] bg-[#800020]/5'
                                                            : 'border-neutral-200 hover:border-neutral-300'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="design_service_code"
                                                        value={option.code}
                                                        checked={active}
                                                        onChange={() => {
                                                            setDesignServiceCode(
                                                                option.code,
                                                                );
                                                            setDesignServiceError(null);
                                                        }}
                                                        className="mt-0.5 size-4 accent-[#800020]"
                                                    />
                                                    <span>
                                                        <span className="block text-sm font-bold text-neutral-900">
                                                            {option.title}
                                                        </span>
                                                        {option.description && (
                                                            <span className="mt-1 block text-xs leading-relaxed text-neutral-600">
                                                                {option.description}
                                                            </span>
                                                        )}
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                    {designServiceError && (
                                        <p className="mt-2 text-sm text-red-600">
                                            {designServiceError}
                                        </p>
                                    )}
                                    {designServicesNote && (
                                        <p className="mt-3 rounded-md border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs leading-relaxed text-neutral-600">
                                            {designServicesNote}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Right: Form */}
                        <div className="md:col-span-5">
                            <DesignServiceForm
                                productOptions={productOptions}
                                designServices={designServices}
                                designServicesHeading={designServicesHeading}
                                designServicesRequiredError={
                                    designServicesRequiredError
                                }
                                designServicesNote={designServicesNote}
                                returnTo={returnTo}
                                onDesignServiceSaved={onDesignServiceSaved}
                                designServiceCode={designServiceCode}
                                onDesignServiceCodeChange={setDesignServiceCode}
                                onDesignServiceError={setDesignServiceError}
                                hideDesignServices
                                onSuccess={() => onOpenChange(false)}
                            />
                        </div>
                    </div>
                </div>
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
