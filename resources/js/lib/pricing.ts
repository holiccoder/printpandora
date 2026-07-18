export interface PricingScenario {
    packageName: string;
    basePrice: number;
    startQuantity: number;
    paperRates: Record<string, number>;
    processes: Array<{
        name: string;
        markup: number;
        rates: Record<string, number>;
    }>;
}

export interface DynamicPricingData {
    rectangle: PricingScenario;
    uv: PricingScenario;
    square: PricingScenario;
    square_uv: PricingScenario;
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
    sizeIndex: number,
    finishIndex: number,
): keyof DynamicPricingData {
    if (sizeIndex === 0) {
        return finishIndex === 2 ? 'uv' : 'rectangle';
    }

    return finishIndex === 2 ? 'square_uv' : 'square';
}

export function computeDynamicTiers(
    data: DynamicPricingData,
    sizeIndex: number,
    finishIndex: number,
    cornersIndex: number,
    specialFinishIndex: number,
): QuantityTier[] {
    const scenario = data[resolvePricingScenario(sizeIndex, finishIndex)];

    if (!scenario) {
        return [];
    }

    const quantities = Object.keys(scenario.paperRates)
        .map((q) => parseInt(q, 10))
        .filter((q) => q >= scenario.startQuantity)
        .sort((a, b) => a - b);

    const roundedProcess = scenario.processes.find((p) => p.name === '圆角');
    const foilProcess = scenario.processes.find((p) => p.name === '烫金');

    const rounded = cornersIndex === 1 && roundedProcess != null;
    const foiled = specialFinishIndex > 0 && foilProcess != null;

    return quantities.map((qty) => {
        const isStart = qty === scenario.startQuantity;

        let unit = scenario.basePrice;

        if (!isStart) {
            const paperRate = scenario.paperRates[String(qty)] ?? 0;
            unit -= scenario.basePrice * (paperRate / 100);
        }

        if (rounded) {
            unit += roundedProcess.markup;

            if (!isStart) {
                const rate = roundedProcess.rates[String(qty)] ?? 0;
                unit -= roundedProcess.markup * (rate / 100);
            }
        }

        if (foiled) {
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
