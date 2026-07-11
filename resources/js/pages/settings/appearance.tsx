// Content (headings/descriptions) sourced from `content/hardcoded-content.json` via useContent('settings_appearance_page').
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const c = useContent('settings_appearance_page') as any;

    return (
        <>
            <SEO title={c.seo.title} description={c.seo.description} />

            <h1 className="sr-only">{c.sr_heading}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={c.section_heading}
                    description={c.section_description}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};