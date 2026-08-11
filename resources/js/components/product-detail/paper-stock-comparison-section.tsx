import React from 'react';
import { ChevronRight, Check } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { PaperStockContent } from '@/types/product-detail';

interface PaperStockComparisonSectionProps {
    content: PaperStockContent;
}

export default function PaperStockComparisonSection({
    content,
}: PaperStockComparisonSectionProps) {
    const { heading, subtitle, items } = content;

    return (
        <section className="bg-white py-12 lg:py-16">
            <div className="mx-auto max-w-7xl px-4">
                <header className="mx-auto mb-12 max-w-3xl text-center">
                    <h2 className="text-3xl font-extrabold tracking-tight text-neutral-900 sm:text-4xl">
                        {heading}
                    </h2>
                    <p className="mt-4 text-base leading-relaxed text-neutral-500">
                        {subtitle}
                    </p>
                </header>

                <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="flex h-full flex-col overflow-hidden rounded-2xl border border-neutral-100 bg-neutral-50 shadow-sm transition-all duration-300 hover:border-neutral-200 hover:shadow-md"
                        >
                            {/* Card Image */}
                            <div className="aspect-[4/3] w-full overflow-hidden bg-neutral-100">
                                <img
                                    src={item.image_url}
                                    alt={item.name}
                                    loading="lazy"
                                    className="h-full w-full transform object-cover transition-transform duration-500 hover:scale-105"
                                />
                            </div>

                            {/* Card Details */}
                            <div className="flex flex-grow flex-col p-6">
                                <h3 className="text-lg font-bold text-neutral-900">
                                    {item.name}
                                </h3>
                                <p className="mt-1 text-sm font-semibold text-[#0f4c3a]">
                                    {item.price}
                                </p>

                                {/* Features List */}
                                <ul className="mt-5 mb-8 flex-grow space-y-2.5">
                                    {item.features.map((feature, idx) => (
                                        <li
                                            key={idx}
                                            className="flex items-start gap-2 text-xs text-neutral-600"
                                        >
                                            <Check className="mt-0.5 size-4 shrink-0 text-[#0f4c3a]" />
                                            <span className="leading-relaxed">
                                                {feature}
                                            </span>
                                        </li>
                                    ))}
                                </ul>

                                {/* CTA Link */}
                                <div className="mt-auto">
                                    <Link
                                        href={item.href}
                                        className="inline-flex w-full items-center justify-center gap-1 rounded-xl bg-neutral-900 px-4 py-2.5 text-center text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#0f4c3a]"
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
