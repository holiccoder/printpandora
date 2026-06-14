import { Form, Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import InputError from '@/components/input-error';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    return (
        <StorefrontLayout>
            <SEO
                title="Edit profile"
                description="Update your PrintPandora profile name and email address."
            />

            <section className="bg-neutral-50">
                <div className="mx-auto max-w-3xl px-4 py-10 lg:py-14">
                    <header className="mb-6">
                        <Link
                            href="/dashboard"
                            className="mb-3 inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                            style={{ color: ACCENT }}
                        >
                            <ChevronLeft className="size-4" /> Back to dashboard
                        </Link>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                            Edit profile
                        </h1>
                        <p className="mt-1 text-sm text-neutral-600">
                            Update your name and email address.
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
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={user.name}
                                            required
                                            autoComplete="name"
                                            placeholder="Full name"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            defaultValue={user.email}
                                            required
                                            autoComplete="username"
                                            placeholder="you@example.com"
                                        />
                                        <InputError message={errors.email} />
                                        {user.email_verified_at === null && (
                                            <p className="text-xs text-amber-600">
                                                Your email address is unverified.
                                            </p>
                                        )}
                                    </div>

                                    {/* Password is optional — leave both fields blank to keep the
                                        existing password unchanged. */}
                                    <div className="border-t border-neutral-100 pt-6">
                                        <p className="mb-4 text-xs text-neutral-500">
                                            Leave the password fields blank to keep your current password.
                                        </p>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="grid gap-2">
                                                <Label htmlFor="password">New password</Label>
                                                <Input
                                                    id="password"
                                                    type="password"
                                                    name="password"
                                                    autoComplete="new-password"
                                                    placeholder="At least 8 characters"
                                                />
                                                <InputError message={errors.password} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="password_confirmation">
                                                    Confirm new password
                                                </Label>
                                                <Input
                                                    id="password_confirmation"
                                                    type="password"
                                                    name="password_confirmation"
                                                    autoComplete="new-password"
                                                    placeholder="Repeat new password"
                                                />
                                                <InputError message={errors.password_confirmation} />
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-3 pt-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="text-white"
                                            style={{ backgroundColor: ACCENT }}
                                        >
                                            {processing ? 'Saving…' : 'Save changes'}
                                        </Button>
                                        {recentlySuccessful && (
                                            <span className="text-sm text-emerald-700">Saved.</span>
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
