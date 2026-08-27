import React, { useState } from 'react';
import { Download, Info } from 'lucide-react';
import type { DesignSpecificationContent } from '@/types/product-detail';
import FileFormatIcon from './file-format-icon';

interface DesignSpecificationsSectionProps {
    content: DesignSpecificationContent;
}

export default function DesignSpecificationsSection({
    content,
}: DesignSpecificationsSectionProps) {
    const { heading, diagram, downloads = [] } = content;
    const [hoveredArea, setHoveredArea] = useState<string | null>(null);

    return (
        <section className="mt-12 bg-neutral-100 py-12 lg:py-16">
            <div className="product-detail-container mx-auto max-w-7xl px-4">
                <h2 className="mb-10 text-center text-3xl font-extrabold tracking-tight text-neutral-900 md:text-4xl lg:text-left">
                    {heading}
                </h2>

                {/* Unified Card Container */}
                <div className="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm md:p-8 lg:p-10">
                    <div className="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:items-stretch">
                        {/* Left Column: Code-drawn diagram & legend */}
                        <div className="flex flex-col space-y-8 lg:col-span-7">
                            <div>
                                <h3 className="mb-1 text-xl font-bold text-neutral-900">
                                    Specifications Diagram
                                </h3>
                                <p className="text-sm text-neutral-500">
                                    Hover over the diagram or list items to
                                    highlight each design zone and check
                                    dimensions.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
                                {/* Responsive Diagram container */}
                                <div
                                    className="relative flex items-center justify-center rounded-xl border border-neutral-100 bg-neutral-50 p-6 transition-all"
                                    aria-label="Interactive design specification diagram showing bleed, trim, and safe areas"
                                >
                                    {/* Card Outline mimicking Bleed Area (Outer boundaries) */}
                                    <div
                                        onMouseEnter={() =>
                                            setHoveredArea('bleed')
                                        }
                                        onMouseLeave={() =>
                                            setHoveredArea(null)
                                        }
                                        className={`relative aspect-[3.66/2.16] w-full max-w-md cursor-pointer rounded-lg border-4 bg-white shadow-sm transition-all duration-300 ${
                                            hoveredArea === 'bleed'
                                                ? 'scale-[1.02] border-[#800020] ring-4 ring-[#800020]/20'
                                                : 'border-[#800020]/40'
                                        }`}
                                    >
                                        {/* Trim Line (Solid inner line) */}
                                        <div
                                            onMouseEnter={(e) => {
                                                e.stopPropagation();
                                                setHoveredArea('trim');
                                            }}
                                            onMouseLeave={() =>
                                                setHoveredArea(null)
                                            }
                                            className={`absolute inset-3 cursor-pointer rounded border-2 transition-all duration-300 ${
                                                hoveredArea === 'trim'
                                                    ? 'scale-[1.01] border-[#800020] ring-2 ring-[#800020]/20'
                                                    : 'border-solid border-neutral-400'
                                            }`}
                                        >
                                            {/* Safe Area (Dotted inner line) */}
                                            <div
                                                onMouseEnter={(e) => {
                                                    e.stopPropagation();
                                                    setHoveredArea('safe_area');
                                                }}
                                                onMouseLeave={() =>
                                                    setHoveredArea(null)
                                                }
                                                className={`absolute inset-3 flex cursor-pointer items-center justify-center rounded bg-neutral-50/50 transition-all duration-300 ${
                                                    hoveredArea === 'safe_area'
                                                        ? 'border-2 border-dashed border-[#800020] bg-[#800020]/5'
                                                        : 'border border-dashed border-neutral-300'
                                                }`}
                                            >
                                                <div className="p-2 text-center">
                                                    <span
                                                        className={`text-[10px] font-semibold tracking-wider uppercase transition-colors select-none ${
                                                            hoveredArea ===
                                                            'safe_area'
                                                                ? 'font-bold text-[#800020]'
                                                                : 'text-neutral-400'
                                                        }`}
                                                    >
                                                        Safe Area
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Trim label in diagram */}
                                            <span
                                                className={`absolute right-2 bottom-1 text-[9px] font-semibold tracking-wider uppercase select-none ${
                                                    hoveredArea === 'trim'
                                                        ? 'font-bold text-[#800020]'
                                                        : 'text-neutral-400'
                                                }`}
                                            >
                                                Trim Line
                                            </span>
                                        </div>

                                        {/* Bleed label in diagram */}
                                        <span
                                            className={`absolute top-1 left-2 text-[9px] font-bold tracking-wider uppercase select-none ${
                                                hoveredArea === 'bleed'
                                                    ? 'text-[#800020]'
                                                    : 'text-[#800020]/70'
                                            }`}
                                        >
                                            Bleed Area
                                        </span>
                                    </div>
                                </div>

                                {/* Interactive Legend List */}
                                <div className="space-y-4">
                                    {/* Bleed Area item */}
                                    <div
                                        onMouseEnter={() =>
                                            setHoveredArea('bleed')
                                        }
                                        onMouseLeave={() =>
                                            setHoveredArea(null)
                                        }
                                        className={`cursor-pointer rounded-xl border p-3.5 transition-all duration-200 ${
                                            hoveredArea === 'bleed'
                                                ? 'border-[#800020]/20 bg-[#800020]/5 shadow-sm'
                                                : 'border-neutral-100 bg-white'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span
                                                className="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-[#800020] bg-[#800020]/10"
                                                aria-hidden="true"
                                            />
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="text-sm font-bold text-neutral-800">
                                                        {diagram.bleed.label}
                                                    </h4>
                                                    <span className="rounded border border-[#800020]/20 bg-[#800020]/10 px-1.5 py-0.5 text-[10px] font-semibold text-[#800020]">
                                                        {
                                                            diagram.bleed
                                                                .dimensions
                                                        }
                                                    </span>
                                                </div>
                                                <p className="text-xs leading-relaxed text-neutral-600">
                                                    {diagram.bleed.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Trim Line item */}
                                    <div
                                        onMouseEnter={() =>
                                            setHoveredArea('trim')
                                        }
                                        onMouseLeave={() =>
                                            setHoveredArea(null)
                                        }
                                        className={`cursor-pointer rounded-xl border p-3.5 transition-all duration-200 ${
                                            hoveredArea === 'trim'
                                                ? 'border-[#800020]/20 bg-[#800020]/5 shadow-sm'
                                                : 'border-neutral-100 bg-white'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span
                                                className="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-[#800020] bg-[#800020]/10"
                                                aria-hidden="true"
                                            />
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="text-sm font-bold text-neutral-800">
                                                        {diagram.trim.label}
                                                    </h4>
                                                    <span className="rounded border border-[#800020]/20 bg-[#800020]/10 px-1.5 py-0.5 text-[10px] font-semibold text-[#800020]">
                                                        {
                                                            diagram.trim
                                                                .dimensions
                                                        }
                                                    </span>
                                                </div>
                                                <p className="text-xs leading-relaxed text-neutral-600">
                                                    {diagram.trim.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Safe Area item */}
                                    <div
                                        onMouseEnter={() =>
                                            setHoveredArea('safe_area')
                                        }
                                        onMouseLeave={() =>
                                            setHoveredArea(null)
                                        }
                                        className={`cursor-pointer rounded-xl border p-3.5 transition-all duration-200 ${
                                            hoveredArea === 'safe_area'
                                                ? 'border-[#800020]/20 bg-[#800020]/5 shadow-sm'
                                                : 'border-neutral-100 bg-white'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span
                                                className="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-dashed border-[#800020] bg-[#800020]/10"
                                                aria-hidden="true"
                                            />
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="text-sm font-bold text-neutral-800">
                                                        {
                                                            diagram.safe_area
                                                                .label
                                                        }
                                                    </h4>
                                                    <span className="rounded border border-[#800020]/20 bg-[#800020]/10 px-1.5 py-0.5 text-[10px] font-semibold text-[#800020]">
                                                        {
                                                            diagram.safe_area
                                                                .dimensions
                                                        }
                                                    </span>
                                                </div>
                                                <p className="text-xs leading-relaxed text-neutral-600">
                                                    {
                                                        diagram.safe_area
                                                            .description
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Download guideline templates */}
                        <div className="flex h-full flex-col justify-between lg:col-span-5 lg:border-l lg:border-neutral-100 lg:pl-10">
                            <div>
                                <h3 className="mb-2 text-xl font-bold text-neutral-900">
                                    Download a Design Guideline
                                </h3>
                                <p className="mb-6 text-sm leading-relaxed text-neutral-500">
                                    Use our pre-formatted templates to ensure
                                    your designs are sized perfectly and adhere
                                    to our safety standards.
                                </p>

                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1">
                                    {downloads.map((item) => (
                                        <a
                                            key={item.id}
                                            href={item.href}
                                            download
                                            className="group flex items-center justify-between rounded-xl border border-neutral-100 bg-neutral-50/50 p-4 transition-all duration-200 hover:border-neutral-200 hover:bg-white hover:shadow-sm"
                                            aria-label={`Download ${item.label} (${item.extension}) template guideline`}
                                        >
                                            <div className="flex items-center gap-3">
                                                <FileFormatIcon
                                                    id={item.id}
                                                    extension={item.extension}
                                                    color={item.color}
                                                    className="transition-transform group-hover:scale-105"
                                                />
                                                <div>
                                                    <h4 className="text-sm font-bold text-neutral-800 group-hover:text-neutral-900">
                                                        {item.label} Template
                                                    </h4>
                                                    <p className="text-xs text-neutral-400">
                                                        {['illustrator', 'indesign', 'photoshop'].includes(item.id) ? 'Adobe ' : ''}
                                                        {item.label}{' '}
                                                        format ({item.extension}
                                                        )
                                                    </p>
                                                </div>
                                            </div>
                                            <span className="flex h-8 w-8 items-center justify-center rounded-full border border-neutral-100 bg-white text-neutral-500 shadow-sm transition-colors duration-200 group-hover:border-neutral-900 group-hover:bg-neutral-900 group-hover:text-white">
                                                <Download className="size-4" />
                                            </span>
                                        </a>
                                    ))}
                                </div>
                            </div>

                            <div className="mt-8 flex items-start gap-2.5 border-t border-neutral-100 pt-6 text-xs text-neutral-500">
                                <Info className="mt-0.5 size-4 shrink-0 text-neutral-400" />
                                <p className="leading-relaxed">
                                    Not sure how to design? Feel free to contact
                                    our expert support team or check our design
                                    service options below.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
