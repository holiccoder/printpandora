import { Link, usePage } from '@inertiajs/react';
import type { ComponentProps } from 'react';

type Props = ComponentProps<typeof Link> & {
    unlinkedAs?: 'span' | 'div';
};

export default function StorefrontLink({
    unlinkedAs: UnlinkedElement = 'span',
    ...props
}: Props) {
    const page = usePage();
    const href = typeof props.href === 'string' ? props.href : props.href?.url;
    const baseUrl =
        typeof page.props.appUrl === 'string'
            ? page.props.appUrl
            : 'https://www.inkpavo.com';
    const pageUrl = new URL(page.url, baseUrl);
    const targetUrl = href ? new URL(href, pageUrl) : null;
    const method =
        typeof props.href === 'object'
            ? props.href.method
            : (props.method ?? 'get');
    const isBusinessCardsLanding =
        pageUrl.pathname.replace(/\/+$/, '') === '/business-cards';
    const isSelfLink =
        method === 'get' &&
        isBusinessCardsLanding &&
        targetUrl?.origin === pageUrl.origin &&
        ['/business-cards', '/products/business-cards'].includes(
            targetUrl.pathname.replace(/\/+$/, ''),
        ) &&
        !targetUrl.hash;

    if ((isBusinessCardsLanding && !href) || isSelfLink) {
        return (
            <UnlinkedElement
                id={props.id}
                title={props.title}
                className={props.className}
                style={props.style}
                aria-label={props['aria-label']}
                aria-hidden={props['aria-hidden']}
                aria-current={isSelfLink ? 'page' : undefined}
            >
                {props.children}
            </UnlinkedElement>
        );
    }

    return <Link {...props} />;
}
