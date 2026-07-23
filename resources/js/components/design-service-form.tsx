import { Link } from '@inertiajs/react';
import { Image } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
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

interface DesignServiceFormProps {
    productOptions?: string[];
    onSuccess?: () => void;
    submitLabel?: string;
    className?: string;
}

export default function DesignServiceForm({
    productOptions,
    onSuccess,
    submitLabel = 'Submit',
    className = '',
}: DesignServiceFormProps) {
    const [agreed, setAgreed] = useState(false);

    return (
        <form
            className={`space-y-5 ${className}`}
            onSubmit={(e) => {
                e.preventDefault();
                toast.success(
                    'Thanks — our design team will contact you by email shortly.',
                );
                onSuccess?.();
            }}
        >
            <FormRow label="Your primary contact email">
                <Input
                    id="ds-email"
                    name="email"
                    type="email"
                    placeholder="you@example.com"
                    required
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

            <FormRow label="Name of your business">
                <Input
                    id="ds-business"
                    name="business_name"
                    placeholder="Your business name"
                    required
                />
            </FormRow>

            <FormRow label="Information on the card">
                <div className="space-y-2">
                    <textarea
                        id="ds-info"
                        name="card_info"
                        rows={4}
                        placeholder="Name, title, contact information, address, website etc."
                        className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <p className="text-xs text-neutral-500">
                        Name, title, contact information, address, website etc
                        you want to have on the card.
                    </p>
                </div>
            </FormRow>

            <FormRow label="Business card type">
                <Select name="business_card_type" required>
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
                    checked={agreed}
                    onCheckedChange={(checked) => setAgreed(checked === true)}
                    className="mt-0.5"
                />
                <Label
                    htmlFor="ds-terms"
                    className="text-sm leading-relaxed text-neutral-700"
                >
                    I agree with{' '}
                    <Link
                        href="/terms"
                        className="text-primary hover:underline"
                        onClick={(e) => e.stopPropagation()}
                    >
                        InkPavo's terms and conditions
                    </Link>
                </Label>
            </div>

            <Button type="submit" className="w-full" disabled={!agreed}>
                {submitLabel}
            </Button>
        </form>
    );
}

function FormRow({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid grid-cols-1 items-start gap-2 sm:grid-cols-[160px_1fr] sm:gap-4">
            <Label className="pt-2 text-sm font-medium text-neutral-900">
                {label}
            </Label>
            <div className="w-full">{children}</div>
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
