import { useForm, usePage } from '@inertiajs/react';
import { Image } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import type { ChangeEventHandler, FormEventHandler, RefObject } from 'react';
import InputError from '@/components/input-error';
import { Button, buttonVariants } from '@/components/ui/button';
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
import { useContent } from '@/hooks/use-content';

export interface DesignServiceOption {
    code: string;
    title: string;
    description?: string;
}

export type DesignSubmissionTarget = 'design-service' | 'product-design';
export type ProductDesignMode = 'upload' | 'design-for-you';

const DESIGN_FILE_ACCEPT = '.ai,.eps,.pdf,.jpg,.jpeg,.png,.psd,.svg,.tiff';
const MAX_DESIGN_FILE_BYTES = 75 * 1024 * 1024;

interface DesignServiceFormProps {
    productOptions?: string[];
    businessCardType?: string;
    businessCardTypeDisabled?: boolean;
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
    submissionTarget?: DesignSubmissionTarget;
    productDesignMode?: ProductDesignMode;
    productId?: number;
    productName?: string;
    productSlug?: string;
    productTypeLabel?: string;
}

type DesignServiceFormData = {
    email: string;
    business_name: string;
    card_info: string;
    business_card_type: string;
    design_service_code: string;
    return_to: string;
    terms_accepted: boolean;
    logo_file: File | null;
    example_files: File[];
    design_file: File | null;
};

export default function DesignServiceForm({
    productOptions,
    businessCardType,
    businessCardTypeDisabled = false,
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
    submissionTarget = 'design-service',
    productDesignMode = 'upload',
    productId,
    productName,
    productSlug,
    productTypeLabel,
}: DesignServiceFormProps) {
    const flashSuccess = (
        usePage().props.flash as { success?: string } | undefined
    )?.success;

    const hasDesignServices = (designServices?.length ?? 0) > 0;
    const uploadContent = useContent('upload_files_modal');
    const requiresDesignFile =
        submissionTarget === 'product-design' && productDesignMode === 'upload';
    const [designServiceError, setDesignServiceError] = useState<string | null>(
        null,
    );
    const [designFileError, setDesignFileError] = useState<string | null>(null);
    const designInputRef = useRef<HTMLInputElement>(null);
    const logoInputRef = useRef<HTMLInputElement>(null);
    const examplesInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, reset, transform } =
        useForm<DesignServiceFormData>({
            email: '',
            business_name: '',
            card_info: '',
            design_service_code: '',
            return_to: returnTo ?? '',
            terms_accepted: false,
            business_card_type: businessCardType ?? '',
            logo_file: null,
            example_files: [],
            design_file: null,
        });

    useEffect(() => {
        if (
            businessCardType !== undefined &&
            data.business_card_type !== businessCardType
        ) {
            setData('business_card_type', businessCardType);
        }
    }, [businessCardType, data.business_card_type, setData]);

    const selectableProductOptions = Array.from(
        new Set(
            businessCardType
                ? [businessCardType, ...(productOptions ?? [])]
                : (productOptions ?? []),
        ),
    );

    const handleLogoChange: ChangeEventHandler<HTMLInputElement> = (event) => {
        setData('logo_file', event.target.files?.[0] ?? null);
    };

    const handleDesignFileChange: ChangeEventHandler<HTMLInputElement> = (
        event,
    ) => {
        const file = event.target.files?.[0] ?? null;

        if (file && file.size > MAX_DESIGN_FILE_BYTES) {
            setData('design_file', null);
            setDesignFileError(uploadContent.file_input_error);
            event.target.value = '';

            return;
        }

        setDesignFileError(null);
        setData('design_file', file);
    };

    const handleExamplesChange: ChangeEventHandler<HTMLInputElement> = (
        event,
    ) => {
        setData('example_files', Array.from(event.target.files ?? []));
    };

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

        transform((formData) => {
            const normalizedData = {
                ...formData,
                business_card_type:
                    businessCardType ?? formData.business_card_type,
                design_service_code: designServiceCode,
            };

            if (submissionTarget === 'product-design') {
                return {
                    desgin: JSON.stringify({
                        source: 'product-page',
                        mode: productDesignMode,
                        product_id: productId ?? null,
                        product_name: productName ?? null,
                        product_slug: productSlug ?? null,
                        email: normalizedData.email,
                        business_name: normalizedData.business_name,
                        card_info: normalizedData.card_info,
                        business_card_type: normalizedData.business_card_type,
                        design_service_code:
                            normalizedData.design_service_code || null,
                        terms_accepted: normalizedData.terms_accepted,
                    }),
                    return_to: normalizedData.return_to,
                    design_file: normalizedData.design_file,
                    logo_file: normalizedData.logo_file,
                    example_files: normalizedData.example_files,
                };
            }

            return normalizedData;
        });

        post(
            submissionTarget === 'product-design'
                ? '/product-designs'
                : '/business-card-design-service',
            {
                forceFormData: true,
                preserveScroll: true,
                // Keep product-page state (incl. the saved design service)
                // alive across the redirect back to the product page.
                preserveState: true,
                onSuccess: () => {
                    if (savedCode) {
                        onDesignServiceSaved?.(savedCode);
                    }

                    reset();

                    if (designInputRef.current) {
                        designInputRef.current.value = '';
                    }

                    if (logoInputRef.current) {
                        logoInputRef.current.value = '';
                    }

                    if (examplesInputRef.current) {
                        examplesInputRef.current.value = '';
                    }

                    onSuccess?.();
                },
            },
        );
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
                            const active = designServiceCode === option.code;

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

            {requiresDesignFile && (
                <FormRow
                    label={uploadContent.file_input_label}
                    error={designFileError ?? errors.design_file}
                >
                    <div className="space-y-2">
                        <UploadButton
                            inputRef={designInputRef}
                            onChange={handleDesignFileChange}
                            accept={DESIGN_FILE_ACCEPT}
                            required
                            large
                            selectedFiles={
                                data.design_file === null
                                    ? []
                                    : [data.design_file]
                            }
                        />
                        <p className="text-xs text-neutral-500">
                            {uploadContent.file_input_help}
                        </p>
                    </div>
                </FormRow>
            )}

            <FormRow label="Company logo" error={errors.logo_file}>
                <div className="space-y-2">
                    <UploadButton
                        inputRef={logoInputRef}
                        onChange={handleLogoChange}
                        selectedFiles={
                            data.logo_file === null ? [] : [data.logo_file]
                        }
                    />
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
                label={productTypeLabel ?? 'Business card type'}
                error={errors.business_card_type}
            >
                <Select
                    required
                    value={data.business_card_type}
                    disabled={businessCardTypeDisabled}
                    onValueChange={(value) =>
                        setData('business_card_type', value)
                    }
                >
                    <SelectTrigger
                        className="w-full"
                        disabled={businessCardTypeDisabled}
                    >
                        <SelectValue placeholder="Please select product" />
                    </SelectTrigger>
                    <SelectContent>
                        {selectableProductOptions.map((option: string) => (
                            <SelectItem key={option} value={option}>
                                {option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </FormRow>

            <FormRow
                label="Business card examples you like"
                error={errors.example_files ?? errors['example_files.0']}
            >
                <UploadButton
                    inputRef={examplesInputRef}
                    onChange={handleExamplesChange}
                    multiple
                    selectedFiles={data.example_files}
                />
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
                        href="/terms-and-conditions"
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
                disabled={
                    !data.terms_accepted ||
                    processing ||
                    (hasDesignServices && !designServiceCode) ||
                    (requiresDesignFile && !data.design_file)
                }
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

function UploadButton({
    inputRef,
    onChange,
    selectedFiles,
    multiple = false,
    accept = '.ai,.eps,.svg,.pdf,.png,.jpg,.jpeg,.webp,.psd',
    large = false,
    required = false,
}: {
    inputRef: RefObject<HTMLInputElement | null>;
    onChange: ChangeEventHandler<HTMLInputElement>;
    selectedFiles: File[];
    multiple?: boolean;
    accept?: string;
    large?: boolean;
    required?: boolean;
}) {
    const inputId = `design-service-upload-${useId().replaceAll(':', '-')}`;

    return (
        <div className="space-y-2">
            <input
                id={inputId}
                ref={inputRef}
                type="file"
                accept={accept}
                multiple={multiple}
                required={required}
                onChange={onChange}
                className="sr-only"
            />
            <label
                htmlFor={inputId}
                className={buttonVariants({
                    variant: 'outline',
                    className: large
                        ? 'flex min-h-36 w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-primary px-6 py-8 text-center text-primary hover:bg-primary/5'
                        : 'cursor-pointer border-primary text-primary hover:bg-primary/5',
                })}
            >
                <Image className={large ? 'size-8' : 'size-4'} />
                <span>
                    {large ? 'Choose a file to upload' : 'UPLOAD FILES'}
                </span>
                {large && (
                    <span className="text-xs font-normal text-neutral-500">
                        AI, EPS, PDF, JPG, PNG, PSD, SVG, or TIFF · Up to 75 MB
                    </span>
                )}
            </label>
            {selectedFiles.length > 0 && (
                <ul
                    className="space-y-1 text-xs text-neutral-600"
                    aria-live="polite"
                >
                    {selectedFiles.map((file, index) => (
                        <li
                            key={`${file.name}-${file.lastModified}-${index}`}
                            className="break-all"
                        >
                            {file.name}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
