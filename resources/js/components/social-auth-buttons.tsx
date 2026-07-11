import { Button } from '@/components/ui/button';

type Provider = 'google' | 'facebook';

type Props = {
    /** "Log in" or "Sign up" — controls the button text. */
    intent: 'login' | 'register';
    className?: string;
};

const VERB: Record<Props['intent'], string> = {
    login: 'Continue',
    register: 'Sign up',
};

/**
 * Two-button block (Google + Facebook) plus an "or" divider, used on the
 * login and register pages. The buttons are real `<a>` redirects to
 * `/auth/{provider}/redirect`, so they integrate with whatever OAuth
 * driver is wired up server-side (e.g. Laravel Socialite).
 */
export default function SocialAuthButtons({ intent, className }: Props) {
    const verb = VERB[intent];

    return (
        <div className={className}>
            <div className="grid gap-3">
                <SocialButton provider="google" label={`${verb} with Google`} />
                <SocialButton provider="facebook" label={`${verb} with Facebook`} />
            </div>
            <div className="my-6 flex items-center gap-3 text-xs uppercase tracking-wider text-muted-foreground">
                <span aria-hidden className="h-px flex-1 bg-border" />
                <span>or</span>
                <span aria-hidden className="h-px flex-1 bg-border" />
            </div>
        </div>
    );
}

function SocialButton({ provider, label }: { provider: Provider; label: string }) {
    return (
        <Button
            asChild
            type="button"
            variant="outline"
            className="w-full justify-center gap-2 font-medium"
        >
            <a href={`/auth/${provider}/redirect`}>
                {provider === 'google' ? <GoogleIcon /> : <FacebookIcon />}
                {label}
            </a>
        </Button>
    );
}

function GoogleIcon() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 24 24"
            className="size-4"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                fill="#4285F4"
                d="M23.49 12.27c0-.79-.07-1.55-.2-2.27H12v4.51h6.46c-.28 1.49-1.13 2.75-2.41 3.6v2.99h3.9c2.28-2.1 3.54-5.2 3.54-8.83Z"
            />
            <path
                fill="#34A853"
                d="M12 24c3.24 0 5.96-1.07 7.95-2.9l-3.9-2.99c-1.08.72-2.46 1.16-4.05 1.16-3.11 0-5.74-2.1-6.69-4.93H1.27v3.09C3.25 21.3 7.31 24 12 24Z"
            />
            <path
                fill="#FBBC05"
                d="M5.31 14.34a7.18 7.18 0 0 1 0-4.6V6.65H1.27a12 12 0 0 0 0 10.78l4.04-3.09Z"
            />
            <path
                fill="#EA4335"
                d="M12 4.75c1.76 0 3.34.6 4.59 1.78l3.45-3.45C17.95 1.18 15.23 0 12 0 7.31 0 3.25 2.7 1.27 6.65l4.04 3.09C6.26 6.85 8.89 4.75 12 4.75Z"
            />
        </svg>
    );
}

function FacebookIcon() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 24 24"
            className="size-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="#1877F2"
        >
            <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.52c-1.49 0-1.96.93-1.96 1.89v2.27h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z" />
        </svg>
    );
}
