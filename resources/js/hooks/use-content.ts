import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { Content, ContentSections, SectionKey } from '@/types/content';

/**
 * Tokens recognised in JSON string values. Anything else is left as-is
 * so we don't accidentally mangle real text that happens to contain
 * curly braces.
 */
const TOKEN_RE = /\{(YEAR|currentYear)\}/g;

function substituteTokens<T>(value: T, year: number): T {
    if (typeof value === 'string') {
        if (!value.includes('{')) {
return value;
}

        return value.replace(TOKEN_RE, () => String(year)) as unknown as T;
    }

    if (Array.isArray(value)) {
        return value.map((v) => substituteTokens(v, year)) as unknown as T;
    }

    if (value && typeof value === 'object') {
        const out: Record<string, unknown> = {};

        for (const [k, v] of Object.entries(value as Record<string, unknown>)) {
            out[k] = substituteTokens(v, year);
        }

        return out as unknown as T;
    }

    return value;
}

/**
 * Access the storefront content tree loaded from
 * `content/hardcoded-content.json` and shared via Inertia.
 *
 * Call with no args for the full tree, or pass a section key for a
 * typed subtree:
 *
 *     const c = useContent('global_chrome');
 *     c.header.search.placeholder; // typed string
 *
 * `{YEAR}` and `{currentYear}` tokens inside string values are
 * substituted with the current year on the client, so the footer
 * copyright stays correct past midnight on Dec 31.
 */
export function useContent(): Content;
export function useContent<K extends SectionKey>(section: K): ContentSections[K];
// Untyped fallback: any string key returns Record<string, unknown> so pages
// can opt in to JSON-driven content before a full interface has been declared
// in `types/content.ts`. Cast at the destructure site to remove the unknown.
export function useContent(section: string): Record<string, unknown>;
export function useContent(section?: string) {
    const { content } = usePage().props as unknown as { content: Content };

    // Memoise on the section reference + year. The content object identity
    // is stable across renders of the same page, so this hits the cache
    // until Inertia replaces the props (i.e. on navigation).
    const year = new Date().getFullYear();
    const slice = section === undefined ? content : (content?.[section] as unknown);

    return useMemo(() => substituteTokens(slice, year), [slice, year]);
}
