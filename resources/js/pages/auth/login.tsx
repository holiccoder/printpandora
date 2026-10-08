// Content (labels/placeholders/links) sourced from `content/hardcoded-content.json` via useContent('auth_login_page').
import { Form, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import SEO from '@/components/seo';
import SocialAuthButtons from '@/components/social-auth-buttons';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useContent } from '@/hooks/use-content';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    error?: string;
    status?: string;
    canResetPassword: boolean;
    passwordRules: string;
};

export default function Login({
    error,
    status,
    canResetPassword,
    passwordRules,
}: Props) {
    const c = useContent('auth_login_page') as any;
    const registrationContent = useContent('auth_register_page') as any;
    const isVerificationCodeSentStatus =
        status === 'A verification code was sent to your email address.';
    const [codeSent, setCodeSent] = useState(false);
    const [sendingCode, setSendingCode] = useState(false);
    const [sendCodeError, setSendCodeError] = useState<string | null>(null);
    const registration = useForm({
        name: '',
        email: '',
        email_code: '',
        password: '',
        password_confirmation: '',
    });

    const sendVerificationCode = () => {
        setSendingCode(true);
        setSendCodeError(null);

        router.post(
            '/register/send-code',
            { email: registration.data.email },
            {
                preserveScroll: true,
                onSuccess: () => setCodeSent(true),
                onError: (errors) =>
                    setSendCodeError(
                        errors.email ??
                            'We could not send a code. Check the email address and try again.',
                    ),
                onFinish: () => setSendingCode(false),
            },
        );
    };

    const submitRegistration = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        registration.post('/register/verified', {
            preserveScroll: true,
        });
    };

    const updateRegistrationEmail = (email: string) => {
        registration.setData('email', email);
        registration.setData('email_code', '');
        registration.clearErrors();
        setCodeSent(false);
        setSendCodeError(null);
    };

    return (
        <>
            <SEO title={c.seo.title} description={c.seo.description} />

            {error && (
                <div className="mb-6 rounded-md border border-destructive/30 bg-destructive/5 p-3 text-center text-sm text-destructive">
                    {error}
                </div>
            )}

            {status && (
                <div
                    className={`mb-4 text-center text-sm font-medium ${
                        isVerificationCodeSentStatus
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-green-600'
                    }`}
                >
                    {status}
                </div>
            )}

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section className="rounded-xl border border-neutral-200 p-5 sm:p-6 dark:border-neutral-700">
                    <h2 className="mb-5 text-center text-lg font-semibold text-neutral-900 dark:text-white">
                        {c.buttons.log_in}
                    </h2>

                    <SocialAuthButtons intent="login" />

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
                        className="flex flex-col gap-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-5">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            {c.labels.email}
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            autoFocus
                                            autoComplete="email"
                                            placeholder={c.placeholders.email}
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <div className="flex items-center">
                                            <Label htmlFor="password">
                                                {c.labels.password}
                                            </Label>
                                            {canResetPassword && (
                                                <TextLink
                                                    href={request()}
                                                    className="ml-auto text-sm"
                                                >
                                                    {c.links.forgot_password}
                                                </TextLink>
                                            )}
                                        </div>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            required
                                            autoComplete="current-password"
                                            placeholder={c.placeholders.password}
                                        />
                                        <InputError
                                            message={errors.password}
                                        />
                                    </div>

                                    <div className="flex items-center space-x-3">
                                        <Checkbox id="remember" name="remember" />
                                        <Label htmlFor="remember">
                                            {c.labels.remember}
                                        </Label>
                                    </div>

                                    <Button
                                        type="submit"
                                        className="mt-1 w-full"
                                        disabled={processing}
                                        data-test="login-button"
                                    >
                                        {processing && <Spinner />}
                                        {c.buttons.log_in}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                <section
                    id="create-account"
                    className="rounded-xl border border-neutral-200 p-5 sm:p-6 dark:border-neutral-700"
                >
                    <h2 className="mb-5 text-center text-lg font-semibold text-neutral-900 dark:text-white">
                        {registrationContent.buttons.create_account}
                    </h2>

                    <form
                        className="flex flex-col gap-5"
                        onSubmit={submitRegistration}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="register-name">
                                {registrationContent.labels.name}
                            </Label>
                            <Input
                                id="register-name"
                                type="text"
                                required
                                autoComplete="name"
                                value={registration.data.name}
                                onChange={(event) =>
                                    registration.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                                placeholder={registrationContent.placeholders.name}
                            />
                            <InputError message={registration.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="register-email">
                                {registrationContent.labels.email}
                            </Label>
                            <div className="flex min-w-0 rounded-md border border-input focus-within:ring-[3px] focus-within:ring-ring/50">
                                <Input
                                    id="register-email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    value={registration.data.email}
                                    onChange={(event) =>
                                        updateRegistrationEmail(
                                            event.target.value,
                                        )
                                    }
                                    placeholder={
                                        registrationContent.placeholders.email
                                    }
                                    className="min-w-0 flex-1 border-0 shadow-none focus-visible:ring-0"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="h-9 shrink-0 rounded-l-none border-0 border-l px-3 shadow-none"
                                    disabled={
                                        sendingCode ||
                                        !registration.data.email.trim()
                                    }
                                    onClick={sendVerificationCode}
                                >
                                    {sendingCode ? (
                                        <Spinner />
                                    ) : codeSent ? (
                                        'Send again'
                                    ) : (
                                        'Send code'
                                    )}
                                </Button>
                            </div>
                            <InputError
                                message={
                                    registration.errors.email ?? sendCodeError
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="register-email-code">
                                Email verification code
                            </Label>
                            <Input
                                id="register-email-code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                required
                                value={registration.data.email_code}
                                onChange={(event) =>
                                    registration.setData(
                                        'email_code',
                                        event.target.value.replace(/\D/g, ''),
                                    )
                                }
                                placeholder="Enter the 6-digit code"
                            />
                            <InputError
                                message={registration.errors.email_code}
                            />
                            {codeSent && (
                                <p className="text-xs text-red-600 dark:text-red-400">
                                    Check your inbox for the verification code.
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="register-password">
                                {registrationContent.labels.password}
                            </Label>
                            <PasswordInput
                                id="register-password"
                                required
                                autoComplete="new-password"
                                value={registration.data.password}
                                onChange={(event) =>
                                    registration.setData(
                                        'password',
                                        event.target.value,
                                    )
                                }
                                placeholder={
                                    registrationContent.placeholders.password
                                }
                                passwordrules={passwordRules}
                            />
                            <InputError
                                message={registration.errors.password}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="register-password-confirmation">
                                {registrationContent.labels.password_confirmation}
                            </Label>
                            <PasswordInput
                                id="register-password-confirmation"
                                required
                                autoComplete="new-password"
                                value={registration.data.password_confirmation}
                                onChange={(event) =>
                                    registration.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                                placeholder={
                                    registrationContent.placeholders.password_confirmation
                                }
                                passwordrules={passwordRules}
                            />
                            <InputError
                                message={
                                    registration.errors.password_confirmation
                                }
                            />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={registration.processing}
                            data-test="register-user-button"
                        >
                            {registration.processing && <Spinner />}
                            {registrationContent.buttons.create_account}
                        </Button>
                    </form>
                </section>
            </div>
        </>
    );
}

Login.layout = {
    title: 'Log in or create an account',
    description: 'Log in or create an account to continue to checkout',
    wide: true,
};
