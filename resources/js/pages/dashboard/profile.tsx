// Content (labels/placeholders/headings) sourced from `content/hardcoded-content.json` via useContent('dashboard_profile_page').
import { Form, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import InputError from '@/components/input-error';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

const ACCENT = '#0f4c3a';

type Props = {
    user: {
        name: string;
        email: string;
        email_verified_at: string | null;
    };
    status?: string;
};

export default function DashboardProfile({ user, status }: Props) {
    const c = useContent('dashboard_profile_page') as any;

    return (
        <StorefrontLayout>
            <SEO
                title={c.seo.title}
                description={c.seo.description}
            />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-3xl px-4 py-10 lg:py-14">
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
                            {status}
                        </div>
                    )}

                    <div className="rounded-lg border border-neutral-200 bg-white p-6 shadow-sm">
                        <Form
                            action="/dashboard/profile"
                            method="patch"
                            options={{ preserveScroll: true }}
                            resetOnSuccess={['password', 'password_confirmation']}
                            className="space-y-6"
                        >
                            {({ processing, errors, recentlySuccessful }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">{c.labels.name}</Label>
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
                                        <Label htmlFor="email">{c.labels.email}</Label>
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

                                    {/* Password is optional — leave both fields blank to keep the
                                        existing password unchanged. */}
                                    <div className="border-t border-neutral-100 pt-6">
                                        <p className="mb-4 text-xs text-neutral-500">
                                            {c.password_help}
                                        </p>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="password">{c.labels.new_password}</Label>
                                                <Input
                                                    id="password"
                                                    type="password"
                                                    name="password"
                                                    autoComplete="new-password"
                                                    placeholder={c.placeholders.password}
                                                />
                                                <InputError message={errors.password} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="password_confirmation">
                                                    {c.labels.confirm_new_password}
                                                </Label>
                                                <Input
                                                    id="password_confirmation"
                                                    type="password"
                                                    name="password_confirmation"
                                                    autoComplete="new-password"
                                                    placeholder={c.placeholders.password_confirmation}
                                                />
                                                <InputError message={errors.password_confirmation} />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 pt-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-primary text-primary-foreground hover:bg-primary/90"
                                        >
                                            {processing ? c.buttons.saving : c.buttons.save}
                                        </Button>
                                        {recentlySuccessful && (
                                            <span className="text-sm text-emerald-700">{c.success_inline}</span>
                                        )}
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </section>
        </StorefrontLayout>
    );
}