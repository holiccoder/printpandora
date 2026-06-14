import { ReactNode } from 'react';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

type Props = {
    title: string;
    description: string;
    /** Optional small line under the heading (e.g. "Last updated June 2026"). */
    eyebrow?: string;
    children: ReactNode;
};

/**
 * Shared shell for static information pages (About, Terms, Privacy).
 * Wraps the page in the storefront chrome and provides a centred, narrow
 * column with consistent typography for prose-heavy content.
 */
export default function LegalPage({ title, description, eyebrow, children }: Props) {
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
                        {children}
                    </div>
                </article>
            </div>
        </StorefrontLayout>
    );
}
