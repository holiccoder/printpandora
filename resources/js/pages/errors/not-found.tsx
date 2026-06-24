import { Link } from '@inertiajs/react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

export default function NotFound() {
    const c = useContent('not_found_page');

    return (
        <StorefrontLayout>
            <SEO
                title={c.seo.title}
                description={c.seo.description}
                robots={c.seo.robots as 'noindex,follow'}
            />

            <div className="bg-white py-20 md:py-28 lg:py-36">
                <div className="mx-auto max-w-2xl px-4 text-center md:px-6">
                    {/* Large 404 graphic */}
                    <p className="text-[120px] font-bold leading-none tracking-tighter text-[#0f4c3a] md:text-[180px] lg:text-[220px]">
                        {c.graphic_text}
                    </p>

                    {/* Heading */}
                    <h1 className="-mt-4 text-2xl font-bold tracking-tight text-neutral-900 md:text-3xl lg:text-4xl">
                        {c.heading}
                    </h1>

                    {/* Description */}
                    <p className="mx-auto mt-4 max-w-md text-base leading-relaxed text-neutral-600">
                        {c.description}
                    </p>

                    {/* Action buttons — first link is the primary "home" CTA. */}
                    <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        {c.action_links.map((link, i) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={
                                    i === 0
                                        ? 'inline-flex items-center rounded-lg bg-[#0f4c3a] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0d3f30]'
                                        : 'inline-flex items-center rounded-lg border border-neutral-300 bg-white px-6 py-2.5 text-sm font-semibold text-neutral-700 shadow-sm transition hover:bg-neutral-50'
                                }
                            >
                                {link.label}
                            </Link>
                        ))}
                    </div>

                    {/* Quick links grid */}
                    <div className="mt-16 border-t border-neutral-100 pt-10">
                        <p className="mb-6 text-sm font-semibold text-neutral-500">
                            {c.quick_links_heading}
                        </p>
                        <div className="grid grid-cols-2 gap-x-6 gap-y-3 text-left">
                            {c.quick_links.map((link) => (
                                <Link
                                    key={link.href}
                                    href={link.href}
                                    className="text-sm text-[#0f4c3a] transition hover:underline"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}

export { NotFound };
