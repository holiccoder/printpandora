import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const samplePackPages = [
    '../../resources/js/pages/free-sample-pack.tsx',
    '../../resources/js/pages/business-card-sample-pack.tsx',
    '../../resources/js/pages/sample-packs.tsx',
];

test('free sample pack button posts to the special product cart endpoint', () => {
    const buttonSource = readFileSync(
        new URL(
            '../../resources/js/components/free-sample-pack-cart-button.tsx',
            import.meta.url,
        ),
        'utf8',
    );

    assert.match(
        buttonSource,
        /router\.post\(\s*['"]\/cart\/add\/free-sample-pack['"]/,
        'the shared button must post to the free sample pack cart endpoint',
    );

    for (const page of samplePackPages) {
        const source = readFileSync(new URL(page, import.meta.url), 'utf8');

        assert.match(
            source,
            /FreeSamplePackCartButton/,
            `${page} must use the free sample pack cart button`,
        );
    }
});
