import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

declare global {
    interface Window {
        Intercom?: IntercomApi;
        intercomSettings?: {
            app_id: string;
            api_base?: string;
        };
    }
}

type IntercomApi = (...args: unknown[]) => void;

type IntercomStub = IntercomApi & {
    c: (args: unknown[]) => void;
    q: unknown[];
};

/**
 * Loads the Intercom messenger widget on storefront pages.
 *
 * The widget is only initialised when:
 *   - an INTERCOM_APP_ID is configured, and
 *   - the current page is not a dashboard page (dashboard/* also uses
 *     StorefrontLayout, but Intercom should be limited to public storefront
 *     pages).
 *
 * The script is injected once and `Intercom('update')` is called on every
 * Inertia navigation finish so the messenger persists across SPA page swaps.
 */
export default function IntercomMessenger() {
    const page = usePage();
    const appId = page.props.intercom?.app_id;
    const pageComponent = page.component;
    const loadedRef = useRef(false);

    const isDashboardPage =
        pageComponent === 'dashboard' || pageComponent.startsWith('dashboard/');

    useEffect(() => {
        if (!appId || isDashboardPage) {
            return;
        }

        if (loadedRef.current) {
            return;
        }

        window.intercomSettings = {
            app_id: appId,
            api_base: 'https://api-iam.intercom.io',
        };

        const loadIntercom = () => {
            const w = window;
            const ic = w.Intercom;

            if (typeof ic === 'function') {
                ic('reattach_activator');
                ic('update', w.intercomSettings);
            } else {
                const d = document;
                const i = function (...args: unknown[]) {
                    i.c(args);
                } as IntercomStub;
                i.c = (args) => i.q.push(args);
                i.q = [];
                w.Intercom = i;

                const s = d.createElement('script');
                s.type = 'text/javascript';
                s.async = true;
                s.src = `https://widget.intercom.io/widget/${appId}`;
                const x = d.getElementsByTagName('script')[0];
                x?.parentNode?.insertBefore(s, x);
            }
        };

        const runWhenReady = () => {
            if (document.readyState === 'complete') {
                loadIntercom();
            } else {
                window.addEventListener('load', loadIntercom);
            }
        };

        runWhenReady();
        loadedRef.current = true;

        const handlePageVisit = () => {
            window.Intercom?.('update');
        };

        document.addEventListener('inertia:finish', handlePageVisit);

        return () => {
            document.removeEventListener('inertia:finish', handlePageVisit);
        };
    }, [appId, isDashboardPage]);

    return null;
}
