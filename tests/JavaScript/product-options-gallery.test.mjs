import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
    findMatchingGallery,
    getProductThumbnailImages,
    getPreferredGalleryMatchKey,
} from '../../resources/js/lib/product-options.ts';

const pvcProducts = [
    {
        file: 'basic-pvc-card.json',
        otherSelection: { print_code: 'no_print_code' },
        finishImages: {
            matte: '/images/products/pvc/basic-pvc-card-matte.png',
            gloss: '/images/products/pvc/basic-pvc-card-gloss.png',
            frosted: '/images/products/pvc/basic-pvc-card-frosted.png',
        },
    },
    {
        file: 'standard-pvc-card.json',
        otherSelection: {
            print_code_or_signature_stripe: 'no_print_code_or_signature_stripe',
        },
        finishImages: {
            matte: '/images/products/pvc/standard-pvc-card-matte.png',
            gloss: '/images/products/pvc/standard-pvc-card-gloss.png',
            frosted: '/images/products/pvc/standard-pvc-card-frosted.png',
        },
    },
    {
        file: 'premium-pvc-card.json',
        otherSelection: { print_code: 'no_print_code' },
        finishImages: {
            matte: '/images/products/pvc/premium-pvc-card-matte.png',
            gloss: '/images/products/pvc/premium-pvc-card-gloss.png',
            frosted: '/images/products/pvc/premium-pvc-card-frosted.png',
        },
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
            assert.equal(gallery?.images[0], product.finishImages[finish]);
        }
    }
});

test('selected PVC finish changes the primary without appending thumbnails', () => {
    for (const product of pvcProducts) {
        const config = JSON.parse(
            readFileSync(
                new URL(
                    `../../content/product-options/pvc-business-cards/${product.file}`,
                    import.meta.url,
                ),
                'utf8',
            ),
        );
        const defaultGallery = config.galleries.find(
            (candidate) => candidate.id === 'default',
        );
        const thumbnails = getProductThumbnailImages(defaultGallery);

        assert.deepEqual(thumbnails, defaultGallery.images.slice(0, 4));
        assert.equal(thumbnails.length, 4);
        assert.deepEqual(defaultGallery.images.slice(1, 4), [
            product.finishImages.matte,
            product.finishImages.gloss,
            product.finishImages.frosted,
        ]);

        for (const finish of ['gloss', 'frosted']) {
            const finishGallery = config.galleries.find(
                (candidate) => candidate.id === `${finish}_gallery`,
            );

            assert.equal(finishGallery.images[0], product.finishImages[finish]);
            assert.deepEqual(
                getProductThumbnailImages(defaultGallery),
                thumbnails,
            );
            assert.equal(thumbnails.includes(finishGallery.images[0]), true);
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
