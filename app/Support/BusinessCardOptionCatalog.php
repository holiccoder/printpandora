<?php

namespace App\Support;

final class BusinessCardOptionCatalog
{
    public const STANDARD_SIZE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/standard-size.webp';

    public const SQUARE_SIZE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/square-size.webp';

    public const CUSTOM_SIZE_DESCRIPTION = 'max range: 2.1 - 3.5 inches';

    public const COTTON_CUSTOM_SIZE_DESCRIPTION = 'Width 0.70-3.54 in; height 0.70-2.13 in.';

    public const CLASSIC_STANDARD_STANDARD_SIZE_DESCRIPTION = '2.0 x 3.5 inches';

    public const CLASSIC_STANDARD_SQUARE_SIZE_DESCRIPTION = '2.5 x 2.5 inches';

    public const CLASSIC_STANDARD_CUSTOM_SIZE_DESCRIPTION = '2.1 - 3.5 inches';

    public const NO_PRINT_CODE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/pvc-no-print-code.png';

    public const PRINT_CODE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/pvc-print-code.png';

    public const PVC_PRINT_CODE_SWATCH_IMAGE = '/images/products/pvc/pvc-print-code.png';

    public const NO_DRILLING_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/drilling/no-drilling.png';

    public const NEEDS_DRILLING_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/drilling/needs-drilling.png';

    public const SIGNATURE_STRIPE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/pvc-signature-stripe.png';

    public const PVC_SIGNATURE_STRIPE_SWATCH_IMAGE = '/images/products/pvc/pvc-signature-stripe.png';

    /**
     * @var array<string, array<string, string>>
     */
    private const PVC_FINISH_IMAGES = [
        'basic-pvc-card' => [
            'matte' => '/images/products/pvc/basic-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/basic-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/basic-pvc-card-frosted.png',
        ],
        'standard-pvc-card' => [
            'matte' => '/images/products/pvc/standard-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/standard-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/standard-pvc-card-frosted.png',
        ],
        'premium-pvc-card' => [
            'matte' => '/images/products/pvc/premium-pvc-card-matte.png',
            'gloss' => '/images/products/pvc/premium-pvc-card-gloss.png',
            'frosted' => '/images/products/pvc/premium-pvc-card-frosted.png',
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const QUALITY_TEXTURE_SWATCH_IMAGES = [
        'matte' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
        'gloss' => '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
        'starlight_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
        'holographic_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
        'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
    ];

    /**
     * Keep the generic legacy fallback stable for products whose own texture
     * contract is not the standard-quality business-card contract.
     *
     * @var array<string, string>
     */
    private const SHARED_TEXTURE_SWATCH_IMAGES = [
        'matte' => '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
        'gloss' => '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
        'starlight_film' => '/images/products/standard-quality-business-cards/texture/starlight-film.png',
        'holographic_film' => '/images/products/standard-quality-business-cards/texture/holographic-film.png',
        'soft_touch_film' => '/images/products/standard-quality-business-cards/texture/soft-touch-film.png',
    ];

    private const QUALITY_3D_UV_SWATCH_IMAGE = '/images/products/standard-quality-business-cards/3d-uv.png';

    /**
     * @var array<string, string>
     */
    private const QUALITY_COLD_FOIL_SWATCH_IMAGES = [
        'cold_red_gold' => '/images/product-options/business-cards/swatches/cold/red-gold.png',
        'cold_blue_gold' => '/images/product-options/business-cards/swatches/cold/blue-gold.png',
        'cold_bright_gold' => '/images/product-options/business-cards/swatches/cold/bright-gold.png',
        'cold_bright_silver' => '/images/product-options/business-cards/swatches/cold/bright-silver.png',
        'cold_green_gold' => '/images/product-options/business-cards/swatches/cold/green-gold.png',
        'cold_matte_gold' => '/images/product-options/business-cards/swatches/cold/matte-gold.png',
        'cold_matte_silver' => '/images/product-options/business-cards/swatches/cold/matte-silver.png',
    ];

    /**
     * Cotton special-finish semantic diagrams are shared by all five
     * cotton-card products and are used only as option swatches.
     *
     * @var array<string, string>
     */
    private const COTTON_SPECIAL_FINISH_SWATCH_IMAGES = [
        'laser' => '/images/products/cotton/special-finishes/laser-diagram.png',
        'edge_coloring' => '/images/products/cotton/special-finishes/edge-coloring-diagram.png',
        'double_mounting' => '/images/products/cotton/special-finishes/double-mounting-diagram.png',
        'custom_die_cut' => '/images/products/cotton/special-finishes/custom-die-cut-diagram.png',
        'emboss' => '/images/products/cotton/special-finishes/emboss-deboss-diagram.png',
    ];

    /**
     * Supplied primary artwork for cotton special finishes. Custom die-cut
     * intentionally has no gallery override until a primary image is supplied.
     *
     * @var array<string, string>
     */
    private const COTTON_SPECIAL_FINISH_PRIMARY_IMAGES = [
        'laser' => '/images/products/cotton/special-finishes/laser.png',
        'edge_coloring' => '/images/products/cotton/special-finishes/edge-coloring.png',
        'double_mounting' => '/images/products/cotton/special-finishes/double-mounting.png',
        'emboss' => '/images/products/cotton/special-finishes/emboss.png',
    ];

    /**
     * Cotton thicknesses are shared by all five cotton-card products.
     *
     * @var list<array{code: string, label: string}>
     */
    private const COTTON_THICKNESSES = [
        ['code' => '300_360g', 'label' => '300-360g'],
        ['code' => '360_450g', 'label' => '360-450g'],
        ['code' => '450_700g', 'label' => '450-700g'],
    ];

    /**
     * English storefront names for the supplied cotton-paper brands.
     *
     * @var array<string, string>
     */
    private const COTTON_TEXTURE_LABELS = [
        '维尔德(300GSM)' => 'Wilde (300 GSM)',
        '蒙特利卡(360GSM)' => 'Montericca (360 GSM)',
        '卡昆滑面(300GSM)' => 'Kakun Smooth (300 GSM)',
        '冠雅(352GSM)' => 'Guanya (352 GSM)',
        '臻皮(360GSM)' => 'Zhenpi (360 GSM)',
        '维尔德(450GSM)' => 'Wilde (450 GSM)',
        '凝柔(420GSM)' => 'Ningrou (420 GSM)',
        '卡昆滑面(450GSM)' => 'Kakun Smooth (450 GSM)',
        '冠雅(446GSM)' => 'Guanya (446 GSM)',
        '蒙特利卡(530GSM)' => 'Montericca (530 GSM)',
        '凝柔(523g)' => 'Ningrou (523 GSM)',
        '海蒂(550GSM)' => 'Haiti (550 GSM)',
        '艾维克(550GSM)' => 'Aweike (550 GSM)',
        '新冠雅(550GSM)' => 'New Guanya (550 GSM)',
        '素墨(700GSM)' => 'Sumei (700 GSM)',
        '爵士金属(500GSM)' => 'Jazz Metal (500 GSM)',
        '冰白珠光(500GSM)' => 'Ice White Pearl (500 GSM)',
        '松卡(600GSM)' => 'Songka (600 GSM)',
        '极墨(680GSM)' => 'Extreme Ink (680 GSM)',
        '丝感古典白(685GSM)' => 'Silk Classic White (685 GSM)',
    ];

    /**
     * English storefront names for cotton-paper colors and finishes.
     *
     * @var array<string, string>
     */
    private const COTTON_COLOR_LABELS = [
        '白色' => 'White',
        '米色' => 'Beige',
        '棉白' => 'Cotton White',
        '土灰' => 'Earth Gray',
        '深蓝' => 'Deep Blue',
        '青色' => 'Teal',
        '黑色' => 'Black',
        '深红' => 'Deep Red',
        '靛蓝' => 'Indigo',
        '犀牛纹' => 'Rhino Texture',
        '蜥蜴纹黑色' => 'Black Lizard Texture',
        '浅棕' => 'Light Brown',
        '深咖' => 'Dark Coffee',
        '奶黄' => 'Cream Yellow',
        '浅灰' => 'Light Gray',
        '巧克力' => 'Chocolate',
        '雪白' => 'Snow White',
        '超白' => 'Ultra White',
        '牛皮' => 'Kraft',
        '坚毅黑' => 'Strong Black',
        '大红' => 'Bright Red',
        '深灰' => 'Dark Gray',
        '牛仔色' => 'Denim',
        '拉丝金' => 'Brushed Gold',
        '拉丝银' => 'Brushed Silver',
        '淡黄色' => 'Pale Yellow',
    ];

    /**
     * Cotton paper sample artwork is shared by all five cotton-card
     * products. Texture values are deliberately one value per paper/color
     * combination so the selected value can map directly to a gallery rule.
     * The storefront groups these values back into paper cards and color
     * circles using the metadata below.
     *
     * @var list<array{
     *     code: string,
     *     thickness_code: string,
     *     texture_code: string,
     *     texture_label: string,
     *     color_code: string,
     *     color_label: string,
     *     image: string,
     *     color_swatch_image: string
     * }>
     */
    private const COTTON_TEXTURES = [
        [
            'code' => 'wild_300gsm_white',
            'thickness_code' => '300_360g',
            'texture_code' => 'wild_300gsm',
            'texture_label' => '维尔德(300GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/wild-300gsm-white.png',
        ],
        [
            'code' => 'montericca_360gsm_beige',
            'thickness_code' => '300_360g',
            'texture_code' => 'montericca_360gsm',
            'texture_label' => '蒙特利卡(360GSM)',
            'color_code' => 'beige',
            'color_label' => '米色',
            'image' => '/images/products/cotton/paper-samples/montericca-360gsm-beige.png',
        ],
        [
            'code' => 'montericca_360gsm_cotton_white',
            'thickness_code' => '300_360g',
            'texture_code' => 'montericca_360gsm',
            'texture_label' => '蒙特利卡(360GSM)',
            'color_code' => 'cotton_white',
            'color_label' => '棉白',
            'image' => '/images/products/cotton/paper-samples/montericca-360gsm-cotton-white.png',
        ],
        [
            'code' => 'montericca_360gsm_earth_gray',
            'thickness_code' => '300_360g',
            'texture_code' => 'montericca_360gsm',
            'texture_label' => '蒙特利卡(360GSM)',
            'color_code' => 'earth_gray',
            'color_label' => '土灰',
            'image' => '/images/products/cotton/paper-samples/montericca-360gsm-earth-gray.png',
        ],
        [
            'code' => 'montericca_360gsm_deep_blue',
            'thickness_code' => '300_360g',
            'texture_code' => 'montericca_360gsm',
            'texture_label' => '蒙特利卡(360GSM)',
            'color_code' => 'deep_blue',
            'color_label' => '深蓝',
            'image' => '/images/products/cotton/paper-samples/montericca-360gsm-deep-blue.png',
        ],
        [
            'code' => 'montericca_360gsm_teal_brown',
            'thickness_code' => '300_360g',
            'texture_code' => 'montericca_360gsm',
            'texture_label' => '蒙特利卡(360GSM)',
            'color_code' => 'teal_brown',
            'color_label' => '青色',
            'image' => '/images/products/cotton/paper-samples/montericca-360gsm-teal-brown.png',
        ],
        [
            'code' => 'montericca_360gsm_black',
            'thickness_code' => '300_360g',
            'texture_code' => 'montericca_360gsm',
            'texture_label' => '蒙特利卡(360GSM)',
            'color_code' => 'black',
            'color_label' => '黑色',
            'image' => '/images/products/cotton/paper-samples/montericca-360gsm-black.png',
        ],
        [
            'code' => 'kakun_smooth_300gsm_deep_red',
            'thickness_code' => '300_360g',
            'texture_code' => 'kakun_smooth_300gsm',
            'texture_label' => '卡昆滑面(300GSM)',
            'color_code' => 'deep_red',
            'color_label' => '深红',
            'image' => '/images/products/cotton/paper-samples/kakun-smooth-300gsm-deep-red.png',
        ],
        [
            'code' => 'kakun_smooth_300gsm_indigo',
            'thickness_code' => '300_360g',
            'texture_code' => 'kakun_smooth_300gsm',
            'texture_label' => '卡昆滑面(300GSM)',
            'color_code' => 'indigo',
            'color_label' => '靛蓝',
            'image' => '/images/products/cotton/paper-samples/kakun-smooth-300gsm-indigo.png',
        ],
        [
            'code' => 'kakun_smooth_300gsm_deep_blue',
            'thickness_code' => '300_360g',
            'texture_code' => 'kakun_smooth_300gsm',
            'texture_label' => '卡昆滑面(300GSM)',
            'color_code' => 'deep_blue',
            'color_label' => '深蓝',
            'image' => '/images/products/cotton/paper-samples/kakun-smooth-300gsm-deep-blue.png',
        ],
        [
            'code' => 'guanya_352gsm_black',
            'thickness_code' => '300_360g',
            'texture_code' => 'guanya_352gsm',
            'texture_label' => '冠雅(352GSM)',
            'color_code' => 'black',
            'color_label' => '黑色',
            'image' => '/images/products/cotton/paper-samples/guanya-352gsm-black.png',
        ],
        [
            'code' => 'zhenpi_360gsm_rhinoceros',
            'thickness_code' => '300_360g',
            'texture_code' => 'zhenpi_360gsm',
            'texture_label' => '臻皮(360GSM)',
            'color_code' => 'rhinoceros',
            'color_label' => '犀牛纹',
            'image' => '/images/products/cotton/paper-samples/zhenpi-360gsm-rhinoceros.png',
        ],
        [
            'code' => 'zhenpi_360gsm_lizard_black',
            'thickness_code' => '300_360g',
            'texture_code' => 'zhenpi_360gsm',
            'texture_label' => '臻皮(360GSM)',
            'color_code' => 'lizard_black',
            'color_label' => '蜥蜴纹黑色',
            'image' => '/images/products/cotton/paper-samples/zhenpi-360gsm-lizard-black.png',
        ],
        [
            'code' => 'wild_450gsm_white',
            'thickness_code' => '360_450g',
            'texture_code' => 'wild_450gsm',
            'texture_label' => '维尔德(450GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/wild-450gsm-white.png',
        ],
        [
            'code' => 'wild_450gsm_light_brown',
            'thickness_code' => '360_450g',
            'texture_code' => 'wild_450gsm',
            'texture_label' => '维尔德(450GSM)',
            'color_code' => 'light_brown',
            'color_label' => '浅棕',
            'image' => '/images/products/cotton/paper-samples/wild-450gsm-light-brown.png',
        ],
        [
            'code' => 'wild_450gsm_dark_coffee',
            'thickness_code' => '360_450g',
            'texture_code' => 'wild_450gsm',
            'texture_label' => '维尔德(450GSM)',
            'color_code' => 'dark_coffee',
            'color_label' => '深咖',
            'image' => '/images/products/cotton/paper-samples/wild-450gsm-dark-coffee.png',
        ],
        [
            'code' => 'wild_450gsm_black',
            'thickness_code' => '360_450g',
            'texture_code' => 'wild_450gsm',
            'texture_label' => '维尔德(450GSM)',
            'color_code' => 'black',
            'color_label' => '黑色',
            'image' => '/images/products/cotton/paper-samples/wild-450gsm-black.png',
        ],
        [
            'code' => 'ningrou_420gsm_cream_yellow',
            'thickness_code' => '360_450g',
            'texture_code' => 'ningrou_420gsm',
            'texture_label' => '凝柔(420GSM)',
            'color_code' => 'cream_yellow',
            'color_label' => '奶黄',
            'image' => '/images/products/cotton/paper-samples/ningrou-420gsm-cream-yellow.png',
        ],
        [
            'code' => 'ningrou_420gsm_light_gray',
            'thickness_code' => '360_450g',
            'texture_code' => 'ningrou_420gsm',
            'texture_label' => '凝柔(420GSM)',
            'color_code' => 'light_gray',
            'color_label' => '浅灰',
            'image' => '/images/products/cotton/paper-samples/ningrou-420gsm-light-gray.png',
        ],
        [
            'code' => 'ningrou_420gsm_white',
            'thickness_code' => '360_450g',
            'texture_code' => 'ningrou_420gsm',
            'texture_label' => '凝柔(420GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/ningrou-420gsm-white.png',
        ],
        [
            'code' => 'ningrou_420gsm_indigo',
            'thickness_code' => '360_450g',
            'texture_code' => 'ningrou_420gsm',
            'texture_label' => '凝柔(420GSM)',
            'color_code' => 'indigo',
            'color_label' => '靛蓝',
            'image' => '/images/products/cotton/paper-samples/ningrou-420gsm-indigo.png',
        ],
        [
            'code' => 'ningrou_420gsm_chocolate',
            'thickness_code' => '360_450g',
            'texture_code' => 'ningrou_420gsm',
            'texture_label' => '凝柔(420GSM)',
            'color_code' => 'chocolate',
            'color_label' => '巧克力',
            'image' => '/images/products/cotton/paper-samples/ningrou-420gsm-chocolate.png',
        ],
        [
            'code' => 'kakun_smooth_450gsm_black',
            'thickness_code' => '360_450g',
            'texture_code' => 'kakun_smooth_450gsm',
            'texture_label' => '卡昆滑面(450GSM)',
            'color_code' => 'black',
            'color_label' => '黑色',
            'image' => '/images/products/cotton/paper-samples/kakun-smooth-450gsm-black.png',
        ],
        [
            'code' => 'kakun_smooth_450gsm_snow_white',
            'thickness_code' => '360_450g',
            'texture_code' => 'kakun_smooth_450gsm',
            'texture_label' => '卡昆滑面(450GSM)',
            'color_code' => 'snow_white',
            'color_label' => '雪白',
            'image' => '/images/products/cotton/paper-samples/kakun-smooth-450gsm-snow-white.png',
        ],
        [
            'code' => 'guanya_446gsm_ultra_white',
            'thickness_code' => '360_450g',
            'texture_code' => 'guanya_446gsm',
            'texture_label' => '冠雅(446GSM)',
            'color_code' => 'ultra_white',
            'color_label' => '超白',
            'image' => '/images/products/cotton/paper-samples/guanya-446gsm-ultra-white.png',
        ],
        [
            'code' => 'guanya_446gsm_cream_yellow',
            'thickness_code' => '360_450g',
            'texture_code' => 'guanya_446gsm',
            'texture_label' => '冠雅(446GSM)',
            'color_code' => 'cream_yellow',
            'color_label' => '奶黄',
            'image' => '/images/products/cotton/paper-samples/guanya-446gsm-cream-yellow.png',
        ],
        [
            'code' => 'montericca_530gsm_cotton_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'montericca_530gsm',
            'texture_label' => '蒙特利卡(530GSM)',
            'color_code' => 'cotton_white',
            'color_label' => '棉白',
            'image' => '/images/products/cotton/paper-samples/montericca-530gsm-cotton-white.png',
        ],
        [
            'code' => 'ningrou_523g_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'ningrou_523g',
            'texture_label' => '凝柔(523g)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/ningrou-523g-white.png',
        ],
        [
            'code' => 'haiti_550gsm_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'haiti_550gsm',
            'texture_label' => '海蒂(550GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/haiti-550gsm-white.png',
        ],
        [
            'code' => 'haiti_550gsm_black',
            'thickness_code' => '450_700g',
            'texture_code' => 'haiti_550gsm',
            'texture_label' => '海蒂(550GSM)',
            'color_code' => 'black',
            'color_label' => '黑色',
            'image' => '/images/products/cotton/paper-samples/haiti-550gsm-black.png',
        ],
        [
            'code' => 'haiti_550gsm_kraft',
            'thickness_code' => '450_700g',
            'texture_code' => 'haiti_550gsm',
            'texture_label' => '海蒂(550GSM)',
            'color_code' => 'kraft',
            'color_label' => '牛皮',
            'image' => '/images/products/cotton/paper-samples/haiti-550gsm-kraft.png',
        ],
        [
            'code' => 'aweike_550gsm_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'aweike_550gsm',
            'texture_label' => '艾维克(550GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/aweike-550gsm-white.png',
        ],
        [
            'code' => 'aweike_550gsm_strong_black',
            'thickness_code' => '450_700g',
            'texture_code' => 'aweike_550gsm',
            'texture_label' => '艾维克(550GSM)',
            'color_code' => 'strong_black',
            'color_label' => '坚毅黑',
            'image' => '/images/products/cotton/paper-samples/aweike-550gsm-strong-black.png',
        ],
        [
            'code' => 'new_guanya_550gsm_cream_yellow',
            'thickness_code' => '450_700g',
            'texture_code' => 'new_guanya_550gsm',
            'texture_label' => '新冠雅(550GSM)',
            'color_code' => 'cream_yellow',
            'color_label' => '奶黄',
            'image' => '/images/products/cotton/paper-samples/new-guanya-550gsm-cream-yellow.png',
        ],
        [
            'code' => 'new_guanya_550gsm_ultra_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'new_guanya_550gsm',
            'texture_label' => '新冠雅(550GSM)',
            'color_code' => 'ultra_white',
            'color_label' => '超白',
            'image' => '/images/products/cotton/paper-samples/new-guanya-550gsm-ultra-white.png',
        ],
        [
            'code' => 'sumei_700gsm_red',
            'thickness_code' => '450_700g',
            'texture_code' => 'sumei_700gsm',
            'texture_label' => '素墨(700GSM)',
            'color_code' => 'red',
            'color_label' => '大红',
            'image' => '/images/products/cotton/paper-samples/sumei-700gsm-red.png',
        ],
        [
            'code' => 'sumei_700gsm_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'sumei_700gsm',
            'texture_label' => '素墨(700GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/sumei-700gsm-white.png',
        ],
        [
            'code' => 'sumei_700gsm_deep_blue',
            'thickness_code' => '450_700g',
            'texture_code' => 'sumei_700gsm',
            'texture_label' => '素墨(700GSM)',
            'color_code' => 'deep_blue',
            'color_label' => '深蓝',
            'image' => '/images/products/cotton/paper-samples/sumei-700gsm-deep-blue.png',
        ],
        [
            'code' => 'sumei_700gsm_dark_gray',
            'thickness_code' => '450_700g',
            'texture_code' => 'sumei_700gsm',
            'texture_label' => '素墨(700GSM)',
            'color_code' => 'dark_gray',
            'color_label' => '深灰',
            'image' => '/images/products/cotton/paper-samples/sumei-700gsm-dark-gray.png',
        ],
        [
            'code' => 'sumei_700gsm_denim',
            'thickness_code' => '450_700g',
            'texture_code' => 'sumei_700gsm',
            'texture_label' => '素墨(700GSM)',
            'color_code' => 'denim',
            'color_label' => '牛仔色',
            'image' => '/images/products/cotton/paper-samples/sumei-700gsm-denim.png',
        ],
        [
            'code' => 'sumei_700gsm_black',
            'thickness_code' => '450_700g',
            'texture_code' => 'sumei_700gsm',
            'texture_label' => '素墨(700GSM)',
            'color_code' => 'black',
            'color_label' => '黑色',
            'image' => '/images/products/cotton/paper-samples/sumei-700gsm-black.png',
        ],
        [
            'code' => 'jazz_metal_500gsm_brushed_gold',
            'thickness_code' => '450_700g',
            'texture_code' => 'jazz_metal_500gsm',
            'texture_label' => '爵士金属(500GSM)',
            'color_code' => 'brushed_gold',
            'color_label' => '拉丝金',
            'image' => '/images/products/cotton/paper-samples/jazz-metal-500gsm-brushed-gold.png',
        ],
        [
            'code' => 'jazz_metal_500gsm_brushed_silver',
            'thickness_code' => '450_700g',
            'texture_code' => 'jazz_metal_500gsm',
            'texture_label' => '爵士金属(500GSM)',
            'color_code' => 'brushed_silver',
            'color_label' => '拉丝银',
            'image' => '/images/products/cotton/paper-samples/jazz-metal-500gsm-brushed-silver.png',
        ],
        [
            'code' => 'ice_white_pearl_500gsm_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'ice_white_pearl_500gsm',
            'texture_label' => '冰白珠光(500GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/ice-white-pearl-500gsm-white.png',
        ],
        [
            'code' => 'songka_600gsm_pale_yellow',
            'thickness_code' => '450_700g',
            'texture_code' => 'songka_600gsm',
            'texture_label' => '松卡(600GSM)',
            'color_code' => 'pale_yellow',
            'color_label' => '淡黄色',
            'image' => '/images/products/cotton/paper-samples/songka-600gsm-pale-yellow.png',
        ],
        [
            'code' => 'extreme_ink_680gsm_black',
            'thickness_code' => '450_700g',
            'texture_code' => 'extreme_ink_680gsm',
            'texture_label' => '极墨(680GSM)',
            'color_code' => 'black',
            'color_label' => '黑色',
            'image' => '/images/products/cotton/paper-samples/extreme-ink-680gsm-black.png',
        ],
        [
            'code' => 'silk_classic_white_685gsm_white',
            'thickness_code' => '450_700g',
            'texture_code' => 'silk_classic_white_685gsm',
            'texture_label' => '丝感古典白(685GSM)',
            'color_code' => 'white',
            'color_label' => '白色',
            'image' => '/images/products/cotton/paper-samples/silk-classic-white-685gsm-white.png',
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const COTTON_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
    ];

    /**
     * @var array<int, string>
     */
    private const CONTRACT_SLUGS = [
        'standard-quality-business-cards',
        'basic-pvc-card',
        'standard-pvc-card',
        'premium-pvc-card',
        'classic-metal-business-cards',
        'premium-metal-business-cards',
        'luxe-metal-business-cards',
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
        'super-luxe-business-cards',
        'super-standard-business-cards',
    ];

    /**
     * All purchasable business-card product slugs. The design-service page is
     * deliberately not part of this list.
     *
     * @var list<string>
     */
    private const BUSINESS_CARD_PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
        'super-standard-business-cards',
        'super-luxe-business-cards',
        'basic-pvc-card',
        'standard-pvc-card',
        'premium-pvc-card',
        'classic-metal-business-cards',
        'premium-metal-business-cards',
        'luxe-metal-business-cards',
        'classic-standard-business-cards',
        'classic-special-business-cards',
        'standard-quality-business-cards',
        'solid-quality-business-cards',
    ];

    /**
     * Return whether the product has a centrally managed option contract.
     */
    public static function supports(string $slug): bool
    {
        return in_array($slug, self::CONTRACT_SLUGS, true);
    }

    public static function isBusinessCardProduct(string $slug): bool
    {
        return in_array($slug, self::BUSINESS_CARD_PRODUCT_SLUGS, true);
    }

    public static function isCottonBusinessCard(string $slug): bool
    {
        return in_array($slug, self::COTTON_SLUGS, true);
    }

    /**
     * Add the shared cotton texture galleries ahead of less-specific rules.
     * A texture gallery must win over the existing rounded-corner gallery when
     * both options are selected. Cotton special-finish diagrams remain option
     * swatches; supplied finish artwork is used for finish gallery primaries.
     *
     * Obsolete texture, special-finish, NFC, and finish-side rules are removed
     * while unrelated product-specific rules remain intact.
     *
     * @param  array<int, mixed>  $rules
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeCottonGalleryRules(array $rules): array
    {
        $defaultRules = [];
        $otherRules = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (
                array_key_exists('thickness', $match)
                || array_key_exists('texture', $match)
                || array_key_exists('special_finish', $match)
                || array_key_exists('special_finish_on_sides', $match)
                || array_key_exists('with_nfc', $match)
            ) {
                continue;
            }

            if ($match === [] || ($rule['id'] ?? null) === 'default') {
                if ($defaultRules === []) {
                    $defaultRules[] = $rule;
                }

                continue;
            }

            $otherRules[] = $rule;
        }

        $textureRules = array_map(
            static fn (array $texture): array => [
                'id' => "texture_{$texture['code']}",
                'match' => [
                    'thickness' => $texture['thickness_code'],
                    'texture' => $texture['code'],
                ],
                'images' => [$texture['image']],
                'primary' => $texture['image'],
            ],
            self::COTTON_TEXTURES,
        );

        $specialFinishRules = array_map(
            static fn (string $code, string $image): array => [
                'id' => "special_finish_{$code}",
                'match' => ['special_finish' => $code],
                'images' => [$image],
                'primary' => $image,
            ],
            array_keys(self::COTTON_SPECIAL_FINISH_PRIMARY_IMAGES),
            array_values(self::COTTON_SPECIAL_FINISH_PRIMARY_IMAGES),
        );

        return [
            ...$defaultRules,
            ...$specialFinishRules,
            ...$textureRules,
            ...$otherRules,
        ];
    }

    /**
     * Apply the shared size metadata to any business-card option map that
     * exposes standard, square, or custom sizes.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function normalizeSharedSizeSwatches(array $options, ?string $slug = null): array
    {
        // Sticker sizes carry their own square-inch area and custom bounds;
        // the shared size contract belongs only to business-card products.
        if ($slug !== null && ! self::isBusinessCardProduct($slug)) {
            return $options;
        }

        if (! is_array($options['sizes'] ?? null)) {
            return $options;
        }

        $isCanonicalGroup = array_key_exists('values', $options['sizes']);

        if ($isCanonicalGroup && ! is_array($options['sizes']['values'])) {
            return $options;
        }

        $values = $isCanonicalGroup ? $options['sizes']['values'] : $options['sizes'];

        $standardSwatchImage = self::STANDARD_SIZE_SWATCH_IMAGE;
        $squareSwatchImage = self::SQUARE_SIZE_SWATCH_IMAGE;

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = $value['code'] ?? null;

            if ($code === 'standard') {
                $value['swatch_image'] = $standardSwatchImage;

                if ($slug === 'classic-standard-business-cards') {
                    $value['description'] = self::CLASSIC_STANDARD_STANDARD_SIZE_DESCRIPTION;
                }
            } elseif ($code === 'square') {
                $value['swatch_image'] = $squareSwatchImage;

                if ($slug === 'classic-standard-business-cards') {
                    $value['description'] = self::CLASSIC_STANDARD_SQUARE_SIZE_DESCRIPTION;
                }
            } elseif ($code === 'custom') {
                if ($slug === 'classic-standard-business-cards') {
                    $value['description'] = self::CLASSIC_STANDARD_CUSTOM_SIZE_DESCRIPTION;
                } elseif (self::isCottonBusinessCard((string) $slug)) {
                    $value['description'] = self::COTTON_CUSTOM_SIZE_DESCRIPTION;
                } else {
                    $value['description'] = self::CUSTOM_SIZE_DESCRIPTION;
                }
            }
        }
        unset($value);

        if ($isCanonicalGroup) {
            $options['sizes']['values'] = array_values($values);
        } else {
            $options['sizes'] = array_values($values);
        }

        return $options;
    }

    /**
     * Fill missing shared swatches without replacing a product-specific image
     * that is already present.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function normalizeSharedSwatchImages(array $options): array
    {
        $fallbacks = [
            'print_code' => [
                'no_print_code' => self::NO_PRINT_CODE_SWATCH_IMAGE,
                'print_code' => self::PRINT_CODE_SWATCH_IMAGE,
                'need_print_code' => self::PRINT_CODE_SWATCH_IMAGE,
            ],
            'drill' => [
                'no_drilling' => self::NO_DRILLING_SWATCH_IMAGE,
                'needs_drilling' => self::NEEDS_DRILLING_SWATCH_IMAGE,
            ],
            'print_code_or_signature_stripe' => [
                'no_print_code_or_signature_stripe' => self::NO_PRINT_CODE_SWATCH_IMAGE,
                'print_code' => self::PRINT_CODE_SWATCH_IMAGE,
                'signature_stripe' => self::SIGNATURE_STRIPE_SWATCH_IMAGE,
            ],
            'texture' => self::SHARED_TEXTURE_SWATCH_IMAGES,
        ];

        foreach ($fallbacks as $groupKey => $groupFallbacks) {
            if (! is_array($options[$groupKey] ?? null)) {
                continue;
            }

            $isCanonicalGroup = array_key_exists('values', $options[$groupKey]);
            $values = $isCanonicalGroup ? $options[$groupKey]['values'] ?? null : $options[$groupKey];

            if (! is_array($values)) {
                continue;
            }

            foreach ($values as &$value) {
                if (! is_array($value)) {
                    continue;
                }

                $swatchImage = $groupFallbacks[$value['code'] ?? ''] ?? null;

                if ($swatchImage !== null && blank($value['swatch_image'] ?? null)) {
                    $value['swatch_image'] = $swatchImage;
                }
            }
            unset($value);

            if ($isCanonicalGroup) {
                $options[$groupKey]['values'] = array_values($values);
            } else {
                $options[$groupKey] = array_values($values);
            }
        }

        return $options;
    }

    /**
     * Remove retired NFC option groups and values from a business-card option
     * map. This accepts both canonical groups (`values`) and legacy flat
     * arrays so stale data cannot reappear at the storefront boundary.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function withoutNfcOptions(array $options): array
    {
        unset($options['with_nfc']);

        foreach (array_keys($options) as $groupKey) {
            if (self::isNfcOptionToken($groupKey)) {
                unset($options[$groupKey]);

                continue;
            }

            $group = $options[$groupKey] ?? null;

            if (! is_array($group)) {
                continue;
            }

            $isCanonicalGroup = array_key_exists('values', $group);
            $values = $isCanonicalGroup ? ($group['values'] ?? null) : $group;

            if (! is_array($values)) {
                continue;
            }

            $filteredValues = array_values(array_filter(
                $values,
                static fn (mixed $value): bool => ! self::isNfcOptionValue($value),
            ));

            if ($values !== [] && $filteredValues === []) {
                unset($options[$groupKey]);

                continue;
            }

            if ($isCanonicalGroup) {
                $group['values'] = $filteredValues;

                if (self::isNfcOptionValue($group['default'] ?? null)) {
                    $group['default'] = self::optionValueCode($filteredValues[0] ?? null);
                }

                $options[$groupKey] = $group;
            } else {
                $options[$groupKey] = $filteredValues;
            }
        }

        return $options;
    }

    /**
     * Normalize an existing option map to the requested product contract.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    public static function normalize(string $slug, array $options): ?array
    {
        $normalized = match ($slug) {
            'standard-quality-business-cards' => self::classicQuality($options),
            'basic-pvc-card' => self::basicPvc($options),
            'standard-pvc-card' => self::standardPvc($options),
            'premium-pvc-card' => self::premiumPvc($options),
            'classic-metal-business-cards' => self::metal($options, false),
            'premium-metal-business-cards', 'luxe-metal-business-cards' => self::metal($options, true),
            'basic-cotton-business-card',
            'classic-cotton-business-card',
            'premium-cotton-business-card',
            'luxe-cotton-business-card',
            'grand-cotton-business-card' => self::cotton($options),
            'super-luxe-business-cards' => self::luxeBusinessCards($options),
            'super-standard-business-cards' => self::superBusinessCards($options),
            default => null,
        };

        if ($normalized === null) {
            return null;
        }

        foreach ($normalized as $key => &$group) {
            if (
                is_array($options[$key] ?? null)
                && ($options[$key]['type'] ?? null) === 'multi_select'
            ) {
                $group['type'] = 'multi_select';
            }
        }
        unset($group);

        return self::normalizeSharedSwatchImages($normalized);
    }

    /**
     * Return the canonical finish image paths for a PVC product. The same
     * image is used for the finish swatch and the selected finish gallery's
     * primary image.
     *
     * @return array<string, string>
     */
    public static function pvcFinishImages(string $slug): array
    {
        return self::PVC_FINISH_IMAGES[$slug] ?? [];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function classicQuality(array $options): array
    {
        return [
            'sizes' => self::group('Size', self::sizeValues($options), 'standard'),
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'texture' => self::group(
                'Paper Finish',
                self::textureValues($options),
                'matte',
                false,
                false,
            ),
            'uv_finish' => self::group(
                '3D UV',
                self::uvFinishValues($options),
                null,
                false,
                false,
            ),
            'special_finish' => self::group(
                'Special Finish',
                [...self::hotFoilValues($options), ...self::coldFoilValues($options)],
                'no_special_finish',
                true,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function basicPvc(array $options): array
    {
        return [
            'paper_finish' => self::group(
                'Paper Finish',
                self::pvcPaperFinishValues($options, self::pvcFinishImages('basic-pvc-card')),
                'matte',
            ),
            'print_code' => self::group(
                'Print Code',
                self::printCodeValues($options, self::PVC_PRINT_CODE_SWATCH_IMAGE),
                'no_print_code',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function standardPvc(array $options): array
    {
        return [
            'paper_finish' => self::group(
                'Paper Finish',
                self::pvcPaperFinishValues($options, self::pvcFinishImages('standard-pvc-card')),
                'matte',
            ),
            'print_code_or_signature_stripe' => self::group(
                'Print Code or Signature Stripe',
                self::printCodeOrSignatureStripeValues(
                    $options,
                    self::PVC_PRINT_CODE_SWATCH_IMAGE,
                    self::PVC_SIGNATURE_STRIPE_SWATCH_IMAGE,
                ),
                'no_print_code_or_signature_stripe',
            ),
            'special_finish' => self::group(
                'Special Finish',
                self::hotFoilValues($options),
                'no_special_finish',
                true,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function premiumPvc(array $options): array
    {
        return [
            'paper_finish' => self::group(
                'Paper Finish',
                self::pvcPaperFinishValues($options, self::pvcFinishImages('premium-pvc-card')),
                'matte',
            ),
            'print_code' => self::group(
                'Print Code',
                self::printCodeValues($options, self::PVC_PRINT_CODE_SWATCH_IMAGE),
                'no_print_code',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function cotton(array $options): array
    {
        return [
            'sizes' => self::group('Size', self::cottonSizeValues($options), 'standard'),
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'thickness' => self::group(
                'Thickness',
                self::cottonThicknessValues($options),
                '300_360g',
            ),
            'texture' => self::group(
                'Texture',
                self::cottonTextureValues($options),
                'wild_300gsm_white',
            ),
            'special_finish' => self::group(
                'Special Finish',
                self::cottonSpecialFinishValues($options),
                [],
                true,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function luxeBusinessCards(array $options): array
    {
        return [
            'sizes' => self::group('Size', self::sizeValues($options), 'standard'),
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'texture' => self::group('Texture', self::luxeTextureValues($options), 'inkpavo_j1'),
            'special_finish' => self::group(
                'Special Finish',
                self::hotFoilValues($options),
                'no_special_finish',
                true,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function superBusinessCards(array $options): array
    {
        return [
            'sizes' => self::group('Size', self::sizeValues($options), 'standard'),
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'texture' => self::group('Texture', self::superTextureValues($options), 'j1_water_ripple_paper'),
            'special_finish' => self::group(
                'Special Finish',
                self::hotFoilValues($options),
                'no_special_finish',
                true,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function metal(array $options, bool $withSpecialFinish): array
    {
        $groups = [
            'thickness' => self::group('Thickness', [
                self::value($options, 'thickness', '0_3_mm', [
                    'label' => '12pt',
                    'description' => '12pt metal card thickness.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/thickness-0-3mm.png',
                ]),
                self::value($options, 'thickness', '0_5_mm', [
                    'label' => '20pt',
                    'description' => '20pt metal card thickness.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/thickness-0-5mm.png',
                ]),
            ], '0_3_mm'),
            'sizes' => self::group('Size', [
                self::value($options, 'sizes', '89x51_mm', [
                    'label' => '3.5 × 2.0 inches',
                    'description' => '3.5 × 2.0 inches metal business card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/size-89x51mm.png',
                ]),
                self::value($options, 'sizes', '85x54_mm', [
                    'label' => '3.35 × 2.13 inches',
                    'description' => '3.35 × 2.13 inches metal business card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/size-85x54mm.png',
                ]),
                self::value($options, 'sizes', '80x50_mm', [
                    'label' => '3.15 × 1.97 inches',
                    'description' => '3.15 × 1.97 inches metal business card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/size-80x50mm.png',
                ]),
            ], '89x51_mm'),
            'print_code_or_magnetic_stripe' => self::group(
                'Print Code or Magnetic Stripe',
                [
                    self::value($options, 'print_code_or_magnetic_stripe', 'no_print_code_or_magnetic_stripe', [
                        'label' => 'No print code or magnetic stripe',
                        'description' => 'No print code or magnetic stripe.',
                        'swatch_image' => '/images/product-options/business-cards/swatches/metal/no-print-code-or-magnetic-stripe.png',
                    ]),
                    self::value($options, 'print_code_or_magnetic_stripe', 'print_code', [
                        'label' => 'Print code',
                        'description' => 'Add a printed code to the card.',
                        'swatch_image' => '/images/product-options/business-cards/swatches/metal/print-code.png',
                    ]),
                    self::value($options, 'print_code_or_magnetic_stripe', 'magnetic_stripe', [
                        'label' => 'Magnetic stripe',
                        'description' => 'Add a magnetic stripe to the card.',
                        'swatch_image' => '/images/product-options/business-cards/swatches/metal/magnetic-stripe.png',
                    ]),
                ],
                'no_print_code_or_magnetic_stripe',
            ),
        ];

        if ($withSpecialFinish) {
            $groups['special_finish'] = self::group('Special Finish', [
                self::value($options, 'special_finish', 'laser_engraving', [
                    'label' => 'Laser Engraving',
                    'description' => 'Laser engraving for a precise, tactile finish.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/laser-engraving.png',
                ]),
                self::value($options, 'special_finish', 'color_printing', [
                    'label' => 'Color Printing',
                    'description' => 'Full-color printing on the metal card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/color-printing.png',
                ]),
                self::value($options, 'special_finish', 'plating', [
                    'label' => 'Plating',
                    'description' => 'Metal plating for a refined finish.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/plating.png',
                ]),
            ], 'laser_engraving');
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function sizeValues(array $options): array
    {
        return [
            self::value($options, 'sizes', 'standard', [
                'label' => 'Standard',
                'description' => '2.0" x 3.5"',
                'width' => '2.0',
                'height' => '3.5',
                'swatch_image' => self::STANDARD_SIZE_SWATCH_IMAGE,
            ]),
            self::value($options, 'sizes', 'square', [
                'label' => 'Square',
                'description' => '2.5" x 2.5"',
                'width' => '2.5',
                'height' => '2.5',
                'swatch_image' => self::SQUARE_SIZE_SWATCH_IMAGE,
            ]),
            self::value($options, 'sizes', 'custom', [
                'label' => 'Custom',
                'description' => self::CUSTOM_SIZE_DESCRIPTION,
                'swatch_image' => '/images/product-options/business-cards/swatches/custom-size.webp',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function cottonSizeValues(array $options): array
    {
        return [
            self::value($options, 'sizes', 'standard', [
                'label' => '3.54 × 2.13 in',
                'width' => '3.54',
                'height' => '2.13',
                'swatch_image' => self::STANDARD_SIZE_SWATCH_IMAGE,
            ]),
            self::value($options, 'sizes', 'compact', [
                'label' => '3.5 × 2.0 in',
                'width' => '3.5',
                'height' => '2.0',
                'swatch_image' => self::STANDARD_SIZE_SWATCH_IMAGE,
            ]),
            self::value($options, 'sizes', 'custom', [
                'label' => 'Custom',
                'description' => self::COTTON_CUSTOM_SIZE_DESCRIPTION,
                'swatch_image' => '/images/product-options/business-cards/swatches/custom-size.webp',
                'min_width' => '0.70',
                'max_width' => '3.54',
                'min_height' => '0.70',
                'max_height' => '2.13',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function cornerValues(array $options): array
    {
        return [
            self::value($options, 'corners', 'square', [
                'label' => 'Square',
                'description' => 'Sharp and stylish.',
                'swatch_image' => '/images/product-options/business-cards/swatches/square.webp',
            ]),
            self::value($options, 'corners', 'rounded', [
                'label' => 'Rounded',
                'description' => 'Smooth and rounded.',
                'swatch_image' => '/images/product-options/business-cards/swatches/rounded.webp',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<array<string, mixed>>
     */
    private static function cottonThicknessValues(array $options): array
    {
        return array_map(
            fn (array $thickness): array => self::value(
                $options,
                'thickness',
                $thickness['code'],
                [
                    'label' => $thickness['label'],
                    'description' => $thickness['label'].' cotton paper.',
                ],
            ),
            self::COTTON_THICKNESSES,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, string>|null  $swatchImages
     * @return array<int, array<string, mixed>>
     */
    private static function pvcPaperFinishValues(array $options, ?array $swatchImages = null): array
    {
        $swatchImages ??= [
            'matte' => '/images/products/pvc/matte-pvc.png',
            'gloss' => '/images/products/pvc/gloss-pvc.png',
            'frosted' => '/images/products/pvc/frosted-pvc.png',
        ];

        return [
            self::value($options, 'paper_finish', 'matte', [
                'label' => 'Matte',
                'description' => 'Smooth, non-reflective matte finish.',
                'swatch_image' => $swatchImages['matte'],
            ]),
            self::value($options, 'paper_finish', 'gloss', [
                'label' => 'Gloss',
                'description' => 'Shiny and highly reflective gloss finish.',
                'swatch_image' => $swatchImages['gloss'],
            ]),
            self::value($options, 'paper_finish', 'frosted', [
                'label' => 'Frosted Glass',
                'description' => 'A translucent frosted-glass finish with a soft, elegant look.',
                'swatch_image' => $swatchImages['frosted'],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function printCodeValues(array $options, ?string $printCodeSwatchImage = null): array
    {
        $printCodeSwatchImage ??= self::PRINT_CODE_SWATCH_IMAGE;

        return [
            self::value($options, 'print_code', 'no_print_code', [
                'label' => 'No print code',
                'description' => 'Do not add a print code.',
                'swatch_image' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
            ]),
            self::value($options, 'print_code', 'print_code', [
                'label' => 'Print code',
                'description' => 'Add a print code to the card.',
                'swatch_image' => $printCodeSwatchImage,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function printCodeOrSignatureStripeValues(
        array $options,
        ?string $printCodeSwatchImage = null,
        ?string $signatureStripeSwatchImage = null,
    ): array {
        $printCodeSwatchImage ??= self::PRINT_CODE_SWATCH_IMAGE;
        $signatureStripeSwatchImage ??= self::SIGNATURE_STRIPE_SWATCH_IMAGE;

        return [
            self::value($options, 'print_code_or_signature_stripe', 'no_print_code_or_signature_stripe', [
                'label' => 'No print code or signature stripe',
                'description' => 'Do not add a print code or signature stripe.',
                'swatch_image' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
            ]),
            self::value($options, 'print_code_or_signature_stripe', 'print_code', [
                'label' => 'Print code',
                'description' => 'Add a print code to the card.',
                'swatch_image' => $printCodeSwatchImage,
            ]),
            self::value($options, 'print_code_or_signature_stripe', 'signature_stripe', [
                'label' => 'Signature stripe',
                'description' => 'Add a writable signature stripe.',
                'swatch_image' => $signatureStripeSwatchImage,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function hotFoilValues(array $options): array
    {
        $swatches = '/images/product-options/business-cards/swatches/';
        $foils = [
            ['code' => 'no_special_finish', 'label' => 'No finish', 'swatch_image' => '/images/product-options/no-foil.png', 'description' => 'No special finish, thanks.'],
            ['code' => 'black_gold', 'label' => 'Black Gold', 'swatch_image' => $swatches.'black-gold.png'],
            ['code' => 'blue_gold', 'label' => 'Blue Gold', 'swatch_image' => $swatches.'blue-gold.png'],
            ['code' => 'bright_gold', 'label' => 'Bright Gold', 'swatch_image' => $swatches.'bright-gold.png'],
            ['code' => 'bright_silver', 'label' => 'Bright Silver', 'swatch_image' => $swatches.'bright-silver.png'],
            ['code' => 'green_gold', 'label' => 'Green Gold', 'swatch_image' => $swatches.'green-gold.png'],
            ['code' => 'matte_gold', 'label' => 'Matte Gold', 'swatch_image' => $swatches.'matte-gold.png'],
            ['code' => 'matte_silver', 'label' => 'Matte Silver', 'swatch_image' => $swatches.'matte-silver.png'],
            ['code' => 'red_gold', 'label' => 'Red Gold', 'swatch_image' => $swatches.'red-gold.png'],
            ['code' => 'rose_gold', 'label' => 'Rose Gold', 'swatch_image' => $swatches.'rose-gold.png'],
            ['code' => 'aged_gold', 'label' => 'Aged Gold', 'swatch_image' => $swatches.'aged-gold.png'],
            ['code' => 'muted_purple_gold', 'label' => 'Muted Purple Gold', 'swatch_image' => $swatches.'muted-purple-gold.png'],
        ];

        return array_map(
            fn (array $foil): array => self::value(
                $options,
                'special_finish',
                $foil['code'],
                array_replace(
                    [
                        'label' => $foil['label'],
                        'description' => $foil['code'] === 'no_special_finish'
                            ? 'No special finish, thanks.'
                            : $foil['label'].' hot foil.',
                        'swatch_image' => $foil['swatch_image'],
                    ],
                    $foil,
                ),
            ),
            $foils,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function coldFoilValues(array $options): array
    {
        $foils = [
            ['code' => 'cold_red_gold', 'label' => 'Cold Red Gold', 'description' => 'Vibrant cold red foil', 'swatch_image' => self::QUALITY_COLD_FOIL_SWATCH_IMAGES['cold_red_gold']],
            ['code' => 'cold_blue_gold', 'label' => 'Cold Blue Gold', 'description' => 'Elegant cold blue foil', 'swatch_image' => self::QUALITY_COLD_FOIL_SWATCH_IMAGES['cold_blue_gold']],
            ['code' => 'cold_bright_gold', 'label' => 'Cold Bright Gold', 'description' => 'Glistening cold gold foil', 'swatch_image' => self::QUALITY_COLD_FOIL_SWATCH_IMAGES['cold_bright_gold']],
            ['code' => 'cold_bright_silver', 'label' => 'Cold Bright Silver', 'description' => 'Shining cold silver foil', 'swatch_image' => self::QUALITY_COLD_FOIL_SWATCH_IMAGES['cold_bright_silver']],
            ['code' => 'cold_green_gold', 'label' => 'Cold Green Gold', 'description' => 'Rich cold green gold foil', 'swatch_image' => self::QUALITY_COLD_FOIL_SWATCH_IMAGES['cold_green_gold']],
            ['code' => 'cold_matte_gold', 'label' => 'Cold Matte Gold', 'description' => 'Sophisticated matte gold foil', 'swatch_image' => self::QUALITY_COLD_FOIL_SWATCH_IMAGES['cold_matte_gold']],
            ['code' => 'cold_matte_silver', 'label' => 'Cold Matte Silver', 'description' => 'Elegant matte silver foil', 'swatch_image' => self::QUALITY_COLD_FOIL_SWATCH_IMAGES['cold_matte_silver']],
        ];

        return array_map(
            fn (array $foil): array => self::value($options, 'special_finish', $foil['code'], $foil),
            $foils,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function textureValues(array $options): array
    {
        $textures = [
            [
                'code' => 'matte',
                'label' => 'Matte',
                'description' => 'With a smooth feel. Shine-free so no glare.',
            ],
            [
                'code' => 'gloss',
                'label' => 'Gloss',
                'description' => 'Eye-catchingly shiny. Makes color photos pop.',
            ],
            ['code' => 'starlight_film', 'label' => 'Starlight Film', 'description' => ''],
            ['code' => 'holographic_film', 'label' => 'Holographic Film', 'description' => ''],
            ['code' => 'soft_touch_film', 'label' => 'Soft-Touch Film', 'description' => ''],
        ];

        return array_map(
            fn (array $texture): array => self::value($options, 'texture', $texture['code'], [
                'label' => $texture['label'],
                'description' => $texture['description'],
                'swatch_image' => self::QUALITY_TEXTURE_SWATCH_IMAGES[$texture['code']],
            ]),
            $textures,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function uvFinishValues(array $options): array
    {
        return array_map(
            fn (array $value): array => self::value(
                $options,
                'uv_finish',
                $value['code'],
                $value,
            ),
            [
                [
                    'code' => 'single_side_uv',
                    'label' => 'single side',
                    'description' => '',
                    'swatch_image' => self::QUALITY_3D_UV_SWATCH_IMAGE,
                ],
                [
                    'code' => 'both_sides_uv',
                    'label' => 'both sides',
                    'description' => '',
                    'swatch_image' => self::QUALITY_3D_UV_SWATCH_IMAGE,
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function cottonTextureValues(array $options): array
    {
        return array_map(function (array $texture) use ($options): array {
            $textureLabel = self::COTTON_TEXTURE_LABELS[$texture['texture_label']]
                ?? $texture['texture_label'];
            $colorLabel = self::COTTON_COLOR_LABELS[$texture['color_label']]
                ?? $texture['color_label'];

            return self::value(
                $options,
                'texture',
                $texture['code'],
                [
                    'label' => $textureLabel.' · '.$colorLabel,
                    'description' => $textureLabel.' · '.$colorLabel,
                    'swatch_image' => $texture['image'],
                    'color_swatch_image' => str_replace(
                        '/images/products/cotton/paper-samples/',
                        '/images/products/cotton/paper-samples/swatches/',
                        (string) preg_replace('/\.png$/', '.webp', $texture['image']),
                    ),
                    'thickness_code' => $texture['thickness_code'],
                    'texture_code' => $texture['texture_code'],
                    'texture_label' => $textureLabel,
                    'color_code' => $texture['color_code'],
                    'color_label' => $colorLabel,
                ],
            );
        }, self::COTTON_TEXTURES);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function cottonSpecialFinishValues(array $options): array
    {
        $finishes = [
            [
                'code' => 'laser',
                'label' => 'Laser',
                'description' => 'Precision laser cutting or detailing.',
                'swatch_image' => self::COTTON_SPECIAL_FINISH_SWATCH_IMAGES['laser'],
            ],
            [
                'code' => 'edge_coloring',
                'label' => 'Edge Coloring',
                'description' => 'Color applied to the edges of the card.',
                'swatch_image' => self::COTTON_SPECIAL_FINISH_SWATCH_IMAGES['edge_coloring'],
            ],
            [
                'code' => 'double_mounting',
                'label' => 'Double Mounting',
                'description' => 'Two cotton paper layers mounted together.',
                'swatch_image' => self::COTTON_SPECIAL_FINISH_SWATCH_IMAGES['double_mounting'],
            ],
            [
                'code' => 'custom_die_cut',
                'label' => 'Custom Die-Cut',
                'description' => 'A custom die-cut card shape.',
                'swatch_image' => self::COTTON_SPECIAL_FINISH_SWATCH_IMAGES['custom_die_cut'],
            ],
            [
                'code' => 'emboss',
                'label' => 'Emboss / Deboss',
                'description' => 'Raised or recessed detail pressed into the card.',
                'swatch_image' => self::COTTON_SPECIAL_FINISH_SWATCH_IMAGES['emboss'],
            ],
        ];

        return array_map(
            fn (array $finish): array => self::value(
                $options,
                'special_finish',
                $finish['code'],
                $finish,
            ),
            $finishes,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function luxeTextureValues(array $options): array
    {
        $textures = array_map(
            fn (int $number): array => [
                'code' => "inkpavo_j{$number}",
                'label' => "InkPavo-J{$number}",
                'description' => "InkPavo-J{$number} texture.",
                'swatch_image' => "/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j{$number}.png",
            ],
            range(1, 8),
        );

        return array_map(
            fn (array $texture): array => self::value($options, 'texture', $texture['code'], $texture),
            $textures,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function superTextureValues(array $options): array
    {
        $textures = [
            ['code' => 'j1_water_ripple_paper', 'label' => 'J1 Water Ripple Paper'],
            ['code' => 'j2_cloth_texture_paper', 'label' => 'J2 Cloth Texture Paper'],
            ['code' => 'j3_eggshell_texture', 'label' => 'J3 Eggshell Texture'],
            ['code' => 'j4_high_grade_paper', 'label' => 'J4 High-Grade Paper'],
            ['code' => 'j5_pearlescent_paper', 'label' => 'J5 Pearlescent Paper'],
            ['code' => 'j6_kraft_paper', 'label' => 'J6 Kraft Paper'],
            ['code' => 'j7_absorbent_cotton_paper', 'label' => 'J7 Absorbent Cotton Paper'],
            ['code' => 'j8_pinhole_paper', 'label' => 'J8 Pinhole Paper'],
        ];

        return array_map(
            fn (array $texture): array => self::value($options, 'texture', $texture['code'], [
                'label' => $texture['label'],
                'description' => $texture['label'].'.',
                'swatch_image' => '/images/products/super-business-cards/texture/'.str_replace('_', '-', $texture['code']).'.png',
            ]),
            $textures,
        );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $values
     * @return array<string, mixed>
     */
    private static function group(
        string $label,
        array $values,
        string|array|null $default,
        bool $multiSelect = false,
        bool $required = true,
    ): array {
        return [
            'label' => $label,
            'type' => $multiSelect ? 'multi_select' : 'select',
            'required' => $required,
            'default' => $default,
            'values' => array_values($values),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private static function value(array $options, string $groupKey, string $code, array $defaults): array
    {
        $values = data_get($options, "{$groupKey}.values", []);

        if (is_array($values)) {
            foreach ($values as $value) {
                if (is_array($value) && ($value['code'] ?? null) === $code) {
                    return array_replace($value, $defaults, ['code' => $code]);
                }
            }
        }

        return array_replace(['code' => $code], $defaults);
    }

    private static function isNfcOptionValue(mixed $value): bool
    {
        if (is_array($value)) {
            if (array_is_list($value) && ! array_key_exists('code', $value)) {
                return array_any($value, self::isNfcOptionValue(...));
            }

            foreach (['code', 'label', 'name'] as $key) {
                if (self::isNfcOptionToken($value[$key] ?? null)) {
                    return true;
                }
            }

            return false;
        }

        return self::isNfcOptionToken($value);
    }

    private static function isNfcOptionToken(mixed $value): bool
    {
        return in_array(self::normalizeOptionToken($value), ['nfc', 'with_nfc', 'no_nfc'], true);
    }

    private static function normalizeOptionToken(mixed $value): string
    {
        return str_replace(['-', ' '], '_', strtolower(trim((string) $value)));
    }

    private static function optionValueCode(mixed $value): ?string
    {
        if (is_scalar($value)) {
            $code = trim((string) $value);

            return $code !== '' ? $code : null;
        }

        if (! is_array($value)) {
            return null;
        }

        $code = trim((string) ($value['code'] ?? ''));

        return $code !== '' ? $code : null;
    }
}
