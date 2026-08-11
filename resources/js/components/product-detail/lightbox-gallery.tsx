import React, { useEffect, useState } from 'react';
import { X, ChevronLeft, ChevronRight } from 'lucide-react';

interface LightboxGalleryProps {
    open: boolean;
    onClose: () => void;
    images: string[];
    initialIndex: number;
}

export default function LightboxGallery({
    open,
    onClose,
    images,
    initialIndex,
}: LightboxGalleryProps) {
    const [currentIndex, setCurrentIndex] = useState(initialIndex);

    // Sync state when lightbox opens or initialIndex changes
    useEffect(() => {
        if (open) {
            setCurrentIndex(initialIndex);
            // Disable background scrolling when modal is open
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [open, initialIndex]);

    // Handle slide controls
    const handlePrev = (e?: React.MouseEvent) => {
        e?.stopPropagation();
        setCurrentIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1));
    };

    const handleNext = (e?: React.MouseEvent) => {
        e?.stopPropagation();
        setCurrentIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1));
    };

    // Keyboard navigation
    useEffect(() => {
        if (!open) return;

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'ArrowLeft') {
                handlePrev();
            } else if (e.key === 'ArrowRight') {
                handleNext();
            } else if (e.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [open, images]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex animate-in flex-col items-center justify-center bg-black/95 backdrop-blur-sm transition-all duration-300 select-none fade-in"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
            aria-label="Product Image Lightbox"
        >
            {/* Top Bar with counter & close button */}
            <div className="absolute inset-x-0 top-0 flex items-center justify-between bg-gradient-to-b from-black/60 to-transparent p-4 text-white md:p-6">
                <span className="font-mono text-sm font-semibold tracking-wider">
                    {currentIndex + 1} / {images.length}
                </span>
                <button
                    type="button"
                    onClick={onClose}
                    className="rounded-full p-2 text-white/80 transition-colors duration-200 outline-none hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-white"
                    aria-label="Close lightbox"
                >
                    <X className="size-6 md:size-8" />
                </button>
            </div>

            {/* Main Image View */}
            <div className="relative flex h-full w-full max-w-6xl items-center justify-center px-4 py-20 md:px-16">
                {/* Previous Button (Left) */}
                <button
                    type="button"
                    onClick={handlePrev}
                    className="absolute left-2 z-10 rounded-full border border-white/10 bg-white/5 p-3 text-white transition-all duration-200 hover:border-white/20 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white md:left-6"
                    aria-label="Previous image"
                >
                    <ChevronLeft className="size-6 md:size-8" />
                </button>

                {/* Image Container */}
                <div
                    className="relative flex max-h-full items-center justify-center"
                    onClick={(e) => e.stopPropagation()} // Prevent closing when clicking the image
                >
                    <img
                        src={images[currentIndex]}
                        alt={`Product detail image ${currentIndex + 1}`}
                        className="max-h-[70vh] w-auto max-w-full rounded-lg object-contain shadow-2xl transition-transform duration-300 md:max-h-[80vh]"
                    />
                </div>

                {/* Next Button (Right) */}
                <button
                    type="button"
                    onClick={handleNext}
                    className="absolute right-2 z-10 rounded-full border border-white/10 bg-white/5 p-3 text-white transition-all duration-200 hover:border-white/20 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white md:right-6"
                    aria-label="Next image"
                >
                    <ChevronRight className="size-6 md:size-8" />
                </button>
            </div>

            {/* Bottom Bar: Thumbnail previews */}
            <div
                className="absolute inset-x-0 bottom-6 flex items-center justify-center gap-3 px-4"
                onClick={(e) => e.stopPropagation()}
            >
                {images.map((src, idx) => (
                    <button
                        key={src}
                        type="button"
                        onClick={() => setCurrentIndex(idx)}
                        className={`relative h-14 w-14 overflow-hidden rounded-md border-2 transition-all duration-200 md:h-16 md:w-16 ${
                            currentIndex === idx
                                ? 'scale-105 border-white shadow-md shadow-white/10'
                                : 'border-transparent opacity-50 hover:scale-102 hover:opacity-100'
                        }`}
                        aria-label={`View image ${idx + 1}`}
                    >
                        <img
                            src={src}
                            alt=""
                            className="h-full w-full object-cover"
                        />
                    </button>
                ))}
            </div>
        </div>
    );
}
