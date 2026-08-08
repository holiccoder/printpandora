export interface DiagramArea {
    label: string;
    dimensions: string;
    description: string;
}

export interface DiagramData {
    bleed: DiagramArea;
    trim: DiagramArea;
    safe_area: DiagramArea;
}

export interface DownloadLink {
    id: string;
    label: string;
    extension: string;
    href: string;
    color: string;
}

export interface DesignSpecificationContent {
    heading: string;
    diagram: DiagramData;
    downloads?: DownloadLink[];
}

export interface DesignServiceBannerContent {
    heading: string;
    body: string;
    cta_label: string;
    cta_href: string;
    image_url: string;
    image_alt: string;
}

export interface PaperStockItem {
    id: string;
    name: string;
    price: string;
    image_url: string;
    href: string;
    cta: string;
    features: string[];
}

export interface PaperStockContent {
    heading: string;
    subtitle: string;
    items: PaperStockItem[];
}

export interface MoreGoodStuffItem {
    id: string;
    name: string;
    image_url: string;
    href: string;
    link_label: string;
}

export interface MoreGoodStuffContent {
    heading: string;
    items: MoreGoodStuffItem[];
}

export interface FaqItem {
    question: string;
    answer: string;
}

export interface FaqContent {
    heading: string;
    items: FaqItem[];
}

export interface ProductDetailSections {
    design_specifications?: DesignSpecificationContent;
    design_service_banner?: DesignServiceBannerContent;
    paper_stocks?: PaperStockContent;
    more_good_stuff?: MoreGoodStuffContent;
    faq?: FaqContent;
}
