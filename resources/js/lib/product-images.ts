const PVC_PRODUCT_SLUGS = new Set([
    'basic-pvc-card',
    'standard-pvc-card',
    'premium-pvc-card',
]);

/** PVC gallery images are portrait-oriented and should not be cropped. */
export function isPvcProductSlug(slug: string | null | undefined): boolean {
    return typeof slug === 'string' && PVC_PRODUCT_SLUGS.has(slug);
}
