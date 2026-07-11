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
    const lower = value.toLowerCase();

    if (group === 'special_finish') {
        return lower.replace(/\s+/g, '-');
    }

    return lower;
}

function matches(
    match: Record<string, string>,
    selected: Record<string, string>,
    defaults: Record<string, string>,
): boolean {
    return Object.entries(selected).every(([key, selectedValue]) => {
        const matchValue = match[key];

        if (matchValue !== undefined) {
            return selectedValue === normalizeOptionValue(key, matchValue);
        }

        // Unspecified options must equal their default/first value.
        return selectedValue === defaults[key];
    });
}

/**
 * Find the best gallery for the current option selection.
 *
 * Returns the first non-default gallery whose `match` is satisfied.
 * Any option key omitted from a gallery's `match` is treated as matching
 * only when the selected value equals the corresponding default value.
 * Falls back to the gallery marked `is_default`.
 */
export function findMatchingGallery(
    galleries: ProductGallery[],
    selected: Record<string, string>,
    defaults: Record<string, string>,
): ProductGallery | undefined {
    const specific = galleries.find(
        (gallery) => !gallery.is_default && matches(gallery.match, selected, defaults),
    );

    if (specific) {
        return specific;
    }

    return galleries.find((gallery) => gallery.is_default);
}
