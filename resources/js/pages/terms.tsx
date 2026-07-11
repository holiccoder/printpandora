import LegalPage from '@/components/legal-page';
import { useContent } from '@/hooks/use-content';

export default function Terms() {
    const c = useContent('terms_page');

    return (
        <LegalPage
            eyebrow={c.eyebrow}
            title={c.title}
            description={c.description}
            intro={c.intro}
            sections={c.sections}
        />
    );
}
