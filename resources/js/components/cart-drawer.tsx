import { Link } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useContent } from '@/hooks/use-content';

/**
 * Right-side cart drawer. Wraps a trigger element (the cart icon in the
 * storefront header) and opens a Radix Sheet on click.
 *
 * All labels and the empty state come from
 * `content/hardcoded-content.json` → `global_chrome.cart_drawer`.
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
    const c = useContent('global_chrome').cart_drawer;
    const isEmpty = items.length === 0;
    const itemCount = items.reduce((sum, i) => sum + i.quantity, 0);

    return (
        <Sheet>
            <SheetTrigger asChild>
                {trigger ?? (
                    <button
                        type="button"
                        aria-label={c.trigger_aria_label}
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
                        {c.header_title}
                        {itemCount > 0 && (
                            <span className="ml-2 text-sm font-normal text-neutral-500">
                                ({itemCount} {itemCount === 1 ? c.item_count_singular : c.item_count_plural})
                            </span>
                        )}
                    </SheetTitle>
                </SheetHeader>

                <div className="flex-1 overflow-y-auto px-6 py-4">
                    {isEmpty ? <EmptyState /> : <CartLines items={items} qtyLabelPrefix={c.quantity_label_prefix} />}
                </div>

                {!isEmpty && (
                    <SheetFooter className="border-t border-neutral-200 bg-white p-6">
                        <div className="mb-3 flex items-center justify-between text-sm">
                            <span className="text-neutral-600">{c.footer.subtotal_label}</span>
                            <span className="font-bold text-neutral-900">
                                {subtotal ?? c.footer.subtotal_fallback}
                            </span>
                        </div>
                        <p className="mb-4 text-xs text-neutral-500">
                            {c.footer.shipping_note}
                        </p>
                        <Button
                            asChild
                            className="w-full bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            <Link href={c.footer.checkout_button_href}>{c.footer.checkout_button_label}</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href={c.footer.view_cart_button_href}>{c.footer.view_cart_button_label}</Link>
                        </Button>
                    </SheetFooter>
                )}
            </SheetContent>
        </Sheet>
    );
}

function EmptyState() {
    const c = useContent('global_chrome').cart_drawer.empty_state;

    return (
        <div className="flex h-full flex-col items-center justify-center text-center">
            <div className="mb-4 flex size-14 items-center justify-center rounded-full bg-neutral-100">
                <ShoppingCart className="size-6 text-neutral-400" />
            </div>
            <h3 className="mb-1 text-base font-semibold text-neutral-900">
                {c.heading}
            </h3>
            <p className="mb-6 max-w-xs text-sm text-neutral-500">
                {c.description}
            </p>
            <Button asChild className="bg-primary text-primary-foreground hover:bg-primary/90">
                <Link href={c.cta_href}>{c.cta_label}</Link>
            </Button>
        </div>
    );
}

function CartLines({ items, qtyLabelPrefix }: { items: CartItem[]; qtyLabelPrefix: string }) {
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
                            {qtyLabelPrefix} {item.quantity}
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
