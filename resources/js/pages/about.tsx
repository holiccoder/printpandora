import { Palette, Award, Zap, Smile, ArrowRight } from 'lucide-react';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

export default function About() {
    const c = useContent('about_page');

    // Lucide icons for the 4 pillars
    const ICONS = [
        <Palette className="size-6 text-[#800020]" />,
        <Award className="size-6 text-[#800020]" />,
        <Zap className="size-6 text-[#800020]" />,
        <Smile className="size-6 text-[#800020]" />,
    ];

    return (
        <StorefrontLayout>
            <SEO title={c.title} description={c.description} />

            {/* Hero Section */}
            <div className="relative isolate min-h-[27rem] overflow-hidden border-b border-neutral-100 bg-[#FAF7F2]">
                <img
                    src="/images/about/hero-banner.png"
                    alt=""
                    aria-hidden="true"
                    className="absolute inset-0 h-full w-full object-cover object-center"
                />
                <div className="absolute inset-0 bg-gradient-to-r from-[#FAF7F2] via-[#FAF7F2]/90 to-[#FAF7F2]/10" />
                <div className="relative mx-auto flex min-h-[27rem] max-w-7xl items-center px-4 py-20 sm:px-6 lg:px-8">
                    <div className="max-w-xl text-left">
                        <span className="text-xs font-semibold tracking-wider text-[#800020] uppercase">
                            {c.eyebrow}
                        </span>
                        <h1 className="mt-4 font-serif text-4xl font-bold tracking-tight text-neutral-900 md:text-5xl lg:text-6xl">
                            {c.title}
                        </h1>
                        <p className="mt-6 max-w-xl text-lg font-medium tracking-wide text-neutral-600 md:text-xl">
                            {c.description}
                        </p>
                    </div>
                </div>
            </div>

            {/* Split Story Section */}
            <div className="bg-white py-16 lg:py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
                        {/* Left Side: Body text */}
                        <div className="space-y-6 text-base leading-relaxed text-neutral-700">
                            {c.body_paragraphs.map((p: string, i: number) => (
                                <p
                                    key={`p-${i}`}
                                    className="first:text-lg first:text-neutral-950"
                                >
                                    {p}
                                </p>
                            ))}
                        </div>

                        {/* Right Side: Image */}
                        <div className="relative aspect-[4/3] w-full overflow-hidden rounded-2xl bg-neutral-100 shadow-md">
                            <img
                                src={c.image_url}
                                alt="Collaborative meeting inside an InkPavo conference room"
                                className="h-full w-full object-cover"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Full-width Vision Section */}
            {c.sections.map((s: any, i: number) => (
                <div
                    key={`s-${i}`}
                    className="border-y border-neutral-100/80 bg-neutral-50 py-16 lg:py-20"
                >
                    <div className="mx-auto max-w-4xl px-4 text-center">
                        <h2 className="font-serif text-3xl font-bold text-neutral-900 md:text-4xl">
                            {s.heading}
                        </h2>
                        <p className="mx-auto mt-6 max-w-3xl text-base leading-relaxed text-neutral-600">
                            {s.body}
                        </p>
                    </div>
                </div>
            ))}

            {/* Core Values / Feature Cards Section */}
            <div className="border-b border-neutral-100 bg-[#FAF7F2] py-16 lg:py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <header className="mx-auto mb-16 max-w-2xl text-center">
                        <h2 className="font-serif text-3xl font-bold text-[#800020] md:text-4xl">
                            Our Pillars of Excellence
                        </h2>
                        <p className="mt-4 text-neutral-600">
                            How we elevate your custom printing experience.
                        </p>
                    </header>

                    <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        {c.cards.map((card: any, i: number) => (
                            <div
                                key={card.title}
                                className="flex flex-col items-start rounded-2xl border border-neutral-100/60 bg-white p-6 shadow-sm transition-transform duration-300 hover:scale-[1.02]"
                            >
                                <div className="mb-5 flex size-12 items-center justify-center rounded-xl bg-[#800020]/10 text-[#800020]">
                                    {ICONS[i] ?? ICONS[0]}
                                </div>
                                <h3 className="mb-2 font-serif text-lg font-bold text-neutral-900">
                                    {card.title}
                                </h3>
                                <p className="text-sm leading-relaxed text-neutral-600">
                                    {card.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Closing Contact Section */}
            <div className="bg-white py-16 text-center">
                <div className="mx-auto max-w-3xl px-4">
                    <p className="text-lg font-medium text-neutral-700">
                        {c.closing_paragraph}
                    </p>
                    <div className="mt-8">
                        <a
                            href={c.closing_link_href}
                            className="inline-flex items-center gap-2 rounded-lg bg-[#800020] px-6 py-3 text-sm font-bold tracking-wider text-white uppercase transition hover:bg-[#800020]/90"
                        >
                            {c.closing_link_text}
                            <ArrowRight className="size-4" />
                        </a>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
