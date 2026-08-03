import React from 'react';

interface FileFormatIconProps {
    id: string;
    extension: string;
    color?: string;
    className?: string;
}

export default function FileFormatIcon({ id, extension, color, className = '' }: FileFormatIconProps) {
    // Get short uppercase label (e.g., PSD, AI, INDD, JPG)
    const label = extension.replace('.', '').toUpperCase();

    // Default colors if not provided
    const bgStyle = color ? { backgroundColor: color } : undefined;

    return (
        <div
            style={bgStyle}
            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg font-bold text-white text-xs tracking-wider shadow-sm select-none ${className}`}
            aria-hidden="true"
        >
            {label}
        </div>
    );
}
