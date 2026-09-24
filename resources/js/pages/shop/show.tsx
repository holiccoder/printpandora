// Global UI labels and common site copy come from
// `content/hardcoded-content.json` via useContent('product_detail_page').
// Product fields, pricing, FAQs, and detail sections come from the database;
// the product-option JSON is limited to option metadata and galleries.
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Lightbulb } from 'lucide-react';
import { Fragment, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import DesignServiceFormModal from '@/components/design-service-form-modal';
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
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import { computeDynamicTiers, squareInchesToSquareMetres } from '@/lib/pricing';
import type { DynamicPricingData } from '@/lib/pricing';
import type { PricingRule } from '@/lib/pricing';
import { isPvcProductSlug } from '@/lib/product-images';
import {
    applyTextureUvSelection,
    findMatchingGallery,
    getProductThumbnailImages,
    getPreferredGalleryMatchKey,
} from '@/lib/product-options';
import type { ProductGallery } from '@/lib/product-options';
import { cn } from '@/lib/utils';

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

const CUSTOM_SIZE_MIN = 2.1;
const CUSTOM_SIZE_MAX = 3.5;
const NO_SPECIAL_FINISH_CODES = [
    'none',
    'no-special-finish',
    'no_special_finish',
    'no-foil',
    'no_foil',
];
const HOT_FOIL_CODES = new Set([
    'black gold',
    'blue gold',
    'bright gold',
    'bright silver',
    'green gold',
    'matte gold',
    'matte silver',
    'red gold',
    'rose gold',
    'aged gold',
    'muted purple gold',
]);

type SpecialFinishSide = 'one_side' | 'both_sides';
type FoilOptionGroupKey = 'special_finish' | 'hot_foil';

const DEFAULT_SPECIAL_FINISH_SIDE: SpecialFinishSide = 'one_side';
const SPECIAL_FINISH_SIDE_OPTIONS: Array<{
    value: SpecialFinishSide;
    label: string;
}> = [
    { value: 'one_side', label: 'single side' },
    { value: 'both_sides', label: 'both sides' },
];

const OPTION_GROUP_ORDER: Record<string, number> = {
    sizes: 1,
    size: 1,
    corners: 2,
    corner: 2,
    thickness: 3,
    texture: 4,
    paper_finish: 5,
    uv_finish: 6,
    special_finish: 7,
    hot_foil: 8,
};

const OPTION_GROUP_FALLBACK_ORDER = Object.keys(OPTION_GROUP_ORDER).length + 1;

import DesignSpecificationsSection from '@/components/product-detail/design-specifications-section';
import DesignServiceBanner from '@/components/product-detail/design-service-banner';
import PaperStockComparisonSection from '@/components/product-detail/paper-stock-comparison-section';
import MoreGoodStuffSection from '@/components/product-detail/more-good-stuff-section';
import ProductFaqSection from '@/components/product-detail/product-faq-section';
import LightboxGallery from '@/components/product-detail/lightbox-gallery';
import type {
    ProductDetailSections,
    ProductFeatureCardContent,
} from '@/types/product-detail';

interface Product {
    id: number;
    name: string;
    slug: string;
    subtitle: string | null;
    description: string | null;
    description_title: string | null;
    bullet_points: string[] | null;
    price_line: string | null;
    price: string;
    featured_image: string | null;
    category: { id: number; name: string; slug: string };
}

interface ProductOptionValue {
    code?: string;
    name: string;
    description?: string;
    swatch_image?: string;
    color_swatch_image?: string;
    width?: string;
    height?: string;
    min_width?: string;
    max_width?: string;
    min_height?: string;
    max_height?: string;
    thickness_code?: string;
    texture_code?: string;
    texture_label?: string;
    color_code?: string;
    color_label?: string;
}

interface ProductOptionGroup {
    key: string;
    label: string;
    type: 'select' | 'multi_select';
    required?: boolean;
    default?: string | string[] | null;
    values: ProductOptionValue[];
}

interface ProductOptions {
    dynamic_options?: boolean;
    show_gang_run_printing?: boolean;
    option_groups?: ProductOptionGroup[];
    sizes?: Array<{
        code?: string;
        name: string;
        description?: string;
        width?: string;
        height?: string;
        swatch_image?: string;
        min_width?: string;
        max_width?: string;
        min_height?: string;
        max_height?: string;
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
        swatch_image?: string;
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

interface CustomSizeLimits {
    minWidth: number;
    maxWidth: number;
    minHeight: number;
    maxHeight: number;
}

const DEFAULT_CUSTOM_SIZE_LIMITS: CustomSizeLimits = {
    minWidth: CUSTOM_SIZE_MIN,
    maxWidth: CUSTOM_SIZE_MAX,
    minHeight: CUSTOM_SIZE_MIN,
    maxHeight: CUSTOM_SIZE_MAX,
};

function customSizeLimitsForValue(
    value?: Pick<
        ProductOptionValue,
        'min_width' | 'max_width' | 'min_height' | 'max_height'
    >,
): CustomSizeLimits {
    const read = (candidate: string | undefined, fallback: number) => {
        const parsed = Number(candidate);

        return Number.isFinite(parsed) ? parsed : fallback;
    };

    return {
        minWidth: read(value?.min_width, DEFAULT_CUSTOM_SIZE_LIMITS.minWidth),
        maxWidth: read(value?.max_width, DEFAULT_CUSTOM_SIZE_LIMITS.maxWidth),
        minHeight: read(
            value?.min_height,
            DEFAULT_CUSTOM_SIZE_LIMITS.minHeight,
        ),
        maxHeight: read(
            value?.max_height,
            DEFAULT_CUSTOM_SIZE_LIMITS.maxHeight,
        ),
    };
}

function formatSizeLimit(value: number): string {
    return value.toFixed(2);
}

const STICKER_PRODUCT_SLUGS = [
    'classic-stickers',
    'premium-stickers',
    'super-stickers',
] as const;

function isStickerProductSlug(slug: string): boolean {
    return (STICKER_PRODUCT_SLUGS as readonly string[]).includes(slug);
}

function stickerAreaForSizeValues(
    values: ProductOptionValue[],
    selected?: string | string[],
): number {
    const code = Array.isArray(selected) ? selected[0] : selected;

    if (!code) {
        return 0;
    }

    const value = values.find(
        (candidate) => optionValueCode(candidate) === code,
    );

    return squareInchesToSquareMetres(
        Number(value?.width),
        Number(value?.height),
    );
}

function stickerAreaForSize(
    groups: ProductOptionGroup[],
    selected?: string | string[],
): number {
    return stickerAreaForSizeValues(
        groups.find((group) => group.key === 'sizes')?.values ?? [],
        selected,
    );
}

function stickerAreaForCustomSize(width: number, height: number): number {
    return squareInchesToSquareMetres(width, height);
}

function formatStickerArea(area: number): string {
    return area > 0 ? area.toFixed(8) : '';
}

function stickerAreaOptionValue(area: number): string {
    return area > 0 ? area.toFixed(8) : '';
}

interface Props {
    product: Product;
    productOptions?: ProductOptions;
    fallbackGalleryImages?: string[];
    deliveryEstimates: {
        standard: string;
        fast: string;
    };
}

/* -------------------------------------------------------------------------- */
/* Layout-only metadata (shapes, hrefs) — kept here, not in JSON              */
/* -------------------------------------------------------------------------- */

const sizeShapes: Record<string, 'rect' | 'square'> = {
    standard: 'rect',
    square: 'square',
};

const generatedSwatchBase = '/images/product-options/business-cards/generated';
const reusableSwatchBase = '/images/product-options/business-cards/swatches';

const generatedSizeSwatches: Record<string, string> = {
    standard: `${reusableSwatchBase}/standard-size.webp`,
    square: `${reusableSwatchBase}/square-size.webp`,
    custom: `${reusableSwatchBase}/custom-size.webp`,
};

const captionedSizeSwatchCodes = new Set(['standard', 'square', 'custom']);

function shouldShowSwatchCaption(groupKey: string, code: string): boolean {
    return (
        groupKey === 'sizes' && captionedSizeSwatchCodes.has(code.toLowerCase())
    );
}

function sizeSwatchFor(code: string, fallback?: string): string | undefined {
    return generatedSizeSwatches[code.toLowerCase()] ?? fallback;
}

const generatedFinishSwatches: Record<string, string> = {
    matte: `${reusableSwatchBase}/matte-paper-finish.webp`,
    gloss: `${reusableSwatchBase}/gloss-paper-finish.webp`,
    cotton: `${generatedSwatchBase}/cotton.png`,
    pvc: `${generatedSwatchBase}/pvc.png`,
};

const generatedCornerSwatches: Record<string, string> = {
    square: `${reusableSwatchBase}/square-corner.svg`,
    standard: `${reusableSwatchBase}/square-corner.svg`,
    rounded: `${reusableSwatchBase}/rounded-corner.svg`,
};

function cornerSwatchFor(value: string): string | undefined {
    const normalized = value.toLowerCase();

    if (normalized.includes('round')) {
        return generatedCornerSwatches.rounded;
    }

    if (normalized.includes('square') || normalized.includes('standard')) {
        return generatedCornerSwatches.square;
    }

    return undefined;
}

// Display-only mirror of the server-side fee map
// (App\Models\DesignServiceRequest::DESIGN_SERVICE_FEES). The server
// re-computes the fee from the code; clients never submit amounts.
const designServiceFees: Record<string, number> = {
    card_layout: 29,
    card_design: 79,
};

const businessBlockHrefs = [
    '/business-card-design-service',
    '/business-card-design-service',
    '/contact-us',
];

function productCategoryHref(categorySlug: string): string {
    switch (categorySlug) {
        case 'postcards':
        case 'cards-and-postcards':
            return '/cards-and-postcards';
        case 'stickers-and-labels':
        case 'stickers-labels':
            return '/stickers-and-labels';
        case 'flyers':
        case 'flyers-brochures':
        case 'flyers-and-brochures':
            return '/flyers-and-brochures';
        default:
            return '/business-cards';
    }
}

function optionValueCode(value: ProductOptionValue): string {
    return (
        value.code?.trim() ||
        value.name
            .trim()
            .toLowerCase()
            .replace(/[\s_]+/g, '-')
    );
}

function accentProductShortDescription(html: string): string {
    const withAvailableHeading = html.replace(
        /(<(?:strong|b)\b[^>]*>)(\s*Available(?:\s+finishing)?\s+(?:options?|finishes?)\s*:?\s*)(<\/(?:strong|b)>)/gi,
        '$1<span class="text-primary">$2</span>$3',
    );

    return withAvailableHeading
        .split(/(<[^>]+>)/g)
        .map((part) => {
            if (part.startsWith('<')) {
                return part;
            }

            return part.replace(
                /\bshipping\b|\bbusiness(?:\s|&nbsp;|-)days?\b/gi,
                (match) => `<span class="text-primary">${match}</span>`,
            );
        })
        .join('');
}

function isColdFoilCode(value?: string | null): boolean {
    return normalizeOptionText(value).startsWith('cold ');
}

function isFoilOption(
    code?: string | null,
    label?: string | null,
    description?: string | null,
): boolean {
    if (isNoSpecialFinishCode(code)) {
        return false;
    }

    const normalizedCode = normalizeOptionText(code);
    const text = [normalizedCode, label, description]
        .map(normalizeOptionText)
        .filter(Boolean)
        .join(' ');

    return (
        isColdFoilCode(code) ||
        HOT_FOIL_CODES.has(normalizedCode) ||
        text.includes('foil')
    );
}

interface SelectedOptionDetails {
    groupKey: string;
    code?: string | null;
    label?: string | null;
    description?: string | null;
}

function normalizeOptionText(value?: string | null): string {
    return value?.trim().toLowerCase().replace(/[_-]+/g, ' ') ?? '';
}

function isNoSpecialFinishCode(value?: string | null): boolean {
    const normalized = normalizeOptionText(value).replace(/\s+/g, '_');

    return NO_SPECIAL_FINISH_CODES.includes(normalized);
}

function selectedSpecialFinishCodes(
    value: string | string[] | null | undefined,
): string[] {
    const values = Array.isArray(value) ? value : value ? [value] : [];

    return [...new Set(values.filter((code) => !isNoSpecialFinishCode(code)))];
}

function specialFinishSidesForSelection(
    value: string | string[] | null | undefined,
    sides: Record<string, SpecialFinishSide>,
): Record<string, SpecialFinishSide> | undefined {
    const codes = selectedSpecialFinishCodes(value);

    if (codes.length === 0) {
        return undefined;
    }

    return codes.reduce<Record<string, SpecialFinishSide>>((result, code) => {
        result[code] = sides[code] ?? DEFAULT_SPECIAL_FINISH_SIDE;

        return result;
    }, {});
}

function foilSidesForSelection(
    options: Record<string, string | string[]>,
    sides: Record<string, SpecialFinishSide>,
): Record<string, SpecialFinishSide> {
    return {
        ...(specialFinishSidesForSelection(options.special_finish, sides) ??
            {}),
        ...(specialFinishSidesForSelection(options.hot_foil, sides) ?? {}),
    };
}

function optionAddsTurnaroundTime(option: SelectedOptionDetails): boolean {
    const group = normalizeOptionText(option.groupKey);
    const code = normalizeOptionText(option.code);
    const text = [group, code, option.label, option.description]
        .map(normalizeOptionText)
        .filter(Boolean)
        .join(' ');
    const isNoSpecialFinish =
        NO_SPECIAL_FINISH_CODES.some(
            (value) => normalizeOptionText(value) === code,
        ) || /\bno\s+(?:special\s+)?finish\b/.test(text);

    if (isNoSpecialFinish) {
        return false;
    }

    const isFoil =
        text.includes('foil') ||
        (group.includes('special finish') &&
            (HOT_FOIL_CODES.has(code) || code.startsWith('cold ')));
    const isThreeDUv =
        /\b3d\s+uv\b/.test(text) || (code === 'uv' && group.includes('finish'));
    const isCustomDieCut =
        /\bdie\s+cut\b/.test(text) ||
        (code === 'custom' && /(corner|shape|die)/.test(group));

    return isFoil || isThreeDUv || isCustomDieCut;
}

function hasAdditionalTurnaroundTime(
    options: SelectedOptionDetails[],
): boolean {
    return options.some(optionAddsTurnaroundTime);
}

function selectedDynamicOptionDetails(
    groups: ProductOptionGroup[],
    selected: Record<string, string | string[]>,
): SelectedOptionDetails[] {
    return groups.flatMap((group) => {
        const selectedValues = selected[group.key];
        const selectedCodes = (
            Array.isArray(selectedValues)
                ? selectedValues
                : selectedValues != null
                  ? [selectedValues]
                  : []
        ).filter((code) => code !== '');

        return selectedCodes.map((code) => {
            const value = group.values.find(
                (candidate) => optionValueCode(candidate) === code,
            );

            return {
                groupKey: group.key,
                code,
                label:
                    group.key === 'texture' && value?.texture_label
                        ? `${value.texture_label}${value.color_label ? ` · ${value.color_label}` : ''}`
                        : value?.name,
                description: value?.description,
            };
        });
    });
}

function addTurnaroundTime(workdays: string): string {
    const range = workdays.match(/(\d+)\s*-\s*(\d+)/);

    if (!range) {
        return `${workdays} + 5 - 7 Business Days`;
    }

    return `${Number(range[1]) + 5} - ${Number(range[2]) + 7} Workdays`;
}

function orderOptionGroups(groups: ProductOptionGroup[]): ProductOptionGroup[] {
    return groups
        .map((group, index) => ({ group, index }))
        .sort((left, right) => {
            const leftOrder =
                OPTION_GROUP_ORDER[left.group.key] ??
                OPTION_GROUP_FALLBACK_ORDER;
            const rightOrder =
                OPTION_GROUP_ORDER[right.group.key] ??
                OPTION_GROUP_FALLBACK_ORDER;

            return leftOrder - rightOrder || left.index - right.index;
        })
        .map(({ group }) => group);
}

function getProductTurnaround(
    product: Product,
    hasAdditionalTurnaround: boolean,
): { label: string; description: string } | null {
    const slug = product.slug.toLowerCase();
    const name = product.name.toLowerCase();
    const category = product.category?.slug.toLowerCase();

    let workdays: string | null = null;

    if (slug.includes('metal') || name.includes('metal business card')) {
        workdays = '15 - 20 Workdays';
    } else if (
        isPvcProductSlug(slug) ||
        category === 'pvc-business-cards' ||
        name.includes('pvc')
    ) {
        workdays =
            slug.includes('premium') || name.includes('premium')
                ? '5 - 7 Workdays'
                : slug.includes('standard') || name.includes('standard')
                  ? '3 - 5 Workdays'
                  : '2 - 3 Workdays';
    } else if (
        category === 'cotton-business-cards' ||
        slug.includes('cotton') ||
        name.includes('cotton business card')
    ) {
        workdays = '5 - 7 Workdays';
    } else if (
        slug === 'super-luxe-business-cards' ||
        name === 'super luxe business cards'
    ) {
        workdays = '3 - 4 Workdays';
    } else if (
        slug === 'super-standard-business-cards' ||
        name.includes('super standard business card')
    ) {
        workdays = '2 - 3 Workdays';
    } else if (category === 'stickers-and-labels' || slug.includes('sticker')) {
        workdays = '3 - 4 Workdays';
    } else if (
        category === 'quality-business-cards' ||
        slug.includes('quality') ||
        slug.includes('classic') ||
        name.includes('classic business card')
    ) {
        workdays = '2 - 3 Workdays';
    }

    if (workdays === null) {
        return null;
    }

    return {
        label: `${hasAdditionalTurnaround ? addTurnaroundTime(workdays) : workdays} Turnaround Time`,
        description: hasAdditionalTurnaround
            ? `Custom business days for ${product.name}. The selected option adds 5 - 7 business days.`
            : `Custom business days for ${product.name}.`,
    };
}

/* -------------------------------------------------------------------------- */
/* Page                                                                       */
/* -------------------------------------------------------------------------- */

/**
 * Prices and other dynamic values must not be machine-translated: Google
 * Translate localizes punctuation (turning "1.9" into "1。9") and wraps
 * text nodes in <font> elements, which makes React's in-place text updates
 * land on detached nodes. translate="no" keeps Translate away entirely;
 * keying by the rendered string is a belt-and-suspenders remount in case
 * another translator ignores the attribute.
 */
function LiveText({ text, className }: { text: string; className?: string }) {
    return (
        <span key={text} className={className} translate="no">
            {text}
        </span>
    );
}

export default function ShopShow({
    product,
    productOptions,
    fallbackGalleryImages,
    deliveryEstimates,
}: Props) {
    const c = useContent('product_detail_page') as any;
    const isPvcProduct =
        isPvcProductSlug(product.slug) ||
        product.category.slug === 'pvc-business-cards';
    const isBusinessCardProduct =
        product.slug.includes('business-card') ||
        product.category?.slug.includes('business-card');
    const isStickerProduct = isStickerProductSlug(product.slug);
    const ACCENT = c.accent_color;

    const galleryThumbs: string[] = c.gallery_thumb_image_urls;
    const finishThumbs: string[] = c.finish_thumb_image_urls;

    const hasProductOptions = productOptions != null;
    const showGangRunPrinting = productOptions?.show_gang_run_printing === true;
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
                ? orderOptionGroups(productOptions.option_groups)
                : [],
        [usesDynamicOptions, productOptions],
    );
    const specialFinishGroup = dynamicOptionGroups.find(
        (group) => group.key === 'special_finish',
    );
    const specialFinishRequired = specialFinishGroup?.required === true;

    const dynamicOptionDefaults = useMemo<
        Record<string, string | string[]>
    >(() => {
        const defaults: Record<string, string | string[]> = {};

        for (const group of dynamicOptionGroups) {
            const valueCodes = group.values.map(optionValueCode);
            const firstCode = valueCodes[0] ?? '';

            if (group.type === 'multi_select') {
                const configuredDefaults = Array.isArray(group.default)
                    ? group.default
                    : typeof group.default === 'string'
                      ? [group.default]
                      : null;

                if (!group.required && configuredDefaults === null) {
                    defaults[group.key] = [];

                    continue;
                }

                defaults[group.key] =
                    configuredDefaults !== null
                        ? configuredDefaults.filter((code) =>
                              valueCodes.includes(code),
                          )
                        : firstCode
                          ? [firstCode]
                          : [];

                continue;
            }

            const configuredDefault =
                typeof group.default === 'string' ? group.default.trim() : '';

            if (!group.required && configuredDefault === '') {
                defaults[group.key] = '';

                continue;
            }

            defaults[group.key] = valueCodes.includes(configuredDefault)
                ? configuredDefault
                : firstCode;
        }

        return defaults;
    }, [dynamicOptionGroups]);

    const customSizeLimits = useMemo(() => {
        const dynamicCustomSize = dynamicOptionGroups
            .find((group) => group.key === 'sizes')
            ?.values.find((value) => optionValueCode(value) === 'custom');
        const staticCustomSize = productOptions?.sizes?.find(
            (value) => (value.code ?? '').trim() === 'custom',
        );

        return customSizeLimitsForValue(dynamicCustomSize ?? staticCustomSize);
    }, [dynamicOptionGroups, productOptions]);

    const sizes = useMemo(() => {
        const sizeSwatches = generatedSizeSwatches;

        return hasProductOptions && Array.isArray(productOptions.sizes)
            ? productOptions.sizes.map((s) => {
                  const id = s.code ?? s.name.toLowerCase();
                  const isCustom = id === 'custom';

                  return {
                      id,
                      label: s.name.charAt(0).toUpperCase() + s.name.slice(1),
                      dims: isCustom
                          ? (s.description ??
                            `W ${formatSizeLimit(customSizeLimits.minWidth)}-${formatSizeLimit(customSizeLimits.maxWidth)} in × H ${formatSizeLimit(customSizeLimits.minHeight)}-${formatSizeLimit(customSizeLimits.maxHeight)} in`)
                          : s.width && s.height
                            ? `${s.width}" x ${s.height}"`
                            : '',
                      swatch: sizeSwatchFor(
                          id,
                          sizeSwatches[s.name.toLowerCase()] ?? s.swatch_image,
                      ),
                  };
              })
            : c.configurator_options.sizes.map((s: any) => ({
                  ...s,
                  swatch: sizeSwatches[s.id.toLowerCase()] ?? s.swatch,
              }));
    }, [
        hasProductOptions,
        productOptions,
        c.configurator_options.sizes,
        product.slug,
        customSizeLimits,
    ]);

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
                      swatch: cornerSwatchFor(cn.name) ?? cn.swatch_image,
                  }))
                : c.configurator_options.corners.map((cn: any) => ({
                      ...cn,
                      swatch: cornerSwatchFor(cn.id) ?? cn.swatch,
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
                      .filter((f) => !isNoSpecialFinishCode(f.code ?? f.name))
                      .map((f) => ({
                          id:
                              f.code ??
                              f.name.toLowerCase().replace(/\s+/g, '-'),
                          label:
                              f.name.charAt(0).toUpperCase() + f.name.slice(1),
                          description: f.description,
                          thumb: f.swatch_image,
                      }))
                : [],
        [hasProductOptions, productOptions],
    );

    // Dynamic pricing is read from the database-backed product configuration.
    const hasDynamicPricing =
        hasProductOptions &&
        (productOptions.pricing_data != null ||
            (productOptions.pricing_rules?.length ?? 0) > 0);

    const dynamicRecommendedQty = hasDynamicPricing
        ? (productOptions.pricing_data?.rectangle?.recommendedQuantity ??
          productOptions.pricing_rules?.[0]?.pricing.recommendedQuantity ??
          productOptions.pricing_data?.rectangle?.startQuantity ??
          productOptions.pricing_rules?.[0]?.pricing.startQuantity ??
          null)
        : null;

    // "X cards from $Y" derived from data: X = startQuantity from the
    // pricing JSON, Y = subtotal (currentPrice) of the first row of the
    // quantity pricing table under the default option configuration.
    const startingPriceText = useMemo(() => {
        if (hasDynamicPricing && productOptions.pricing_data) {
            const defaultPricingOptions: Record<string, string | string[]> = {};

            for (const group of dynamicOptionGroups) {
                const value = dynamicOptionDefaults[group.key];

                if (value !== undefined) {
                    defaultPricingOptions[group.key] = value;
                }
            }

            if (isStickerProduct) {
                defaultPricingOptions.paper_area = stickerAreaOptionValue(
                    stickerAreaForSize(
                        dynamicOptionGroups,
                        dynamicOptionDefaults.sizes,
                    ),
                );
            }

            const firstTier = computeDynamicTiers(
                {
                    ...productOptions.pricing_data,
                    rules: productOptions.pricing_rules,
                },
                0, // default size
                0, // default paper finish
                0, // default corners
                0, // default special finish
                defaultPricingOptions,
            )[0];

            if (firstTier) {
                return `${firstTier.qty} ${isStickerProduct ? 'stickers' : 'cards'} from $${firstTier.currentPrice}`;
            }
        }

        if (hasDynamicPricing && productOptions.pricing_rules?.[0]?.pricing) {
            const defaultPricingOptions: Record<string, string | string[]> = {};

            for (const group of dynamicOptionGroups) {
                const value = dynamicOptionDefaults[group.key];

                if (value !== undefined) {
                    defaultPricingOptions[group.key] = value;
                }
            }

            if (isStickerProduct) {
                defaultPricingOptions.paper_area = stickerAreaOptionValue(
                    stickerAreaForSize(
                        dynamicOptionGroups,
                        dynamicOptionDefaults.sizes,
                    ),
                );
            }

            const firstTier = computeDynamicTiers(
                { rules: productOptions.pricing_rules },
                0,
                0,
                0,
                0,
                defaultPricingOptions,
            )[0];

            if (firstTier) {
                return `${firstTier.qty} ${isStickerProduct ? 'stickers' : 'cards'} from $${firstTier.currentPrice}`;
            }

            const pricing = productOptions.pricing_rules[0].pricing;
            const defaultArea = isStickerProduct
                ? stickerAreaForSize(
                      dynamicOptionGroups,
                      dynamicOptionDefaults.sizes,
                  )
                : 1;
            const defaultMultiplier =
                pricing.unitMultipliers?.[String(pricing.startQuantity)] ?? 1;
            const total = Math.round(
                pricing.startQuantity *
                    pricing.basePrice *
                    defaultMultiplier *
                    defaultArea,
            );

            return `${pricing.startQuantity} ${isStickerProduct ? 'stickers' : 'cards'} from $${total}`;
        }

        return product.price_line ?? undefined;
    }, [
        dynamicOptionDefaults,
        dynamicOptionGroups,
        hasDynamicPricing,
        isStickerProduct,
        productOptions,
        product.price_line,
    ]);

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

    const RECOMMENDED_QTY = dynamicRecommendedQty ?? staticRecommendedQty;

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

    const [selectedSize, setSelectedSize] = useState<string | null>(() => {
        return sizes.length > 0 ? sizes[0].id : null;
    });
    const [customSizeOpen, setCustomSizeOpen] = useState(false);
    const [customWidth, setCustomWidth] = useState('');
    const [customHeight, setCustomHeight] = useState('');
    const [confirmedCustomSize, setConfirmedCustomSize] = useState<{
        width: number;
        height: number;
    } | null>(null);
    const [customSizeError, setCustomSizeError] = useState<string | null>(null);
    const [selectedFinish, setSelectedFinish] = useState<string | null>(() => {
        return finishes.length > 0 ? finishes[0].id : null;
    });
    const [selectedCorners, setSelectedCorners] = useState<string | null>(
        () => {
            return cornersList.length > 0 ? cornersList[0].id : null;
        },
    );
    const [selectedDynamicOptions, setSelectedDynamicOptions] = useState<
        Record<string, string | string[]>
    >(dynamicOptionDefaults);
    const [selectedTexture, setSelectedTexture] = useState<string | null>(
        () => {
            return textures.length > 0 ? textures[0].id : 'none';
        },
    );
    const [selectedSpecialFinish, setSelectedSpecialFinish] = useState<
        string | null
    >(() => {
        return specialFinishes.length > 0 && specialFinishRequired
            ? specialFinishes[0].id
            : null;
    });
    const [selectedSpecialFinishSides, setSelectedSpecialFinishSides] =
        useState<Record<string, SpecialFinishSide>>({});
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
        markInteracted();
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
        if (
            !hasProductOptions ||
            !(productOptions as any).embossing_or_signature_panel
        ) {
            return [];
        }
        return (productOptions as any).embossing_or_signature_panel.map(
            (item: any) => ({
                id: item.code,
                label: item.name,
                description: item.description,
            }),
        );
    }, [hasProductOptions, productOptions]);

    const [selectedEmbossing, setSelectedEmbossing] = useState<string | null>(
        () => {
            return embossingList.length > 0 ? embossingList[0].id : 'none';
        },
    );

    const [
        selectedEmbossingOrSignaturePanel,
        setSelectedEmbossingOrSignaturePanel,
    ] = useState<string | null>(() => {
        return embossingOrSignaturePanelList.length > 0
            ? embossingOrSignaturePanelList[0].id
            : 'none';
    });

    const [selectedQty, setSelectedQty] = useState<number | null>(
        RECOMMENDED_QTY,
    );
    const [selectedThumbnail, setSelectedThumbnail] = useState<string | null>(
        null,
    );
    const [lastSelectedGalleryOptionKey, setLastSelectedGalleryOptionKey] =
        useState<string | null>(null);
    const [lastInteractedOptionGroupKey, setLastInteractedOptionGroupKey] =
        useState<string | null>(null);
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);
    const [added, setAdded] = useState(false);
    const [checkoutConfirmationOpen, setCheckoutConfirmationOpen] =
        useState(false);
    const [isSubmittingCart, setIsSubmittingCart] = useState(false);
    const [designModal, setDesignModal] = useState<
        'canva' | 'upload' | 'design-for-you' | null
    >(null);
    const [submittedDesignModes, setSubmittedDesignModes] = useState<
        Record<'canva' | 'upload' | 'design-for-you', boolean>
    >({
        canva: false,
        upload: false,
        'design-for-you': false,
    });
    const [designSelectionError, setDesignSelectionError] = useState<
        string | null
    >(null);
    const [selectedDesignService, setSelectedDesignService] = useState<
        string | null
    >(null);
    const [hasInteracted, setHasInteracted] = useState(false);

    const stickerPaperArea = useMemo(() => {
        if (!isStickerProduct) {
            return 0;
        }

        const selected = usesDynamicOptions
            ? selectedDynamicOptions.sizes
            : selectedSize;

        if (selected === 'custom') {
            return confirmedCustomSize
                ? stickerAreaForCustomSize(
                      confirmedCustomSize.width,
                      confirmedCustomSize.height,
                  )
                : 0;
        }

        const configuredSizeValues = usesDynamicOptions
            ? (dynamicOptionGroups.find((group) => group.key === 'sizes')
                  ?.values ?? [])
            : (productOptions?.sizes ?? []);

        return stickerAreaForSizeValues(
            configuredSizeValues,
            selected ?? undefined,
        );
    }, [
        confirmedCustomSize,
        dynamicOptionGroups,
        isStickerProduct,
        productOptions,
        selectedDynamicOptions,
        selectedSize,
        usesDynamicOptions,
    ]);

    const hasSubmittedDesign =
        Object.values(submittedDesignModes).some(Boolean);

    const markDesignSubmitted = (
        mode: 'canva' | 'upload' | 'design-for-you',
    ) => {
        setSubmittedDesignModes((current) => ({
            ...current,
            [mode]: true,
        }));
        setDesignSelectionError(null);
        setDesignModal(null);
    };

    const openDesignModal = (mode: 'canva' | 'upload' | 'design-for-you') => {
        setDesignSelectionError(null);
        setDesignModal(mode);
    };

    const markInteracted = () => {
        setHasInteracted(true);
        setSelectedThumbnail(null);
    };

    const rememberGalleryOptionSelection = (groupKey: string) => {
        setLastInteractedOptionGroupKey(groupKey);
        setLastSelectedGalleryOptionKey(
            getPreferredGalleryMatchKey(
                configuredGalleries,
                isPvcProduct,
                groupKey,
            )
                ? groupKey
                : null,
        );
    };

    const hasSelection = usesDynamicOptions
        ? dynamicOptionGroups.every((group) => {
              const selected = selectedDynamicOptions[group.key];

              if (group.type === 'multi_select') {
                  const selectedValues = Array.isArray(selected)
                      ? selected
                      : [];

                  return (
                      (!group.required && selectedValues.length === 0) ||
                      selectedValues.length > 0
                  );
              }

              if (!group.required && (selected == null || selected === '')) {
                  return true;
              }

              return (
                  typeof selected === 'string' &&
                  selected !== '' &&
                  (group.key !== 'sizes' ||
                      selected !== 'custom' ||
                      confirmedCustomSize != null)
              );
          }) &&
          (!isStickerProduct || stickerPaperArea > 0)
        : (sizes.length === 0 ||
              (selectedSize !== 'custom'
                  ? selectedSize != null
                  : confirmedCustomSize != null)) &&
          (finishes.length === 0 || selectedFinish != null) &&
          (cornersList.length === 0 || selectedCorners != null) &&
          (textures.length === 0 || selectedTexture != null) &&
          (specialFinishes.length === 0 ||
              !specialFinishRequired ||
              selectedSpecialFinish != null) &&
          (embossingList.length === 0 || selectedEmbossing != null) &&
          (embossingOrSignaturePanelList.length === 0 ||
              selectedEmbossingOrSignaturePanel != null);

    const defaultOptions = useMemo<Record<string, string | string[]>>(() => {
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

            if (isStickerProduct) {
                opts.paper_area = stickerAreaOptionValue(
                    stickerAreaForSize(
                        dynamicOptionGroups,
                        dynamicOptionDefaults.sizes,
                    ),
                );
            }

            return opts;
        }

        if (sizes.length > 0) opts['sizes'] = sizes[0]?.id;
        if (finishes.length > 0) opts['paper_finish'] = finishes[0]?.id;
        if (cornersList.length > 0) opts['corners'] = cornersList[0]?.id;
        if (textures.length > 0) opts['texture'] = textures[0]?.id ?? 'none';
        if (specialFinishes.length > 0 && selectedSpecialFinish) {
            opts['special_finish'] = selectedSpecialFinish;
        }
        if (embossingList.length > 0)
            opts['embossing'] = embossingList[0]?.id ?? 'none';
        if (embossingOrSignaturePanelList.length > 0)
            opts['embossing_or_signature_panel'] =
                embossingOrSignaturePanelList[0]?.id ?? 'none';
        return opts;
    }, [
        sizes,
        finishes,
        cornersList,
        textures,
        specialFinishes,
        embossingList,
        embossingOrSignaturePanelList,
        RECOMMENDED_QTY,
        usesDynamicOptions,
        dynamicOptionGroups,
        dynamicOptionDefaults,
        isStickerProduct,
        selectedSpecialFinish,
    ]);

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
                        typeof selected === 'string'
                            ? selected
                            : (dynamicOptionDefaults[group.key] ?? '');
                }

                if (
                    group.key === 'sizes' &&
                    selected === 'custom' &&
                    confirmedCustomSize
                ) {
                    opts['custom_width'] = confirmedCustomSize.width.toFixed(2);
                    opts['custom_height'] =
                        confirmedCustomSize.height.toFixed(2);
                }
            }

            if (isStickerProduct) {
                opts.paper_area = stickerAreaOptionValue(stickerPaperArea);
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
        if (selectedSize === 'custom' && confirmedCustomSize) {
            opts['custom_width'] = confirmedCustomSize.width.toFixed(2);
            opts['custom_height'] = confirmedCustomSize.height.toFixed(2);
        }
        if (finishes.length > 0 && selectedFinish)
            opts['paper_finish'] = selectedFinish;
        if (cornersList.length > 0 && selectedCorners)
            opts['corners'] = selectedCorners;
        if (textures.length > 0 && selectedTexture)
            opts['texture'] = selectedTexture;
        if (specialFinishes.length > 0 && selectedSpecialFinish) {
            opts['special_finish'] = selectedSpecialFinish;
        }
        if (embossingList.length > 0 && selectedEmbossing)
            opts['embossing'] = selectedEmbossing;
        if (
            embossingOrSignaturePanelList.length > 0 &&
            selectedEmbossingOrSignaturePanel
        )
            opts['embossing_or_signature_panel'] =
                selectedEmbossingOrSignaturePanel;

        if (isStickerProduct) {
            opts.paper_area = stickerAreaOptionValue(stickerPaperArea);
        }

        return opts;
    }, [
        hasSelection,
        defaultOptions,
        selectedSize,
        confirmedCustomSize,
        selectedFinish,
        selectedCorners,
        selectedTexture,
        selectedSpecialFinish,
        selectedEmbossing,
        selectedEmbossingOrSignaturePanel,
        selectedQty,
        RECOMMENDED_QTY,
        sizes,
        finishes,
        cornersList,
        textures,
        specialFinishes,
        embossingList,
        embossingOrSignaturePanelList,
        usesDynamicOptions,
        dynamicOptionGroups,
        selectedDynamicOptions,
        dynamicOptionDefaults,
        confirmedCustomSize,
        isStickerProduct,
        stickerPaperArea,
    ]);

    const cartOptions = useMemo<
        Record<string, string | string[] | Record<string, SpecialFinishSide>>
    >(() => {
        const specialFinishSides = specialFinishSidesForSelection(
            selectedOptions.special_finish,
            selectedSpecialFinishSides,
        );
        const hotFoilSides = specialFinishSidesForSelection(
            selectedOptions.hot_foil,
            selectedSpecialFinishSides,
        );

        return {
            ...selectedOptions,
            ...(specialFinishSides
                ? { special_finish_on_sides: specialFinishSides }
                : {}),
            ...(hotFoilSides ? { hot_foil_on_sides: hotFoilSides } : {}),
        };
    }, [selectedOptions, selectedSpecialFinishSides]);

    const defaultGallery = useMemo(
        () => configuredGalleries.find((g) => g.is_default) ?? fallbackGallery,
        [configuredGalleries, fallbackGallery],
    );

    const activeGallery = useMemo(() => {
        if (!hasInteracted) {
            return defaultGallery;
        }

        if (configuredGalleries.length > 0) {
            const preferredGalleryMatchKey = getPreferredGalleryMatchKey(
                configuredGalleries,
                isPvcProduct,
                lastSelectedGalleryOptionKey,
            );

            // Cotton finish diagrams are swatches only. Selecting a finish
            // without a dedicated gallery rule must leave the original
            // product gallery in place instead of falling through to the
            // selected texture's sample image.
            if (
                lastInteractedOptionGroupKey === 'special_finish' &&
                !preferredGalleryMatchKey
            ) {
                return defaultGallery;
            }

            const matched = findMatchingGallery(
                configuredGalleries,
                selectedOptions,
                preferredGalleryMatchKey,
            );

            if (matched) {
                return matched;
            }
        }

        return fallbackGallery;
    }, [
        hasInteracted,
        configuredGalleries,
        selectedOptions,
        isPvcProduct,
        lastSelectedGalleryOptionKey,
        lastInteractedOptionGroupKey,
        defaultGallery,
        fallbackGallery,
    ]);

    const displayImages = useMemo(() => {
        return getProductThumbnailImages(defaultGallery, isStickerProduct);
    }, [defaultGallery, isStickerProduct]);

    const activeImage = useMemo(() => {
        if (selectedThumbnail && displayImages.includes(selectedThumbnail)) {
            return selectedThumbnail;
        }

        if (isStickerProduct && hasInteracted && activeGallery.images[0]) {
            return activeGallery.images[0];
        }

        return (
            activeGallery.images[0] ??
            displayImages[0] ??
            product.featured_image ??
            galleryThumbs[0]
        );
    }, [
        selectedThumbnail,
        displayImages,
        isStickerProduct,
        hasInteracted,
        activeGallery,
        product.featured_image,
        galleryThumbs,
    ]);

    const lightboxImages = useMemo(() => {
        if (!activeImage) {
            return displayImages;
        }

        return [
            activeImage,
            ...displayImages.filter((src) => src !== activeImage),
        ];
    }, [activeImage, displayImages]);

    const quantityTiers = useMemo(() => {
        if (hasDynamicPricing) {
            const sizeIndex = sizes.findIndex(
                (s: any) =>
                    s.id ===
                    (selectedSize === 'custom' ? 'standard' : selectedSize),
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
                foilSidesForSelection(
                    selectedOptions,
                    selectedSpecialFinishSides,
                ),
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
        selectedSpecialFinishSides,
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

        rememberGalleryOptionSelection(groupKey);

        if (
            groupKey === 'paper_finish' ||
            groupKey === 'thickness' ||
            groupKey === 'texture'
        ) {
            setSelectedThumbnail(null);
        }

        if (groupKey === 'sizes' && group.type !== 'multi_select') {
            setSelectedSize(value);
        }

        if (groupKey === 'special_finish' || groupKey === 'hot_foil') {
            if (groupKey === 'special_finish' && isNoSpecialFinishCode(value)) {
                setSelectedSpecialFinishSides({});
            } else if (group.type === 'multi_select') {
                const selectedValues = Array.isArray(
                    selectedDynamicOptions[groupKey],
                )
                    ? selectedDynamicOptions[groupKey]
                    : [];

                setSelectedSpecialFinishSides((current) => {
                    const next = { ...current };

                    if (selectedValues.includes(value)) {
                        delete next[value];
                    } else {
                        next[value] ??= DEFAULT_SPECIAL_FINISH_SIDE;
                    }

                    return next;
                });
            } else {
                setSelectedSpecialFinishSides((current) => ({
                    ...current,
                    [value]: current[value] ?? DEFAULT_SPECIAL_FINISH_SIDE,
                }));
            }
        }

        setSelectedDynamicOptions((current) => {
            if (group.type !== 'multi_select') {
                if (group.required === false && current[groupKey] === value) {
                    return {
                        ...current,
                        [groupKey]: '',
                    };
                }

                if (groupKey === 'thickness') {
                    const textureGroup = dynamicOptionGroups.find(
                        (item) => item.key === 'texture',
                    );
                    const currentTextureCode =
                        typeof current.texture === 'string'
                            ? current.texture
                            : '';
                    const currentTexture = textureGroup?.values.find(
                        (item) =>
                            optionValueCode(item) === currentTextureCode &&
                            item.thickness_code === value,
                    );
                    const firstTexture = textureGroup?.values.find(
                        (item) => item.thickness_code === value,
                    );
                    const nextTexture = currentTexture ?? firstTexture;

                    return {
                        ...current,
                        [groupKey]: value,
                        texture: nextTexture
                            ? optionValueCode(nextTexture)
                            : '',
                    };
                }

                const next = applyTextureUvSelection(current, groupKey, value);
                const paperFinishGroup = dynamicOptionGroups.find(
                    (item) => item.key === 'paper_finish',
                );
                const uvGroup = dynamicOptionGroups.find(
                    (item) => item.key === 'uv_finish',
                );

                if (
                    groupKey === 'paper_finish' &&
                    uvGroup?.required === false
                ) {
                    next.uv_finish = '';
                }

                if (
                    groupKey === 'uv_finish' &&
                    paperFinishGroup?.required === false
                ) {
                    next.paper_finish = '';
                }

                return next;
            }

            const selected = Array.isArray(current[groupKey])
                ? current[groupKey]
                : [];

            if (groupKey === 'special_finish') {
                if (isNoSpecialFinishCode(value)) {
                    return { ...current, [groupKey]: [value] };
                }

                const finishSelections = selected.filter(
                    (item) => !isNoSpecialFinishCode(item),
                );
                const next = finishSelections.includes(value)
                    ? finishSelections.filter((item) => item !== value)
                    : [...finishSelections, value];
                return {
                    ...current,
                    [groupKey]: next,
                };
            }

            return {
                ...current,
                [groupKey]: selected.includes(value)
                    ? selected.filter((item) => item !== value)
                    : [...selected, value],
            };
        });

        markInteracted();
    }

    function selectSpecialFinishSide(
        side: SpecialFinishSide,
        finishCode: string,
        groupKey: FoilOptionGroupKey = 'special_finish',
    ) {
        if (usesDynamicOptions) {
            const selected = selectedDynamicOptions[groupKey];
            const selectedValues = Array.isArray(selected)
                ? selected
                : selected
                  ? [selected]
                  : [];

            if (!selectedValues.includes(finishCode)) {
                selectDynamicOption(groupKey, finishCode);
            }
        } else if (
            groupKey === 'special_finish' &&
            selectedSpecialFinish !== finishCode
        ) {
            selectOption('special_finish', finishCode);
        }

        setSelectedSpecialFinishSides((current) => ({
            ...current,
            [finishCode]: side,
        }));
        markInteracted();
    }

    function openCustomSizeModal() {
        setCustomWidth(confirmedCustomSize?.width.toFixed(2) ?? '');
        setCustomHeight(confirmedCustomSize?.height.toFixed(2) ?? '');
        setCustomSizeError(null);
        setCustomSizeOpen(true);
    }

    function selectSize(value: string) {
        if (value === 'custom') {
            openCustomSizeModal();

            return;
        }

        setSelectedSize(value);
        markInteracted();
    }

    function confirmCustomSize() {
        const width = Number(customWidth);
        const height = Number(customHeight);

        if (
            !Number.isFinite(width) ||
            !Number.isFinite(height) ||
            width < customSizeLimits.minWidth ||
            width > customSizeLimits.maxWidth ||
            height < customSizeLimits.minHeight ||
            height > customSizeLimits.maxHeight
        ) {
            setCustomSizeError(
                `Enter a width between ${formatSizeLimit(customSizeLimits.minWidth)} and ${formatSizeLimit(customSizeLimits.maxWidth)} inches and a height between ${formatSizeLimit(customSizeLimits.minHeight)} and ${formatSizeLimit(customSizeLimits.maxHeight)} inches.`,
            );

            return;
        }

        setConfirmedCustomSize({
            width: Number(width.toFixed(2)),
            height: Number(height.toFixed(2)),
        });
        setSelectedSize('custom');

        markInteracted();
        if (usesDynamicOptions) {
            setSelectedDynamicOptions((current) => ({
                ...current,
                sizes: 'custom',
            }));
        }
        setCustomSizeError(null);
        setCustomSizeOpen(false);
    }

    function selectOption(
        group:
            | 'sizes'
            | 'paper_finish'
            | 'corners'
            | 'texture'
            | 'special_finish'
            | 'embossing'
            | 'embossing_or_signature_panel',
        value: string,
    ) {
        rememberGalleryOptionSelection(group);

        switch (group) {
            case 'sizes':
                setSelectedSize(value);
                break;
            case 'paper_finish':
                setSelectedFinish(value);
                setSelectedThumbnail(null);

                break;
            case 'corners':
                setSelectedCorners(value);
                break;
            case 'texture':
                setSelectedTexture(value);
                break;
            case 'special_finish':
                if (!specialFinishRequired && selectedSpecialFinish === value) {
                    setSelectedSpecialFinish(null);
                    setSelectedSpecialFinishSides((current) => {
                        const next = { ...current };
                        delete next[value];

                        return next;
                    });
                } else {
                    setSelectedSpecialFinish(value);
                }

                if (isNoSpecialFinishCode(value)) {
                    setSelectedSpecialFinishSides({});
                } else if (selectedSpecialFinish !== value) {
                    setSelectedSpecialFinishSides((current) => ({
                        ...current,
                        [value]: current[value] ?? DEFAULT_SPECIAL_FINISH_SIDE,
                    }));
                }
                break;
            case 'embossing':
                setSelectedEmbossing(value);
                break;
            case 'embossing_or_signature_panel':
                setSelectedEmbossingOrSignaturePanel(value);
                break;
        }

        markInteracted();
    }

    const sizeLabel =
        selectedSize === 'custom' && confirmedCustomSize
            ? `Custom (${confirmedCustomSize.width.toFixed(2)}" x ${confirmedCustomSize.height.toFixed(2)}")`
            : (sizes.find((s: any) => s.id === selectedSize)?.label ?? '');
    const finishLabel =
        finishes.find((f: any) => f.id === selectedFinish)?.label ?? '';
    const cornersLabel =
        cornersList.find((cn: any) => cn.id === selectedCorners)?.label ?? '';
    const specialFinishLabel =
        specialFinishes.find((f: any) => f.id === selectedSpecialFinish)
            ?.label ??
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
        embossingOrSignaturePanelList.find(
            (e: any) => e.id === selectedEmbossingOrSignaturePanel,
        )?.label ?? '';

    const selectedProductSpecialFinish =
        usesDynamicOptions &&
        Array.isArray(selectedDynamicOptions.special_finish)
            ? (selectedDynamicOptions.special_finish[0] ?? null)
            : typeof selectedDynamicOptions.special_finish === 'string'
              ? selectedDynamicOptions.special_finish
              : selectedSpecialFinish;
    const selectedSpecialFinishOption =
        specialFinishes.find(
            (finish: any) => finish.id === selectedProductSpecialFinish,
        ) ??
        COLD_FOIL_OPTIONS.find(
            (finish) => finish.id === selectedProductSpecialFinish,
        );
    const hotFoilFinishes = specialFinishes.filter(
        (finish: any) => !isColdFoilCode(finish.id),
    );
    const configuredColdFoilFinishes = specialFinishes.filter((finish: any) =>
        isColdFoilCode(finish.id),
    );
    const coldFoilFinishes =
        configuredColdFoilFinishes.length > 0
            ? configuredColdFoilFinishes
            : COLD_FOIL_OPTIONS;
    const selectedTurnaroundOptions: SelectedOptionDetails[] = [
        {
            groupKey: 'paper_finish',
            code: selectedFinish,
            label: finishLabel,
        },
        {
            groupKey: 'corners',
            code: selectedCorners,
            label: cornersLabel,
        },
    ];

    if (usesDynamicOptions) {
        selectedTurnaroundOptions.push(
            ...selectedDynamicOptionDetails(
                dynamicOptionGroups,
                selectedDynamicOptions,
            ),
        );
    } else {
        selectedTurnaroundOptions.push({
            groupKey: 'special_finish',
            code: selectedProductSpecialFinish,
            label: selectedSpecialFinishOption?.label,
            description: selectedSpecialFinishOption?.description,
        });
    }

    const productTurnaround = getProductTurnaround(
        product,
        hasAdditionalTurnaroundTime(selectedTurnaroundOptions),
    );

    const showSpecialFinishInSummary =
        specialFinishes.length > 0 && selectedProductSpecialFinish != null;
    const showTextureInSummary = textures.length > 0;
    const showEmbossingInSummary = embossingList.length > 0;
    const showEmbossingOrSignaturePanelInSummary =
        embossingOrSignaturePanelList.length > 0;

    const breadcrumbs: string[] = c.breadcrumbs;
    const featureChips: string[] = c.feature_chips;
    const featureChipDescriptions: string[] = c.feature_chip_descriptions;
    const gangRunTooltip: any = c.gang_run_printing_tooltip;
    const turnaroundTooltip: any = c.turnaround_tooltip;
    const featureCards = productOptions?.detail_sections?.feature_cards ?? [];
    const firstFeatureCard: ProductFeatureCardContent = featureCards[0] ?? {};
    const secondFeatureCard: ProductFeatureCardContent = featureCards[1] ?? {};
    const firstFeatureCardTooltip = firstFeatureCard.tooltip_content?.trim();
    const secondFeatureCardTooltip = secondFeatureCard.tooltip_content?.trim();
    const summaryLabels: string[] = c.order_summary.labels;
    const designServicesConfig: any = c.design_services;
    const designFeeLabel: string = c.design_fee_label;
    const pageUrl = usePage().url;

    const submitAddToCart = (continueToCheckout = false) => {
        setAdded(true);
        setIsSubmittingCart(true);
        router.post(
            '/cart/add',
            {
                product_id: product.id,
                // Only the code is submitted; the server resolves the fee.
                options: selectedDesignService
                    ? {
                          ...cartOptions,
                          design_service: selectedDesignService,
                      }
                    : cartOptions,
            },
            {
                onSuccess: () => {
                    setCheckoutConfirmationOpen(false);

                    if (continueToCheckout) {
                        router.visit('/checkout');
                    }
                },
                onError: () => {
                    setAdded(false);

                    if (continueToCheckout) {
                        setCheckoutConfirmationOpen(true);
                    }
                },
                onFinish: () => {
                    setIsSubmittingCart(false);

                    if (!continueToCheckout) {
                        setTimeout(() => setAdded(false), 2000);
                    }
                },
            },
        );
    };

    const addToCart = () => {
        if (!hasSubmittedDesign) {
            const message =
                c.design_cta?.required_error ??
                'Please choose and submit one of the three design options before adding this product to your cart.';

            setDesignSelectionError(message);
            toast.error(message);

            return;
        }

        if (isBusinessCardProduct) {
            setCheckoutConfirmationOpen(true);

            return;
        }

        submitAddToCart();
    };

    const confirmAddToCart = () => {
        submitAddToCart(true);
    };

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
                <ol className="product-detail-container mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 text-sm text-neutral-500">
                    <li>
                        <Link href="/" className="hover:text-neutral-900">
                            {breadcrumbs[0]}
                        </Link>
                    </li>
                    <ChevronRight className="size-3.5" />
                    <li>
                        <Link
                            href={productCategoryHref(product.category.slug)}
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
                <div className="product-detail-container mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 lg:grid-cols-2">
                    {/* Keep the gallery below the sticky desktop storefront header. */}
                    <div className="lg:sticky lg:top-[140px] lg:self-start">
                        <div
                            className="aspect-[4/3] w-full cursor-zoom-in overflow-hidden rounded-lg bg-neutral-100 transition-all duration-300 hover:opacity-95"
                            onClick={() => {
                                const index =
                                    lightboxImages.indexOf(activeImage);
                                setLightboxIndex(index !== -1 ? index : 0);
                                setLightboxOpen(true);
                            }}
                            title="Click to view fullscreen gallery"
                        >
                            <img
                                src={activeImage}
                                alt={product.name}
                                className="h-full w-full transform object-contain transition-transform duration-500 hover:scale-[1.02]"
                            />
                        </div>
                        <div className="mt-3 grid grid-cols-4 gap-2">
                            {displayImages.map((src) => (
                                <button
                                    key={src}
                                    type="button"
                                    onClick={() => setSelectedThumbnail(src)}
                                    className={`aspect-[4/3] overflow-hidden rounded-md border-2 transition-colors ${
                                        activeImage === src
                                            ? 'border-[#800020]'
                                            : 'border-transparent hover:border-neutral-200'
                                    }`}
                                >
                                    <img
                                        src={src}
                                        alt=""
                                        className="h-full w-full bg-neutral-100 object-contain"
                                    />
                                </button>
                            ))}
                        </div>
                        {isBusinessCardProduct && (
                            <Link
                                href="/blog/business-card-buying-ordering-guide"
                                className="mt-4 flex items-center gap-3 rounded-lg border border-neutral-200 bg-white p-4 transition-colors hover:border-[#800020]/40 hover:bg-[#800020]/5 focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:outline-none"
                            >
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#800020]/10 text-[#800020]">
                                    <Lightbulb
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span className="text-sm font-semibold text-neutral-900">
                                    Business Card Buying Guide
                                </span>
                            </Link>
                        )}
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
                            className="mt-4 text-sm leading-relaxed text-neutral-700 [&_a]:underline [&_em]:text-primary [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-0 [&_p+p]:mt-2 [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:text-primary"
                            dangerouslySetInnerHTML={{
                                __html: accentProductShortDescription(
                                    product.subtitle ?? '',
                                ),
                            }}
                        />
                        {startingPriceText && (
                            <p className="mt-2 text-sm font-semibold text-neutral-900">
                                <LiveText text={startingPriceText} />
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
                                customSize={confirmedCustomSize}
                                onCustomSizeSelect={openCustomSizeModal}
                                showSpecialFinishSides={!isCottonBusinessCards}
                                showHotFoilSides={true}
                                specialFinishSides={selectedSpecialFinishSides}
                                onSpecialFinishSideChange={
                                    selectSpecialFinishSide
                                }
                            />
                        )}

                        {!usesDynamicOptions &&
                            sizes.length > 0 &&
                            !isCottonBusinessCards && (
                                <OptionGroup label={c.configurator_labels.size}>
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        {sizes.map((s: any) => {
                                            const shape =
                                                sizeShapes[s.id] ?? 'rect';
                                            const hasSwatch = !!s.swatch;

                                            return (
                                                <ChoiceTile
                                                    key={s.id}
                                                    active={
                                                        selectedSize === s.id &&
                                                        hasInteracted
                                                    }
                                                    onClick={() =>
                                                        selectSize(s.id)
                                                    }
                                                    label={
                                                        s.id === 'custom' &&
                                                        confirmedCustomSize
                                                            ? `${s.label} (${confirmedCustomSize.width.toFixed(2)}" x ${confirmedCustomSize.height.toFixed(2)}")`
                                                            : s.label
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
                                                                        s.id &&
                                                                    hasInteracted
                                                                        ? 'border-[#800020] bg-[#800020]/5'
                                                                        : 'border-neutral-300 bg-neutral-50'
                                                                } ${shape === 'rect' ? 'h-8 w-14' : 'size-10'}`}
                                                            />
                                                        )}
                                                    </div>
                                                    {shouldShowSwatchCaption(
                                                        'sizes',
                                                        s.id,
                                                    ) && (
                                                        <p className="text-xs text-neutral-500">
                                                            {s.id ===
                                                                'custom' &&
                                                            confirmedCustomSize
                                                                ? `${confirmedCustomSize.width.toFixed(2)}" x ${confirmedCustomSize.height.toFixed(2)}"`
                                                                : s.dims}
                                                        </p>
                                                    )}
                                                </ChoiceTile>
                                            );
                                        })}
                                    </div>
                                </OptionGroup>
                            )}

                        {!usesDynamicOptions && cornersList.length > 0 && (
                            <OptionGroup label={c.configurator_labels.corners}>
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    {cornersList.map((cn: any) => (
                                        <CornerChoiceCard
                                            key={cn.id}
                                            label={cn.label}
                                            swatch={cn.swatch}
                                            active={
                                                selectedCorners === cn.id &&
                                                hasInteracted
                                            }
                                            onClick={() =>
                                                selectOption('corners', cn.id)
                                            }
                                        />
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        {!usesDynamicOptions && textures.length > 0 && (
                            <OptionGroup label="Texture">
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {textures.map((t: any) => (
                                        <ChoiceTile
                                            key={t.id}
                                            active={
                                                selectedTexture === t.id &&
                                                hasInteracted
                                            }
                                            onClick={() =>
                                                selectOption('texture', t.id)
                                            }
                                            label={t.label}
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
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        {!usesDynamicOptions && !isCottonBusinessCards && (
                            <OptionGroup
                                label={c.configurator_labels.paper_finish}
                            >
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {finishes.map((f: any) => (
                                        <ChoiceTile
                                            key={f.id}
                                            active={
                                                selectedFinish === f.id &&
                                                hasInteracted
                                            }
                                            onClick={() =>
                                                selectOption(
                                                    'paper_finish',
                                                    f.id,
                                                )
                                            }
                                            label={f.label}
                                        >
                                            <img
                                                src={f.thumb}
                                                alt=""
                                                className="aspect-[3/2] w-full rounded-sm object-cover"
                                            />
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        {!usesDynamicOptions &&
                            !isCottonBusinessCards &&
                            specialFinishes.length > 0 && (
                                <div className="mt-6">
                                    <>
                                        <div className="mb-3 flex items-center justify-between border-b border-neutral-100 pb-2">
                                            <span className="text-sm font-bold text-neutral-900">
                                                Special Finish
                                            </span>
                                            <div className="flex rounded-md bg-neutral-100 p-0.5">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleFoilTabChange(
                                                            'hot',
                                                        )
                                                    }
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

                                        {foilTab === 'hot' ||
                                        !supportsColdFoil ? (
                                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                {hotFoilFinishes.map(
                                                    (f: any) => {
                                                        const glossLimited =
                                                            selectedFinish ===
                                                                'gloss' &&
                                                            !isNoSpecialFinishCode(
                                                                f.id,
                                                            );
                                                        const active =
                                                            selectedSpecialFinish ===
                                                                f.id &&
                                                            hasInteracted;
                                                        const tileContent = (
                                                            <>
                                                                <img
                                                                    src={
                                                                        f.thumb
                                                                    }
                                                                    alt=""
                                                                    className="aspect-square w-full rounded-sm bg-neutral-50 object-contain"
                                                                />
                                                            </>
                                                        );

                                                        if (
                                                            !isNoSpecialFinishCode(
                                                                f.id,
                                                            )
                                                        ) {
                                                            return (
                                                                <SpecialFinishChoiceTile
                                                                    key={f.id}
                                                                    active={
                                                                        active
                                                                    }
                                                                    disabled={
                                                                        glossLimited
                                                                    }
                                                                    onClick={() =>
                                                                        selectOption(
                                                                            'special_finish',
                                                                            f.id,
                                                                        )
                                                                    }
                                                                    label={
                                                                        f.label
                                                                    }
                                                                    finishSide={
                                                                        selectedSpecialFinishSides[
                                                                            f.id
                                                                        ] ??
                                                                        DEFAULT_SPECIAL_FINISH_SIDE
                                                                    }
                                                                    onFinishSideChange={(
                                                                        side,
                                                                    ) =>
                                                                        selectSpecialFinishSide(
                                                                            side,
                                                                            f.id,
                                                                        )
                                                                    }
                                                                >
                                                                    {
                                                                        tileContent
                                                                    }
                                                                </SpecialFinishChoiceTile>
                                                            );
                                                        }

                                                        return (
                                                            <ChoiceTile
                                                                key={f.id}
                                                                active={active}
                                                                disabled={
                                                                    glossLimited
                                                                }
                                                                onClick={() =>
                                                                    selectOption(
                                                                        'special_finish',
                                                                        f.id,
                                                                    )
                                                                }
                                                                label={f.label}
                                                            >
                                                                {tileContent}
                                                            </ChoiceTile>
                                                        );
                                                    },
                                                )}
                                            </div>
                                        ) : (
                                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                {coldFoilFinishes.map(
                                                    (f: any) => {
                                                        const active =
                                                            selectedSpecialFinish ===
                                                                f.id &&
                                                            hasInteracted;

                                                        return (
                                                            <SpecialFinishChoiceTile
                                                                key={f.id}
                                                                active={active}
                                                                onClick={() =>
                                                                    selectOption(
                                                                        'special_finish',
                                                                        f.id,
                                                                    )
                                                                }
                                                                label={f.label}
                                                                finishSide={
                                                                    selectedSpecialFinishSides[
                                                                        f.id
                                                                    ] ??
                                                                    DEFAULT_SPECIAL_FINISH_SIDE
                                                                }
                                                                onFinishSideChange={(
                                                                    side,
                                                                ) =>
                                                                    selectSpecialFinishSide(
                                                                        side,
                                                                        f.id,
                                                                    )
                                                                }
                                                            >
                                                                <img
                                                                    src={
                                                                        f.thumb
                                                                    }
                                                                    alt=""
                                                                    className="aspect-square w-full rounded-sm bg-neutral-50 object-contain"
                                                                />
                                                            </SpecialFinishChoiceTile>
                                                        );
                                                    },
                                                )}
                                            </div>
                                        )}
                                    </>
                                </div>
                            )}

                        {!usesDynamicOptions && embossingList.length > 0 && (
                            <OptionGroup label="Embossing">
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {embossingList.map((e: any) => (
                                        <ChoiceTile
                                            key={e.id}
                                            active={
                                                selectedEmbossing === e.id &&
                                                hasInteracted
                                            }
                                            onClick={() =>
                                                selectOption('embossing', e.id)
                                            }
                                            label={e.label}
                                        >
                                            <div className="flex aspect-square w-full items-center justify-center rounded-sm bg-neutral-50 p-4">
                                                {e.id === 'embossing' ? (
                                                    <span className="text-xs font-bold text-[#800020]">
                                                        EMBOSSED TEXT
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-neutral-400">
                                                        FLAT TEXT
                                                    </span>
                                                )}
                                            </div>
                                        </ChoiceTile>
                                    ))}
                                </div>
                            </OptionGroup>
                        )}

                        {!usesDynamicOptions &&
                            embossingOrSignaturePanelList.length > 0 && (
                                <OptionGroup label="Embossing or Signature Panel">
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        {embossingOrSignaturePanelList.map(
                                            (e: any) => (
                                                <ChoiceTile
                                                    key={e.id}
                                                    active={
                                                        selectedEmbossingOrSignaturePanel ===
                                                            e.id &&
                                                        hasInteracted
                                                    }
                                                    onClick={() =>
                                                        selectOption(
                                                            'embossing_or_signature_panel',
                                                            e.id,
                                                        )
                                                    }
                                                    label={e.label}
                                                >
                                                    <div className="flex aspect-square w-full items-center justify-center rounded-sm bg-neutral-50 p-4">
                                                        {e.id ===
                                                        'embossing' ? (
                                                            <span className="text-xs font-bold text-[#800020]">
                                                                EMBOSSING
                                                            </span>
                                                        ) : e.id ===
                                                          'signature_panel' ? (
                                                            <span className="text-xs font-bold text-[#800020]">
                                                                SIGNATURE
                                                            </span>
                                                        ) : (
                                                            <span className="text-xs text-neutral-400">
                                                                NONE
                                                            </span>
                                                        )}
                                                    </div>
                                                </ChoiceTile>
                                            ),
                                        )}
                                    </div>
                                </OptionGroup>
                            )}

                        <OptionGroup label={c.configurator_labels.quantity}>
                            <div className="overflow-x-auto rounded-md border border-neutral-200">
                                <table className="w-full min-w-[32rem] text-sm sm:min-w-0">
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
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {(() => {
                                            const baseUnitPrice =
                                                quantityTiers[0]
                                                    ?.pricePerCard ?? 0;
                                            return quantityTiers.map(
                                                (t: any) => {
                                                    const recommended =
                                                        !!t.recommended;
                                                    const active =
                                                        selectedQty === t.qty;
                                                    const now = t.currentPrice;
                                                    const bracketPrice =
                                                        t.qty * baseUnitPrice;

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
                                                                        checked={
                                                                            active
                                                                        }
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
                                                                <LiveText
                                                                    text={`$${t.pricePerCard.toFixed(3)}`}
                                                                />
                                                            </td>
                                                            <td className="px-4 py-3 font-semibold text-neutral-900">
                                                                <LiveText
                                                                    text={`$${Math.round(now).toFixed(0)}`}
                                                                />
                                                                <span className="ml-2 font-normal text-neutral-400">
                                                                    <LiveText
                                                                        className="line-through"
                                                                        text={`($${Number(bracketPrice.toFixed(2))})`}
                                                                    />
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    );
                                                },
                                            );
                                        })()}
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
                                    {c.delivery_callout.title.replace(
                                        '{date}',
                                        deliveryEstimates.standard,
                                    )}
                                </p>
                                <p className="text-neutral-600">
                                    {c.delivery_callout.subtitle.replace(
                                        '{date}',
                                        deliveryEstimates.fast,
                                    )}
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
                                            {dynamicOptionGroups.map(
                                                (group) => {
                                                    const selected =
                                                        selectedOptions[
                                                            group.key
                                                        ];
                                                    const selectedCodes =
                                                        Array.isArray(selected)
                                                            ? selected
                                                            : selected
                                                              ? [selected]
                                                              : [];
                                                    const labels = group.values
                                                        .filter((value) =>
                                                            selectedCodes.includes(
                                                                optionValueCode(
                                                                    value,
                                                                ),
                                                            ),
                                                        )
                                                        .map((value) =>
                                                            group.key ===
                                                                'sizes' &&
                                                            optionValueCode(
                                                                value,
                                                            ) === 'custom' &&
                                                            confirmedCustomSize
                                                                ? `Custom (${confirmedCustomSize.width.toFixed(2)}" x ${confirmedCustomSize.height.toFixed(2)}")`
                                                                : value.name,
                                                        );

                                                    return (
                                                        <Fragment
                                                            key={group.key}
                                                        >
                                                            <dt className="text-neutral-500">
                                                                {group.label}
                                                            </dt>
                                                            <dd className="text-right font-medium">
                                                                {labels.join(
                                                                    ', ',
                                                                )}
                                                            </dd>
                                                        </Fragment>
                                                    );
                                                },
                                            )}
                                            {isStickerProduct && (
                                                <>
                                                    <dt className="text-neutral-500">
                                                        Paper area
                                                    </dt>
                                                    <dd className="text-right font-medium">
                                                        <LiveText
                                                            text={`${formatStickerArea(stickerPaperArea)} m²`}
                                                        />
                                                    </dd>
                                                </>
                                            )}
                                            <dt className="text-neutral-500">
                                                {summaryLabels[2]}
                                            </dt>
                                            <dd className="text-right font-medium">
                                                <LiveText
                                                    text={String(selectedQty)}
                                                />
                                            </dd>
                                            {selectedDesignService && (
                                                <>
                                                    <dt className="order-10 text-neutral-500">
                                                        {designFeeLabel}
                                                    </dt>
                                                    <dd className="order-10 text-right font-medium">
                                                        <LiveText
                                                            text={`$${designFee}`}
                                                        />
                                                    </dd>
                                                </>
                                            )}
                                        </>
                                    ) : (
                                        <>
                                            <dt className="order-3 text-neutral-500">
                                                {summaryLabels[0]}
                                            </dt>
                                            <dd className="order-3 text-right font-medium">
                                                {finishLabel}
                                            </dd>
                                            {sizes.length > 0 && (
                                                <>
                                                    <dt className="order-1 text-neutral-500">
                                                        {summaryLabels[1]}
                                                    </dt>
                                                    <dd className="order-1 text-right font-medium">
                                                        {sizeLabel}
                                                    </dd>
                                                </>
                                            )}
                                            <dt className="order-9 text-neutral-500">
                                                {summaryLabels[2]}
                                            </dt>
                                            <dd className="order-9 text-right font-medium">
                                                <LiveText
                                                    text={String(selectedQty)}
                                                />
                                            </dd>
                                            {cornersList.length > 0 && (
                                                <>
                                                    <dt className="order-2 text-neutral-500">
                                                        {summaryLabels[3]}
                                                    </dt>
                                                    <dd className="order-2 text-right font-medium capitalize">
                                                        {cornersLabel}
                                                    </dd>
                                                </>
                                            )}
                                            {showTextureInSummary && (
                                                <>
                                                    <dt className="order-5 text-neutral-500">
                                                        Texture
                                                    </dt>
                                                    <dd className="order-5 text-right font-medium">
                                                        {textureLabel}
                                                    </dd>
                                                </>
                                            )}
                                            {showSpecialFinishInSummary && (
                                                <>
                                                    <dt className="order-4 text-neutral-500">
                                                        Special finish
                                                    </dt>
                                                    <dd className="order-4 text-right font-medium">
                                                        {specialFinishLabel}
                                                    </dd>
                                                </>
                                            )}
                                            {showEmbossingInSummary && (
                                                <>
                                                    <dt className="order-6 text-neutral-500">
                                                        Embossing
                                                    </dt>
                                                    <dd className="order-6 text-right font-medium">
                                                        {embossingLabel}
                                                    </dd>
                                                </>
                                            )}
                                            {showEmbossingOrSignaturePanelInSummary && (
                                                <>
                                                    <dt className="order-7 text-neutral-500">
                                                        Embossing / Signature
                                                    </dt>
                                                    <dd className="order-7 text-right font-medium">
                                                        {
                                                            embossingOrSignaturePanelLabel
                                                        }
                                                    </dd>
                                                </>
                                            )}
                                            {selectedDesignService && (
                                                <>
                                                    <dt className="order-10 text-neutral-500">
                                                        {designFeeLabel}
                                                    </dt>
                                                    <dd className="order-10 text-right font-medium">
                                                        <LiveText
                                                            text={`$${designFee}`}
                                                        />
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
                                                    <LiveText
                                                        text={`$${Math.round(fullPrice).toFixed(0)}`}
                                                    />
                                                </span>
                                            )}
                                        <span className="text-2xl font-bold text-neutral-900">
                                            <LiveText
                                                text={`$${Math.round(finalPrice).toFixed(0)}`}
                                            />
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
                                    submitted={submittedDesignModes.canva}
                                    onClick={() => openDesignModal('canva')}
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
                                    submitted={submittedDesignModes.upload}
                                    onClick={() => openDesignModal('upload')}
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
                                    submitted={
                                        submittedDesignModes['design-for-you']
                                    }
                                    onClick={() =>
                                        openDesignModal('design-for-you')
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
                            {designSelectionError && (
                                <p
                                    id="design-selection-error"
                                    role="alert"
                                    className="mt-3 text-sm font-medium text-red-600"
                                >
                                    {designSelectionError}
                                </p>
                            )}
                        </div>

                        {/* design modals */}
                        <CanvaDesignModal
                            open={designModal === 'canva'}
                            onOpenChange={(open) =>
                                setDesignModal(open ? 'canva' : null)
                            }
                            productId={product.id}
                            productName={product.name}
                            productSlug={product.slug}
                            returnTo={pageUrl}
                            onSubmitted={() => markDesignSubmitted('canva')}
                        />
                        <DesignServiceFormModal
                            open={designModal === 'upload'}
                            onOpenChange={(open) =>
                                setDesignModal(open ? 'upload' : null)
                            }
                            title="Upload a full design (free)"
                            description="Send us your print-ready artwork and we'll prepare a free proof before printing."
                            productOptions={c.design_form_product_options}
                            businessCardType={product.name}
                            businessCardTypeDisabled
                            submissionTarget="product-design"
                            productDesignMode="upload"
                            productId={product.id}
                            productName={product.name}
                            productSlug={product.slug}
                            returnTo={pageUrl}
                            onSubmitted={() => markDesignSubmitted('upload')}
                        />
                        <DesignServiceFormModal
                            open={designModal === 'design-for-you'}
                            onOpenChange={(open) =>
                                setDesignModal(open ? 'design-for-you' : null)
                            }
                            title="Design for you"
                            productOptions={c.design_form_product_options}
                            businessCardType={product.name}
                            businessCardTypeDisabled
                            submissionTarget="product-design"
                            productDesignMode="design-for-you"
                            productId={product.id}
                            productName={product.name}
                            productSlug={product.slug}
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
                            onSubmitted={() =>
                                markDesignSubmitted('design-for-you')
                            }
                        />

                        <CustomSizeModal
                            open={customSizeOpen}
                            onOpenChange={setCustomSizeOpen}
                            width={customWidth}
                            height={customHeight}
                            minWidth={customSizeLimits.minWidth}
                            maxWidth={customSizeLimits.maxWidth}
                            minHeight={customSizeLimits.minHeight}
                            maxHeight={customSizeLimits.maxHeight}
                            error={customSizeError}
                            onWidthChange={setCustomWidth}
                            onHeightChange={setCustomHeight}
                            onConfirm={confirmCustomSize}
                        />

                        <Button
                            onClick={addToCart}
                            aria-describedby={
                                designSelectionError
                                    ? 'design-selection-error'
                                    : undefined
                            }
                            disabled={added || !hasSelection || !tier}
                            className={`mt-6 h-12 w-full text-base font-semibold text-primary-foreground ${added ? 'bg-primary/90' : 'bg-primary hover:bg-primary/90'}`}
                        >
                            {added ? (
                                c.added_to_cart_button
                            ) : hasSelection && tier ? (
                                <LiveText
                                    text={String(
                                        c.add_to_cart_button_template,
                                    ).replace(
                                        '{price}',
                                        Math.round(finalPrice).toFixed(0),
                                    )}
                                />
                            ) : (
                                'Select options'
                            )}
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
                                productOptions.detail_sections
                                    .design_specifications
                            }
                        />
                    )}
                    {productOptions.detail_sections.design_service_banner && (
                        <DesignServiceBanner
                            content={
                                productOptions.detail_sections
                                    .design_service_banner
                            }
                        />
                    )}
                    {productOptions.detail_sections.paper_stocks && (
                        <PaperStockComparisonSection
                            content={
                                productOptions.detail_sections.paper_stocks
                            }
                        />
                    )}
                    {productOptions.detail_sections.more_good_stuff && (
                        <MoreGoodStuffSection
                            content={
                                productOptions.detail_sections.more_good_stuff
                            }
                        />
                    )}
                    {productOptions.detail_sections.faq && (
                        <ProductFaqSection
                            content={productOptions.detail_sections.faq}
                        />
                    )}
                </>
            )}

            <Dialog
                open={checkoutConfirmationOpen}
                onOpenChange={(open) => {
                    if (!isSubmittingCart) {
                        setCheckoutConfirmationOpen(open);
                    }
                }}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Review before checkout</DialogTitle>
                        <DialogDescription>
                            Please review these production details before adding
                            your business cards to the cart and continuing to
                            checkout.
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        className={cn(
                            'grid grid-cols-1 gap-4',
                            showGangRunPrinting && 'sm:grid-cols-2',
                        )}
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
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                            }
                            label={
                                productTurnaround?.label ||
                                firstFeatureCard.title ||
                                featureChips[0]
                            }
                            description={
                                productTurnaround?.description ||
                                firstFeatureCard.description ||
                                featureChipDescriptions[0]
                            }
                            detailTitle={
                                firstFeatureCard.tooltip_title ||
                                turnaroundTooltip.title
                            }
                            details={
                                firstFeatureCardTooltip ? (
                                    <div
                                        dangerouslySetInnerHTML={{
                                            __html: firstFeatureCardTooltip,
                                        }}
                                    />
                                ) : (
                                    turnaroundTooltip.sections.map(
                                        (section: any) => (
                                            <p key={section.heading}>
                                                <span className="font-semibold">
                                                    {section.heading}
                                                </span>
                                                <br />
                                                {section.body}
                                            </p>
                                        ),
                                    )
                                )
                            }
                        />
                        {showGangRunPrinting && (
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
                                        <circle cx="7" cy="14" r="1" />
                                        <circle cx="11" cy="14" r="1" />
                                        <circle cx="15" cy="14" r="1" />
                                    </svg>
                                }
                                label={
                                    secondFeatureCard.title || featureChips[1]
                                }
                                description={
                                    secondFeatureCard.description ||
                                    featureChipDescriptions[1]
                                }
                                detailTitle={
                                    secondFeatureCard.tooltip_title ||
                                    gangRunTooltip.title
                                }
                                details={
                                    secondFeatureCardTooltip ? (
                                        <div
                                            dangerouslySetInnerHTML={{
                                                __html: secondFeatureCardTooltip,
                                            }}
                                        />
                                    ) : (
                                        <>
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
                                        </>
                                    )
                                }
                            />
                        )}
                    </div>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            disabled={isSubmittingCart}
                            onClick={() => setCheckoutConfirmationOpen(false)}
                        >
                            Back to product
                        </Button>
                        <Button
                            type="button"
                            disabled={isSubmittingCart}
                            onClick={confirmAddToCart}
                        >
                            {isSubmittingCart
                                ? 'Adding to cart…'
                                : 'Confirm and continue to checkout'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            {lightboxOpen && (
                <LightboxGallery
                    open={lightboxOpen}
                    onClose={() => setLightboxOpen(false)}
                    images={lightboxImages}
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
    detailTitle,
    details,
}: {
    icon: React.ReactNode;
    label: string;
    description?: string;
    detailTitle?: string;
    details?: React.ReactNode;
}) {
    const detailContent = (detailTitle || details) && (
        <div className="mt-4 border-t border-neutral-100 pt-3">
            {detailTitle && (
                <p className="text-sm font-semibold text-neutral-700">
                    {detailTitle}
                </p>
            )}
            {details && (
                <div className="mt-1 text-sm leading-relaxed text-neutral-600 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-4 [&_p+p]:mt-2 [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-4">
                    {details}
                </div>
            )}
        </div>
    );

    return (
        <div className="flex h-full flex-col rounded-lg border border-neutral-200 bg-white p-4">
            <div className="flex gap-3">
                <span className="mt-0.5 shrink-0">{icon}</span>
                <div className="min-w-0">
                    <p className="text-base font-bold text-neutral-900">
                        {label}
                    </p>
                    {description && (
                        <p className="mt-1 text-sm leading-relaxed text-neutral-500">
                            {description}
                        </p>
                    )}
                </div>
            </div>
            {detailContent}
        </div>
    );
}

function DynamicOptionGroups({
    groups,
    selected,
    onSelect,
    customSize,
    onCustomSizeSelect,
    showSpecialFinishSides,
    showHotFoilSides,
    specialFinishSides,
    onSpecialFinishSideChange,
}: {
    groups: ProductOptionGroup[];
    selected: Record<string, string | string[]>;
    onSelect: (groupKey: string, value: string) => void;
    customSize?: { width: number; height: number } | null;
    onCustomSizeSelect?: () => void;
    showSpecialFinishSides: boolean;
    showHotFoilSides: boolean;
    specialFinishSides: Record<string, SpecialFinishSide>;
    onSpecialFinishSideChange: (
        side: SpecialFinishSide,
        finishCode: string,
        groupKey: FoilOptionGroupKey,
    ) => void;
}) {
    const [foilTab, setFoilTab] = useState<'hot' | 'cold'>('hot');
    const [hasInteracted, setHasInteracted] = useState(false);

    const markInteracted = () => {
        setHasInteracted(true);
    };

    return (
        <>
            {groups.map((group) => {
                const isFoilGroup =
                    group.key === 'special_finish' || group.key === 'hot_foil';
                const hasColdFoilValues =
                    isFoilGroup &&
                    group.values.some((value) =>
                        optionValueCode(value).startsWith('cold_'),
                    );
                const values = hasColdFoilValues
                    ? group.values.filter((value) =>
                          foilTab === 'cold'
                              ? optionValueCode(value).startsWith('cold_')
                              : !optionValueCode(value).startsWith('cold_'),
                      )
                    : group.values;
                const visibleTextureValues =
                    group.key === 'texture' &&
                    typeof selected.thickness === 'string' &&
                    selected.thickness !== ''
                        ? values.filter(
                              (value) =>
                                  value.thickness_code === selected.thickness,
                          )
                        : values;

                if (group.key === 'thickness') {
                    return (
                        <ThicknessOptionGroup
                            key={group.key}
                            group={group}
                            selected={selected}
                            onSelect={(groupKey, code) => {
                                onSelect(groupKey, code);
                                markInteracted();
                            }}
                        />
                    );
                }

                if (
                    group.key === 'texture' &&
                    visibleTextureValues.some(
                        (value) => value.texture_code && value.color_code,
                    )
                ) {
                    return (
                        <CottonTextureOptionGroup
                            key={group.key}
                            group={group}
                            values={visibleTextureValues}
                            selected={selected}
                            onSelect={(groupKey, code) => {
                                onSelect(groupKey, code);
                                markInteracted();
                            }}
                        />
                    );
                }

                return (
                    <OptionGroup key={group.key} label={group.label}>
                        {isFoilGroup && group.type === 'multi_select' && (
                            <div className="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-neutral-100 pb-2">
                                <span className="text-sm font-semibold text-neutral-700">
                                    {group.values.some((value) =>
                                        isFoilOption(
                                            optionValueCode(value),
                                            value.name,
                                            value.description,
                                        ),
                                    )
                                        ? 'Choose one or more foil colors'
                                        : 'Choose one or more finishes'}
                                </span>
                                {hasColdFoilValues && (
                                    <div className="flex rounded-md bg-neutral-100 p-0.5">
                                        {(['hot', 'cold'] as const).map(
                                            (tab) => (
                                                <button
                                                    key={tab}
                                                    type="button"
                                                    onClick={() => {
                                                        setFoilTab(tab);
                                                        markInteracted();
                                                    }}
                                                    className={`rounded-[4px] px-3 py-1 text-xs font-semibold transition-all ${
                                                        foilTab === tab
                                                            ? 'bg-white text-[#800020] shadow-sm'
                                                            : 'text-neutral-500 hover:text-neutral-800'
                                                    }`}
                                                >
                                                    {tab === 'hot'
                                                        ? 'Hot Foil'
                                                        : 'Cold Foil'}
                                                </button>
                                            ),
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                        <div
                            className={`grid grid-cols-2 gap-3 ${
                                group.key === 'texture'
                                    ? 'sm:grid-cols-3'
                                    : 'sm:grid-cols-4'
                            }`}
                        >
                            {values.map((value) => {
                                const code = optionValueCode(value);
                                const selectedValue = selected[group.key];
                                const isSelected =
                                    group.type === 'multi_select'
                                        ? Array.isArray(selectedValue) &&
                                          selectedValue.includes(code)
                                        : selectedValue === code;
                                const active = isSelected && hasInteracted;
                                const swatch =
                                    group.key === 'corners'
                                        ? (cornerSwatchFor(code) ??
                                          value.swatch_image)
                                        : group.key === 'sizes'
                                          ? sizeSwatchFor(
                                                code,
                                                value.swatch_image,
                                            )
                                          : value.swatch_image;
                                const isCustomSize =
                                    group.key === 'sizes' && code === 'custom';
                                const hasSpecialFinishSide =
                                    (group.key === 'special_finish'
                                        ? showSpecialFinishSides
                                        : group.key === 'hot_foil'
                                          ? showHotFoilSides
                                          : false) &&
                                    !isNoSpecialFinishCode(code);
                                const isSvg =
                                    typeof swatch === 'string' &&
                                    swatch.trimStart().startsWith('<svg');

                                const handleSelect = () => {
                                    if (isCustomSize && onCustomSizeSelect) {
                                        onCustomSizeSelect();
                                    } else {
                                        onSelect(group.key, code);
                                    }
                                    markInteracted();
                                };
                                const tileContent = (
                                    <>
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
                                                    {group.type ===
                                                    'multi_select'
                                                        ? 'Select option'
                                                        : 'Option'}
                                                </span>
                                            )}
                                        </div>
                                        {shouldShowSwatchCaption(
                                            group.key,
                                            code,
                                        ) &&
                                            (isCustomSize &&
                                            active &&
                                            customSize ? (
                                                <p className="text-xs text-neutral-500">
                                                    {customSize.width.toFixed(
                                                        2,
                                                    )}
                                                    " x{' '}
                                                    {customSize.height.toFixed(
                                                        2,
                                                    )}
                                                    "
                                                </p>
                                            ) : value.description ? (
                                                <p className="text-xs text-neutral-500">
                                                    {value.description}
                                                </p>
                                            ) : null)}
                                    </>
                                );

                                if (hasSpecialFinishSide) {
                                    return (
                                        <SpecialFinishChoiceTile
                                            key={code}
                                            active={active}
                                            onClick={handleSelect}
                                            label={value.name}
                                            finishSide={
                                                specialFinishSides[code] ??
                                                DEFAULT_SPECIAL_FINISH_SIDE
                                            }
                                            onFinishSideChange={(side) => {
                                                onSpecialFinishSideChange(
                                                    side,
                                                    code,
                                                    group.key === 'hot_foil'
                                                        ? 'hot_foil'
                                                        : 'special_finish',
                                                );
                                                markInteracted();
                                            }}
                                        >
                                            {tileContent}
                                        </SpecialFinishChoiceTile>
                                    );
                                }

                                if (group.key === 'corners') {
                                    return (
                                        <CornerChoiceCard
                                            key={code}
                                            label={value.name}
                                            swatch={swatch}
                                            active={active}
                                            onClick={handleSelect}
                                        />
                                    );
                                }

                                return (
                                    <ChoiceTile
                                        key={code}
                                        active={active}
                                        onClick={handleSelect}
                                        label={
                                            isCustomSize && active && customSize
                                                ? `${value.name} (${customSize.width.toFixed(2)}" x ${customSize.height.toFixed(2)}")`
                                                : value.name
                                        }
                                    >
                                        {tileContent}
                                    </ChoiceTile>
                                );
                            })}
                        </div>
                    </OptionGroup>
                );
            })}
        </>
    );
}

function ThicknessOptionGroup({
    group,
    selected,
    onSelect,
}: {
    group: ProductOptionGroup;
    selected: Record<string, string | string[]>;
    onSelect: (groupKey: string, value: string) => void;
}) {
    const selectedValue = selected[group.key];
    const selectedCode = typeof selectedValue === 'string' ? selectedValue : '';

    return (
        <OptionGroup label={group.label}>
            <div
                className="grid grid-cols-1 gap-3 sm:grid-cols-3"
                role="radiogroup"
                aria-label={group.label}
            >
                {group.values.map((value) => {
                    const code = optionValueCode(value);
                    const active = selectedCode === code;

                    return (
                        <label
                            key={code}
                            className={`flex cursor-pointer items-center gap-3 rounded-md border-2 px-4 py-3 text-sm font-semibold transition-colors ${
                                active
                                    ? 'border-[#800020] bg-[#800020]/5 text-neutral-900'
                                    : 'border-neutral-200 text-neutral-700 hover:border-neutral-300'
                            }`}
                        >
                            <input
                                type="radio"
                                name={`option-${group.key}`}
                                value={code}
                                checked={active}
                                onChange={() => onSelect(group.key, code)}
                                className="size-4 accent-[#800020]"
                            />
                            <span>{value.name}</span>
                        </label>
                    );
                })}
            </div>
        </OptionGroup>
    );
}

function CottonTextureOptionGroup({
    group,
    values,
    selected,
    onSelect,
}: {
    group: ProductOptionGroup;
    values: ProductOptionValue[];
    selected: Record<string, string | string[]>;
    onSelect: (groupKey: string, value: string) => void;
}) {
    const textureGroups = new Map<
        string,
        { label: string; values: ProductOptionValue[] }
    >();

    for (const value of values) {
        const textureCode = value.texture_code ?? optionValueCode(value);
        const existing = textureGroups.get(textureCode);

        if (existing) {
            existing.values.push(value);
        } else {
            textureGroups.set(textureCode, {
                label: value.texture_label ?? value.name,
                values: [value],
            });
        }
    }

    const selectedValue = selected[group.key];
    const selectedCode = typeof selectedValue === 'string' ? selectedValue : '';

    return (
        <OptionGroup label={group.label}>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                {Array.from(textureGroups.entries()).map(
                    ([textureCode, texture]) => {
                        const activeValue =
                            texture.values.find(
                                (value) =>
                                    optionValueCode(value) === selectedCode,
                            ) ?? texture.values[0];
                        const textureActive = texture.values.some(
                            (value) => optionValueCode(value) === selectedCode,
                        );

                        if (!activeValue) {
                            return null;
                        }

                        return (
                            <div
                                key={textureCode}
                                className={`rounded-md border-2 p-3 transition-colors ${
                                    textureActive
                                        ? 'border-[#800020] bg-[#800020]/5'
                                        : 'border-neutral-200'
                                }`}
                            >
                                <button
                                    type="button"
                                    aria-pressed={textureActive}
                                    onClick={() =>
                                        onSelect(
                                            group.key,
                                            optionValueCode(activeValue),
                                        )
                                    }
                                    className="block w-full text-left"
                                >
                                    {activeValue.swatch_image ? (
                                        <img
                                            src={activeValue.swatch_image}
                                            alt=""
                                            className="h-40 w-full rounded-sm object-contain"
                                        />
                                    ) : (
                                        <div className="flex h-40 items-center justify-center text-xs text-neutral-400">
                                            Texture sample
                                        </div>
                                    )}
                                    <p className="mt-2 text-sm font-semibold text-neutral-900">
                                        {texture.label}
                                    </p>
                                    {activeValue.color_label && (
                                        <p className="mt-1 text-xs text-neutral-500">
                                            {activeValue.color_label}
                                        </p>
                                    )}
                                </button>

                                <div className="mt-2 flex flex-wrap gap-1">
                                    {texture.values.map((value) => {
                                        const code = optionValueCode(value);
                                        const colorActive =
                                            selectedCode === code;

                                        return (
                                            <button
                                                key={code}
                                                type="button"
                                                aria-label={`${texture.label} ${value.color_label ?? value.name}`}
                                                aria-pressed={colorActive}
                                                title={
                                                    value.color_label ??
                                                    value.name
                                                }
                                                onClick={() =>
                                                    onSelect(group.key, code)
                                                }
                                                className={`flex size-7 items-center justify-center rounded-full border-2 transition-colors focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:outline-none ${
                                                    colorActive
                                                        ? 'border-[#800020]'
                                                        : 'border-neutral-200 hover:border-neutral-400'
                                                }`}
                                            >
                                                {value.color_swatch_image ? (
                                                    <img
                                                        src={
                                                            value.color_swatch_image
                                                        }
                                                        alt=""
                                                        className="size-5 rounded-full object-cover"
                                                    />
                                                ) : (
                                                    <span className="size-5 rounded-full bg-neutral-200" />
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    },
                )}
            </div>
        </OptionGroup>
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

function CornerChoiceCard({
    label,
    swatch,
    active,
    onClick,
}: {
    label: string;
    swatch?: string;
    active: boolean;
    onClick: () => void;
}) {
    const isSvg =
        typeof swatch === 'string' && swatch.trimStart().startsWith('<svg');

    return (
        <button
            type="button"
            aria-pressed={active}
            onClick={onClick}
            className={`flex min-h-16 items-center justify-between gap-3 rounded-md border-2 px-3 py-2 text-left transition-colors ${
                active
                    ? 'border-[#800020] bg-[#800020]/5'
                    : 'border-neutral-200 hover:border-neutral-300'
            }`}
        >
            <span className="text-sm font-semibold text-neutral-900">
                {label}
            </span>
            <span className="flex size-12 shrink-0 items-center justify-center text-neutral-700">
                {isSvg ? (
                    <span
                        className="size-10"
                        dangerouslySetInnerHTML={{ __html: swatch as string }}
                    />
                ) : swatch ? (
                    <img
                        src={swatch}
                        alt={`${label} corner preview`}
                        className="size-12 object-contain"
                    />
                ) : null}
            </span>
        </button>
    );
}

function SpecialFinishChoiceTile({
    active,
    disabled,
    onClick,
    label,
    children,
    finishSide,
    onFinishSideChange,
}: {
    active: boolean;
    disabled?: boolean;
    onClick: () => void;
    label: React.ReactNode;
    children: React.ReactNode;
    finishSide: SpecialFinishSide;
    onFinishSideChange: (side: SpecialFinishSide) => void;
}) {
    const tileClassName = `group relative h-full min-h-0 overflow-hidden rounded-md border-2 text-left transition-colors ${
        disabled
            ? 'cursor-not-allowed border-neutral-100 bg-neutral-50 opacity-50'
            : active
              ? 'border-[#800020] bg-[#800020]/5'
              : 'border-neutral-200 hover:border-neutral-300'
    }`;

    return (
        <div className={tileClassName}>
            <button
                type="button"
                aria-label={typeof label === 'string' ? label : undefined}
                aria-pressed={active}
                disabled={disabled}
                onClick={onClick}
                className="absolute inset-0 z-0 rounded-md focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none focus-visible:ring-inset"
            />
            <div className="pointer-events-none relative z-10 p-2">
                {children}
                {label && (
                    <p className="mt-2 text-sm font-bold text-black">{label}</p>
                )}
                <div className="pointer-events-none mt-1 flex overflow-hidden rounded-sm">
                    {SPECIAL_FINISH_SIDE_OPTIONS.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            aria-label={`Apply finish to ${option.label}`}
                            aria-pressed={finishSide === option.value}
                            disabled={disabled}
                            onClick={() => onFinishSideChange(option.value)}
                            className={`pointer-events-auto flex min-w-0 flex-1 items-center justify-center border px-2 py-1 text-center text-[10px] leading-tight font-semibold shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none ${
                                finishSide === option.value
                                    ? 'border-primary bg-primary text-primary-foreground hover:bg-[#800020]'
                                    : 'border-white/80 bg-white/90 text-neutral-800 backdrop-blur-sm hover:bg-white'
                            }`}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

function ChoiceTile({
    active,
    disabled,
    onClick,
    label,
    children,
}: {
    active: boolean;
    disabled?: boolean;
    onClick: () => void;
    label?: React.ReactNode;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            aria-pressed={active}
            disabled={disabled}
            onClick={onClick}
            className={`group relative overflow-hidden rounded-md border-2 p-2 text-left transition-colors ${
                disabled
                    ? 'cursor-not-allowed border-neutral-100 bg-neutral-50 opacity-50'
                    : active
                      ? 'border-[#800020] bg-[#800020]/5'
                      : 'border-neutral-200 hover:border-neutral-300'
            }`}
        >
            {children}
            {label && <p className="mt-2 text-sm font-semibold">{label}</p>}
        </button>
    );
}

function DesignChoice({
    title,
    body,
    icon,
    accent,
    onClick,
    submitted,
}: {
    title: string;
    body: string;
    icon: React.ReactNode;
    accent: string;
    onClick?: () => void;
    submitted?: boolean;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={submitted}
            className={`flex h-full flex-col items-start gap-2 rounded-md border-2 p-4 text-left transition-colors hover:border-[#800020] hover:bg-[#800020]/5 ${
                submitted
                    ? 'border-[#800020] bg-[#800020]/5 ring-1 ring-[#800020]'
                    : 'border-neutral-200 bg-white'
            }`}
        >
            <span style={{ color: accent }}>{icon}</span>
            <p className="text-sm font-bold text-neutral-900">{title}</p>
            <p className="text-xs leading-relaxed text-neutral-600">{body}</p>
        </button>
    );
}

function CustomSizeModal({
    open,
    onOpenChange,
    width,
    height,
    minWidth,
    maxWidth,
    minHeight,
    maxHeight,
    error,
    onWidthChange,
    onHeightChange,
    onConfirm,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    width: string;
    height: string;
    minWidth: number;
    maxWidth: number;
    minHeight: number;
    maxHeight: number;
    error: string | null;
    onWidthChange: (value: string) => void;
    onHeightChange: (value: string) => void;
    onConfirm: () => void;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Enter a custom card size</DialogTitle>
                    <DialogDescription>
                        Enter the width and height in inches. Width must be
                        between {formatSizeLimit(minWidth)} and{' '}
                        {formatSizeLimit(maxWidth)} inches; height must be
                        between {formatSizeLimit(minHeight)} and{' '}
                        {formatSizeLimit(maxHeight)} inches.
                    </DialogDescription>
                </DialogHeader>

                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        onConfirm();
                    }}
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label className="space-y-1.5 text-sm font-medium text-neutral-900">
                            Width (in)
                            <Input
                                type="number"
                                inputMode="decimal"
                                min={minWidth}
                                max={maxWidth}
                                step="0.01"
                                value={width}
                                onChange={(event) =>
                                    onWidthChange(event.target.value)
                                }
                                placeholder={formatSizeLimit(minWidth)}
                                required
                            />
                        </label>
                        <label className="space-y-1.5 text-sm font-medium text-neutral-900">
                            Height (in)
                            <Input
                                type="number"
                                inputMode="decimal"
                                min={minHeight}
                                max={maxHeight}
                                step="0.01"
                                value={height}
                                onChange={(event) =>
                                    onHeightChange(event.target.value)
                                }
                                placeholder={formatSizeLimit(minHeight)}
                                required
                            />
                        </label>
                    </div>

                    {error && (
                        <p className="text-sm text-red-600" role="alert">
                            {error}
                        </p>
                    )}

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit">Confirm size</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CanvaDesignModal({
    open,
    onOpenChange,
    onSubmitted,
    productId,
    productName,
    productSlug,
    returnTo,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmitted: () => void;
    productId?: number;
    productName?: string;
    productSlug?: string;
    returnTo?: string;
}) {
    const { setData, post, processing, errors, reset } = useForm<{
        desgin: string;
        design_file: File | null;
        return_to: string;
    }>({
        desgin: JSON.stringify({
            source: 'product-page',
            mode: 'canva',
            product_id: productId ?? null,
            product_name: productName ?? null,
            product_slug: productSlug ?? null,
        }),
        design_file: null,
        return_to: returnTo ?? '',
    });
    const designInputRef = useRef<HTMLInputElement>(null);

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
                        post('/product-designs', {
                            forceFormData: true,
                            preserveScroll: true,
                            preserveState: true,
                            onSuccess: () => {
                                toast.success(
                                    'File received — we will attach it to your order.',
                                );
                                reset();
                                if (designInputRef.current) {
                                    designInputRef.current.value = '';
                                }
                                onSubmitted();
                                onOpenChange(false);
                            },
                        });
                    }}
                >
                    <h3 className="text-sm font-bold text-neutral-900">
                        Upload your Canva design file
                    </h3>
                    <Input
                        ref={designInputRef}
                        type="file"
                        accept=".pdf,.png,.jpg,.jpeg,.svg,.ai,.psd"
                        required
                        onChange={(e) =>
                            setData('design_file', e.target.files?.[0] ?? null)
                        }
                    />
                    {errors.design_file && (
                        <p className="text-sm text-red-600">
                            {errors.design_file}
                        </p>
                    )}
                    <Button
                        type="submit"
                        className="w-full sm:w-auto"
                        disabled={processing}
                    >
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
