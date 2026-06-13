import { Link } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import { ReactNode } from 'react';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';

/**
 * Right-side cart drawer. Wraps a trigger element (the cart icon in the
 * storefront header) and opens a Radix Sheet on click.
 *
 * The actual cart state isn't wired yet — for now this renders an empty
 * state with a CTA back into the shop. When we plumb cart items in, swap
 * the empty block for a list of CartLine rows and pull totals from props.
 */

export type CartItem = {
    id: number | string;
    name: string;
    /** Display price, e.g. "$12.00" */
    price: string;
    quantity: number;
    image?: string | null;
    href: string;
};

type Props = {
    /** Render-prop trigger; defaults to a cart icon button. */
    trigger?: ReactNode;
    items?: CartItem[];
    /** Pre-formatted subtotal string, e.g. "$48.00". */
    subtotal?: string;
};

const ACCENT = '#0f4c3a';

export function CartDrawer({ trigger, items = [], subtotal }: Props) {
    const isEmpty = items.length === 0;
    const itemCount = items.reduce((sum, i) => sum + i.quantity, 0);

    return (
        <Sheet>
            <SheetTrigger asChild>
                {trigger ?? (
                    <button
                        type="button"
                        aria-label="Open cart"
                        className="relative inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-neutral-100"
                    >
                        <ShoppingCart className="size-5 opacity-80" />
                        {itemCount > 0 && (
                            <span
                                className="absolute -top-0.5 -right-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-bold text-white"
                                style={{ backgroundColor: ACCENT }}
                            >
                                {itemCount}
                            </span>
                        )}
                    </button>
                )}
            </SheetTrigger>
            <SheetContent
                side="right"
                className="flex w-full flex-col gap-0 p-0 sm:max-w-md"
            >
                <SheetHeader className="border-b border-neutral-200 px-6 py-4">
                    <SheetTitle className="text-lg font-bold text-neutral-900">
                        Your cart
                        {itemCount > 0 && (
                            <span className="ml-2 text-sm font-normal text-neutral-500">
                                ({itemCount} {itemCount === 1 ? 'item' : 'items'})
                            </span>
                        )}
                    </SheetTitle>
                </SheetHeader>

                <div className="flex-1 overflow-y-auto px-6 py-4">
                    {isEmpty ? <EmptyState /> : <CartLines items={items} />}
                </div>

                {!isEmpty && (
                    <SheetFooter className="border-t border-neutral-200 bg-white p-6">
                        <div className="mb-3 flex items-center justify-between text-sm">
                            <span className="text-neutral-600">Subtotal</span>
                            <span className="font-bold text-neutral-900">
                                {subtotal ?? '—'}
                            </span>
                        </div>
                        <p className="mb-4 text-xs text-neutral-500">
                            Shipping and taxes calculated at checkout.
                        </p>
                        <Button
                            asChild
                            className="w-full"
                            style={{ backgroundColor: ACCENT }}
                        >
                            <Link href="/checkout">Checkout</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href="/cart">View cart</Link>
                        </Button>
                    </SheetFooter>
                )}
            </SheetContent>
        </Sheet>
    );
}

function EmptyState() {
    return (
        <div className="flex h-full flex-col items-center justify-center text-center">
            <div className="mb-4 flex size-14 items-center justify-center rounded-full bg-neutral-100">
                <ShoppingCart className="size-6 text-neutral-400" />
            </div>
            <h3 className="mb-1 text-base font-semibold text-neutral-900">
                Your cart is empty
            </h3>
            <p className="mb-6 max-w-xs text-sm text-neutral-500">
                Looks like you haven't added anything yet. Start with our most
                popular print products.
            </p>
            <Button asChild style={{ backgroundColor: ACCENT }}>
                <Link href="/shop">Continue shopping</Link>
            </Button>
        </div>
    );
}

function CartLines({ items }: { items: CartItem[] }) {
    return (
        <ul className="divide-y divide-neutral-200">
            {items.map((item) => (
                <li key={item.id} className="flex gap-3 py-4">
                    <Link
                        href={item.href}
                        className="block size-16 shrink-0 overflow-hidden rounded bg-neutral-100"
                    >
                        {item.image ? (
                            <img
                                src={item.image}
                                alt={item.name}
                                className="h-full w-full object-cover"
                                loading="lazy"
                            />
                        ) : null}
                    </Link>
                    <div className="flex flex-1 flex-col">
                        <Link
                            href={item.href}
                            className="text-sm font-medium text-neutral-900 hover:text-[#0f4c3a]"
                        >
                            {item.name}
                        </Link>
                        <span className="mt-1 text-xs text-neutral-500">
                            Qty {item.quantity}
                        </span>
                    </div>
                    <span className="text-sm font-semibold text-neutral-900">
                        {item.price}
                    </span>
                </li>
            ))}
        </ul>
    );
}

export default CartDrawer;
