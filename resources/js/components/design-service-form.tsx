import { useForm, usePage } from '@inertiajs/react';
import { Image } from 'lucide-react';
import { useState } from 'react';
import type { FormEventHandler } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export interface DesignServiceOption {
    code: string;
    title: string;
    description?: string;
}

interface DesignServiceFormProps {
    productOptions?: string[];
    onSuccess?: () => void;
    submitLabel?: string;
    className?: string;
    designServices?: DesignServiceOption[];
    designServicesHeading?: string;
    designServicesRequiredError?: string;
    designServicesNote?: string;
    returnTo?: string;
    onDesignServiceSaved?: (code: string) => void;
    designServiceCode?: string;
    onDesignServiceCodeChange?: (code: string) => void;
    onDesignServiceError?: (message: string | null) => void;
    hideDesignServices?: boolean;
}

type DesignServiceFormData = {
    email: string;
    business_name: string;
    card_info: string;
    business_card_type: string;
    design_service_code: string;
    return_to: string;
    terms_accepted: boolean;
};

export default function DesignServiceForm({
    productOptions,
    onSuccess,
    submitLabel = 'Submit',
    className = '',
    designServices,
    designServicesHeading,
    designServicesRequiredError,
    designServicesNote,
    returnTo,
    onDesignServiceSaved,
    designServiceCode: controlledDesignServiceCode,
    onDesignServiceCodeChange,
    onDesignServiceError,
    hideDesignServices = false,
}: DesignServiceFormProps) {
    const flashSuccess = (
        usePage().props.flash as { success?: string } | undefined
    )?.success;

    const hasDesignServices = (designServices?.length ?? 0) > 0;
    const [designServiceError, setDesignServiceError] = useState<string | null>(
        null,
    );

    const { data, setData, post, processing, errors, reset } =
        useForm<DesignServiceFormData>({
            email: '',
            business_name: '',
            card_info: '',
            business_card_type: '',
            design_service_code: '',
            return_to: returnTo ?? '',
            terms_accepted: false,
        });

    const designServiceCode =
        controlledDesignServiceCode ?? data.design_service_code;

    const setDesignServiceCode = (code: string) => {
        if (onDesignServiceCodeChange) {
            onDesignServiceCodeChange(code);
        } else {
            setData('design_service_code', code);
        }
        setDesignServiceError(null);
        onDesignServiceError?.(null);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (hasDesignServices && !designServiceCode) {
            const message =
                designServicesRequiredError ??
                'Please select a design service.';
            setDesignServiceError(message);
            onDesignServiceError?.(message);

            return;
        }

        setDesignServiceError(null);
        onDesignServiceError?.(null);
        const savedCode = designServiceCode;
        setData('design_service_code', designServiceCode);

        post('/business-card-design-service', {
            preserveScroll: true,
            // Keep product-page state (incl. the saved design service)
            // alive across the redirect back to the product page.
            preserveState: true,
            onSuccess: () => {
                if (savedCode) {
                    onDesignServiceSaved?.(savedCode);
                }

                reset();
                onSuccess?.();
            },
        });
    };

    return (
        <form className={`space-y-5 ${className}`} onSubmit={submit} noValidate>
            {flashSuccess && (
                <div
                    role="status"
                    className="rounded-md border border-[#800020]/20 bg-[#eaf3ec] px-4 py-3 text-sm text-[#800020]"
                >
                    {flashSuccess}
                </div>
            )}

            {hasDesignServices && !hideDesignServices && (
                <FormRow
                    label={designServicesHeading ?? 'Design service'}
                    error={designServiceError ?? undefined}
                >
                    <div
                        role="radiogroup"
                        aria-label={designServicesHeading ?? 'Design service'}
                        className="grid grid-cols-1 gap-3"
                    >
                        {designServices!.map((option) => {
                            const active =
                                designServiceCode === option.code;

                            return (
                                <label
                                    key={option.code}
                                    className={`flex cursor-pointer items-start gap-3 rounded-md border-2 p-4 transition-colors ${
                                        active
                                            ? 'border-[#800020] bg-[#800020]/5'
                                            : 'border-neutral-200 hover:border-neutral-300'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="design_service_code"
                                        value={option.code}
                                        checked={active}
                                        onChange={() =>
                                            setDesignServiceCode(option.code)
                                        }
                                        className="mt-0.5 size-4 accent-[#800020]"
                                    />
                                    <span>
                                        <span className="block text-sm font-bold text-neutral-900">
                                            {option.title}
                                        </span>
                                        {option.description && (
                                            <span className="mt-1 block text-xs leading-relaxed text-neutral-600">
                                                {option.description}
                                            </span>
                                        )}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                    {designServicesNote && (
                        <p className="mt-3 rounded-md border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs leading-relaxed text-neutral-600">
                            {designServicesNote}
                        </p>
                    )}
                </FormRow>
            )}

            <FormRow label="Your primary contact email" error={errors.email}>
                <Input
                    id="ds-email"
                    type="email"
                    placeholder="you@example.com"
                    required
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                />
            </FormRow>

            <FormRow label="Company logo">
                <div className="space-y-2">
                    <UploadButton />
                    <p className="text-xs text-neutral-500">
                        Vector format preferred (AI, EPS, SVG, PDF).
                    </p>
                </div>
            </FormRow>

            <FormRow label="Name of your business" error={errors.business_name}>
                <Input
                    id="ds-business"
                    placeholder="Your business name"
                    required
                    value={data.business_name}
                    onChange={(e) => setData('business_name', e.target.value)}
                />
            </FormRow>

            <FormRow label="Information on the card" error={errors.card_info}>
                <div className="space-y-2">
                    <textarea
                        id="ds-info"
                        rows={4}
                        placeholder="Name, title, contact information, address, website etc."
                        className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        value={data.card_info}
                        onChange={(e) => setData('card_info', e.target.value)}
                    />
                    <p className="text-xs text-neutral-500">
                        Name, title, contact information, address, website etc
                        you want to have on the card.
                    </p>
                </div>
            </FormRow>

            <FormRow
                label="Business card type"
                error={errors.business_card_type}
            >
                <Select
                    required
                    value={data.business_card_type}
                    onValueChange={(value) =>
                        setData('business_card_type', value)
                    }
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Please select product" />
                    </SelectTrigger>
                    <SelectContent>
                        {productOptions?.map((option: string) => (
                            <SelectItem key={option} value={option}>
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </FormRow>

            <FormRow label="Business card examples you like">
                <UploadButton />
            </FormRow>

            <div className="flex items-start gap-3 rounded-md border border-neutral-200 bg-neutral-50 p-4">
                <Checkbox
                    id="ds-terms"
                    checked={data.terms_accepted}
                    onCheckedChange={(checked) =>
                        setData('terms_accepted', checked === true)
                    }
                    className="mt-0.5"
                />
                <Label
                    htmlFor="ds-terms"
                    className="text-sm leading-relaxed text-neutral-700"
                >
                    I agree with{' '}
                    <a
                        href="/terms"
                        className="text-primary hover:underline"
                        onClick={(e) => e.stopPropagation()}
                    >
                        InkPavo's terms and conditions
                    </a>
                </Label>
            </div>
            {errors.terms_accepted && (
                <InputError message={errors.terms_accepted} />
            )}

            <Button
                type="submit"
                className="w-full"
                disabled={!data.terms_accepted || processing}
            >
                {submitLabel}
            </Button>
        </form>
    );
}

function FormRow({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid grid-cols-1 items-start gap-2 sm:grid-cols-[160px_1fr] sm:gap-4">
            <Label className="pt-2 text-sm font-medium text-neutral-900">
                {label}
            </Label>
            <div className="w-full">
                {children}
                {error && <InputError message={error} className="mt-1" />}
            </div>
        </div>
    );
}

function UploadButton() {
    return (
        <Button
            type="button"
            variant="outline"
            className="inline-flex items-center gap-2 border-primary text-primary hover:bg-primary/5"
        >
            <Image className="size-4" />
            UPLOAD FILES
        </Button>
    );
}
