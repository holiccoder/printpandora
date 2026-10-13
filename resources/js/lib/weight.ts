const GRAMS_PER_POUND = 453.59237;

export function formatGramsAsPounds(grams: number): string {
    const poundValue = (grams / GRAMS_PER_POUND).toFixed(2);
    const gramValue = String(grams);

    return `${poundValue} lbs (${gramValue}g)`;
}

export function formatWeightInGramsAndPounds(grams: number): string {
    const gramValue = Math.max(0, Math.round(grams));
    const poundValue = (gramValue / GRAMS_PER_POUND).toFixed(2);

    return `${gramValue} g (${poundValue} lbs)`;
}

/** Convert explicit gram weights in display copy, leaving GSM values intact. */
export function formatGramMeasurements(text: string): string {
    return text.replace(
        /(^|[^\w./])(\d+(?:[.,]\d+)?)\s*(grams?|g)\b(?!\s*(?:\/\s*m(?:\u00B2|2)|\s+per\s+square))/gi,
        (_match, prefix: string, value: string) => {
            const grams = Number(value.replace(',', '.'));

            return Number.isFinite(grams)
                ? prefix + formatGramsAsPounds(grams)
                : _match;
        },
    );
}
