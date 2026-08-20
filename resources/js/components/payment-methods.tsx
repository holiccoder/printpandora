/* Brand-colored payment badges (lucide has no brand icons). Rendered as
   compact 48x32 SVG marks so no image assets are needed. */
export function PaymentIcon({ method }: { method: string }) {
    const common = {
        viewBox: '0 0 48 32',
        className: 'h-7 w-auto rounded border border-neutral-200 bg-white',
        role: 'img' as const,
        'aria-label': method,
    };
    const text = {
        fontFamily: 'Arial, Helvetica, sans-serif',
        textAnchor: 'middle' as const,
    };

    switch (method) {
        case 'visa':
            return (
                <svg {...common}>
                    <text
                        {...text}
                        x="24"
                        y="21"
                        fontSize="12"
                        fontWeight="800"
                        fontStyle="italic"
                        letterSpacing="1"
                        fill="#1A1F71"
                    >
                        VISA
                    </text>
                </svg>
            );
        case 'mastercard':
            return (
                <svg {...common}>
                    <circle cx="20.5" cy="16" r="8" fill="#EB001B" />
                    <circle cx="27.5" cy="16" r="8" fill="#F79E1B" />
                    <path
                        d="M24 9.6a8 8 0 0 0-3 6.4 8 8 0 0 0 3 6.4 8 8 0 0 0 3-6.4 8 8 0 0 0-3-6.4z"
                        fill="#FF5F00"
                    />
                </svg>
            );
        case 'paypal':
            return (
                <svg {...common}>
                    <text
                        {...text}
                        x="24"
                        y="20.5"
                        fontSize="10.5"
                        fontWeight="800"
                        fontStyle="italic"
                    >
                        <tspan fill="#003087">Pay</tspan>
                        <tspan fill="#0079C1">Pal</tspan>
                    </text>
                </svg>
            );
        case 'jcb':
            return (
                <svg {...common}>
                    <rect x="6" y="8" width="10" height="16" rx="2" fill="#E21F26" />
                    <rect x="19" y="8" width="10" height="16" rx="2" fill="#0056A8" />
                    <rect x="32" y="8" width="10" height="16" rx="2" fill="#009A44" />
                    <text {...text} x="11" y="19.5" fontSize="8.5" fontWeight="700" fill="#fff">J</text>
                    <text {...text} x="24" y="19.5" fontSize="8.5" fontWeight="700" fill="#fff">C</text>
                    <text {...text} x="37" y="19.5" fontSize="8.5" fontWeight="700" fill="#fff">B</text>
                </svg>
            );
        case 'amex':
            return (
                <svg {...common}>
                    <rect x="1" y="4" width="46" height="24" rx="3" fill="#2E77BC" />
                    <text
                        {...text}
                        x="24"
                        y="20"
                        fontSize="10.5"
                        fontWeight="800"
                        letterSpacing="0.5"
                        fill="#fff"
                    >
                        AMEX
                    </text>
                </svg>
            );
        default:
            return null;
    }
}

export function PaymentMethods({
    methods,
    className = 'flex flex-wrap items-center gap-2',
}: {
    methods: string[];
    className?: string;
}) {
    if (!methods?.length) {
        return null;
    }

    return (
        <ul className={className} aria-label="Accepted payment methods">
            {methods.map((method) => (
                <li key={method}>
                    <PaymentIcon method={method} />
                </li>
            ))}
        </ul>
    );
}

export default PaymentMethods;
