import React from 'react';
import { ChevronRight } from 'lucide-react';
import { Link } from '@inertiajs/react';
import type { MoreGoodStuffContent } from '@/types/product-detail';

interface MoreGoodStuffSectionProps {
    content: MoreGoodStuffContent;
}

export default function MoreGoodStuffSection({
    content,
}: MoreGoodStuffSectionProps) {
    const { heading, items } = content;

    return (
        <section className="bg-[#f9f8f6] py-12 lg:py-16">
            <div className="mx-auto max-w-7xl px-4">
                <header className="mb-10 text-center">
                    <h2 className="text-3xl font-extrabold tracking-tight text-neutral-900 sm:text-4xl">
                        {heading}
                    </h2>
                </header>

                <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="group flex flex-col items-center text-center"
                        >
                            {/* Card Square Image Container */}
                            <div className="aspect-square w-full overflow-hidden rounded-2xl border border-neutral-200/50 bg-neutral-100 shadow-sm transition-all duration-300 group-hover:border-neutral-200 group-hover:shadow-md">
                                <img
                                    src={item.image_url}
                                    alt={item.name}
                                    loading="lazy"
                                    className="h-full w-full transform object-cover transition-transform duration-500 hover:scale-105"
                                />
                            </div>

                            {/* Card Content & Link */}
                            <div className="mt-4">
                                <Link
                                    href={item.href}
                                    className="inline-flex items-center gap-1 text-sm font-bold text-[#0f4c3a] transition-colors hover:text-[#0c3e2f] hover:underline"
                                >
                                    {item.link_label}
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
