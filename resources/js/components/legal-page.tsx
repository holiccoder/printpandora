import type { ReactNode } from 'react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';
import type { LegalSection } from '@/types/content';

type Props = {
    title: string;
    description: string;
    /** Optional small line under the heading (e.g. "Last updated June 2026"). */
    eyebrow?: string;
    /** Optional intro paragraph rendered before the section list. */
    intro?: string;
    /** Structured sections rendered after the intro. Coming from the JSON content tree. */
    sections?: LegalSection[];
    /** Optional closing paragraph after the sections (used by the About page). */
    closingParagraph?: string;
    closingLinkText?: string;
    closingLinkHref?: string;
    /** Additional body content (paragraphs that should follow the intro before sections). */
    bodyParagraphs?: string[];
    /** Optional escape hatch for callers that still want to render custom JSX bodies. */
    children?: ReactNode;
};

/**
 * Shared shell for static information pages (About, Terms, Privacy).
 * Wraps the page in the storefront chrome and provides a centred, narrow
 * column with consistent typography for prose-heavy content.
 *
 * Pages should drive their copy from `content/hardcoded-content.json` —
 * pass `intro` / `bodyParagraphs` / `sections` rather than JSX children.
 * The `children` prop is kept as an escape hatch for pages that still
 * need bespoke JSX.
 */
export default function LegalPage({
    title,
    description,
    eyebrow,
    intro,
    sections,
    closingParagraph,
    closingLinkText,
    closingLinkHref,
    bodyParagraphs,
    children,
}: Props) {
    return (
        <StorefrontLayout>
            <SEO title={title} description={description} />
            <div className="bg-white py-14 md:py-20">
                <article className="mx-auto w-full max-w-3xl px-4 md:px-6">
                    {eyebrow && (
                        <p className="text-xs font-semibold uppercase tracking-wider text-[#0f4c3a]">
                            {eyebrow}
                        </p>
                    )}
                    <h1 className="mt-2 text-3xl font-bold tracking-tight text-neutral-900 md:text-4xl lg:text-5xl">
                        {title}
                    </h1>
                    <div
                        className="
                            mt-8 space-y-6 text-base leading-relaxed text-neutral-700
                            [&_h2]:mt-10 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:text-neutral-900
                            [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-neutral-900
                            [&_p]:text-base [&_p]:leading-relaxed
                            [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-6
                            [&_ol]:list-decimal [&_ol]:space-y-2 [&_ol]:pl-6
                            [&_a]:text-[#0f4c3a] [&_a]:underline-offset-2 hover:[&_a]:underline
                            [&_strong]:font-semibold [&_strong]:text-neutral-900
                        "
                    >
                        {intro && <p>{intro}</p>}
                        {bodyParagraphs?.map((p, i) => <p key={`body-${i}`}>{p}</p>)}
                        {sections?.map((s, i) => (
                            <Section key={`section-${i}`} section={s} />
                        ))}
                        {closingParagraph && (
                            <p>
                                {closingParagraph}{' '}
                                {closingLinkText && closingLinkHref && (
                                    <a href={closingLinkHref}>{closingLinkText}</a>
                                )}
                            </p>
                        )}
                        {children}
                    </div>
                </article>
            </div>
        </StorefrontLayout>
    );
}

/** Render a single structured section (heading + body + bullets). */
function Section({ section }: { section: LegalSection }) {
    const HeadingTag = (section.level === 'h3' ? 'h3' : 'h2') as 'h2' | 'h3';

    return (
        <>
            {section.heading && <HeadingTag>{section.heading}</HeadingTag>}
            {section.body && <BodyWithLinks section={section} />}
            {section.list_items && section.list_items.length > 0 && (
                <ul>
                    {section.list_items.map((item, i) => (
                        <li key={i}>
                            {typeof item === 'string' ? (
                                item
                            ) : (
                                <>
                                    {item.strong && <strong>{item.strong}</strong>}
                                    {item.strong && item.text && ' '}
                                    {item.text}
                                </>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}

/**
 * Render the section body, auto-linking the email and contact-link
 * placeholders from the JSON when present. We keep this targeted to the
 * known JSON conventions (email_link, contact_link_text/href) rather
 * than invent a full inline-markup format.
 */
function BodyWithLinks({ section }: { section: LegalSection }) {
    const { body, email_link, contact_link_text, contact_link_href } = section;

    if (!body) {
return null;
}

    let nodes: ReactNode[] = [body];

    if (email_link) {
        nodes = splitAround(nodes, email_link, (key) => (
            <a key={key} href={`mailto:${email_link}`}>
                {email_link}
            </a>
        ));
    }

    if (contact_link_text && contact_link_href) {
        nodes = splitAround(nodes, contact_link_text, (key) => (
            <a key={key} href={contact_link_href}>
                {contact_link_text}
            </a>
        ));
    }

    return <p>{nodes}</p>;
}

/**
 * Split each string node in `nodes` around the first occurrence of
 * `needle`, inserting the result of `make(key)` in its place.
 */
function splitAround(
    nodes: ReactNode[],
    needle: string,
    make: (key: string) => ReactNode,
): ReactNode[] {
    const out: ReactNode[] = [];
    let counter = 0;
    nodes.forEach((node) => {
        if (typeof node !== 'string') {
            out.push(node);

            return;
        }

        const idx = node.indexOf(needle);

        if (idx === -1) {
            out.push(node);

            return;
        }

        if (idx > 0) {
out.push(node.slice(0, idx));
}

        out.push(make(`${needle}-${counter++}`));
        const tail = node.slice(idx + needle.length);

        if (tail) {
out.push(tail);
}
    });

    return out;
}
