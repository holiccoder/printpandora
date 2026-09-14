export interface PricingScenario {
    packageName: string;
    basePrice: number;
    startQuantity: number;
    paperRates: Record<string, number>;
    unitMultipliers?: Record<string, number>;
    area_based?: boolean;
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

    const preferred = isUv ? 'square_uv' : 'square';

    if (data[preferred] != null) {
        return preferred;
    }

    return isUv ? 'uv' : 'rectangle';
}

function normalizeOptionValue(value: string): string {
    return value
        .trim()
        .toLowerCase()
        .replace(/[\s_]+/g, '-');
}

function hasPositiveSelection(value: string | string[] | undefined): boolean {
    const negativeValues = new Set([
        '',
        'none',
        'no',
        'no-foil',
        'no-special-finish',
        'no-print-code',
        'no-print-code-or-magnetic-stripe',
        'no-magnetic-stripe',
        'no-signature-stripe',
    ]);
    const values = Array.isArray(value) ? value : [value ?? ''];

    return values.some(
        (item) => !negativeValues.has(normalizeOptionValue(item)),
    );
}

function hasPrintCodeSelection(value: string | string[] | undefined): boolean {
    const values = Array.isArray(value) ? value : [value ?? ''];

    return values.some((item) => normalizeOptionValue(item) === 'print-code');
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
            ...Object.keys(scenario.unitMultipliers ?? {})
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
            code === 'special-finish' ||
            name.includes('foil') ||
            name.includes('烫金') ||
            name.includes('special finish') ||
            name.includes('激光雕刻') ||
            name.includes('彩印') ||
            name.includes('镀色')
        );
    });
    const printCodeProcess = scenario.processes.find((p) => {
        const code = normalizeOptionValue(p.code ?? '');
        const name = p.name.toLowerCase();

        return (
            code === 'print-code' ||
            code === 'print-code-or-magnetic-stripe' ||
            name.includes('print code') ||
            name.includes('打码')
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
    const printCodeSelected = [
        selectedOptions.print_code,
        selectedOptions.print_code_or_signature_stripe,
    ].some(hasPrintCodeSelection);
    const printCodeOrMagneticStripeSelected =
        selectedOptions.print_code_or_magnetic_stripe !== undefined &&
        hasPositiveSelection(selectedOptions.print_code_or_magnetic_stripe);
    const effectivePrintCodeSelected =
        printCodeSelected || printCodeOrMagneticStripeSelected;
    const rounded = roundedSelected && roundedProcess != null;
    const foiled = foiledSelected && foilProcess != null;

    return quantities.map((qty) => {
        const isStart = qty === scenario.startQuantity;

        let unit = scenario.basePrice;
        const unitMultiplier = scenario.unitMultipliers?.[String(qty)];

        if (unitMultiplier != null && Number.isFinite(unitMultiplier)) {
            unit = scenario.basePrice * unitMultiplier;
        } else if (!isStart) {
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

        if (effectivePrintCodeSelected && printCodeProcess) {
            unit += printCodeProcess.markup;

            if (!isStart) {
                const rate = printCodeProcess.rates[String(qty)] ?? 0;
                unit -= printCodeProcess.markup * (rate / 100);
            }
        }

        if (foiled && foilProcess) {
            unit += foilProcess.markup;

            if (!isStart) {
                const rate = foilProcess.rates[String(qty)] ?? 0;
                unit -= foilProcess.markup * (rate / 100);
            }
        }

        const paperArea = scenario.area_based
            ? Number(selectedOptions.paper_area ?? 0)
            : 1;
        const adjustedUnit =
            Number.isFinite(paperArea) && paperArea > 0 ? unit * paperArea : 0;

        return {
            qty,
            pricePerCard: adjustedUnit,
            currentPrice: Math.round(qty * adjustedUnit),
            originalPrice: null,
            recommended: isStart,
        };
    });
}
