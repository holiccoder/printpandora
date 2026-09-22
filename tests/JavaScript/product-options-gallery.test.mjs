import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
    applyTextureUvSelection,
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

test('classic standard galleries map every size, finish, corner, and UV selection', () => {
    const config = JSON.parse(
        readFileSync(
            new URL(
                '../../content/product-options/business-cards/classic-standard-business-cards.json',
                import.meta.url,
            ),
            'utf8',
        ),
    );
    const defaultGallery = config.galleries.find(
        (candidate) => candidate.id === 'default',
    );

    assert.deepEqual(defaultGallery.images, [
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-01.png',
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-02.png',
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-03.png',
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-04.png',
    ]);

    const selections = [
        [
            'standard-matte-square',
            {
                sizes: 'standard',
                paper_finish: 'matte',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-matte-square',
            {
                sizes: 'square',
                paper_finish: 'matte',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'standard-matte-rounded',
            {
                sizes: 'standard',
                paper_finish: 'matte',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-matte-rounded',
            {
                sizes: 'square',
                paper_finish: 'matte',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'standard-gloss-square',
            {
                sizes: 'standard',
                paper_finish: 'gloss',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-gloss-square',
            {
                sizes: 'square',
                paper_finish: 'gloss',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'standard-gloss-rounded',
            {
                sizes: 'standard',
                paper_finish: 'gloss',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-gloss-rounded',
            {
                sizes: 'square',
                paper_finish: 'gloss',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'standard-uv-square-single-side',
            {
                sizes: 'standard',
                uv_finish: 'single_side_uv',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'standard-uv-square-both-sides',
            {
                sizes: 'standard',
                uv_finish: 'both_sides_uv',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'standard-uv-rounded-single-side',
            {
                sizes: 'standard',
                uv_finish: 'single_side_uv',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'standard-uv-rounded-both-sides',
            {
                sizes: 'standard',
                uv_finish: 'both_sides_uv',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-uv-square-single-side',
            {
                sizes: 'square',
                uv_finish: 'single_side_uv',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-uv-square-both-sides',
            {
                sizes: 'square',
                uv_finish: 'both_sides_uv',
                corners: 'square',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-uv-rounded-single-side',
            {
                sizes: 'square',
                uv_finish: 'single_side_uv',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
        [
            'square-uv-rounded-both-sides',
            {
                sizes: 'square',
                uv_finish: 'both_sides_uv',
                corners: 'rounded',
                special_finish: 'no_special_finish',
            },
        ],
    ];

    for (const [id, selected] of selections) {
        const gallery = findMatchingGallery(config.galleries, selected);

        assert.equal(gallery?.id, id);
        assert.equal(gallery?.images.length, 1);
    }
});

test('classic special pin-point embossed paper galleries follow the selected corner', () => {
    const config = JSON.parse(
        readFileSync(
            new URL(
                '../../content/product-options/business-cards/classic-special-business-cards.json',
                import.meta.url,
            ),
            'utf8',
        ),
    );

    const squareGallery = findMatchingGallery(config.galleries, {
        sizes: 'standard',
        texture: 'pin_point_embossed_paper',
        corners: 'square',
    });
    const roundedGallery = findMatchingGallery(config.galleries, {
        sizes: 'square',
        texture: 'pin_point_embossed_paper',
        corners: 'rounded',
    });

    assert.equal(squareGallery?.id, 'texture-pin-point-embossed-paper-square');
    assert.deepEqual(squareGallery?.images, [
        '/images/products/classic-special-business-cards/texture/pin-point-embossed-paper-square.png',
    ]);
    assert.equal(
        roundedGallery?.id,
        'texture-pin-point-embossed-paper-rounded',
    );
    assert.deepEqual(roundedGallery?.images, [
        '/images/products/classic-special-business-cards/texture/pin-point-embossed-paper-rounded.png',
    ]);
});

test('classic special art-paper textures map every size and corner image', () => {
    const config = JSON.parse(
        readFileSync(
            new URL(
                '../../content/product-options/business-cards/classic-special-business-cards.json',
                import.meta.url,
            ),
            'utf8',
        ),
    );
    const textures = [
        {
            code: 'water_ripple_paper',
            slug: 'water-ripple-paper',
            images: {
                standardSquare:
                    '/images/products/classic-special-business-cards/texture/water-ripple-paper.png',
                standardRounded:
                    '/images/products/classic-special-business-cards/texture/water-ripple-paper-rounded.png',
                squareSquare:
                    '/images/products/classic-special-business-cards/texture/water-ripple-paper-square-size.png',
                squareRounded:
                    '/images/products/classic-special-business-cards/texture/water-ripple-paper-square-size-rounded.png',
            },
        },
        {
            code: 'linen_paper',
            slug: 'linen-paper',
            images: {
                standardSquare:
                    '/images/products/classic-special-business-cards/texture/linen-paper.png',
                standardRounded:
                    '/images/products/classic-special-business-cards/texture/linen-paper-rounded.png',
                squareSquare:
                    '/images/products/classic-special-business-cards/texture/linen-paper-square-size.png',
                squareRounded:
                    '/images/products/classic-special-business-cards/texture/linen-paper-square-size-rounded.png',
            },
        },
        {
            code: 'eggshell_paper',
            slug: 'eggshell-paper',
            images: {
                standardSquare:
                    '/images/products/classic-special-business-cards/texture/eggshell-paper.png',
                standardRounded:
                    '/images/products/classic-special-business-cards/texture/eggshell-paper-rounded.png',
                squareSquare:
                    '/images/products/classic-special-business-cards/texture/eggshell-paper-square-size.png',
                squareRounded:
                    '/images/products/classic-special-business-cards/texture/eggshell-paper-square-size-rounded.png',
            },
        },
        {
            code: 'white_cardstock',
            slug: 'white-cardstock',
            images: {
                standardSquare:
                    '/images/products/classic-special-business-cards/texture/white-cardstock.png',
                standardRounded:
                    '/images/products/classic-special-business-cards/texture/white-cardstock-rounded.png',
                squareSquare:
                    '/images/products/classic-special-business-cards/texture/white-cardstock-square-size.png',
                squareRounded:
                    '/images/products/classic-special-business-cards/texture/white-cardstock-square-size-rounded.png',
            },
        },
        {
            code: 'pearlized_paper',
            slug: 'pearlized-paper',
            images: {
                standardSquare:
                    '/images/products/classic-special-business-cards/texture/pearlized-paper.png',
                standardRounded:
                    '/images/products/classic-special-business-cards/texture/pearlized-paper-rounded.png',
                squareSquare:
                    '/images/products/classic-special-business-cards/texture/pearlized-paper-square-size.png',
                squareRounded:
                    '/images/products/classic-special-business-cards/texture/pearlized-paper-square-size-rounded.png',
            },
        },
    ];
    const selections = [
        ['standard-square', 'standard', 'square', 'standardSquare'],
        ['standard-rounded', 'standard', 'rounded', 'standardRounded'],
        ['square-size-square', 'square', 'square', 'squareSquare'],
        ['square-size-rounded', 'square', 'rounded', 'squareRounded'],
    ];

    for (const texture of textures) {
        for (const [suffix, sizes, corners, imageKey] of selections) {
            const gallery = findMatchingGallery(config.galleries, {
                texture: texture.code,
                sizes,
                corners,
            });

            assert.equal(gallery?.id, `texture-${texture.slug}-${suffix}`);
            assert.deepEqual(gallery?.images, [texture.images[imageKey]]);
        }
    }
});

test('standard-quality galleries use the new default and finish artwork', () => {
    const config = JSON.parse(
        readFileSync(
            new URL(
                '../../content/product-options/business-cards/standard-quality-business-cards.json',
                import.meta.url,
            ),
            'utf8',
        ),
    );
    const defaultGallery = config.galleries.find(
        (candidate) => candidate.id === 'default',
    );

    assert.deepEqual(defaultGallery.images, [
        '/images/products/standard-quality-business-cards/default-01.png',
        '/images/products/standard-quality-business-cards/default-02.png',
        '/images/products/standard-quality-business-cards/default-03.png',
        '/images/products/standard-quality-business-cards/default-04.png',
    ]);
    assert.deepEqual(getProductThumbnailImages(defaultGallery), defaultGallery.images);

    const selections = [
        [
            'standard-matte-square',
            { sizes: 'standard', corners: 'square', texture: 'matte', special_finish: 'no_special_finish' },
        ],
        [
            'standard-matte-rounded',
            { sizes: 'standard', corners: 'rounded', texture: 'matte', special_finish: 'no_special_finish' },
        ],
        [
            'standard-gloss-rounded',
            { sizes: 'standard', corners: 'rounded', texture: 'gloss', special_finish: 'no_special_finish' },
        ],
        [
            'standard-gloss-square',
            { sizes: 'standard', corners: 'square', texture: 'gloss', special_finish: 'no_special_finish' },
        ],
        [
            '3d-uv-single-side',
            { sizes: 'standard', corners: 'square', texture: '', uv_finish: 'single_side_uv', special_finish: 'no_special_finish' },
        ],
        [
            '3d-uv-both-sides',
            { sizes: 'standard', corners: 'rounded', texture: '', uv_finish: 'both_sides_uv', special_finish: 'no_special_finish' },
        ],
        [
            'texture-starlight-film',
            { sizes: 'standard', corners: 'square', texture: 'starlight_film', uv_finish: '', special_finish: 'no_special_finish' },
        ],
        [
            'texture-holographic-film',
            { sizes: 'standard', corners: 'square', texture: 'holographic_film', uv_finish: '', special_finish: 'no_special_finish' },
        ],
        [
            'texture-soft-touch-film',
            { sizes: 'standard', corners: 'rounded', texture: 'soft_touch_film', uv_finish: '', special_finish: 'no_special_finish' },
        ],
    ];

    for (const [id, selected] of selections) {
        const gallery = findMatchingGallery(config.galleries, selected);

        assert.equal(gallery?.id, id);
        assert.equal(gallery?.images.length, 1);
    }

    const textureWins = findMatchingGallery(config.galleries, {
        sizes: 'standard',
        corners: 'square',
        texture: 'starlight_film',
        uv_finish: 'single_side_uv',
        special_finish: 'no_special_finish',
    });

    assert.equal(textureWins?.id, 'texture-starlight-film');
});

test('standard-quality Texture and 3D UV selections clear only matte and gloss', () => {
    const initial = {
        texture: 'matte',
        uv_finish: '',
    };

    assert.deepEqual(applyTextureUvSelection(initial, 'uv_finish', 'single_side_uv'), {
        texture: '',
        uv_finish: 'single_side_uv',
    });
    assert.deepEqual(
        applyTextureUvSelection(
            { texture: 'starlight_film', uv_finish: '' },
            'uv_finish',
            'both_sides_uv',
        ),
        { texture: 'starlight_film', uv_finish: 'both_sides_uv' },
    );
    assert.deepEqual(
        applyTextureUvSelection(
            { texture: 'starlight_film', uv_finish: 'single_side_uv' },
            'texture',
            'gloss',
        ),
        { texture: 'gloss', uv_finish: '' },
    );
    assert.deepEqual(
        applyTextureUvSelection(
            { texture: 'starlight_film', uv_finish: 'single_side_uv' },
            'texture',
            'holographic_film',
        ),
        { texture: 'holographic_film', uv_finish: 'single_side_uv' },
    );
});

test('solid-quality galleries use the supplied 640g artwork', () => {
    const config = JSON.parse(
        readFileSync(
            new URL(
                '../../content/product-options/business-cards/solid-quality-business-cards.json',
                import.meta.url,
            ),
            'utf8',
        ),
    );
    const defaultGallery = config.galleries.find(
        (candidate) => candidate.id === 'default',
    );

    assert.deepEqual(defaultGallery.images, [
        '/images/products/solid-quality-business-cards/default-01.png',
        '/images/products/solid-quality-business-cards/default-02.png',
        '/images/products/solid-quality-business-cards/default-03.png',
        '/images/products/solid-quality-business-cards/default-04.png',
    ]);

    const selections = [
        [
            'standard-matte-square',
            { sizes: 'standard', corners: 'square', paper_finish: 'matte', special_finish: 'no_special_finish' },
        ],
        [
            'standard-matte-rounded',
            { sizes: 'standard', corners: 'rounded', paper_finish: 'matte', special_finish: 'no_special_finish' },
        ],
        [
            'standard-gloss-rounded',
            { sizes: 'standard', corners: 'rounded', paper_finish: 'gloss', special_finish: 'no_special_finish' },
        ],
        [
            'standard-starlight-film',
            { sizes: 'standard', corners: 'square', paper_finish: 'starry_film', special_finish: 'no_special_finish' },
        ],
        [
            'standard-laser-film',
            { sizes: 'standard', corners: 'square', paper_finish: 'holo_film', special_finish: 'no_special_finish' },
        ],
        [
            'standard-soft-touch-film',
            { sizes: 'standard', corners: 'square', paper_finish: 'soft_touch_film', special_finish: 'no_special_finish' },
        ],
        [
            '3d-uv-single-side',
            { uv_finish: 'single_side_uv', special_finish: 'no_special_finish' },
        ],
        [
            '3d-uv-both-sides',
            { uv_finish: 'both_sides_uv', special_finish: 'no_special_finish' },
        ],
    ];

    for (const [id, selected] of selections) {
        const gallery = findMatchingGallery(config.galleries, selected);

        assert.equal(gallery?.id, id);
        assert.equal(gallery?.images.length, 1);
    }

    const specialFilmAssets = {
        'standard-starlight-film': {
            images: '/images/products/solid-quality-business-cards/texture/starlight-film.png',
            primary: '/images/products/solid-quality-business-cards/texture/starlight-film-primary.png',
        },
        'standard-laser-film': {
            images: '/images/products/solid-quality-business-cards/texture/laser-film.png',
            primary: '/images/products/solid-quality-business-cards/texture/laser-film-primary.png',
        },
        'standard-soft-touch-film': {
            images: '/images/products/solid-quality-business-cards/texture/soft-touch-film.png',
            primary: '/images/products/solid-quality-business-cards/texture/soft-touch-film-primary.png',
        },
    };

    for (const [id, assets] of Object.entries(specialFilmAssets)) {
        const gallery = config.galleries.find((candidate) => candidate.id === id);

        assert.deepEqual(gallery?.images, [assets.images]);
        assert.equal(gallery?.primary, assets.primary);
    }

    assert.equal(
        findMatchingGallery(config.galleries, {
            special_finish: 'cold_bright_gold',
        })?.images[0],
        '/images/products/solid-quality-business-cards/cold-foil/cold-bright-gold.png',
    );
});
