import React, { useState } from 'react';
import { Download, Info } from 'lucide-react';
import type { DesignSpecificationContent } from '@/types/product-detail';
import FileFormatIcon from './file-format-icon';

interface DesignSpecificationsSectionProps {
    content: DesignSpecificationContent;
}

export default function DesignSpecificationsSection({ content }: DesignSpecificationsSectionProps) {
    const { heading, diagram, downloads = [] } = content;
    const [hoveredArea, setHoveredArea] = useState<string | null>(null);

    return (
        <section className="mt-12 bg-neutral-100 py-12 lg:py-16">
            <div className="mx-auto max-w-7xl px-4">
                <h2 className="mb-10 text-3xl font-extrabold tracking-tight text-neutral-900 md:text-4xl text-center lg:text-left">
                    {heading}
                </h2>

                {/* Unified Card Container */}
                <div className="bg-white rounded-2xl border border-neutral-200 p-6 md:p-8 lg:p-10 shadow-sm">
                    <div className="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:items-stretch">
                        
                        {/* Left Column: Code-drawn diagram & legend */}
                        <div className="flex flex-col space-y-8 lg:col-span-7">
                            <div>
                                <h3 className="text-xl font-bold text-neutral-900 mb-1">Specifications Diagram</h3>
                                <p className="text-sm text-neutral-500">
                                    Hover over the diagram or list items to highlight each design zone and check dimensions.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
                                {/* Responsive Diagram container */}
                                <div
                                    className="relative flex items-center justify-center p-6 bg-neutral-50 rounded-xl border border-neutral-100 transition-all"
                                    aria-label="Interactive design specification diagram showing bleed, trim, and safe areas"
                                >
                                    {/* Card Outline mimicking Bleed Area (Outer boundaries) */}
                                    <div
                                        onMouseEnter={() => setHoveredArea('bleed')}
                                        onMouseLeave={() => setHoveredArea(null)}
                                        className={`relative aspect-[3.66/2.16] w-full max-w-md rounded-lg border-4 transition-all duration-300 bg-white shadow-sm cursor-pointer ${
                                            hoveredArea === 'bleed'
                                                ? 'border-[#800020] ring-4 ring-[#800020]/20 scale-[1.02]'
                                                : 'border-[#800020]/40'
                                        }`}
                                    >
                                        {/* Trim Line (Solid inner line) */}
                                        <div
                                            onMouseEnter={(e) => {
                                                e.stopPropagation();
                                                setHoveredArea('trim');
                                            }}
                                            onMouseLeave={() => setHoveredArea(null)}
                                            className={`absolute inset-3 rounded border-2 transition-all duration-300 cursor-pointer ${
                                                hoveredArea === 'trim'
                                                    ? 'border-[#800020] ring-2 ring-[#800020]/20 scale-[1.01]'
                                                    : 'border-neutral-400 border-solid'
                                            }`}
                                        >
                                            {/* Safe Area (Dotted inner line) */}
                                            <div
                                                onMouseEnter={(e) => {
                                                    e.stopPropagation();
                                                    setHoveredArea('safe_area');
                                                }}
                                                onMouseLeave={() => setHoveredArea(null)}
                                                className={`absolute inset-3 rounded transition-all duration-300 cursor-pointer flex items-center justify-center bg-neutral-50/50 ${
                                                    hoveredArea === 'safe_area'
                                                        ? 'border-2 border-dashed border-[#800020] bg-[#800020]/5'
                                                        : 'border border-dashed border-neutral-300'
                                                }`}
                                            >
                                                <div className="text-center p-2">
                                                    <span className={`text-[10px] uppercase tracking-wider font-semibold select-none transition-colors ${
                                                        hoveredArea === 'safe_area' ? 'text-[#800020] font-bold' : 'text-neutral-400'
                                                    }`}>
                                                        Safe Area
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Trim label in diagram */}
                                            <span className={`absolute bottom-1 right-2 text-[9px] uppercase tracking-wider font-semibold select-none ${
                                                hoveredArea === 'trim' ? 'text-[#800020] font-bold' : 'text-neutral-400'
                                            }`}>
                                                Trim Line
                                            </span>
                                        </div>

                                        {/* Bleed label in diagram */}
                                        <span className={`absolute top-1 left-2 text-[9px] uppercase tracking-wider font-bold select-none ${
                                            hoveredArea === 'bleed' ? 'text-[#800020]' : 'text-[#800020]/70'
                                        }`}>
                                            Bleed Area
                                        </span>
                                    </div>
                                </div>

                                {/* Interactive Legend List */}
                                <div className="space-y-4">
                                    {/* Bleed Area item */}
                                    <div
                                        onMouseEnter={() => setHoveredArea('bleed')}
                                        onMouseLeave={() => setHoveredArea(null)}
                                        className={`p-3.5 rounded-xl border transition-all duration-200 cursor-pointer ${
                                            hoveredArea === 'bleed'
                                                ? 'bg-[#800020]/5 border-[#800020]/20 shadow-sm'
                                                : 'bg-white border-neutral-100'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-[#800020] bg-[#800020]/10" aria-hidden="true" />
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="text-sm font-bold text-neutral-800">{diagram.bleed.label}</h4>
                                                    <span className="px-1.5 py-0.5 text-[10px] font-semibold text-[#800020] bg-[#800020]/10 rounded border border-[#800020]/20">
                                                        {diagram.bleed.dimensions}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-neutral-600 leading-relaxed">
                                                    {diagram.bleed.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Trim Line item */}
                                    <div
                                        onMouseEnter={() => setHoveredArea('trim')}
                                        onMouseLeave={() => setHoveredArea(null)}
                                        className={`p-3.5 rounded-xl border transition-all duration-200 cursor-pointer ${
                                            hoveredArea === 'trim'
                                                ? 'bg-[#800020]/5 border-[#800020]/20 shadow-sm'
                                                : 'bg-white border-neutral-100'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 border-[#800020] bg-[#800020]/10" aria-hidden="true" />
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="text-sm font-bold text-neutral-800">{diagram.trim.label}</h4>
                                                    <span className="px-1.5 py-0.5 text-[10px] font-semibold text-[#800020] bg-[#800020]/10 rounded border border-[#800020]/20">
                                                        {diagram.trim.dimensions}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-neutral-600 leading-relaxed">
                                                    {diagram.trim.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Safe Area item */}
                                    <div
                                        onMouseEnter={() => setHoveredArea('safe_area')}
                                        onMouseLeave={() => setHoveredArea(null)}
                                        className={`p-3.5 rounded-xl border transition-all duration-200 cursor-pointer ${
                                            hoveredArea === 'safe_area'
                                                ? 'bg-[#800020]/5 border-[#800020]/20 shadow-sm'
                                                : 'bg-white border-neutral-100'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded border border-dashed border-[#800020] bg-[#800020]/10" aria-hidden="true" />
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <h4 className="text-sm font-bold text-neutral-800">{diagram.safe_area.label}</h4>
                                                    <span className="px-1.5 py-0.5 text-[10px] font-semibold text-[#800020] bg-[#800020]/10 rounded border border-[#800020]/20">
                                                        {diagram.safe_area.dimensions}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-neutral-600 leading-relaxed">
                                                    {diagram.safe_area.description}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Download guideline templates */}
                        <div className="lg:col-span-5 lg:border-l lg:border-neutral-100 lg:pl-10 flex flex-col justify-between h-full">
                            <div>
                                <h3 className="text-xl font-bold text-neutral-900 mb-2">
                                    Download a Design Guideline
                                </h3>
                                <p className="text-sm text-neutral-500 mb-6 leading-relaxed">
                                    Use our pre-formatted templates to ensure your designs are sized perfectly and adhere to our safety standards.
                                </p>

                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1">
                                    {downloads.map((item) => (
                                        <a
                                            key={item.id}
                                            href={item.href}
                                            download
                                            className="group flex items-center justify-between p-4 rounded-xl border border-neutral-100 bg-neutral-50/50 hover:bg-white hover:border-neutral-200 hover:shadow-sm transition-all duration-200"
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
                                                        Adobe {item.label} format ({item.extension})
                                                    </p>
                                                </div>
                                            </div>
                                            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-white border border-neutral-100 text-neutral-500 group-hover:bg-neutral-900 group-hover:text-white group-hover:border-neutral-900 transition-colors duration-200 shadow-sm">
                                                <Download className="size-4" />
                                            </span>
                                        </a>
                                    ))}
                                </div>
                            </div>

                            <div className="mt-8 pt-6 border-t border-neutral-100 flex items-start gap-2.5 text-xs text-neutral-500">
                                <Info className="size-4 shrink-0 text-neutral-400 mt-0.5" />
                                <p className="leading-relaxed">
                                    Not sure how to design? Feel free to contact our expert support team or check our design service options below.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    );
}
