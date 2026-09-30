// Content (labels/placeholders/links) sourced from `content/hardcoded-content.json` via useContent('settings_security_page').
import { Form } from '@inertiajs/react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import type { Props as ManageTwoFactorProps } from '@/components/manage-two-factor';
import ManageTwoFactor from '@/components/manage-two-factor';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useContent } from '@/hooks/use-content';
import { edit } from '@/routes/security';

type Props = {
    status?: string;
} & ManageTwoFactorProps;

export default function Security(props: Props) {
    const c = useContent('settings_security_page') as any;

    return (
        <>
            <SEO title={c.seo.title} description={c.seo.description} />

            <h1 className="sr-only">{c.sr_heading}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={c.section_heading}
                    description={c.section_description}
                />

                <Form
                    {...SecurityController.requestPasswordReset.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <p className="text-sm text-muted-foreground">
                                {c.password_help}
                            </p>

                            <InputError message={errors.password} />

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="send-password-change-link-button"
                                >
                                    {processing && <Spinner />}
                                    {c.buttons.send_reset_link}
                                </Button>
                            </div>

                            {props.status === 'password-reset-link-sent' && (
                                <p className="text-sm font-medium text-green-600">
                                    {c.password_reset_sent_message}
                                </p>
                            )}
                        </>
                    )}
                </Form>
            </div>

            <ManageTwoFactor
                canManageTwoFactor={props.canManageTwoFactor}
                requiresConfirmation={props.requiresConfirmation}
                twoFactorEnabled={props.twoFactorEnabled}
            />
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security settings',
            href: edit(),
        },
    ],
};
