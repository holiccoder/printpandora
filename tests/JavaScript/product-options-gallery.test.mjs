import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
    findMatchingGallery,
    getPreferredGalleryMatchKey,
} from '../../resources/js/lib/product-options.ts';

const pvcProducts = [
    {
        file: 'basic-pvc-card.json',
        otherSelection: { print_code: 'no_print_code' },
    },
    {
        file: 'standard-pvc-card.json',
        otherSelection: {
            print_code_or_signature_stripe: 'no_print_code_or_signature_stripe',
        },
    },
    {
        file: 'premium-pvc-card.json',
        otherSelection: { print_code: 'no_print_code' },
    },
];

test('PVC paper finish galleries take precedence over other matching galleries', () => {
    for (const product of pvcProducts) {
        const contents = readFileSync(
            new URL(
                `../../content/product-options/pvc-business-cards/${product.file}`,
                import.meta.url,
            ),
            'utf8',
        );
        const config = JSON.parse(contents);

        for (const finish of ['gloss', 'frosted']) {
            const gallery = findMatchingGallery(
                config.galleries,
                { ...product.otherSelection, paper_finish: finish },
                'paper_finish',
            );

            assert.equal(gallery?.id, `${finish}_gallery`);
            assert.equal(
                gallery?.images[0],
                config.galleries.find(
                    (candidate) => candidate.id === `${finish}_gallery`,
                )?.images[0],
            );
        }
    }
});

test('PVC print-code selections prefer their own galleries after the swatch is clicked', () => {
    const scenarios = [
        {
            file: 'basic-pvc-card.json',
            optionKey: 'print_code',
            optionValue: 'print_code',
            galleryId: 'print_code_gallery',
        },
        {
            file: 'standard-pvc-card.json',
            optionKey: 'print_code_or_signature_stripe',
            optionValue: 'print_code',
            galleryId: 'print_code_gallery',
        },
        {
            file: 'standard-pvc-card.json',
            optionKey: 'print_code_or_signature_stripe',
            optionValue: 'signature_stripe',
            galleryId: 'signature_stripe_gallery',
        },
        {
            file: 'premium-pvc-card.json',
            optionKey: 'print_code',
            optionValue: 'print_code',
            galleryId: 'print_code_gallery',
        },
    ];

    for (const scenario of scenarios) {
        const config = JSON.parse(
            readFileSync(
                new URL(
                    `../../content/product-options/pvc-business-cards/${scenario.file}`,
                    import.meta.url,
                ),
                'utf8',
            ),
        );
        const preferredKey = getPreferredGalleryMatchKey(
            config.galleries,
            true,
            scenario.optionKey,
        );
        const gallery = findMatchingGallery(
            config.galleries,
            {
                paper_finish: 'gloss',
                [scenario.optionKey]: scenario.optionValue,
            },
            preferredKey,
        );

        assert.equal(preferredKey, scenario.optionKey);
        assert.equal(gallery?.id, scenario.galleryId);
    }
});
