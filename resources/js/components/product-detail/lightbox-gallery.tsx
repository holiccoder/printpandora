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
            className="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/95 backdrop-blur-sm transition-all duration-300 select-none animate-in fade-in"
            onClick={onClose}
            role="dialog"
            aria-modal="true"
            aria-label="Product Image Lightbox"
        >
            {/* Top Bar with counter & close button */}
            <div className="absolute top-0 inset-x-0 flex items-center justify-between p-4 md:p-6 text-white bg-gradient-to-b from-black/60 to-transparent">
                <span className="text-sm font-semibold tracking-wider font-mono">
                    {currentIndex + 1} / {images.length}
                </span>
                <button
                    type="button"
                    onClick={onClose}
                    className="p-2 rounded-full hover:bg-white/10 text-white/80 hover:text-white transition-colors duration-200 outline-none focus-visible:ring-2 focus-visible:ring-white"
                    aria-label="Close lightbox"
                >
                    <X className="size-6 md:size-8" />
                </button>
            </div>

            {/* Main Image View */}
            <div className="relative flex items-center justify-center w-full max-w-6xl h-full px-4 md:px-16 py-20">
                {/* Previous Button (Left) */}
                <button
                    type="button"
                    onClick={handlePrev}
                    className="absolute left-2 md:left-6 z-10 p-3 rounded-full bg-white/5 hover:bg-white/10 text-white border border-white/10 hover:border-white/20 transition-all duration-200 focus-visible:ring-2 focus-visible:ring-white"
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
                        className="max-h-[70vh] md:max-h-[80vh] w-auto max-w-full object-contain rounded-lg shadow-2xl transition-transform duration-300"
                    />
                </div>

                {/* Next Button (Right) */}
                <button
                    type="button"
                    onClick={handleNext}
                    className="absolute right-2 md:right-6 z-10 p-3 rounded-full bg-white/5 hover:bg-white/10 text-white border border-white/10 hover:border-white/20 transition-all duration-200 focus-visible:ring-2 focus-visible:ring-white"
                    aria-label="Next image"
                >
                    <ChevronRight className="size-6 md:size-8" />
                </button>
            </div>

            {/* Bottom Bar: Thumbnail previews */}
            <div 
                className="absolute bottom-6 inset-x-0 flex items-center justify-center gap-3 px-4"
                onClick={(e) => e.stopPropagation()}
            >
                {images.map((src, idx) => (
                    <button
                        key={src}
                        type="button"
                        onClick={() => setCurrentIndex(idx)}
                        className={`relative h-14 w-14 md:h-16 md:w-16 rounded-md overflow-hidden border-2 transition-all duration-200 ${
                            currentIndex === idx
                                ? 'border-white scale-105 shadow-md shadow-white/10'
                                : 'border-transparent opacity-50 hover:opacity-100 hover:scale-102'
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
