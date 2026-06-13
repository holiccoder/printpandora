import { Head, usePage } from '@inertiajs/react';

interface SEOProps {
    /** Page title (app name will be appended automatically) */
    title: string;
    /** Meta description (recommended: 150-160 chars) */
    description?: string;
    /** Open Graph / Twitter image URL (absolute URL recommended) */
    image?: string;
    /** Canonical URL. Defaults to current page URL if not specified. */
    canonical?: string;
    /** OG type: website, article, product, etc. */
    type?: 'website' | 'article' | 'product' | 'profile';
    /** Robots directives */
    robots?: 'index,follow' | 'noindex,follow' | 'index,nofollow' | 'noindex,nofollow';
    /** Published date for articles (ISO 8601) */
    publishedAt?: string;
    /** Modified date for articles (ISO 8601) */
    modifiedAt?: string;
    /** Author name */
    author?: string;
}

export default function SEO({
    title,
    description,
    image,
    canonical,
    type = 'website',
    robots = 'index,follow',
    publishedAt,
    modifiedAt,
    author,
}: SEOProps) {
    const { url, props } = usePage<{ appName?: string }>();
    const appName = props.appName || import.meta.env.VITE_APP_NAME || 'PrintPandora';
    const fullTitle = title ? `${title} - ${appName}` : appName;

    // Build absolute URL for canonical
    const baseUrl = typeof window !== 'undefined'
        ? window.location.origin
        : import.meta.env.VITE_APP_URL || 'http://localhost';
    const pagePath = canonical ?? url;
    const fullUrl = pagePath.startsWith('http') ? pagePath : `${baseUrl}${pagePath}`;

    // Build absolute URL for image
    const fullImageUrl = image
        ? (image.startsWith('http') ? image : `${baseUrl}${image}`)
        : undefined;

    return (
        <Head>
            {/* Primary meta tags */}
            <title>{fullTitle}</title>
            <meta name="title" content={fullTitle} />
            <meta name="robots" content={robots} />

            {description && (
                <>
                    <meta name="description" content={description} />
                    <meta property="og:description" content={description} />
                    <meta name="twitter:description" content={description} />
                </>
            )}

            {/* Open Graph / Facebook */}
            <meta property="og:type" content={type} />
            <meta property="og:url" content={fullUrl} />
            <meta property="og:title" content={fullTitle} />
            <meta property="og:site_name" content={appName} />
            <meta property="og:locale" content="en_US" />

            {fullImageUrl && (
                <>
                    <meta property="og:image" content={fullImageUrl} />
                    <meta property="og:image:width" content="1200" />
                    <meta property="og:image:height" content="630" />
                </>
            )}

            {/* Twitter Card */}
            <meta name="twitter:card" content={fullImageUrl ? 'summary_large_image' : 'summary'} />
            <meta name="twitter:url" content={fullUrl} />
            <meta name="twitter:title" content={fullTitle} />

            {fullImageUrl && <meta name="twitter:image" content={fullImageUrl} />}

            {/* Canonical */}
            <link rel="canonical" href={fullUrl} />

            {/* Article-specific tags */}
            {type === 'article' && publishedAt && (
                <meta property="article:published_time" content={publishedAt} />
            )}
            {type === 'article' && modifiedAt && (
                <meta property="article:modified_time" content={modifiedAt} />
            )}
            {type === 'article' && author && (
                <meta property="article:author" content={author} />
            )}
        </Head>
    );
}
