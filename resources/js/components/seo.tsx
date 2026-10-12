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
    robots?:
        | 'index,follow'
        | 'noindex,follow'
        | 'index,nofollow'
        | 'noindex,nofollow';
    /** Published date for articles (ISO 8601) */
    publishedAt?: string;
    /** Modified date for articles (ISO 8601) */
    modifiedAt?: string;
    /** Author name */
    author?: string;
}

interface PageProps extends Record<string, unknown> {
    appName?: string;
    appUrl?: string;
    seo?: {
        title?: string;
        description?: string;
        image?: string | null;
        canonical?: string;
        type?: SEOProps['type'];
        robots?: SEOProps['robots'];
        published_at?: string | null;
        modified_at?: string | null;
        author?: string | null;
        is_authoritative?: boolean;
    };
}

function withAppName(title: string, appName: string): string {
    const trimmedTitle = title.trim();

    if (!trimmedTitle) {
        return appName;
    }

    return trimmedTitle.toLocaleLowerCase().endsWith(appName.toLocaleLowerCase())
        ? trimmedTitle
        : `${trimmedTitle} - ${appName}`;
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
    const { url, props } = usePage<PageProps>();
    const appName = props.appName || import.meta.env.VITE_APP_NAME || 'InkPavo';
    const pageMetadata = props.seo?.is_authoritative ? props.seo : undefined;
    const resolvedTitle = pageMetadata?.title ?? title;
    const resolvedDescription = pageMetadata?.description ?? description;
    const resolvedImage = pageMetadata?.image ?? image;
    const resolvedCanonical = props.seo?.canonical ?? canonical ?? url;
    const resolvedType = pageMetadata?.type ?? type;
    const resolvedRobots = props.seo?.robots ?? robots;
    const resolvedPublishedAt = pageMetadata?.published_at ?? publishedAt;
    const resolvedModifiedAt = pageMetadata?.modified_at ?? modifiedAt;
    const resolvedAuthor = pageMetadata?.author ?? author;
    const fullTitle = withAppName(resolvedTitle, appName);

    // Keep canonical and social URLs on the configured production host and
    // strip query strings so filters and tracking parameters do not split URLs.
    const baseUrl =
        props.appUrl ||
        (typeof window !== 'undefined'
            ? window.location.origin
            : import.meta.env.VITE_APP_URL || 'http://localhost');
    const canonicalUrl = new URL(resolvedCanonical, baseUrl);
    canonicalUrl.search = '';
    canonicalUrl.hash = '';
    const fullUrl = canonicalUrl.toString();

    // Build absolute URL for image
    const fullImageUrl = resolvedImage
        ? new URL(resolvedImage, baseUrl).toString()
        : undefined;

    return (
        <Head>
            {/* Primary meta tags */}
            <title>{fullTitle}</title>
            <meta head-key="title" name="title" content={fullTitle} />
            <meta head-key="robots" name="robots" content={resolvedRobots} />

            {resolvedDescription && (
                <>
                    <meta
                        head-key="description"
                        name="description"
                        content={resolvedDescription}
                    />
                    <meta
                        head-key="og:description"
                        property="og:description"
                        content={resolvedDescription}
                    />
                    <meta
                        head-key="twitter:description"
                        name="twitter:description"
                        content={resolvedDescription}
                    />
                </>
            )}

            {/* Open Graph / Facebook */}
            <meta
                head-key="og:type"
                property="og:type"
                content={resolvedType}
            />
            <meta head-key="og:url" property="og:url" content={fullUrl} />
            <meta
                head-key="og:title"
                property="og:title"
                content={fullTitle}
            />
            <meta
                head-key="og:site_name"
                property="og:site_name"
                content={appName}
            />
            <meta
                head-key="og:locale"
                property="og:locale"
                content="en_US"
            />

            {fullImageUrl && (
                <>
                    <meta
                        head-key="og:image"
                        property="og:image"
                        content={fullImageUrl}
                    />
                </>
            )}

            {/* Twitter Card */}
            <meta
                head-key="twitter:card"
                name="twitter:card"
                content={fullImageUrl ? 'summary_large_image' : 'summary'}
            />
            <meta head-key="twitter:url" name="twitter:url" content={fullUrl} />
            <meta
                head-key="twitter:title"
                name="twitter:title"
                content={fullTitle}
            />

            {fullImageUrl && (
                <meta
                    head-key="twitter:image"
                    name="twitter:image"
                    content={fullImageUrl}
                />
            )}

            {/* Canonical */}
            <link head-key="canonical" rel="canonical" href={fullUrl} />

            {/* Article-specific tags */}
            {resolvedType === 'article' && resolvedPublishedAt && (
                <meta
                    head-key="article:published_time"
                    property="article:published_time"
                    content={resolvedPublishedAt}
                />
            )}
            {resolvedType === 'article' && resolvedModifiedAt && (
                <meta
                    head-key="article:modified_time"
                    property="article:modified_time"
                    content={resolvedModifiedAt}
                />
            )}
            {resolvedType === 'article' && resolvedAuthor && (
                <meta
                    head-key="article:author"
                    property="article:author"
                    content={resolvedAuthor}
                />
            )}
        </Head>
    );
}
