import React from 'react';
import type { FaqContent } from '@/types/product-detail';

interface ProductFaqSectionProps {
    content: FaqContent;
}

export default function ProductFaqSection({ content }: ProductFaqSectionProps) {
    const { heading, items } = content;

    return (
        <section className="bg-neutral-100 py-12 lg:py-16 border-t border-neutral-200">
            <div className="mx-auto max-w-7xl px-4">
                <h2 className="mb-10 text-3xl font-extrabold tracking-tight text-neutral-900 sm:text-4xl text-center md:text-left">
                    {heading}
                </h2>

                <ul className="grid grid-cols-1 gap-x-10 gap-y-10 md:grid-cols-2 lg:grid-cols-3">
                    {items.map((item, idx) => (
                        <li key={idx} className="space-y-2">
                            <h3 className="text-base font-bold text-neutral-900 leading-snug">
                                {item.question}
                            </h3>
                            <p className="text-sm leading-relaxed text-neutral-600">
                                {item.answer}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
