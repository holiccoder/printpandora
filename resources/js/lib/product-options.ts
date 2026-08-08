export interface ProductGallery {
    id: string;
    is_default?: boolean;
    match: Record<string, string>;
    images: string[];
}

/**
 * Normalize an option value the same way the product detail page converts
 * raw option names into selected-state ids.
 *
 * Special-finish values are slugified ("spot glass" -> "spot-glass");
 * everything else is lowercased.
 */
export function normalizeOptionValue(group: string, value: string): string {
    return value.toLowerCase().trim().replace(/[\s_]+/g, '-');
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
            ? selectedValue.some((value) => normalizeOptionValue(key, value) === expected)
            : normalizeOptionValue(key, selectedValue) === expected;
    });
}

/**
 * Find the best gallery for the current option selection.
 *
 * Returns the first non-default gallery whose `match` is satisfied.
 * Only keys explicitly present in a gallery's `match` are checked;
 * unspecified keys (e.g. `sizes`, `quantity`) are ignored, so
 * e.g. size changes never affect gallery selection.
 * Falls back to the gallery marked `is_default`.
 */
export function findMatchingGallery(
    galleries: ProductGallery[],
    selected: Record<string, string | string[]>,
): ProductGallery | undefined {
    const specific = galleries.find(
        (gallery) => !gallery.is_default && matches(gallery.match, selected),
    );

    if (specific) {
        return specific;
    }

    return galleries.find((gallery) => gallery.is_default);
}
