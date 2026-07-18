import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, LogOut, Menu, Package, Search, User, UserCog } from 'lucide-react';
import { useState } from 'react';
import { CartDrawer } from '@/components/cart-drawer';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';
import { dashboard, home, login, logout, register } from '@/routes';

import type { NavLink, NestedLink, PromoCard } from '@/types/content';

/**
 * One link in the mega dropdown. `children` (when set) renders a small
 * `>` chevron next to the label and pops a third-level vertical flyout
 * to the right when the user hovers the item.
 */
type MegaLink = NestedLink;

/**
 * A logical block of links separated from neighbours by a dotted rule.
 */
type MegaGroup = {
    links: MegaLink[];
};

type PromoBlock = PromoCard;

type MegaMenu = {
    groups: MegaGroup[];
    promos: [PromoBlock, PromoBlock];
};

type NavCategory = {
    label: string;
    href: string;
    /** when present, the item shows a mega dropdown on hover */
    mega?: MegaMenu;
};

const ACTIVE_GREEN = 'text-[#0f4c3a]';
const INACTIVE_GREY = 'text-neutral-500 hover:text-neutral-900';

export function StorefrontHeader({ activeCategory }: { activeCategory?: string } = {}) {
    const chrome = useContent('global_chrome');
    const h = chrome.header;
    const { auth } = usePage().props as unknown as { auth?: { user?: { name?: string } | null } };
    const user = auth?.user;

    // Build nav categories from JSON content
    const navCategories: NavCategory[] = h.top_navigation.map((nav: NavLink) => {
        if (nav.label === 'Business Cards') {
            const bc = h.business_cards_mega_menu;

            return {
                label: nav.label,
                href: nav.href,
                mega: {
                    groups: bc.link_groups.map((g) => ({
                        links: g.links.map((l) => ({
                            ...l,
                            children: l.children as MegaLink[] | undefined,
                        })),
                    })),
                    promos: bc.promo_cards as [PromoBlock, PromoBlock],
                },
            };
        }

        return { label: nav.label, href: nav.href };
    });

    return (
        <header className="relative z-40 w-full border-b border-neutral-200 bg-white">
            {/* top row — mobile menu / logo / search / cart */}
            <div className="mx-auto flex h-20 max-w-7xl items-center gap-4 px-4">
                {/* mobile menu */}
                <div className="lg:hidden">
                    <MobileNav activeCategory={activeCategory} navCategories={navCategories} />
                </div>

                {/* logo */}
                <Link href={home()} className="flex items-center gap-2" aria-label={h.logo.home_link_aria_label}>
                    <img
                        src={h.logo.image_url}
                        alt={h.logo.alt}
                        className="h-12 w-auto"
                    />
                </Link>

                {/* center search */}
                <form
                    role="search"
                    action={h.search.form_action}
                    method="get"
                    className="mx-auto hidden w-full max-w-xl flex-1 lg:block"
                >
                    <label htmlFor="storefront-search" className="sr-only">
                        {h.search.label_sr_only}
                    </label>
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input
                            id="storefront-search"
                            name={h.search.input_name}
                            type="search"
                            placeholder={h.search.placeholder}
                            className="h-10 w-full rounded-full border border-neutral-200 bg-neutral-50 pl-10 pr-4 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-[#0f4c3a] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0f4c3a]/20"
                        />
                    </div>
                </form>

                {/* right side actions */}
                <div className="ml-auto flex items-center gap-1">
                    {/* mobile-only icon search — opens the same /search page */}
                    <Link
                        href={h.search.mobile_icon_href}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-neutral-100 lg:hidden"
                        aria-label={h.search.mobile_icon_aria_label}
                    >
                        <Search className="size-5 opacity-80" />
                    </Link>

                    {/* auth buttons — desktop */}
                    {user ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="hidden h-9 gap-2 px-3 text-sm font-medium text-neutral-700 hover:text-[#0f4c3a] lg:inline-flex"
                                >
                                    <User className="size-4" />
                                    <span>{h.auth.dashboard_button_label}</span>
                                    <ChevronDown className="size-3.5 opacity-70" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" sideOffset={8} className="w-56">
                                {user.name && (
                                    <DropdownMenuLabel className="font-normal">
                                        <p className="text-xs text-neutral-500">{h.auth.signed_in_as_label}</p>
                                        <p className="truncate text-sm font-semibold text-neutral-900">
                                            {user.name}
                                        </p>
                                    </DropdownMenuLabel>
                                )}
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href={dashboard()} className="cursor-pointer">
                                        <User className="mr-2 size-4" />
                                        {h.auth.dropdown_dashboard_label}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={h.auth.dropdown_profile_href} className="cursor-pointer">
                                        <UserCog className="mr-2 size-4" />
                                        {h.auth.dropdown_profile_label}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={h.auth.dropdown_orders_href} className="cursor-pointer">
                                        <Package className="mr-2 size-4" />
                                        {h.auth.dropdown_orders_label}
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={logout()}
                                        as="button"
                                        onClick={() => router.flushAll()}
                                        className="w-full cursor-pointer"
                                        data-test="logout-button"
                                    >
                                        <LogOut className="mr-2 size-4" />
                                        {h.auth.dropdown_logout_label}
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        <div className="hidden items-center gap-2 lg:flex">
                            <Button
                                asChild
                                variant="ghost"
                                className="h-9 px-3 text-sm font-medium text-neutral-700 hover:text-[#0f4c3a]"
                            >
                                <Link href={login()}>{h.auth.logged_out_login_label}</Link>
                            </Button>
                            <Button
                                asChild
                                className="h-9 bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                <Link href={register()}>{h.auth.logged_out_register_label}</Link>
                            </Button>
                        </div>
                    )}

                    {/* compact account icon on mobile */}
                    <Link
                        href={user ? dashboard() : login()}
                        aria-label={
                            user
                                ? h.auth.mobile_account_aria_label_logged_in
                                : h.auth.mobile_account_aria_label_logged_out
                        }
                        className="inline-flex h-9 w-9 items-center justify-center rounded-md hover:bg-neutral-100 lg:hidden"
                    >
                        <User className="size-5 opacity-80" />
                    </Link>

                    <CartDrawer />
                </div>
            </div>

            {/* nav row — desktop categories sit below the logo */}
            <div className="hidden border-t border-neutral-100 lg:block">
                <div className="mx-auto max-w-7xl px-4">
                    <NavigationMenu
                        viewport={false}
                        className="h-12 max-w-none justify-start"
                        delayDuration={100}
                    >
                        <NavigationMenuList className="flex h-12 items-stretch justify-start gap-0">
                            {navCategories.map((cat) => {
                                const isActive =
                                    activeCategory != null && cat.label === activeCategory;
                                const triggerCls = cn(
                                    'relative flex h-12 items-center px-4 text-base font-medium transition-colors',
                                    isActive ? ACTIVE_GREEN : INACTIVE_GREY,
                                );

                                if (!cat.mega) {
                                    return (
                                        <NavigationMenuItem key={cat.label}>
                                            <Link href={cat.href} className={triggerCls}>
                                                <span>{cat.label}</span>
                                                {isActive && <ActiveUnderline />}
                                            </Link>
                                        </NavigationMenuItem>
                                    );
                                }

                                return (
                                    <NavigationMenuItem key={cat.label}>
                                        <NavigationMenuTrigger
                                            className={cn(
                                                triggerCls,
                                                // Override the shadcn pill background — the storefront
                                                // header uses an underline indicator instead.
                                                'rounded-none bg-transparent hover:bg-transparent focus:bg-transparent data-[state=open]:bg-transparent data-[active=true]:bg-transparent',
                                                // Hide the chevron icon on this header
                                                '[&>svg]:hidden',
                                            )}
                                        >
                                            <span>{cat.label}</span>
                                            {isActive && <ActiveUnderline />}
                                        </NavigationMenuTrigger>
                                        <NavigationMenuContent
                                            // Pinned to the viewport. Offset = announcement bar (h-9 = 36px)
                                            // + top row (h-20 = 80px) + nav row (h-12 = 48px) so the panel
                                            // sits flush under the nav. The trust bar below the nav is
                                            // intentionally covered while the dropdown is open. z-50 keeps
                                            // it above sibling sections (hero carousel, banners) that open
                                            // their own stacking context with `position: relative`.
                                            className="!fixed !inset-x-0 !top-[164px] !left-0 !z-50 !mt-0 !w-screen !max-w-none border-t border-neutral-200 bg-white p-0 shadow-lg"
                                        >
                                            <MegaPanel mega={cat.mega} />
                                        </NavigationMenuContent>
                                    </NavigationMenuItem>
                                );
                            })}
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>
            </div>
        </header>
    );
}

function ActiveUnderline() {
    return (
        <span
            aria-hidden
            className="pointer-events-none absolute inset-x-3 bottom-0 h-[3px] rounded-t-sm bg-[#0f4c3a]"
        />
    );
}

function MegaPanel({ mega }: { mega: MegaMenu }) {
    // Which chevron link (if any) is currently being hovered. The
    // third-level flyout occupies the centre column whenever this is set.
    const [activeSub, setActiveSub] = useState<MegaLink | null>(null);

    return (
        <div
            className="mx-auto grid max-w-7xl grid-cols-12 gap-6 px-6 py-6"
            // Closing the sub-flyout when the cursor leaves the whole panel
            // gives a much smoother feel than relying on per-row leave events.
            onMouseLeave={() => setActiveSub(null)}
        >
            {/* Left: link groups */}
            <ul className="col-span-12 space-y-2 md:col-span-3">
                {mega.groups.map((group, gi) => (
                    <li key={gi}>
                        <ul className="space-y-1">
                            {group.links.map((link) => {
                                const hasChildren = !!link.children?.length;
                                const isActive = activeSub?.label === link.label;

                                return (
                                    <li
                                        key={link.label}
                                        onMouseEnter={() =>
                                            setActiveSub(hasChildren ? link : null)
                                        }
                                    >
                                        <Link
                                            href={link.href}
                                            className={cn(
                                                'group flex items-center justify-between rounded-sm py-1 text-sm text-neutral-700 hover:text-[#0f4c3a]',
                                                isActive && 'text-[#0f4c3a]',
                                            )}
                                        >
                                            <span>{link.label}</span>
                                            {hasChildren && (
                                                <ChevronRight
                                                    className={cn(
                                                        'size-4 text-neutral-400 group-hover:text-[#0f4c3a]',
                                                        isActive && 'text-[#0f4c3a]',
                                                    )}
                                                />
                                            )}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                        {gi < mega.groups.length - 1 && (
                            <div
                                aria-hidden
                                className="mt-2 border-t border-dotted border-neutral-300"
                            />
                        )}
                    </li>
                ))}
            </ul>

            {/* Middle: third-level vertical flyout. Reserve the slot even
                when nothing is hovered so the right-hand promos don't shift. */}
            <div className="col-span-12 md:col-span-3">
                {activeSub && (
                    <div className="rounded-md border border-neutral-200 bg-white p-4">
                        <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-neutral-500">
                            {activeSub.label}
                        </p>
                        <ul className="space-y-1">
                            {activeSub.children!.map((sub) => (
                                <li key={sub.label}>
                                    <Link
                                        href={sub.href}
                                        className="block rounded-sm py-1 text-sm text-neutral-700 hover:text-[#0f4c3a]"
                                    >
                                        {sub.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>

            {/* Right: two compact promo cards side by side */}
            <div className="col-span-12 grid grid-cols-1 gap-4 md:col-span-6 md:grid-cols-2">
                {mega.promos.map((promo) => (
                    <PromoCard key={promo.title} promo={promo} />
                ))}
            </div>
        </div>
    );
}

function PromoCard({ promo }: { promo: PromoBlock }) {
    return (
        <div className="flex flex-col">
            <Link href={promo.cta_href} className="block overflow-hidden rounded-md bg-white">
                <img
                    src={promo.image_url}
                    alt={promo.image_alt}
                    className="aspect-[4/3] h-full w-full object-cover"
                    loading="lazy"
                />
            </Link>
            <h3 className="mt-2 text-sm font-bold leading-snug text-neutral-900">
                {promo.title}
            </h3>
            <p className="mt-1 line-clamp-2 text-xs leading-snug text-neutral-600">
                {promo.description}
            </p>
            <Link
                href={promo.cta_href}
                className="mt-2 inline-flex items-center gap-0.5 text-xs font-semibold text-[#0f4c3a] hover:underline"
            >
                {promo.cta_label} <ChevronRight className="size-3.5" />
            </Link>
        </div>
    );
}

/**
 * Mobile drawer — flat accordion-ish list. Mega dropdowns collapse to a
 * link to the category page; sub-categories still show as a nested list
 * so the user can drill in from a phone.
 */
function MobileNav({ activeCategory, navCategories }: { activeCategory?: string; navCategories: NavCategory[] }) {
    const [openMega, setOpenMega] = useState<string | null>(null);
    const chrome = useContent('global_chrome');
    const h = chrome.header;

    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant="ghost" size="icon" className="h-9 w-9" aria-label={h.mobile_nav.open_menu_aria_label}>
                    <Menu className="size-5" />
                </Button>
            </SheetTrigger>
            <SheetContent side="left" className="w-80 overflow-y-auto p-0">
                <SheetHeader className="border-b px-4 py-3">
                    <SheetTitle className="text-left">
                        <img
                            src={h.mobile_nav.drawer_logo_image_url}
                            alt={h.mobile_nav.drawer_logo_alt}
                            className="h-7 w-auto"
                        />
                    </SheetTitle>
                </SheetHeader>
                <nav className="py-2">
                    {navCategories.map((cat) => {
                        const isActive =
                            activeCategory != null && cat.label === activeCategory;
                        const isOpen = openMega === cat.label;

                        return (
                            <div key={cat.label} className="border-b border-neutral-100 last:border-b-0">
                                {cat.mega ? (
                                    <button
                                        type="button"
                                        onClick={() => setOpenMega(isOpen ? null : cat.label)}
                                        className={cn(
                                            'flex w-full items-center justify-between px-4 py-3 text-sm font-medium',
                                            isActive ? ACTIVE_GREEN : 'text-neutral-700',
                                        )}
                                    >
                                        <span>{cat.label}</span>
                                        <ChevronRight
                                            className={cn(
                                                'size-4 transition-transform',
                                                isOpen && 'rotate-90',
                                            )}
                                        />
                                    </button>
                                ) : (
                                    <Link
                                        href={cat.href}
                                        className={cn(
                                            'flex w-full items-center justify-between px-4 py-3 text-sm font-medium',
                                            isActive ? ACTIVE_GREEN : 'text-neutral-700',
                                        )}
                                    >
                                        {cat.label}
                                    </Link>
                                )}
                                {cat.mega && isOpen && (
                                    <ul className="bg-neutral-50 pb-2">
                                        {cat.mega.groups.flatMap((g) => g.links).map((link) => (
                                            <li key={link.label}>
                                                <Link
                                                    href={link.href}
                                                    className="block px-6 py-2 text-sm text-neutral-600 hover:text-[#0f4c3a]"
                                                >
                                                    {link.label}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        );
                    })}
                </nav>
            </SheetContent>
        </Sheet>
    );
}

export default StorefrontHeader;