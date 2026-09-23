export interface ProductGallery {
    id: string;
    is_default?: boolean;
    match: Record<string, string>;
    images: string[];
}

export const PRODUCT_THUMBNAIL_LIMIT = 4;

/**
 * Return the stable thumbnail strip for a product. Option-switched primary
 * images are intentionally not included so swatches cannot append thumbnails.
 */
export function getProductThumbnailImages(
    defaultGallery: ProductGallery,
    isStickerProduct = false,
): string[] {
    const images = isStickerProduct
        ? Array.from(new Set(defaultGallery.images))
        : defaultGallery.images;

    return images.slice(0, PRODUCT_THUMBNAIL_LIMIT);
}

/**
 * Normalize an option value the same way the product detail page converts
 * raw option names into selected-state ids.
 *
 * Special-finish values are slugified ("spot glass" -> "spot-glass");
 * everything else is lowercased.
 */
export function normalizeOptionValue(group: string, value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[\s_]+/g, '-');
}

/**
 * Apply the standard-quality Texture/3D UV relationship while preserving
 * non-matte/non-gloss textures alongside a UV selection.
 */
export function applyTextureUvSelection(
    selected: Record<string, string | string[]>,
    groupKey: string,
    value: string,
): Record<string, string | string[]> {
    const next = { ...selected, [groupKey]: value };

    if (
        groupKey === 'texture' &&
        Object.prototype.hasOwnProperty.call(next, 'uv_finish') &&
        (value === 'matte' || value === 'gloss')
    ) {
        next.uv_finish = '';
    }

    if (
        groupKey === 'uv_finish' &&
        (next.texture === 'matte' || next.texture === 'gloss')
    ) {
        next.texture = '';
    }

    return next;
}

function matches(
    match: Record<string, string>,
    selected: Record<string, string | string[]>,
): boolean {
    return Object.entries(match).every(([key, matchValue]) => {
        const selectedValue = selected[key];

        if (selectedValue === undefined) {
            return false;
        }

        const expected = normalizeOptionValue(key, matchValue);

        return Array.isArray(selectedValue)
            ? selectedValue.some(
                  (value) => normalizeOptionValue(key, value) === expected,
              )
            : normalizeOptionValue(key, selectedValue) === expected;
    });
}

/**
 * Return the gallery match key that should win when a product has multiple
 * independent option galleries matching the same selection.
 *
 * PVC cards default to their paper-finish gallery. For any product, a clicked
 * option group can take precedence when that group has a gallery rule of its
 * own; this lets multi-select groups show the last clicked value.
 */
export function getPreferredGalleryMatchKey(
    galleries: ProductGallery[],
    isPvcProduct: boolean,
    lastSelectedOptionKey: string | null = null,
): string | undefined {
    const preferredKey =
        lastSelectedOptionKey ?? (isPvcProduct ? 'paper_finish' : undefined);

    if (!preferredKey) {
        return undefined;
    }

    return galleries.some(
        (gallery) =>
            !gallery.is_default &&
            Object.prototype.hasOwnProperty.call(gallery.match, preferredKey),
    )
        ? preferredKey
        : undefined;
}

/**
 * Find the best gallery for the current option selection.
 *
 * Returns the first non-default gallery whose `match` is satisfied, unless a
 * preferred match key is provided and one of the matching galleries includes
 * that key.
 * Only keys explicitly present in a gallery's `match` are checked;
 * unspecified keys (e.g. `quantity`) are ignored, so galleries without a
 * size-specific match continue to apply across size changes.
 * Falls back to the gallery marked `is_default`.
 */
export function findMatchingGallery(
    galleries: ProductGallery[],
    selected: Record<string, string | string[]>,
    preferredMatchKey?: string,
): ProductGallery | undefined {
    const matchingSpecific = galleries.filter(
        (gallery) => !gallery.is_default && matches(gallery.match, selected),
    );

    const preferredValue = preferredMatchKey
        ? selected[preferredMatchKey]
        : undefined;
    const preferredValues = Array.isArray(preferredValue)
        ? [...preferredValue].reverse()
        : preferredValue !== undefined
          ? [preferredValue]
          : [];

    const preferred = preferredMatchKey
        ? preferredValues.reduce<ProductGallery | undefined>(
              (matched, value) =>
                  matched ??
                  matchingSpecific.find(
                      (gallery) =>
                          Object.prototype.hasOwnProperty.call(
                              gallery.match,
                              preferredMatchKey,
                          ) &&
                          normalizeOptionValue(
                              preferredMatchKey,
                              gallery.match[preferredMatchKey],
                          ) === normalizeOptionValue(preferredMatchKey, value),
                  ),
              undefined,
          )
        : undefined;

    if (preferredMatchKey && preferredValues.length > 0 && !preferred) {
        return galleries.find((gallery) => gallery.is_default);
    }

    if (preferred) {
        return preferred;
    }

    if (matchingSpecific[0]) {
        return matchingSpecific[0];
    }

    return galleries.find((gallery) => gallery.is_default);
}
