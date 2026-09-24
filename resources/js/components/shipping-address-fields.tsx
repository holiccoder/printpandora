import { useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { countries, countriesByCode } from '@/data/countries';

export type ShippingAddressValues = {
    shipping_address?: string | null;
    shipping_city?: string | null;
    shipping_state?: string | null;
    shipping_zip?: string | null;
    shipping_country?: string | null;
};

export type ShippingAddressContent = {
    heading: string;
    description: string;
    labels: {
        address: string;
        city: string;
        state: string;
        zip: string;
        country: string;
    };
    placeholders: {
        address: string;
        city: string;
        state: string;
        zip: string;
        country: string;
    };
};

type FormErrors = Record<string, string | undefined>;

type ShippingAddressFieldsProps = {
    initialValues: ShippingAddressValues;
    content: ShippingAddressContent;
    errors: FormErrors;
};

const selectClassName =
    'border-input focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

export default function ShippingAddressFields({
    initialValues,
    content,
    errors,
}: ShippingAddressFieldsProps) {
    const [country, setCountry] = useState(
        initialValues.shipping_country ?? '',
    );
    const [state, setState] = useState(initialValues.shipping_state ?? '');
    const availableStates = countriesByCode[country]?.states ?? [];

    const handleCountryChange = (nextCountry: string) => {
        setCountry(nextCountry);
        setState((currentState) => {
            const nextStates = countriesByCode[nextCountry]?.states ?? [];

            return nextStates.some((option) => option.code === currentState)
                ? currentState
                : '';
        });
    };

    return (
        <section className="space-y-4 border-t pt-6">
            <div>
                <h2 className="text-base font-semibold">{content.heading}</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    {content.description}
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="shipping_address">
                        {content.labels.address}
                    </Label>
                    <Input
                        id="shipping_address"
                        name="shipping_address"
                        defaultValue={initialValues.shipping_address ?? ''}
                        autoComplete="street-address"
                        placeholder={content.placeholders.address}
                        aria-invalid={Boolean(errors.shipping_address)}
                    />
                    <InputError message={errors.shipping_address} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="shipping_city">{content.labels.city}</Label>
                    <Input
                        id="shipping_city"
                        name="shipping_city"
                        defaultValue={initialValues.shipping_city ?? ''}
                        autoComplete="address-level2"
                        placeholder={content.placeholders.city}
                        aria-invalid={Boolean(errors.shipping_city)}
                    />
                    <InputError message={errors.shipping_city} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="shipping_zip">{content.labels.zip}</Label>
                    <Input
                        id="shipping_zip"
                        name="shipping_zip"
                        defaultValue={initialValues.shipping_zip ?? ''}
                        autoComplete="postal-code"
                        placeholder={content.placeholders.zip}
                        aria-invalid={Boolean(errors.shipping_zip)}
                    />
                    <InputError message={errors.shipping_zip} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="shipping_country">
                        {content.labels.country}
                    </Label>
                    <select
                        id="shipping_country"
                        name="shipping_country"
                        value={country}
                        autoComplete="country"
                        onChange={(event) =>
                            handleCountryChange(event.currentTarget.value)
                        }
                        className={selectClassName}
                        aria-invalid={Boolean(errors.shipping_country)}
                    >
                        <option value="">{content.placeholders.country}</option>
                        {countries.map((option) => (
                            <option key={option.code} value={option.code}>
                                {option.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.shipping_country} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="shipping_state">
                        {content.labels.state}
                    </Label>
                    <select
                        id="shipping_state"
                        name="shipping_state"
                        value={state}
                        autoComplete="address-level1"
                        disabled={!country || availableStates.length === 0}
                        onChange={(event) =>
                            setState(event.currentTarget.value)
                        }
                        className={selectClassName}
                        aria-invalid={Boolean(errors.shipping_state)}
                    >
                        <option value="">{content.placeholders.state}</option>
                        {availableStates.map((option) => (
                            <option key={option.code} value={option.code}>
                                {option.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.shipping_state} />
                </div>
            </div>
        </section>
    );
}
