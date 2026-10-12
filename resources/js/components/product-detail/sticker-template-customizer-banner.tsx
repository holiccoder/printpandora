import { Link } from '@inertiajs/react';
import { ArrowRight, Check, Sliders, Sparkles, X } from 'lucide-react';
import { useState } from 'react';

export default function StickerTemplateCustomizerBanner() {
    const [isCustomizerOpen, setIsCustomizerOpen] = useState(false);
    const [customTitle, setCustomTitle] = useState(
        'All Stickers Showcases',
    );
    const [customDescription, setCustomDescription] = useState(
        'You can use our showcase images just as they are. Or adjust them to suit your style, or submit to our cutom design page.',
    );

    return (
        <section
            aria-label="Explore sticker templates"
            className="bg-[#f9f8f6] py-12 lg:py-16"
        >
            <div className="product-detail-container mx-auto max-w-7xl px-4">
                <div className="relative isolate flex aspect-[5/2] min-h-[32rem] items-center overflow-hidden rounded-2xl bg-slate-800 shadow-sm lg:min-h-0">
                    <img
                        src="/images/products/stickers/sticker-templates-banner.png"
                        alt=""
                        aria-hidden="true"
                        loading="lazy"
                        className="absolute inset-0 -z-20 h-full w-full object-cover object-center"
                    />
                    <div
                        aria-hidden="true"
                        className="absolute inset-0 -z-10 bg-black/15"
                    />

                    <div className="absolute top-5 right-5 z-30 sm:top-6 sm:right-6">
                        <button
                            type="button"
                            onClick={() => setIsCustomizerOpen(true)}
                            className="flex items-center gap-2 rounded-full border border-white/40 bg-white/90 px-4 py-2.5 text-xs font-semibold text-slate-800 shadow-lg backdrop-blur-md transition-all hover:scale-105 hover:bg-white"
                        >
                            <Sliders
                                aria-hidden="true"
                                className="h-4 w-4 text-emerald-600"
                            />
                            <span>Customize Text</span>
                        </button>
                    </div>

                    <div className="mx-4 w-[calc(100%-2rem)] rounded-xl border border-slate-100 bg-white p-7 shadow-2xl transition-all sm:mr-[7%] sm:ml-auto sm:w-full sm:max-w-lg sm:p-10">
                        <h2 className="mb-4 text-3xl leading-tight font-bold tracking-tight text-slate-900 sm:text-4xl">
                            {customTitle}
                        </h2>
                        <p className="mb-6 text-sm leading-relaxed font-normal text-slate-600 sm:text-base">
                            {customDescription}
                        </p>
                        <Link
                            href="/stickers-and-labels"
                            className="group inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 transition-colors hover:text-emerald-800 sm:text-base"
                        >
                            <span>Browse All stickers showcases</span>
                            <ArrowRight
                                aria-hidden="true"
                                className="h-4 w-4 transition-transform group-hover:translate-x-1"
                            />
                        </Link>
                    </div>

                    {isCustomizerOpen && (
                        <div
                            className="fixed inset-0 z-50 flex animate-in items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm fade-in duration-200"
                            role="presentation"
                            onMouseDown={(event) => {
                                if (event.target === event.currentTarget) {
                                    setIsCustomizerOpen(false);
                                }
                            }}
                        >
                            <div
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="sticker-template-customizer-title"
                                className="w-full max-w-md space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl"
                            >
                                <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                                    <h3
                                        id="sticker-template-customizer-title"
                                        className="flex items-center gap-2 text-base font-bold text-slate-900"
                                    >
                                        <Sparkles
                                            aria-hidden="true"
                                            className="h-4 w-4 text-emerald-600"
                                        />
                                        <span>Customize Card Content</span>
                                    </h3>
                                    <button
                                        type="button"
                                        aria-label="Close customizer"
                                        onClick={() =>
                                            setIsCustomizerOpen(false)
                                        }
                                        className="rounded-full p-1 text-slate-400 hover:text-slate-700"
                                    >
                                        <X
                                            aria-hidden="true"
                                            className="h-5 w-5"
                                        />
                                    </button>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <label
                                            htmlFor="sticker-template-heading"
                                            className="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase"
                                        >
                                            Card Heading
                                        </label>
                                        <input
                                            id="sticker-template-heading"
                                            type="text"
                                            value={customTitle}
                                            onChange={(event) =>
                                                setCustomTitle(
                                                    event.target.value,
                                                )
                                            }
                                            className="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="sticker-template-description"
                                            className="mb-1.5 block text-xs font-bold tracking-wider text-slate-500 uppercase"
                                        >
                                            Card Description
                                        </label>
                                        <textarea
                                            id="sticker-template-description"
                                            rows={3}
                                            value={customDescription}
                                            onChange={(event) =>
                                                setCustomDescription(
                                                    event.target.value,
                                                )
                                            }
                                            className="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end pt-2">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setIsCustomizerOpen(false)
                                        }
                                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3 text-xs font-semibold text-white shadow-md shadow-emerald-600/20 transition-all hover:bg-emerald-700"
                                    >
                                        <Check
                                            aria-hidden="true"
                                            className="h-4 w-4"
                                        />
                                        <span>Save Changes</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
