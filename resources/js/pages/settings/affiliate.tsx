// Content (labels/headings) sourced from `content/hardcoded-content.json` via useContent('settings_affiliate_page').
import type { Props as ManageAffiliateProps } from '@/components/manage-affiliate';
import ManageAffiliate from '@/components/manage-affiliate';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import { edit } from '@/routes/affiliate';

type Props = ManageAffiliateProps;

export default function Affiliate(props: Props) {
    const c = useContent('settings_affiliate_page') as any;

    return (
        <div className="container mx-auto w-full max-w-5xl space-y-6 px-0 py-2">
            <SEO title={c.seo.title} description={c.seo.description} />
            <h1 className="sr-only">{c.sr_heading}</h1>
            <ManageAffiliate
                isAffiliate={props.isAffiliate}
                affiliate={props.affiliate}
                commissions={props.commissions}
                referrals={props.referrals}
                payouts={props.payouts}
            />
        </div>
    );
}

Affiliate.layout = {
    breadcrumbs: [
        {
            title: 'Affiliate settings',
            href: edit(),
        },
    ],
};
