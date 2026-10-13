import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { computeDynamicTiers } from '../../resources/js/lib/pricing.ts';

const root = fileURLToPath(new URL('../../', import.meta.url));
const workbook = JSON.parse(readFileSync(new URL('../Fixtures/postcard-workbook.json', import.meta.url), 'utf8'));
const catalog = JSON.parse(execFileSync('php', ['-r', `
require 'vendor/autoload.php';
$products = [];
foreach (App\\Support\\PostcardProductCatalog::slugs() as $slug) {
    $products[$slug] = App\\Support\\PostcardProductCatalog::pricingFor($slug);
}
echo json_encode(['sizes' => App\\Support\\PostcardProductCatalog::sizeOptions()['values'], 'products' => $products]);
`], { cwd: root, encoding: 'utf8' }));

function unitPrice(prices, area) {
    return Object.entries(prices).find(([upperArea]) => area <= Number(upperArea))?.[1]
        ?? Object.values(prices).at(-1);
}

function tiers(slug, area, options = {}) {
    return computeDynamicTiers({ rules: catalog.products[slug].rules }, 0, 0, 0, 0, {
        sizes: 'standard', corners: 'square', paper_area: String(area), ...options,
    });
}

test('all postcard sizes and quantity tiers match Sheet1', () => {
    for (const [slug, paper] of Object.entries(workbook.papers)) {
        for (const size of catalog.sizes.slice(0, 7)) {
            const area = Number((Number(size.width) * Number(size.height) * 0.0254 ** 2).toFixed(8));
            const result = tiers(slug, area, { sizes: size.code });
            assert.deepEqual(result.map((tier) => tier.qty), Object.keys(paper.quantity_multipliers).map(Number));
            assert.equal(result.find((tier) => tier.recommended)?.qty, 200);
            for (const tier of result) {
                assert.equal(tier.currentPrice, Math.round(tier.qty * area * unitPrice(paper.area_unit_prices, area) * paper.quantity_multipliers[tier.qty]), `${slug} / ${size.code} / ${tier.qty}`);
            }
        }
    }
});

test('postcard finishes match Sheet2 for each side and selected foil color', () => {
    const slug = 'classic-standard-postcards';
    const cases = [
        [{ corners: 'rounded' }, { uv: 1 }],
        [{ uv_finish: 'single_side_uv' }, { uv: 1 }],
        [{ uv_finish: 'both_sides_uv' }, { uv: 2 }],
        [{ hot_foil: ['bright_gold'] }, { foil: 1 }],
        [{ hot_foil: ['bright_gold'], 'hot_foil_sides[bright_gold]': 'both_sides' }, { foil: 2 }],
        [{ special_finish: ['cold_bright_gold', 'cold_bright_silver'] }, { foil: 2 }],
        [{ special_finish: ['3d_uv'] }, { foil: 1 }],
        [{ special_finish: ['custom_die_cut'] }, { die_cut: 1 }],
        [{ corners: 'rounded', uv_finish: 'both_sides_uv', hot_foil: ['bright_gold'] }, { uv: 3, foil: 1 }],
    ];
    for (const size of catalog.sizes.slice(0, 7)) {
        const area = size.area_sq_m;
        for (const [options, processes] of cases) {
            for (const tier of tiers(slug, area, options)) {
                const paper = workbook.papers[slug];
                let unit = unitPrice(paper.area_unit_prices, area) * paper.quantity_multipliers[tier.qty];
                for (const [code, count] of Object.entries(processes)) {
                    const finish = workbook.finishes[code];
                    unit += unitPrice(finish.area_unit_prices, area) * finish.quantity_multipliers[tier.qty] * count;
                }
                assert.equal(tier.currentPrice, Math.round(tier.qty * area * unit), `${size.code} / ${tier.qty} / ${JSON.stringify(options)}`);
            }
        }
    }
});

test('custom postcard area bands include their upper bound and continue the last rate', () => {
    const slug = 'super-standard-postcards';
    const paper = workbook.papers[slug];
    for (const area of [0.000324, 0.005, 0.00500001, 0.0155, 0.01550001, 0.0365, 0.04, 0.12943]) {
        for (const tier of tiers(slug, area, { sizes: 'custom' })) {
            assert.equal(tier.currentPrice, Math.round(tier.qty * area * unitPrice(paper.area_unit_prices, area) * paper.quantity_multipliers[tier.qty]));
        }
    }
    for (const area of [0, -1, NaN, Infinity]) {
        assert.ok(tiers(slug, area).every((tier) => tier.currentPrice === 0));
    }
});
