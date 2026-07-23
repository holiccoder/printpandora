import DesignServiceForm from '@/components/design-service-form';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#1e3a5f';

export default function BusinessCardDesignService() {
    const c = useContent('design_service_page');

    return (
        <StorefrontLayout>
            <SEO
                title={c.seo.title ?? 'Business Card Design Service'}
                description={c.seo.description}
            />

            {/* Intro ---------------------------------------------------------------- */}
            <section className="bg-white">
                <div className="mx-auto max-w-3xl px-4 py-16 lg:py-20">
                    <h1 className="text-3xl font-bold text-neutral-900 sm:text-4xl lg:text-5xl">
                        {c.heading}
                    </h1>
                    <p className="mt-6 text-base leading-relaxed text-neutral-700">
                        {c.intro}
                    </p>
                </div>
            </section>

            {/* Design Process ------------------------------------------------------- */}
            <section className="bg-neutral-50">
                <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                    <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                        {c.process_heading}
                    </h2>
                    <ol className="mt-6 space-y-4">
                        {c.process_steps.map((step: string, i: number) => (
                            <li key={i} className="flex gap-4">
                                <span
                                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                                    style={{ backgroundColor: ACCENT }}
                                >
                                    {i + 1}
                                </span>
                                <p className="text-sm leading-relaxed text-neutral-700">
                                    {step}
                                </p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            {/* Design Notes --------------------------------------------------------- */}
            <section className="bg-white">
                <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                    <h2 className="text-2xl font-bold text-neutral-900 sm:text-3xl">
                        {c.notes_heading}
                    </h2>
                    <ol className="mt-6 space-y-4">
                        {c.notes.map((note: string, i: number) => (
                            <li key={i} className="flex gap-4">
                                <span
                                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                                    style={{ backgroundColor: ACCENT }}
                                >
                                    {i + 1}
                                </span>
                                <p className="text-sm leading-relaxed text-neutral-700">
                                    {note}
                                </p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            {/* Form ----------------------------------------------------------------- */}
            <section className="bg-neutral-50">
                <div className="mx-auto max-w-4xl px-4 py-12 lg:py-16">
                    <div className="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm sm:p-8 lg:p-10">
                        <h2 className="text-xl font-bold text-neutral-900 sm:text-2xl">
                            {c.form_heading}
                        </h2>
                        <p className="mt-2 text-sm text-neutral-600">
                            {c.form_description}
                        </p>
                        <DesignServiceForm
                            productOptions={c.form_product_options}
                            submitLabel={c.form_submit_label}
                            className="mt-6"
                        />
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}
