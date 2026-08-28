import { useState } from 'react';
import DesignServiceForm from '@/components/design-service-form';
import type { DesignServiceOption } from '@/components/design-service-form';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useContent } from '@/hooks/use-content';

interface DesignServiceFormModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    productOptions?: string[];
    designServices?: DesignServiceOption[];
    designServicesHeading?: string;
    designServicesRequiredError?: string;
    designServicesNote?: string;
    returnTo?: string;
    onDesignServiceSaved?: (code: string) => void;
}

export default function DesignServiceFormModal({
    open,
    onOpenChange,
    title,
    description,
    productOptions,
    designServices,
    designServicesHeading,
    designServicesRequiredError,
    designServicesNote,
    returnTo,
    onDesignServiceSaved,
}: DesignServiceFormModalProps) {
    const ds = useContent('design_service_page') as {
        notes_heading?: string;
        notes?: string[];
    };
    const [designServiceCode, setDesignServiceCode] = useState('');
    const [designServiceError, setDesignServiceError] = useState<string | null>(
        null,
    );
    const hasDesignServices = (designServices?.length ?? 0) > 0;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-7xl">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>

                <div className="mt-4">
                    <div className="grid grid-cols-1 gap-10 md:grid-cols-12">
                        <div className="space-y-8 md:col-span-7">
                            <div>
                                <h3 className="font-serif text-xl font-bold text-[#800020]">
                                    {ds.notes_heading ?? 'Terms & notes'}
                                </h3>
                                <ol className="mt-4 space-y-3">
                                    {(ds.notes ?? []).map((note, i) => (
                                        <li key={i} className="flex gap-3">
                                            <span
                                                className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                                style={{
                                                    backgroundColor: '#800020',
                                                }}
                                            >
                                                {i + 1}
                                            </span>
                                            <p className="text-sm leading-relaxed text-neutral-700">
                                                {note}
                                            </p>
                                        </li>
                                    ))}
                                </ol>
                            </div>

                            {hasDesignServices && (
                                <div className="border-t border-neutral-100 pt-6">
                                    <h3 className="text-base font-bold text-neutral-900">
                                        {designServicesHeading ??
                                            'Choose a design service'}
                                    </h3>
                                    <div className="mt-3 grid grid-cols-1 gap-3">
                                        {designServices!.map((option) => {
                                            const active =
                                                designServiceCode ===
                                                option.code;

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
                                                        onChange={() => {
                                                            setDesignServiceCode(
                                                                option.code,
                                                            );
                                                            setDesignServiceError(
                                                                null,
                                                            );
                                                        }}
                                                        className="mt-0.5 size-4 accent-[#800020]"
                                                    />
                                                    <span>
                                                        <span className="block text-sm font-bold text-neutral-900">
                                                            {option.title}
                                                        </span>
                                                        {option.description && (
                                                            <span className="mt-1 block text-xs leading-relaxed text-neutral-600">
                                                                {
                                                                    option.description
                                                                }
                                                            </span>
                                                        )}
                                                    </span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                    {designServiceError && (
                                        <p className="mt-2 text-sm text-red-600">
                                            {designServiceError}
                                        </p>
                                    )}
                                    {designServicesNote && (
                                        <p className="mt-3 rounded-md border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs leading-relaxed text-neutral-600">
                                            {designServicesNote}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="md:col-span-5">
                            <DesignServiceForm
                                productOptions={productOptions}
                                designServices={designServices}
                                designServicesHeading={designServicesHeading}
                                designServicesRequiredError={
                                    designServicesRequiredError
                                }
                                designServicesNote={designServicesNote}
                                returnTo={returnTo}
                                onDesignServiceSaved={onDesignServiceSaved}
                                designServiceCode={designServiceCode}
                                onDesignServiceCodeChange={setDesignServiceCode}
                                onDesignServiceError={setDesignServiceError}
                                hideDesignServices
                                onSuccess={() => onOpenChange(false)}
                            />
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
