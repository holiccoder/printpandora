// Content (labels/placeholders/headings) sourced from `content/hardcoded-content.json` via useContent('dashboard_profile_page').
import { Form, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import SEO from '@/components/seo';
import ShippingAddressFields from '@/components/shipping-address-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useContent } from '@/hooks/use-content';
import DashboardLayout from '@/layouts/dashboard-layout';

const ACCENT = '#800020';

type Props = {
    user: {
        name: string;
        email: string;
        email_verified_at: string | null;
        shipping_address: string | null;
        shipping_city: string | null;
        shipping_state: string | null;
        shipping_zip: string | null;
        shipping_country: string | null;
    };
    status?: string;
};

export default function DashboardProfile({ user, status }: Props) {
    const c = useContent('dashboard_profile_page') as any;

    return (
        <DashboardLayout>
            <SEO title={c.seo.title} description={c.seo.description} />

            <div className="mx-auto max-w-3xl">
                <header className="mb-6">
                    <Link
                        href="/dashboard"
                        className="mb-3 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                        style={{ color: ACCENT }}
                    >
                        <ChevronLeft className="size-4" /> {c.back_link}
                    </Link>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                        {c.page_heading}
                    </h1>
                    <p className="mt-1 text-sm text-neutral-600">
                        {c.page_subheading}
                    </p>
                </header>

                {status && (
                    <div className="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {status === 'verification-link-sent'
                            ? c.email_verification_sent_message
                            : status === 'password-reset-link-sent'
                              ? c.password_reset_sent_message
                              : status}
                    </div>
                )}

                <div className="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                    <Form
                        action="/dashboard/profile"
                        method="patch"
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors, recentlySuccessful }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">
                                        {c.labels.name}
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={user.name}
                                        required
                                        autoComplete="name"
                                        placeholder={c.placeholders.name}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        {c.labels.email}
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        defaultValue={user.email}
                                        required
                                        autoComplete="username"
                                        placeholder={c.placeholders.email}
                                    />
                                    <InputError message={errors.email} />
                                    {user.email_verified_at === null && (
                                        <p className="text-xs text-amber-600">
                                            {c.unverified_email_notice}
                                        </p>
                                    )}
                                </div>

                                <ShippingAddressFields
                                    initialValues={user}
                                    content={c.shipping_address}
                                    errors={
                                        errors as Record<
                                            string,
                                            string | undefined
                                        >
                                    }
                                />

                                <div className="flex items-center gap-3 pt-2">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-primary text-primary-foreground hover:bg-primary/90"
                                    >
                                        {processing
                                            ? c.buttons.saving
                                            : c.buttons.save}
                                    </Button>
                                    {recentlySuccessful && (
                                        <span className="text-sm text-emerald-700">
                                            {c.success_inline}
                                        </span>
                                    )}
                                </div>
                            </>
                        )}
                    </Form>

                    <div className="border-t border-neutral-100 pt-6">
                        <h2 className="text-base font-semibold text-neutral-900">
                            {c.password_heading}
                        </h2>
                        <p className="mt-1 text-sm text-neutral-500">
                            {c.password_help}
                        </p>

                        <Form
                            {...SecurityController.requestPasswordReset.form()}
                            options={{ preserveScroll: true }}
                            className="mt-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <InputError message={errors.password} />
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="mt-3 bg-primary text-primary-foreground hover:bg-primary/90"
                                    >
                                        {processing && <Spinner />}
                                        {processing
                                            ? c.buttons.sending_password_link
                                            : c.buttons.send_password_link}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
