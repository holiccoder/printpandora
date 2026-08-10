import LegalPage from '@/components/legal-page';
import { useContent } from '@/hooks/use-content';

export function AffiliateTerms() {
    const c = useContent('affiliate_terms_page');

    return (
        <LegalPage
            title={c.title}
            description={c.seo.description}
            intro={c.intro}
            sections={c.sections}
            closingParagraph={c.closing_paragraph}
            closingLinkText={c.closing_link_text}
            closingLinkHref={c.closing_link_href}
        />
    );
}

export default AffiliateTerms;
