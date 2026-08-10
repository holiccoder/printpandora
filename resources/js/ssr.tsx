import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import ReactDOMServer from 'react-dom/server';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import StorefrontLayout from '@/layouts/storefront-layout';
import '../css/app.css';

const appName = process.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        layout: (name) => {
            switch (true) {
                case name === 'home':
                    return null;
                case name === 'about':
                case name === 'terms':
                case name === 'privacy':
                case name === 'shipping':
                case name === 'contact':
                case name === 'sample-packs':
                case name === 'business-card-design-service':
                case name === 'postcards':
                case name === 'stickers-and-labels':
                case name === 'flyers-and-brochures':
                    return null;
                case name === 'dashboard':
                    return null;
                case name.startsWith('dashboard/'):
                    return null;
                case name.startsWith('blog/'):
                    return null;
                case name.startsWith('errors/'):
                    return null;
                case name.startsWith('shop/'):
                    return null;
                case name.startsWith('auth/'):
                    return AuthLayout;
                case name.startsWith('settings/'):
                    return [StorefrontLayout, SettingsLayout];
                default:
                    return null;
            }
        },
        // laravel-vite-plugin's inferred union is wider than the resolver
        // signature accepted by @inertiajs/react 3.x.
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ) as Promise<ComponentType>,
        setup: ({ App, props }) => (
            <TooltipProvider delayDuration={0}>
                <App {...props} />
                <Toaster />
            </TooltipProvider>
        ),
    }),
);
