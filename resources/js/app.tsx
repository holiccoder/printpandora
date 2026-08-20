import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import StorefrontLayout from '@/layouts/storefront-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Chrome's Google Translate mutates React-managed text nodes by wrapping them
// in <font> elements. When React later removes or inserts around those nodes
// it can throw NotFoundError and break rendering. These guards make the DOM
// operations tolerant of nodes that no longer sit where React expects them.
if (typeof window !== 'undefined' && typeof Node === 'function') {
    const originalRemoveChild = Node.prototype.removeChild;
    Node.prototype.removeChild = function (child) {
        if (child.parentNode !== this) {
            return child;
        }

        return originalRemoveChild.call(this, child);
    } as typeof Node.prototype.removeChild;

    const originalInsertBefore = Node.prototype.insertBefore;
    Node.prototype.insertBefore = function (newNode, referenceNode) {
        if (referenceNode && referenceNode.parentNode !== this) {
            return newNode;
        }

        return originalInsertBefore.call(this, newNode, referenceNode);
    } as typeof Node.prototype.insertBefore;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'home':
                return null;
            case name === 'about':
            case name === 'terms':
            case name === 'privacy':
            case name === 'shipping':
            case name === 'shipping-calculator':
            case name === 'contact':
            case name === 'help':
            case name === 'sample-packs':
            case name === 'business-card-sample-pack':
            case name === 'free-sample-pack':
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
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
