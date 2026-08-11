export interface PricingScenario {
    packageName: string;
    basePrice: number;
    startQuantity: number;
    paperRates: Record<string, number>;
    processes: Array<{
        code?: string;
        name: string;
        markup: number;
        rates: Record<string, number>;
    }>;
}

export interface PricingRule {
    id?: string;
    match: Record<string, string>;
    pricing: PricingScenario;
}

export interface DynamicPricingData {
    rectangle?: PricingScenario;
    uv?: PricingScenario;
    square?: PricingScenario;
    square_uv?: PricingScenario;
    rules?: PricingRule[];
}

export interface QuantityTier {
    qty: number;
    pricePerCard: number;
    currentPrice: number;
    originalPrice: number | null;
    recommended: boolean;
    badge?: string;
}

export function resolvePricingScenario(
    data: DynamicPricingData,
    sizeIndex: number,
    finishIndex: number,
): 'rectangle' | 'uv' | 'square' | 'square_uv' {
    const hasUv = data.uv != null;
    const isUv = hasUv && finishIndex === 2;

    if (sizeIndex === 0) {
        return isUv ? 'uv' : 'rectangle';
    }

    return isUv ? 'square_uv' : 'square';
}

function normalizeOptionValue(value: string): string {
    return value
        .trim()
        .toLowerCase()
        .replace(/[\s_]+/g, '-');
}

function normalizeSelectedOptions(
    selected: Record<string, string | string[]>,
): Record<string, string | string[]> {
    if (
        Array.isArray(selected.sizes)
            ? selected.sizes.includes('custom')
            : selected.sizes === 'custom'
    ) {
        return { ...selected, sizes: 'standard' };
    }

    return selected;
}

export function findMatchingPricingRule(
    rules: PricingRule[],
    selected: Record<string, string | string[]>,
): PricingScenario | undefined {
    const normalizedSelected = normalizeSelectedOptions(selected);

    return [...rules]
        .sort(
            (a, b) =>
                Object.keys(b.match ?? {}).length -
                Object.keys(a.match ?? {}).length,
        )
        .find((rule) =>
            Object.entries(rule.match ?? {}).every(([key, expected]) => {
                const actual = normalizedSelected[key];
                const expectedValue = normalizeOptionValue(expected);

                return (
                    actual != null &&
                    (Array.isArray(actual)
                        ? actual.some(
                              (value) =>
                                  normalizeOptionValue(value) === expectedValue,
                          )
                        : normalizeOptionValue(actual) === expectedValue)
                );
            }),
        )?.pricing;
}

export function computeDynamicTiers(
    data: DynamicPricingData,
    sizeIndex: number,
    finishIndex: number,
    cornersIndex: number,
    specialFinishIndex: number,
    selectedOptions: Record<string, string | string[]> = {},
): QuantityTier[] {
    const scenario = data.rules?.length
        ? findMatchingPricingRule(data.rules, selectedOptions)
        : data[resolvePricingScenario(data, sizeIndex, finishIndex)];

    if (!scenario) {
        return [];
    }

    const quantities = [
        ...new Set([
            scenario.startQuantity,
            ...Object.keys(scenario.paperRates)
                .map((q) => parseInt(q, 10))
                .filter((q) => q >= scenario.startQuantity),
        ]),
    ].sort((a, b) => a - b);

    const roundedProcess = scenario.processes.find((p) => {
        const code = normalizeOptionValue(p.code ?? '');
        const name = p.name.toLowerCase();

        return (
            code === 'rounded-corners' ||
            code === 'rounded' ||
            name.includes('rounded') ||
            name.includes('圆角')
        );
    });
    const foilProcess = scenario.processes.find((p) => {
        const code = normalizeOptionValue(p.code ?? '');
        const name = p.name.toLowerCase();

        return (
            code === 'foil' ||
            code === 'nfc' ||
            name.includes('foil') ||
            name.includes('烫金') ||
            name === 'nfc'
        );
    });

    const selectedCorners = selectedOptions.corners;
    const roundedSelected = selectedCorners
        ? Array.isArray(selectedCorners)
            ? selectedCorners.some((value) =>
                  ['rounded', 'rounded-corners', 'round'].includes(
                      normalizeOptionValue(value),
                  ),
              )
            : ['rounded', 'rounded-corners', 'round'].includes(
                  normalizeOptionValue(selectedCorners),
              )
        : cornersIndex === 1;
    const selectedSpecialFinish = selectedOptions.special_finish;
    const specialFinish = selectedSpecialFinish
        ? Array.isArray(selectedSpecialFinish)
            ? selectedSpecialFinish.map(normalizeOptionValue)
            : normalizeOptionValue(selectedSpecialFinish)
        : null;
    const foiledSelected = specialFinish
        ? Array.isArray(specialFinish)
            ? specialFinish.some(
                  (value) =>
                      !['', 'none', 'no-foil', 'no-special-finish'].includes(
                          value,
                      ),
              )
            : !['', 'none', 'no-foil', 'no-special-finish'].includes(
                  specialFinish,
              )
        : specialFinishIndex > 0;

    const rounded = roundedSelected && roundedProcess != null;
    const foiled = foiledSelected && foilProcess != null;

    return quantities.map((qty) => {
        const isStart = qty === scenario.startQuantity;

        let unit = scenario.basePrice;

        if (!isStart) {
            const paperRate = scenario.paperRates[String(qty)] ?? 0;
            unit -= scenario.basePrice * (paperRate / 100);
        }

        if (rounded && roundedProcess) {
            unit += roundedProcess.markup;

            if (!isStart) {
                const rate = roundedProcess.rates[String(qty)] ?? 0;
                unit -= roundedProcess.markup * (rate / 100);
            }
        }

        if (foiled && foilProcess) {
            unit += foilProcess.markup;

            if (!isStart) {
                const rate = foilProcess.rates[String(qty)] ?? 0;
                unit -= foilProcess.markup * (rate / 100);
            }
        }

        return {
            qty,
            pricePerCard: unit,
            currentPrice: Math.round(qty * unit),
            originalPrice: null,
            recommended: isStart,
        };
    });
}
