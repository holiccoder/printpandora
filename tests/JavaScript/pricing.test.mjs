import assert from 'node:assert/strict';
import test from 'node:test';

import { computeDynamicTiers } from '../../resources/js/lib/pricing.ts';

const specialRates = {
    100: 50,
    200: 75,
    500: 80,
    1000: 82,
    2000: 82.5,
    3000: 83,
    4000: 83.5,
    5000: 84,
    10000: 84,
};

const scenario = {
    packageName: '棉纸-基础型',
    basePrice: 1.9,
    startQuantity: 200,
    paperRates: {
        100: 47.37,
        200: 68.42,
        500: 68.42,
        1000: 77.89,
        2000: 78.42,
        3000: 78.95,
        4000: 79.47,
        5000: 80,
        10000: 80,
    },
    processes: [
        {
            name: '圆角',
            code: 'rounded_corners',
            markup: 0.14,
            rates: {
                100: 50,
                200: 75,
                500: 78.57,
                1000: 80,
                2000: 81.43,
                3000: 84.29,
                4000: 85,
                5000: 85.74,
                10000: 85.74,
            },
        },
        ...[
            ['激光', 'laser'],
            ['滚边', 'edge_coloring'],
            ['对裱', 'double_mounting'],
            ['异形模切', 'custom_die_cut'],
        ].map(([name, code]) => ({
            name,
            code,
            markup: 1,
            rates: specialRates,
        })),
    ],
};

function tiersFor(selectedOptions) {
    return computeDynamicTiers(
        {
            rules: [
                {
                    id: 'basic-cotton-business-card-default',
                    match: [],
                    pricing: scenario,
                },
            ],
        },
        0,
        0,
        0,
        0,
        selectedOptions,
    );
}

test('start quantity and recommended quantity are independent', () => {
    const tiers = computeDynamicTiers(
        {
            rules: [
                {
                    id: 'independent-quantity-contract',
                    match: {},
                    pricing: {
                        packageName: 'Business cards',
                        basePrice: 1,
                        startQuantity: 50,
                        recommendedQuantity: 200,
                        paperRates: {
                            100: 0,
                            200: 10,
                        },
                        processes: [],
                    },
                },
            ],
        },
        0,
        0,
        0,
        0,
    );

    assert.deepEqual(
        tiers.map((tier) => tier.qty),
        [50, 100, 200],
    );
    assert.equal(tiers.find((tier) => tier.qty === 50)?.recommended, false);
    assert.equal(tiers.find((tier) => tier.qty === 200)?.recommended, true);
});

test('cotton pricing applies every selected process and the start-tier discounts', () => {
    const tiers = tiersFor({
        sizes: 'standard',
        corners: 'rounded',
        special_finish: [
            'laser',
            'edge_coloring',
            'double_mounting',
            'custom_die_cut',
        ],
    });

    assert.deepEqual(
        tiers.map((tier) => tier.qty),
        [200, 500, 1000, 2000, 3000, 4000, 5000, 10000],
    );
    assert.deepEqual(
        tiers.map((tier) => tier.currentPrice),
        [327, 715, 1168, 2272, 3306, 4284, 5200, 10400],
    );
    assert.ok(Math.abs(tiers[0].pricePerCard - 1.63502) < 1e-10);
});

test('cotton pricing does not add unselected special finishes', () => {
    const tiers = tiersFor({
        sizes: 'standard',
        corners: 'square',
        special_finish: ['laser'],
    });

    assert.equal(tiers[0].currentPrice, 170);
    assert.ok(Math.abs(tiers[0].pricePerCard - 0.85002) < 1e-10);
});

const foilRuleScenario = {
    packageName: 'Foil test',
    basePrice: 0.1,
    startQuantity: 100,
    paperRates: { 100: 0 },
    processes: [
        { code: 'hot_foil', name: 'Hot Foil', markup: 0.2, rates: {} },
        { code: 'cold_foil', name: 'Cold Foil', markup: 0.3, rates: {} },
    ],
};

function foilTiersFor(selectedOptions, sides) {
    return computeDynamicTiers(
        {
            rules: [{ id: 'foil', match: [], pricing: foilRuleScenario }],
        },
        0,
        0,
        0,
        0,
        selectedOptions,
        sides,
    );
}

test('hot and cold foil sides multiply only their own markup', () => {
    assert.equal(
        foilTiersFor(
            { special_finish: ['hot_foil'] },
            { hot_foil: 'one_side' },
        )[0].currentPrice,
        30,
    );
    assert.equal(
        foilTiersFor(
            { special_finish: ['hot_foil'] },
            { hot_foil: 'both_sides' },
        )[0].currentPrice,
        50,
    );
    assert.equal(
        foilTiersFor(
            { special_finish: ['cold_foil'] },
            { cold_foil: 'one_side' },
        )[0].currentPrice,
        40,
    );
    assert.equal(
        foilTiersFor(
            { special_finish: ['cold_foil'] },
            { cold_foil: 'both_sides' },
        )[0].currentPrice,
        70,
    );
    assert.equal(
        foilTiersFor(
            { special_finish: ['hot_foil', 'cold_foil'] },
            {
                hot_foil: 'both_sides',
                cold_foil: 'one_side',
            },
        )[0].currentPrice,
        80,
    );
});

test('hot foil colors can be selected from the independent hot_foil group', () => {
    assert.equal(
        foilTiersFor(
            { hot_foil: ['black_gold'] },
            { black_gold: 'one_side' },
        )[0].currentPrice,
        30,
    );
    assert.equal(
        foilTiersFor(
            { hot_foil: ['black_gold'] },
            { black_gold: 'both_sides' },
        )[0].currentPrice,
        50,
    );
});

const multiFoilRuleScenario = {
    packageName: 'Multiple foil test',
    basePrice: 0.1,
    startQuantity: 100,
    paperRates: { 100: 0 },
    processes: [{ code: 'foil', name: 'Foil', markup: 0.2, rates: {} }],
};

function multiFoilTiersFor(selectedOptions, sides) {
    return computeDynamicTiers(
        {
            rules: [{ id: 'foil', match: [], pricing: multiFoilRuleScenario }],
        },
        0,
        0,
        0,
        0,
        selectedOptions,
        sides,
    );
}

test('multiple selected hot and cold foils scale the markup by selection count', () => {
    assert.equal(
        multiFoilTiersFor(
            { special_finish: ['black_gold', 'cold_red_gold'] },
            {
                black_gold: 'one_side',
                cold_red_gold: 'one_side',
            },
        )[0].currentPrice,
        50,
    );
    assert.equal(
        multiFoilTiersFor(
            { special_finish: ['black_gold', 'cold_red_gold', 'bright_gold'] },
            {
                black_gold: 'one_side',
                cold_red_gold: 'one_side',
                bright_gold: 'one_side',
            },
        )[0].currentPrice,
        70,
    );
    assert.equal(
        multiFoilTiersFor(
            { special_finish: ['black_gold', 'cold_red_gold'] },
            {
                black_gold: 'both_sides',
                cold_red_gold: 'one_side',
            },
        )[0].currentPrice,
        70,
    );
});
