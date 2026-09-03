import { useState } from 'react';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import StorefrontLayout from '@/layouts/storefront-layout';

const PRODUCT_TYPES = [
    { value: 'business-cards', label: 'Business Cards' },
    { value: 'cotton-business-cards', label: 'Cotton Business Cards' },
    { value: 'pvc-business-cards', label: 'PVC Business Cards' },
    { value: 'postcards', label: 'Postcards' },
    { value: 'stickers-labels', label: 'Stickers & Labels' },
    { value: 'flyers', label: 'Flyers' },
];

const COUNTRIES = [
    { value: 'US', label: 'United States' },
    { value: 'CA', label: 'Canada' },
    { value: 'GB', label: 'United Kingdom' },
    { value: 'AU', label: 'Australia' },
    { value: 'NZ', label: 'New Zealand' },
    { value: 'DE', label: 'Germany' },
    { value: 'FR', label: 'France' },
    { value: 'JP', label: 'Japan' },
    { value: 'CN', label: 'China' },
    { value: 'OTHER', label: 'Rest of World' },
];

type ShippingMethod = {
    code: string;
    label: string;
    carrier: string;
    fee: number;
    currency: string;
    description: string;
    estimated_delivery: string;
};

type ShippingQuote = {
    shipping_weight_grams: number;
    currency: string;
    methods: ShippingMethod[];
};

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function formatCurrency(amount: number, currency: string): string {
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency,
        }).format(amount);
    } catch {
        return `${currency} ${amount.toFixed(2)}`;
    }
}

export default function ShippingCalculator() {
    const [country, setCountry] = useState('');
    const [productType, setProductType] = useState('');
    const [quantity, setQuantity] = useState('100');
    const [quote, setQuote] = useState<ShippingQuote | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        setQuote(null);

        try {
            const response = await fetch('/api/shipping/quote', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    country,
                    product_type: productType,
                    quantity: Number(quantity),
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.message ?? 'Unable to calculate shipping right now.',
                );
            }

            setQuote(data as ShippingQuote);
        } catch (exception) {
            setError(
                exception instanceof Error
                    ? exception.message
                    : 'Unable to calculate shipping right now.',
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <StorefrontLayout>
            <SEO
                title="Shipping Calculator"
                description="Estimate shipping costs and delivery times for InkPavo orders."
            />

            <div className="bg-white">
                <div className="mx-auto max-w-3xl px-4 py-12 lg:py-16">
                    <header className="mb-8 text-center">
                        <h1 className="text-3xl font-bold text-neutral-900">
                            Shipping Calculator
                        </h1>
                        <p className="mt-2 text-sm text-neutral-600">
                            Get an estimated shipping cost and delivery time for
                            your order.
                        </p>
                    </header>

                    <form
                        onSubmit={handleSubmit}
                        className="space-y-6 rounded-lg border border-neutral-200 bg-white p-6 shadow-sm"
                    >
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="country">
                                    Destination country
                                </Label>
                                <Select
                                    value={country}
                                    onValueChange={setCountry}
                                    required
                                >
                                    <SelectTrigger id="country">
                                        <SelectValue placeholder="Select country" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {COUNTRIES.map((item) => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="product-type">
                                    Product type
                                </Label>
                                <Select
                                    value={productType}
                                    onValueChange={setProductType}
                                    required
                                >
                                    <SelectTrigger id="product-type">
                                        <SelectValue placeholder="Select product" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {PRODUCT_TYPES.map((item) => (
                                            <SelectItem
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="quantity">Quantity</Label>
                            <Input
                                id="quantity"
                                type="number"
                                min={1}
                                max={100000}
                                value={quantity}
                                onChange={(e) => setQuantity(e.target.value)}
                                required
                            />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={loading}
                        >
                            {loading ? 'Calculating…' : 'Calculate shipping'}
                        </Button>
                    </form>

                    {error && (
                        <p
                            role="alert"
                            className="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        >
                            {error}
                        </p>
                    )}

                    {quote && (
                        <div className="mt-8" aria-live="polite">
                            <p className="mb-4 text-sm font-medium text-neutral-600">
                                Server-side estimate for a{' '}
                                {quote.shipping_weight_grams} g parcel. Final
                                checkout totals are recalculated when you place
                                the order.
                            </p>
                            <div className="overflow-x-auto rounded-lg border border-neutral-200">
                                <table className="w-full min-w-[640px] text-sm">
                                    <thead className="bg-neutral-50 text-left">
                                        <tr>
                                            <th className="px-4 py-3 font-semibold text-neutral-700">
                                                Method
                                            </th>
                                            <th className="px-4 py-3 font-semibold text-neutral-700">
                                                Price
                                            </th>
                                            <th className="px-4 py-3 font-semibold text-neutral-700">
                                                Estimated time
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {quote.methods.map((method) => (
                                            <tr key={method.code}>
                                                <td className="px-4 py-3 font-medium text-neutral-900">
                                                    {method.label}
                                                    <span className="ml-1 text-xs font-normal text-neutral-500">
                                                        ({method.carrier})
                                                    </span>
                                                    <p className="mt-1 text-xs font-normal text-neutral-500">
                                                        {method.description}
                                                    </p>
                                                </td>
                                                <td className="px-4 py-3 text-neutral-700">
                                                    {formatCurrency(
                                                        method.fee,
                                                        method.currency ??
                                                            quote.currency,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-neutral-700">
                                                    {method.estimated_delivery}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </StorefrontLayout>
    );
}
