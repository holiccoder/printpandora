/**
 * Content tree loaded from content/hardcoded-content.json and shared
 * via Inertia as `props.content`.
 *
 * This module defines the top-level section keys we consume in the
 * frontend. Per-section interfaces are added incrementally as tiers
 * are migrated; untyped sections fall through to `unknown` so the
 * whole app never breaks when a new section appears in the JSON.
 */

// ---------------------------------------------------------------------------
// Tier A — global chrome
// ---------------------------------------------------------------------------

export interface AnnouncementMessage {
    text: string;
    href: string;
}

export interface AnnouncementBarContent {
    region_aria_label: string;
    default_interval_ms: number;
    default_messages: AnnouncementMessage[];
}

export interface NestedLink {
    label: string;
    href: string;
    children?: NestedLink[];
}

export interface LinkGroup {
    links: NestedLink[];
}

export interface PromoCard {
    image_url: string;
    image_alt: string;
    title: string;
    description: string;
    cta_label: string;
    cta_href: string;
}

export interface NavLink {
    label: string;
    href: string;
}

export interface BusinessCardsMegaMenu {
    link_groups: LinkGroup[];
    promo_cards: [PromoCard, PromoCard];
}

export interface HeaderContent {
    logo: {
        image_url: string;
        alt: string;
        home_link_aria_label: string;
    };
    search: {
        form_action: string;
        input_name: string;
        label_sr_only: string;
        placeholder: string;
        mobile_icon_aria_label: string;
        mobile_icon_href: string;
    };
    auth: {
        dashboard_button_label: string;
        signed_in_as_label: string;
        dropdown_dashboard_label: string;
        dropdown_profile_label: string;
        dropdown_profile_href: string;
        dropdown_orders_label: string;
        dropdown_orders_href: string;
        dropdown_logout_label: string;
        logged_out_login_label: string;
        logged_out_register_label: string;
        mobile_account_aria_label_logged_in: string;
        mobile_account_aria_label_logged_out: string;
    };
    mobile_nav: {
        open_menu_aria_label: string;
        drawer_logo_image_url: string;
        drawer_logo_alt: string;
    };
    top_navigation: NavLink[];
    business_cards_mega_menu: BusinessCardsMegaMenu;
}

export interface FooterLegalLink {
    label: string;
    href: string;
}

export interface FooterLegalBar {
    copyright_text: string;
    legal_links: FooterLegalLink[];
}

export interface FooterContent {
    column_headings: {
        products: string;
        paper_stocks: string;
        about_us: string;
        help: string;
    };
    products_left_column: NavLink[];
    products_right_column: NavLink[];
    paper_stocks: NavLink[];
    about_us: NavLink[];
    help_links: NavLink[];
    legal_bar: FooterLegalBar;
}

export interface CartDrawerContent {
    trigger_aria_label: string;
    header_title: string;
    item_count_singular: string;
    item_count_plural: string;
    empty_state: {
        heading: string;
        description: string;
        cta_label: string;
        cta_href: string;
    };
    quantity_label_prefix: string;
    footer: {
        subtotal_label: string;
        subtotal_fallback: string;
        shipping_note: string;
        checkout_button_label: string;
        checkout_button_href: string;
        view_cart_button_label: string;
        view_cart_button_href: string;
    };
}

export interface GlobalChromeContent {
    header: HeaderContent;
    footer: FooterContent;
    announcement_bar: AnnouncementBarContent;
    cart_drawer: CartDrawerContent;
}

// ---------------------------------------------------------------------------
// Tier B — static pages
// ---------------------------------------------------------------------------

export interface LegalSection {
    heading?: string;
    level?: string;
    body?: string;
    list_items?: Array<
        | string
        | { strong?: string; text?: string }
    >;
    email_link?: string;
    contact_link_text?: string;
    contact_link_href?: string;
}

export interface PrivacyPageContent {
    eyebrow: string;
    title: string;
    description: string;
    intro: string;
    sections: LegalSection[];
}

export interface TermsPageContent {
    eyebrow: string;
    title: string;
    description: string;
    intro: string;
    sections: LegalSection[];
}

export interface NotFoundPageContent {
    seo: {
        title: string;
        description: string;
        robots: string;
    };
    graphic_text: string;
    heading: string;
    description: string;
    action_links: NavLink[];
    quick_links_heading: string;
    quick_links: NavLink[];
}

export interface AboutPageContent {
    eyebrow: string;
    title: string;
    description: string;
    body_paragraphs: string[];
    sections: LegalSection[];
    closing_paragraph: string;
    closing_link_text: string;
    closing_link_href: string;
}

// ---------------------------------------------------------------------------
// Tier C — storefront pages
// ---------------------------------------------------------------------------

export interface SeoMeta {
    page_title?: string;
    page_description?: string;
    title?: string;
    description?: string;
    type?: string;
    robots?: string;
}

export interface HeroSlide {
    headline: string;
    subheadline: string;
    cta_text: string;
    cta_href: string;
    image_url: string;
    alt: string;
}

export interface PerkItem {
    title: string;
    description: string;
    href: string;
}

export interface PopularProduct {
    name: string;
    image_url: string | null;
    alt: string | null;
    cta: string;
    href: string;
}

export interface BlogCategory {
    label: string;
    href: string;
    active?: boolean;
}

export interface BlogHeroSlide {
    image_url: string;
    alt: string;
    category: string;
    headline: string;
    excerpt: string;
    date: string;
    read_minutes: number;
    href: string;
    sticker_text?: string;
}

export interface BlogHeroContent {
    title: string;
    categories_aria_label: string;
    search_label: string;
    search_placeholder: string;
    prev_aria_label: string;
    next_aria_label: string;
    tablist_aria_label: string;
    go_to_article_template: string;
    read_time_template: string;
    default_categories: BlogCategory[];
    default_slides: BlogHeroSlide[];
}

export interface BlogIndexPageContent {
    seo: SeoMeta;
    active_category: string;
    page_heading: string;
    empty_state: string;
    pagination: {
        prev_label: string;
        next_label: string;
        page_indicator_template: string;
    };
    footer_copyright: string;
    blog_hero: BlogHeroContent;
}

export interface BlogShowPageContent {
    active_category: string;
    brand_name: string;
    breadcrumb_aria_label: string;
    breadcrumb_root_label: string;
    breadcrumb_root_href: string;
    breadcrumb_separator: string;
    metadata: {
        by_prefix: string;
        read_time_template: string;
    };
    share_buttons: {
        twitter_label: string;
        facebook_label: string;
        linkedin_label: string;
        copy_link_label: string;
    };
    find_your_card_form: {
        heading: string;
        description: string;
        fields: Array<{ id: string; label: string; required: boolean }>;
        legal_text: string;
        legal_link_text: string;
        legal_link_href: string;
        submit_label: string;
        submit_label_processing: string;
        success_message: string;
    };
    newsletter_card: {
        title: string;
        description: string;
        cta: string;
        cta_href: string;
    };
    related_section: {
        heading: string;
    };
}

export interface ShopIndexPageContent {
    seo: SeoMeta;
    page_heading: string;
    all_button_label: string;
    empty_state: string;
}

export interface HomePageContent {
    seo: SeoMeta;
    hero_carousel: {
        aria_label: string;
        prev_button_label: string;
        next_button_label: string;
        slides: HeroSlide[];
    };
    popular_products: {
        section_title: string;
        section_description: string;
        footer_cta_text: string;
        footer_cta_href: string;
        products: PopularProduct[];
    };
    perks: {
        section_aria_label: string;
        items: PerkItem[];
        trustpilot: { label: string; href: string };
    };
    sample_pack_banner: {
        title: string;
        description: string;
        cta_text: string;
        cta_href: string;
        image_url: string;
        alt: string;
    };
    recent_posts: {
        eyebrow: string;
        section_title: string;
        view_all_cta: string;
        view_all_href: string;
    };
}

// ---------------------------------------------------------------------------
// Catch-all for any section not yet typed
// ---------------------------------------------------------------------------

/**
 * All recognised section keys. Declare a key here once its interface
 * exists so the discriminated `useContent('section')` overload resolves.
 */
export interface ContentSections {
    global_chrome: GlobalChromeContent;
    home_page: HomePageContent;
    blog_index_page: BlogIndexPageContent;
    blog_show_page: BlogShowPageContent;
    shop_index_page: ShopIndexPageContent;
    privacy_page: PrivacyPageContent;
    terms_page: TermsPageContent;
    not_found_page: NotFoundPageContent;
    about_page: AboutPageContent;
    // untuned sections fall through to unknown
}

export type SectionKey = keyof ContentSections;

/**
 * Root content type — a record of section-name → section-data.
 * Typed sections get autocomplete; untyped ones are `unknown`.
 *
 * Because every section is declared here as unknown | KnownInterface
 * and we use the discriminated overload in the hook, this type is
 * rarely accessed directly by consumers.
 */
export type Content = {
    [K in SectionKey]: ContentSections[K];
} & Record<string, Record<string, unknown>>;