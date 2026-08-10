import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';
import SEO from '@/components/seo';
import { Palette, Award, Zap, Smile, ArrowRight } from 'lucide-react';

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
            <div className="relative bg-[#FAF7F2] py-20 overflow-hidden border-b border-neutral-100">
                <div className="mx-auto max-w-5xl px-4 text-center">
                    <span className="text-xs font-semibold tracking-wider text-[#800020] uppercase">
                        {c.eyebrow}
                    </span>
                    <h1 className="mt-4 font-serif text-4xl font-bold tracking-tight text-neutral-900 md:text-5xl lg:text-6xl">
                        {c.title}
                    </h1>
                    <p className="mx-auto mt-6 max-w-xl text-lg text-neutral-600 md:text-xl font-medium tracking-wide">
                        {c.description}
                    </p>
                </div>
            </div>

            {/* Split Story Section */}
            <div className="bg-white py-16 lg:py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-12 lg:grid-cols-2 items-center">
                        {/* Left Side: Body text */}
                        <div className="space-y-6 text-neutral-700 leading-relaxed text-base">
                            {c.body_paragraphs.map((p: string, i: number) => (
                                <p key={`p-${i}`} className="first:text-lg first:text-neutral-950">
                                    {p}
                                </p>
                            ))}
                            {c.sections.map((s: any, i: number) => (
                                <div key={`s-${i}`} className="space-y-4 pt-6 border-t border-neutral-100">
                                    <h2 className="font-serif text-2xl font-bold text-neutral-900">
                                        {s.heading}
                                    </h2>
                                    <p className="text-sm leading-relaxed text-neutral-600">
                                        {s.body}
                                    </p>
                                </div>
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

            {/* Core Values / Feature Cards Section */}
            <div className="bg-[#FAF7F2] py-16 lg:py-24 border-y border-neutral-100">
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
                                className="flex flex-col items-start rounded-2xl bg-white p-6 shadow-sm border border-neutral-100/60 transition-transform duration-300 hover:scale-[1.02]"
                            >
                                <div className="flex size-12 items-center justify-center rounded-xl bg-[#800020]/10 text-[#800020] mb-5">
                                    {ICONS[i] ?? ICONS[0]}
                                </div>
                                <h3 className="text-lg font-bold text-neutral-900 mb-2 font-serif">
                                    {card.title}
                                </h3>
                                <p className="text-sm text-neutral-600 leading-relaxed">
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
                    <p className="text-lg text-neutral-700 font-medium">
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
