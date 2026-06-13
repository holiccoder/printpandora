import type { Props as ManageAffiliateProps } from '@/components/manage-affiliate';
import ManageAffiliate from '@/components/manage-affiliate';
import SEO from '@/components/seo';
import { edit } from '@/routes/affiliate';

type Props = ManageAffiliateProps;

export default function Affiliate(props: Props) {
    return (
        <>
            <SEO
                title="Affiliate settings"
                description="Manage your PrintPandora affiliate account, referral link, and earnings."
            />
            <h1 className="sr-only">Affiliate settings</h1>
            <ManageAffiliate
                isAffiliate={props.isAffiliate}
                affiliate={props.affiliate}
                commissions={props.commissions}
                referrals={props.referrals}
                payouts={props.payouts}
            />
        </>
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
