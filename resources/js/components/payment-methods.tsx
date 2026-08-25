/* Brand-colored payment badges (lucide has no brand icons). */
export function PaymentIcon({ method }: { method: string }) {
    const common = {
        width: 40,
        height: 26,
        viewBox: '0 0 40 26',
        fill: 'none',
        xmlns: 'http://www.w3.org/2000/svg',
        role: 'img' as const,
        'aria-label': method,
    };

    switch (method) {
        case 'visa':
            return (
                <svg {...common}>
                    <rect
                        width="40"
                        height="26"
                        rx="4"
                        fill="#FFFFFF"
                        stroke="#E2E8F0"
                    />
                    <path
                        d="M16.5 18H14.1L15.6 8.5H18L16.5 18Z"
                        fill="#1A1F71"
                    />
                    <path
                        d="M23.9 8.7C23.4 8.5 22.6 8.3 21.6 8.3C19.1 8.3 17.4 9.6 17.4 11.4C17.4 12.8 18.7 13.5 19.7 14C20.7 14.5 21 14.8 21 15.2C21 15.8 20.3 16.2 19.3 16.2C18.3 16.2 17.5 15.9 16.9 15.6L16.5 17.5C17.2 17.8 18.3 18.1 19.4 18.1C22.1 18.1 23.8 16.8 23.8 14.9C23.8 13.2 22.4 12.4 21.3 11.9C20.4 11.4 19.9 11.1 19.9 10.7C19.9 10.2 20.5 9.9 21.4 9.9C22.2 9.9 22.9 10.1 23.4 10.3L23.9 8.7Z"
                        fill="#1A1F71"
                    />
                    <path
                        d="M27.6 14.7C27.8 14.1 28.6 12 28.6 12C28.6 12 28.8 11.5 28.9 11L29.1 11.9C29.3 12.8 29.7 14.7 29.7 14.7H27.6ZM31.1 8.5H29.3C28.7 8.5 28.2 8.7 28 9.3L23.9 18H26.4L26.9 16.6H30L30.3 18H32.5L31.1 8.5Z"
                        fill="#1A1F71"
                    />
                    <path
                        d="M12.9 8.5L10.6 15L10.3 13.6C9.9 12.1 8.7 10.5 7.2 9.7L9.3 18H11.9L15.8 8.5H12.9Z"
                        fill="#1A1F71"
                    />
                    <path
                        d="M8.8 8.5H5L5 8.7C7.6 9.3 9.7 11.2 10.3 13.6L9.6 9.8C9.5 8.8 8.8 8.5 8.8 8.5Z"
                        fill="#F7B600"
                    />
                </svg>
            );
        case 'mastercard':
            return (
                <svg {...common}>
                    <rect
                        width="40"
                        height="26"
                        rx="4"
                        fill="#FFFFFF"
                        stroke="#E2E8F0"
                    />
                    <circle cx="15.5" cy="13" r="6.5" fill="#EB001B" />
                    <circle cx="24.5" cy="13" r="6.5" fill="#F79E1B" />
                    <path
                        d="M20 7.8A6.47 6.47 0 0 0 17.5 13 6.47 6.47 0 0 0 20 18.2 6.47 6.47 0 0 0 22.5 13 6.47 6.47 0 0 0 20 7.8Z"
                        fill="#FF5F00"
                    />
                </svg>
            );
        case 'paypal':
            return (
                <svg {...common}>
                    <rect
                        width="40"
                        height="26"
                        rx="4"
                        fill="#FFFFFF"
                        stroke="#E2E8F0"
                    />
                    <path
                        d="M15.2 7H20.2C22.6 7 24.2 8.3 23.9 10.5C23.6 12.3 22 13.7 20 13.7H17.8L16.8 19H14L15.2 7Z"
                        fill="#003087"
                    />
                    <path
                        d="M18.2 10H22.7C24.4 10 25.6 11 25.3 12.7C25 14.3 23.5 15.5 21.8 15.5H19.8L19.1 19.5H16.8L18.2 10Z"
                        fill="#0079C1"
                    />
                    <path
                        d="M17.8 13.7L17.3 16.5H19.8C21.3 16.5 22.5 15.6 22.8 14.2C22.6 13.9 22.2 13.7 21.7 13.7H17.8Z"
                        fill="#001C64"
                    />
                </svg>
            );
        case 'amex':
            return (
                <svg {...common}>
                    <rect width="40" height="26" rx="4" fill="#0077A6" />
                    <path
                        d="M9.5 15.5L7.8 11.5H6.2V17H7.4V13.2L8.9 16.8H10.1L11.6 13.2V17H12.8V11.5H11.2L9.5 15.5ZM17 11.5H13.6V17H17V15.9H14.8V14.8H16.7V13.7H14.8V12.6H17V11.5ZM21.2 11.5L19.8 13.6L18.4 11.5H17.1L19.1 14.3L17 17H18.4L19.8 15L21.2 17H22.5L20.5 14.3L22.5 11.5H21.2Z"
                        fill="#FFFFFF"
                    />
                    <path
                        d="M23.3 17H24.5V11.5H23.3V17ZM30.7 13.2C30.7 12.2 29.9 11.5 28.7 11.5H25.4V17H28.7C29.9 17 30.7 16.3 30.7 15.3C30.7 14.5 30.1 14 29.5 13.8C30.2 13.6 30.7 13.1 30.7 13.2ZM26.6 12.6H28.4C28.9 12.6 29.3 12.9 29.3 13.4C29.3 13.9 28.9 14.2 28.4 14.2H26.6V12.6ZM28.5 15.9H26.6V14.8H28.5C29 14.8 29.4 15.1 29.4 15.4C29.4 15.7 29 15.9 28.5 15.9ZM34.2 11.5H31.6V17H34.2C35.7 17 36.6 15.8 36.6 14.2C36.6 12.7 35.7 11.5 34.2 11.5ZM32.8 15.9V12.6H34.1C35 12.6 35.4 13.3 35.4 14.2C35.4 15.2 35 15.9 34.1 15.9H32.8Z"
                        fill="#FFFFFF"
                    />
                </svg>
            );
        case 'jcb':
            return (
                <svg {...common}>
                    <rect
                        width="40"
                        height="26"
                        rx="4"
                        fill="#FFFFFF"
                        stroke="#E2E8F0"
                    />
                    <path
                        d="M9 7.5H13.5C15.4 7.5 17 9.1 17 11V18.5H12.5C10.6 18.5 9 16.9 9 15V7.5Z"
                        fill="#0060A9"
                    />
                    <path
                        d="M17 7.5H21.5C23.4 7.5 25 9.1 25 11V18.5H20.5C18.6 18.5 17 16.9 17 15V7.5Z"
                        fill="#EB1B2D"
                    />
                    <path
                        d="M25 7.5H29.5C31.4 7.5 33 9.1 33 11V18.5H28.5C26.6 18.5 25 16.9 25 15V7.5Z"
                        fill="#1E9B42"
                    />
                    <path
                        d="M14.5 9.5V14.2C14.5 15.2 13.8 15.8 12.8 15.8C12 15.8 11.4 15.4 11.2 14.8L12.4 14.4C12.5 14.7 12.7 14.8 12.8 14.8C13.2 14.8 13.3 14.5 13.3 14.2V9.5H14.5Z"
                        fill="#FFFFFF"
                    />
                    <path
                        d="M22.8 11.2L21.7 11.8C21.4 11.4 21 11.1 20.4 11.1C19.4 11.1 18.7 11.9 18.7 13C18.7 14.1 19.4 14.9 20.4 14.9C21 14.9 21.5 14.6 21.8 14.1L22.9 14.7C22.3 15.5 21.4 16 20.4 16C18.6 16 17.3 14.7 17.3 13C17.3 11.3 18.6 10 20.4 10C21.4 10 22.3 10.5 22.8 11.2Z"
                        fill="#FFFFFF"
                    />
                    <path
                        d="M26.8 9.5H29.2C29.9 9.5 30.5 9.8 30.5 10.5C30.5 11 30.2 11.3 29.8 11.5C30.3 11.7 30.7 12.1 30.7 12.7C30.7 13.5 30 13.9 29.2 13.9H26.8V9.5ZM28 11.1H29.1C29.4 11.1 29.6 10.9 29.6 10.6C29.6 10.3 29.4 10.2 29.1 10.2H28V11.1ZM28 13.2H29.1C29.4 13.2 29.7 13 29.7 12.7C29.7 12.4 29.4 12.2 29.1 12.2H28V13.2Z"
                        fill="#FFFFFF"
                    />
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
