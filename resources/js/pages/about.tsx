import LegalPage from '@/components/legal-page';
import { useContent } from '@/hooks/use-content';

export default function About() {
    const c = useContent('about_page');

    return (
        <LegalPage
            eyebrow={c.eyebrow}
            title={c.title}
            description={c.description}
            bodyParagraphs={c.body_paragraphs}
            sections={c.sections}
            closingParagraph={c.closing_paragraph}
            closingLinkText={c.closing_link_text}
            closingLinkHref={c.closing_link_href}
        />
    );
}
