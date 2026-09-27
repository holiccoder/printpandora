const BUSINESS_CARD_PRODUCT_PATHS: Record<string, string> = {
    'basic-cotton-business-card': '/business-cards/basic-cotton',
    'classic-cotton-business-card': '/business-cards/classic-cotton',
    'premium-cotton-business-card': '/business-cards/premium-cotton',
    'luxe-cotton-business-card': '/business-cards/luxe-cotton',
    'grand-cotton-business-card': '/business-cards/grand-cotton',
    'super-standard-business-cards': '/business-cards/super-standard',
    'super-luxe-business-cards': '/business-cards/super-luxe',
    'basic-pvc-card': '/business-cards/basic-pvc',
    'standard-pvc-card': '/business-cards/standard-pvc',
    'premium-pvc-card': '/business-cards/premium-pvc',
    'classic-metal-business-cards': '/business-cards/classic-metal',
    'premium-metal-business-cards': '/business-cards/premium-metal',
    'luxe-metal-business-cards': '/business-cards/luxe-metal',
    'classic-standard-business-cards': '/business-cards/classic-standard',
    'classic-special-business-cards': '/business-cards/classic-special',
    'standard-quality-business-cards': '/business-cards/standard-quality',
    'solid-quality-business-cards': '/business-cards/solid-quality',
    'design-service': '/business-card-design-service',
};

const STICKER_PRODUCT_PATHS: Record<string, string> = {
    'classic-stickers': '/stickers/classic',
    'premium-stickers': '/stickers/premium',
    'super-stickers': '/stickers/super',
};

const POSTCARD_PRODUCT_PATHS: Record<string, string> = {
    'classic-standard-postcards': '/postcards/classic-standard',
    'classic-special-postcards': '/postcards/classic-special',
    'super-standard-postcards': '/postcards/super-standard',
    'super-luxe-postcards': '/postcards/super-luxe',
    'quality-standard-postcards': '/postcards/quality-standard',
    'quality-solid-postcards': '/postcards/quality-solid',
};

export function productHref(slug: string): string {
    const normalizedSlug = slug.replace(/^\/+/, '');

    return (
        BUSINESS_CARD_PRODUCT_PATHS[normalizedSlug] ??
        POSTCARD_PRODUCT_PATHS[normalizedSlug] ??
        STICKER_PRODUCT_PATHS[normalizedSlug] ??
        `/${normalizedSlug}`
    );
}
