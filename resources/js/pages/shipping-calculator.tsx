import { useRef, useState } from 'react';
import SEO from '@/components/seo';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { formatGramsAsPounds } from '@/lib/weight';

type ProductOption = {
    value: string;
    label: string;
    shippingType: string;
    sizes: SizeOption[];
    defaultSize: string;
};

type SizeOption = {
    code: string;
    label: string;
    description?: string;
    unit: string;
    width?: number | string;
    height?: number | string;
    area_sq_m?: number | string;
    min_width?: number | string;
    max_width?: number | string;
    min_height?: number | string;
    max_height?: number | string;
};

type ProductLine = {
    id: number;
    product: string;
    size: string;
    quantity: string;
    customWidth: string;
    customHeight: string;
};

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
    items?: Array<{
        product: string;
        size: string;
        quantity: number;
    }>;
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

function isCustomSize(size: SizeOption): boolean {
    return `${size.code} ${size.label}`.toLowerCase().includes('custom');
}

export default function ShippingCalculator({
    productOptions,
}: {
    productOptions: ProductOption[];
}) {
    const [country, setCountry] = useState('');
    const nextLineId = useRef(2);
    const [productLines, setProductLines] = useState<ProductLine[]>([
        {
            id: 1,
            product: '',
            size: '',
            quantity: '100',
            customWidth: '',
            customHeight: '',
        },
    ]);
    const [quote, setQuote] = useState<ShippingQuote | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [sizeDialogLineId, setSizeDialogLineId] = useState<number | null>(null);

    const sizeDialogLine = productLines.find(
        (line) => line.id === sizeDialogLineId,
    );
    const sizeDialogProduct = productOptions.find(
        (option) => option.value === sizeDialogLine?.product,
    );
    const sizeDialogSize = sizeDialogProduct?.sizes.find(
        (option) => option.code === sizeDialogLine?.size,
    );

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        const incompleteLine = productLines.find((line) => {
            const product = productOptions.find(
                (option) => option.value === line.product,
            );
            const size = product?.sizes.find(
                (option) => option.code === line.size,
            );

            return (
                !product ||
                !size ||
                !Number.isInteger(Number(line.quantity)) ||
                Number(line.quantity) < 1 ||
                (isCustomSize(size) &&
                    (!line.customWidth || !line.customHeight))
            );
        });

        if (!country || incompleteLine) {
            setError('Select a country and complete each product line.');
            setQuote(null);
            return;
        }

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
                    items: productLines.map((line) => {
                        const size = productOptions
                            .find((option) => option.value === line.product)
                            ?.sizes.find((option) => option.code === line.size);

                        return {
                            product_id: Number(line.product),
                            size: line.size,
                            quantity: Number(line.quantity),
                            ...(size && isCustomSize(size)
                                ? {
                                      custom_width: Number(line.customWidth),
                                      custom_height: Number(line.customHeight),
                                  }
                                : {}),
                        };
                    }),
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                const validationMessage = Object.values(
                    data.errors ?? {},
                ).flat()[0] as string | undefined;

                throw new Error(
                    validationMessage ??
                        data.message ??
                        'Unable to calculate shipping right now.',
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

    const updateProductLine = (id: number, changes: Partial<ProductLine>) => {
        setQuote(null);
        setError(null);
        setProductLines((current) =>
            current.map((line) =>
                line.id === id ? { ...line, ...changes } : line,
            ),
        );
    };

    const addProductLine = () => {
        const id = nextLineId.current++;

        setQuote(null);
        setError(null);
        setProductLines((current) => [
            ...current,
            {
                id,
                product: '',
                size: '',
                quantity: '100',
                customWidth: '',
                customHeight: '',
            },
        ]);
    };

    const removeProductLine = (id: number) => {
        setQuote(null);
        setError(null);
        setProductLines((current) => current.filter((line) => line.id !== id));
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
                        <div className="space-y-2">
                            <Label htmlFor="country">Destination country</Label>
                            <Select
                                value={country}
                                onValueChange={(value) => {
                                    setCountry(value);
                                    setQuote(null);
                                    setError(null);
                                }}
                                required
                            >
                                <SelectTrigger id="country" className="w-full">
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

                        <div className="space-y-4">
                            {productLines.map((line, index) => {
                                const selectedProduct = productOptions.find(
                                    (option) => option.value === line.product,
                                );
                                const selectedSize = selectedProduct?.sizes.find(
                                    (option) => option.code === line.size,
                                );
                                const customSize = selectedSize
                                    ? isCustomSize(selectedSize)
                                    : false;
                                const unit = selectedSize?.unit || 'in';

                                return (
                                    <section
                                        key={line.id}
                                        className="space-y-4 rounded-md border border-neutral-200 p-4"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <h2 className="text-sm font-semibold text-neutral-900">
                                                Product {index + 1}
                                            </h2>
                                            {productLines.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        removeProductLine(line.id)
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor={`product-${line.id}`}>
                                                    Product
                                                </Label>
                                                <Select
                                                    value={line.product}
                                                    onValueChange={(value) => {
                                                        const product =
                                                            productOptions.find(
                                                                (option) =>
                                                                    option.value ===
                                                                    value,
                                                            );
                                                        updateProductLine(
                                                            line.id,
                                                            {
                                                                product: value,
                                                                size:
                                                                    product?.defaultSize ??
                                                                    '',
                                                                customWidth: '',
                                                                customHeight: '',
                                                            },
                                                        );
                                                        if (product) {
                                                            setSizeDialogLineId(line.id);
                                                        }
                                                    }}
                                                    required
                                                >
                                                    <SelectTrigger
                                                        id={`product-${line.id}`}
                                                        className="w-full"
                                                    >
                                                        <SelectValue placeholder="Select product" />
                                                    </SelectTrigger>
                                                    <SelectContent className="max-w-[calc(100vw-2rem)]">
                                                        {productOptions.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={option.value}
                                                                    value={option.value}
                                                                    className="whitespace-normal"
                                                                >
                                                                    {option.label}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor={`size-${line.id}`}>
                                                    Size
                                                </Label>
                                                <Select
                                                    value={line.size}
                                                    onValueChange={(value) => {
                                                        updateProductLine(
                                                            line.id,
                                                            {
                                                                size: value,
                                                                customWidth: '',
                                                                customHeight: '',
                                                            },
                                                        );
                                                        if (
                                                            selectedProduct?.sizes.some(
                                                                (size) =>
                                                                    size.code === value &&
                                                                    isCustomSize(size),
                                                            )
                                                        ) {
                                                            setSizeDialogLineId(line.id);
                                                        }
                                                    }}
                                                    disabled={!selectedProduct}
                                                    required
                                                >
                                                    <SelectTrigger
                                                        id={`size-${line.id}`}
                                                        className="w-full"
                                                    >
                                                        <SelectValue placeholder="Select size" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {selectedProduct?.sizes.map(
                                                            (size) => (
                                                                <SelectItem
                                                                    key={size.code}
                                                                    value={size.code}
                                                                >
                                                                    {size.label}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        {customSize && (
                                            <div className="space-y-2">
                                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <div className="space-y-2">
                                                        <Label htmlFor={`width-${line.id}`}>
                                                            Width ({unit})
                                                        </Label>
                                                        <Input
                                                            id={`width-${line.id}`}
                                                            type="number"
                                                            step="any"
                                                            min={selectedSize?.min_width}
                                                            max={selectedSize?.max_width}
                                                            value={line.customWidth}
                                                            onChange={(event) =>
                                                                updateProductLine(
                                                                    line.id,
                                                                    {
                                                                        customWidth:
                                                                            event.target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                            required
                                                        />
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label htmlFor={`height-${line.id}`}>
                                                            Height ({unit})
                                                        </Label>
                                                        <Input
                                                            id={`height-${line.id}`}
                                                            type="number"
                                                            step="any"
                                                            min={selectedSize?.min_height}
                                                            max={selectedSize?.max_height}
                                                            value={line.customHeight}
                                                            onChange={(event) =>
                                                                updateProductLine(
                                                                    line.id,
                                                                    {
                                                                        customHeight:
                                                                            event.target
                                                                                .value,
                                                                    },
                                                                )
                                                            }
                                                            required
                                                        />
                                                    </div>
                                                </div>
                                                {selectedSize?.description && (
                                                    <p className="text-xs text-neutral-500">
                                                        {selectedSize.description}
                                                    </p>
                                                )}
                                            </div>
                                        )}

                                        <div className="space-y-2">
                                            <Label htmlFor={`quantity-${line.id}`}>
                                                Quantity
                                            </Label>
                                            <Input
                                                id={`quantity-${line.id}`}
                                                type="number"
                                                min={1}
                                                max={100000}
                                                value={line.quantity}
                                                onChange={(event) =>
                                                    updateProductLine(line.id, {
                                                        quantity:
                                                            event.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>
                                    </section>
                                );
                            })}

                            <Dialog
                                open={sizeDialogLineId !== null && !!sizeDialogProduct}
                                onOpenChange={(open) => {
                                    if (!open) setSizeDialogLineId(null);
                                }}
                            >
                                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                                    <DialogHeader>
                                        <DialogTitle>Choose product size</DialogTitle>
                                        <DialogDescription>
                                            {sizeDialogProduct?.label}
                                        </DialogDescription>
                                    </DialogHeader>

                                    {sizeDialogLine && sizeDialogProduct && (
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="dialog-product-size">
                                                    Available sizes
                                                </Label>
                                                <Select
                                                    value={sizeDialogLine.size}
                                                    onValueChange={(value) =>
                                                        updateProductLine(
                                                            sizeDialogLine.id,
                                                            {
                                                                size: value,
                                                                customWidth: '',
                                                                customHeight: '',
                                                            },
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger
                                                        id="dialog-product-size"
                                                        className="w-full"
                                                    >
                                                        <SelectValue placeholder="Select size" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {sizeDialogProduct.sizes.map((size) => (
                                                            <SelectItem key={size.code} value={size.code}>
                                                                {size.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            {sizeDialogSize && isCustomSize(sizeDialogSize) && (
                                                <div className="space-y-2">
                                                    <p className="text-sm font-medium text-neutral-900">
                                                        Enter custom dimensions ({sizeDialogSize.unit || 'in'})
                                                    </p>
                                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                        <div className="space-y-2">
                                                            <Label htmlFor={`dialog-width-${sizeDialogLine.id}`}>
                                                                Width ({sizeDialogSize.unit || 'in'})
                                                            </Label>
                                                            <Input
                                                                id={`dialog-width-${sizeDialogLine.id}`}
                                                                type="number"
                                                                step="any"
                                                                min={sizeDialogSize.min_width}
                                                                max={sizeDialogSize.max_width}
                                                                value={sizeDialogLine.customWidth}
                                                                onChange={(event) =>
                                                                    updateProductLine(sizeDialogLine.id, {
                                                                        customWidth: event.target.value,
                                                                    })
                                                                }
                                                                required
                                                            />
                                                        </div>
                                                        <div className="space-y-2">
                                                            <Label htmlFor={`dialog-height-${sizeDialogLine.id}`}>
                                                                Height ({sizeDialogSize.unit || 'in'})
                                                            </Label>
                                                            <Input
                                                                id={`dialog-height-${sizeDialogLine.id}`}
                                                                type="number"
                                                                step="any"
                                                                min={sizeDialogSize.min_height}
                                                                max={sizeDialogSize.max_height}
                                                                value={sizeDialogLine.customHeight}
                                                                onChange={(event) =>
                                                                    updateProductLine(sizeDialogLine.id, {
                                                                        customHeight: event.target.value,
                                                                    })
                                                                }
                                                                required
                                                            />
                                                        </div>
                                                    </div>
                                                    {sizeDialogSize.description && (
                                                        <p className="text-xs text-neutral-500">
                                                            {sizeDialogSize.description}
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            onClick={() => setSizeDialogLineId(null)}
                                        >
                                            Done
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>

                            <Button
                                type="button"
                                variant="outline"
                                className="w-full"
                                onClick={addProductLine}
                                disabled={productLines.length >= 20}
                            >
                                Add another product
                            </Button>
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={loading}
                        >
                            {loading ? 'Calculating...' : 'Calculate shipping'}
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
                                {formatGramsAsPounds(
                                    quote.shipping_weight_grams,
                                )}{' '}parcel. Final
                                checkout totals are recalculated when you place
                                the order.
                            </p>
                            {quote.items && (
                                <ul className="mb-4 space-y-1 text-sm text-neutral-600">
                                    {quote.items.map((item, index) => (
                                        <li key={`${item.product}-${index}`}>
                                            {item.product} - {item.size} -{' '}
                                            {item.quantity.toLocaleString()} pcs
                                        </li>
                                    ))}
                                </ul>
                            )}
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
