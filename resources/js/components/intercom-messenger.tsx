import { usePage } from '@inertiajs/react';
import { Intercom, shutdown, update } from '@intercom/messenger-js-sdk';
import { useEffect, useRef } from 'react';

/**
 * Loads the Intercom messenger widget on storefront pages using the official
 * `@intercom/messenger-js-sdk`.
 *
 * The widget is only initialised when:
 *   - the build is not a development build,
 *   - an INTERCOM_APP_ID is configured, and
 *   - the current page is not a dashboard page (dashboard/* also uses
 *     StorefrontLayout, but Intercom should be limited to public storefront
 *     pages).
 *
 * For authenticated users, identity (id, name, email, created_at) is forwarded
 * to Intercom so conversations are linked to the user record. For guests, only
 * `app_id` is sent (anonymous visitor session).
 *
 * `update` is called on every Inertia navigation finish so the messenger
 * records the latest URL and persists across SPA page swaps. On unmount the
 * widget is shut down so leaving the storefront (e.g. to the admin dashboard)
 * cleanly tears down the messenger.
 */
export default function IntercomMessenger() {
    const page = usePage();
    const appId = page.props.intercom?.app_id;
    const user = page.props.intercom?.user;
    const pageComponent = page.component;
    const bootedRef = useRef(false);

    const isDashboardPage =
        pageComponent === 'dashboard' || pageComponent.startsWith('dashboard/');

    useEffect(() => {
        if (!appId || isDashboardPage) {
            return;
        }

        Intercom({
            app_id: appId,
            ...(user
                ? {
                      user_id: String(user.id),
                      name: user.name,
                      email: user.email,
                      ...(user.created_at
                          ? { created_at: user.created_at }
                          : {}),
                  }
                : {}),
        });
        bootedRef.current = true;

        const handlePageVisit = () => {
            update(
                user
                    ? {
                          user_id: String(user.id),
                          name: user.name,
                          email: user.email,
                          ...(user.created_at
                              ? { created_at: user.created_at }
                              : {}),
                      }
                    : {},
            );
        };
        document.addEventListener('inertia:finish', handlePageVisit);

        return () => {
            document.removeEventListener('inertia:finish', handlePageVisit);

            if (bootedRef.current) {
                shutdown();
                bootedRef.current = false;
            }
        };
    }, [appId, user, isDashboardPage]);

    return null;
}
