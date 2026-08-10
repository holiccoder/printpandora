import StorefrontLayout from '@/layouts/storefront-layout';
import type { AuthLayoutProps } from '@/types';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: AuthLayoutProps) {
    return (
        <StorefrontLayout>
            <div className="bg-neutral-50 px-4 py-16 sm:px-6 lg:px-8 dark:bg-neutral-950">
                <div className="mx-auto w-full max-w-md">
                    <div className="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm sm:p-8 dark:border-neutral-800 dark:bg-neutral-900">
                        <div className="mb-8 space-y-2 text-center">
                            <h1 className="text-xl font-semibold text-neutral-900 dark:text-white">
                                {title}
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                {description}
                            </p>
                        </div>

                        {children}
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
