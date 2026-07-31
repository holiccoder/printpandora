import type { Auth } from '@/types/auth';
import type { Content } from '@/types/content';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: {
                success?: string;
                error?: string;
            };
            sidebarOpen: boolean;
            content: Content;
            intercom: {
                app_id?: string;
                user?: {
                    id: string | number;
                    name: string;
                    email: string;
                    created_at: number | null;
                } | null;
            };
            [key: string]: unknown;
        };
    }
}
