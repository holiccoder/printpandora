import React from 'react';
import { ChevronRight, Check } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { PaperStockContent } from '@/types/product-detail';

interface PaperStockComparisonSectionProps {
    content: PaperStockContent;
}

export default function PaperStockComparisonSection({ content }: PaperStockComparisonSectionProps) {
    const { heading, subtitle, items } = content;

    return (
        <section className="bg-white py-12 lg:py-16">
            <div className="mx-auto max-w-7xl px-4">
                <header className="mb-12 text-center max-w-3xl mx-auto">
                    <h2 className="text-3xl font-extrabold tracking-tight text-neutral-900 sm:text-4xl">
                        {heading}
                    </h2>
                    <p className="mt-4 text-base text-neutral-500 leading-relaxed">
                        {subtitle}
                    </p>
                </header>

                <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="flex flex-col h-full bg-neutral-50 rounded-2xl border border-neutral-100 overflow-hidden shadow-sm hover:shadow-md hover:border-neutral-200 transition-all duration-300"
                        >
                            {/* Card Image */}
                            <div className="aspect-[4/3] w-full overflow-hidden bg-neutral-100">
                                <img
                                    src={item.image_url}
                                    alt={item.name}
                                    loading="lazy"
                                    className="h-full w-full object-cover transform hover:scale-105 transition-transform duration-500"
                                />
                            </div>

                            {/* Card Details */}
                            <div className="flex flex-col flex-grow p-6">
                                <h3 className="text-lg font-bold text-neutral-900">
                                    {item.name}
                                </h3>
                                <p className="mt-1 text-sm font-semibold text-[#0f4c3a]">
                                    {item.price}
                                </p>

                                {/* Features List */}
                                <ul className="mt-5 mb-8 space-y-2.5 flex-grow">
                                    {item.features.map((feature, idx) => (
                                        <li key={idx} className="flex items-start gap-2 text-xs text-neutral-600">
                                            <Check className="size-4 shrink-0 text-[#0f4c3a] mt-0.5" />
                                            <span className="leading-relaxed">{feature}</span>
                                        </li>
                                    ))}
                                </ul>

                                {/* CTA Link */}
                                <div className="mt-auto">
                                    <Link
                                        href={item.href}
                                        className="inline-flex w-full items-center justify-center gap-1 rounded-xl bg-neutral-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-[#0f4c3a] transition-all duration-200 text-center shadow-sm"
                                    >
                                        {item.cta}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
