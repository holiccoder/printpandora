// Content sourced from `content/hardcoded-content.json` via useContent('auth_confirm_password_page').
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useContent } from '@/hooks/use-content';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const c = useContent('auth_confirm_password_page') as any;

    return (
        <>
            <SEO title={c.seo.title} description={c.seo.description} />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">{c.labels.password}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={c.placeholders.password}
                                autoComplete="current-password"
                                autoFocus
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center">
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                {c.buttons.submit}
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm password',
    description:
        'This is a secure area of the application. Please confirm your password before continuing.',
};
