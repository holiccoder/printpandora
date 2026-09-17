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

const INCH_TO_METRE = 0.0254;

export function squareInchesToSquareMetres(
    widthInches: number,
    heightInches: number,
): number {
    if (
        !Number.isFinite(widthInches) ||
        !Number.isFinite(heightInches) ||
        widthInches <= 0 ||
        heightInches <= 0
    ) {
        return 0;
    }

    return widthInches * INCH_TO_METRE * (heightInches * INCH_TO_METRE);
}

export function resolvePricingScenario(
    data: DynamicPricingData,
    sizeIndex: number,
    finishIndex: number,
    selectedOptions: Record<string, string | string[]> = {},
): 'rectangle' | 'uv' | 'square' | 'square_uv' {
    const hasUv = data.uv != null;
    const selectedUv = selectedOptions.uv_finish;
    const hasSelectedUv =
        (Array.isArray(selectedUv) && selectedUv.length > 0) ||
        (typeof selectedUv === 'string' && selectedUv !== '');
    const isUv =
        hasUv &&
        (hasSelectedUv ||
            (!Object.prototype.hasOwnProperty.call(
                selectedOptions,
                'uv_finish',
            ) && finishIndex === 2));

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

const HOT_FOIL_OPTION_CODES = new Set([
    'black-gold',
    'blue-gold',
    'bright-gold',
    'bright-silver',
    'green-gold',
    'matte-gold',
    'matte-silver',
    'red-gold',
    'rose-gold',
    'aged-gold',
    'muted-purple-gold',
]);

function selectedFinishCodes(
    selectedFinish: string | string[] | undefined,
): string[] {
    return (
        Array.isArray(selectedFinish)
            ? selectedFinish
            : selectedFinish
              ? [selectedFinish]
              : []
    ).map(normalizeOptionValue);
}

function isFoilOptionCode(code: string): boolean {
    return (
        code.includes('foil') ||
        code.startsWith('cold-') ||
        HOT_FOIL_OPTION_CODES.has(code)
    );
}

function isFoilProcess(
    process: PricingScenario['processes'][number],
    selectedFinish: string | string[] | undefined,
): boolean {
    const code = pricingProcessCode(process);
    const rawName = process.name.trim().toLowerCase();

    if (code === 'foil' || code.includes('foil')) {
        return true;
    }

    if (code === 'special-finish') {
        return (
            rawName.includes('foil') ||
            rawName.includes('冷烫') ||
            rawName.includes('热烫') ||
            selectedFinishCodes(selectedFinish).some(isFoilOptionCode)
        );
    }

    return isFoilOptionCode(code) || rawName.includes('foil');
}

function foilSideMultiplier(
    selectedFinish: string | string[] | undefined,
    sides: Record<string, 'one_side' | 'both_sides'>,
    process?: PricingScenario['processes'][number],
): number {
    let selectedCodes = selectedFinishCodes(selectedFinish);

    if (process) {
        const processCode = pricingProcessCode(process);
        const processName = process.name.trim().toLowerCase();
        const hasGenericFoilName =
            processName.includes('foil') || processName.includes('烫金');
        const mentionsColdFoil =
            processCode.includes('cold') ||
            processName.includes('cold foil') ||
            processName.includes('冷烫');
        const mentionsHotFoil =
            processCode.includes('hot') ||
            processName.includes('hot foil') ||
            processName.includes('热烫');
        const isColdFoil = mentionsColdFoil && !mentionsHotFoil;
        const isHotFoil = mentionsHotFoil && !mentionsColdFoil;
        const isGenericFoil =
            (['foil', 'special-finish'].includes(processCode) ||
                hasGenericFoilName) &&
            !isColdFoil &&
            !isHotFoil;

        if (!isGenericFoil) {
            selectedCodes = selectedCodes.filter((code) => {
                if (code === processCode) {
                    return true;
                }

                if (isColdFoil) {
                    return isFoilOptionCode(code) && code.startsWith('cold-');
                }

                if (isHotFoil) {
                    return isFoilOptionCode(code) && !code.startsWith('cold-');
                }

                return false;
            });
        }
    }

    if (selectedCodes.length === 0) {
        return 1;
    }

    const bothSidedCodes = new Set(
        Object.entries(sides)
            .filter(([, side]) => side === 'both_sides')
            .map(([code]) => normalizeOptionValue(code)),
    );

    return selectedCodes.some((code) => bothSidedCodes.has(code)) ? 2 : 1;
}

function pricingProcessCode(
    process: PricingScenario['processes'][number],
): string {
    const explicitCode = process.code?.trim();

    if (explicitCode) {
        return normalizeOptionValue(explicitCode);
    }

    const name = process.name.trim();
    const normalizedName = normalizeOptionValue(name);

    switch (name) {
        case '圆角':
            return 'rounded-corners';
        case '激光':
            return 'laser';
        case '滚边':
            return 'edge-coloring';
        case '对裱':
            return 'double-mounting';
        case '异形模切':
            return 'custom-die-cut';
        default:
            return ['rounded', 'rounded-corners', 'round'].includes(
                normalizedName,
            )
                ? 'rounded-corners'
                : normalizedName;
    }
}

function foilProcessMatchesSelection(
    processCode: string,
    processName: string,
    selectedCode: string,
): boolean {
    if (['foil', 'special-finish'].includes(processCode)) {
        return true;
    }

    if (selectedCode === processCode) {
        return true;
    }

    const hasGenericFoilName =
        processName.includes('foil') || processName.includes('烫金');

    const mentionsColdFoil =
        processCode.includes('cold') ||
        processName.includes('cold foil') ||
        processName.includes('冷烫');
    const mentionsHotFoil =
        processCode.includes('hot') ||
        processName.includes('hot foil') ||
        processName.includes('热烫');

    const isColdFoil = mentionsColdFoil && !mentionsHotFoil;
    const isHotFoil = mentionsHotFoil && !mentionsColdFoil;

    if (hasGenericFoilName && !isColdFoil && !isHotFoil) {
        return true;
    }

    if (isColdFoil) {
        return (
            isFoilOptionCode(selectedCode) && selectedCode.startsWith('cold-')
        );
    }

    if (isHotFoil) {
        return (
            isFoilOptionCode(selectedCode) && !selectedCode.startsWith('cold-')
        );
    }

    return false;
}

function processIsSelected(
    process: PricingScenario['processes'][number],
    selectedOptions: Record<string, string | string[]>,
    cornersIndex: number,
    specialFinishIndex: number,
): boolean {
    const code = pricingProcessCode(process);
    const rawName = process.name.trim().toLowerCase();

    if (
        code === 'print-code-or-magnetic-stripe' &&
        selectedOptions.print_code_or_magnetic_stripe !== undefined
    ) {
        return hasPositiveSelection(
            selectedOptions.print_code_or_magnetic_stripe,
        );
    }

    if (
        code === 'print-code' ||
        code === 'print-code-or-magnetic-stripe' ||
        rawName.includes('print code') ||
        rawName.includes('打码')
    ) {
        return [
            selectedOptions.print_code,
            selectedOptions.print_code_or_signature_stripe,
            selectedOptions.print_code_or_magnetic_stripe,
        ].some((value) => hasPrintCodeSelection(value));
    }

    if (
        code === 'rounded-corners' ||
        rawName.includes('rounded') ||
        rawName.includes('圆角')
    ) {
        const values =
            selectedOptions.corners ??
            (cornersIndex === 1 ? 'rounded' : 'square');

        return (Array.isArray(values) ? values : [values]).some((value) =>
            ['rounded', 'rounded-corners', 'round'].includes(
                normalizeOptionValue(value),
            ),
        );
    }

    if (
        [
            'laser',
            'edge-coloring',
            'double-mounting',
            'custom-die-cut',
        ].includes(code)
    ) {
        const values =
            selectedOptions.special_finish ??
            (specialFinishIndex > 0 ? 'special_finish' : 'none');

        return (Array.isArray(values) ? values : [values]).some(
            (value) => normalizeOptionValue(value) === code,
        );
    }

    if (
        ['foil', 'special-finish'].includes(code) ||
        rawName.includes('foil') ||
        rawName.includes('烫金') ||
        rawName.includes('激光雕刻') ||
        rawName.includes('彩印') ||
        rawName.includes('镀色')
    ) {
        const selectedFinish = selectedOptions.special_finish;

        if (selectedFinish !== undefined) {
            const values = Array.isArray(selectedFinish)
                ? selectedFinish
                : [selectedFinish];

            if (
                values.some(
                    (value) =>
                        hasPositiveSelection(value) &&
                        foilProcessMatchesSelection(
                            code,
                            rawName,
                            normalizeOptionValue(value),
                        ),
                )
            ) {
                return true;
            }
        }

        return Object.entries(selectedOptions).some(([key, value]) => {
            const values = Array.isArray(value) ? value : [value];

            return values.some(
                (item) =>
                    normalizeOptionValue(item) === code &&
                    (key !== 'special_finish' || hasPositiveSelection(item)),
            );
        });
    }

    const selected = selectedOptions[code];

    return selected !== undefined && hasPositiveSelection(selected);
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
    selectedSpecialFinishSides: Record<string, 'one_side' | 'both_sides'> = {},
): QuantityTier[] {
    const scenario = data.rules?.length
        ? findMatchingPricingRule(data.rules, selectedOptions)
        : data[
              resolvePricingScenario(
                  data,
                  sizeIndex,
                  finishIndex,
                  selectedOptions,
              )
          ];

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

    const selectedProcesses = scenario.processes.filter((process) =>
        processIsSelected(
            process,
            selectedOptions,
            cornersIndex,
            specialFinishIndex,
        ),
    );

    return quantities.map((qty) => {
        let unit = scenario.basePrice;
        const unitMultiplier = scenario.unitMultipliers?.[String(qty)];

        if (unitMultiplier != null && Number.isFinite(unitMultiplier)) {
            unit = scenario.basePrice * unitMultiplier;
        } else {
            const paperRate = scenario.paperRates[String(qty)] ?? 0;
            unit -= scenario.basePrice * (paperRate / 100);
        }

        for (const process of selectedProcesses) {
            const markup = isFoilProcess(
                process,
                selectedOptions.special_finish,
            )
                ? process.markup *
                  foilSideMultiplier(
                      selectedOptions.special_finish,
                      selectedSpecialFinishSides,
                      process,
                  )
                : process.markup;

            unit += markup;
            const rate = process.rates[String(qty)] ?? 0;
            unit -= markup * (rate / 100);
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
            recommended: qty === scenario.startQuantity,
        };
    });
}
