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
    { value: 'DE', label: 'Germany' },
    { value: 'FR', label: 'France' },
    { value: 'JP', label: 'Japan' },
    { value: 'CN', label: 'China' },
    { value: 'OTHER', label: 'Rest of World' },
];

// Placeholder rates — replace with real data / API integration later.
const RATES: Record<
    string,
    Array<{ method: string; price: number; days: string; note: string }>
> = {
    default: [
        {
            method: 'Standard Shipping',
            price: 5.99,
            days: '5–8 business days',
            note: ' placeholder rate',
        },
        {
            method: 'Express Shipping',
            price: 14.99,
            days: '2–4 business days',
            note: ' placeholder rate',
        },
        {
            method: 'Next Day Delivery',
            price: 24.99,
            days: '1 business day',
            note: ' placeholder rate (US only)',
        },
    ],
};

export default function ShippingCalculator() {
    const [country, setCountry] = useState('');
    const [productType, setProductType] = useState('');
    const [quantity, setQuantity] = useState('100');
    const [submitted, setSubmitted] = useState(false);

    const results = RATES.default;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitted(true);
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
                                        {COUNTRIES.map((c) => (
                                            <SelectItem
                                                key={c.value}
                                                value={c.value}
                                            >
                                                {c.label}
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
                                        {PRODUCT_TYPES.map((p) => (
                                            <SelectItem
                                                key={p.value}
                                                value={p.value}
                                            >
                                                {p.label}
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
                                value={quantity}
                                onChange={(e) => setQuantity(e.target.value)}
                                required
                            />
                        </div>

                        <Button type="submit" className="w-full">
                            Calculate shipping
                        </Button>
                    </form>

                    {submitted && (
                        <div className="mt-8">
                            <p className="mb-4 text-sm font-medium text-amber-700">
                                These are placeholder rates for demo purposes.
                                Real-time rates will replace them once the
                                shipping API is connected.
                            </p>
                            <div className="overflow-hidden rounded-lg border border-neutral-200">
                                <table className="w-full text-sm">
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
                                        {results.map((rate) => (
                                            <tr key={rate.method}>
                                                <td className="px-4 py-3 font-medium text-neutral-900">
                                                    {rate.method}
                                                    <span className="ml-1 text-xs font-normal text-neutral-500">
                                                        {rate.note}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-neutral-700">
                                                    ${rate.price.toFixed(2)}
                                                </td>
                                                <td className="px-4 py-3 text-neutral-700">
                                                    {rate.days}
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
