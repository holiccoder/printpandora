import { useForm } from '@inertiajs/react';
import { Copy, TrendingUp, Users } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Commission = {
    id: number;
    amount: number;
    rate: number;
    status: string;
    order_id: number;
    order_total: number;
    created_at: string;
};

type Referral = {
    id: number;
    user_name: string;
    user_email: string;
    status: string;
    created_at: string;
};

type Payout = {
    id: number;
    amount: number;
    status: string;
    created_at: string;
};

type AffiliateData = {
    id: number;
    referral_code: string;
    commission_rate: number;
    status: string;
    total_earnings: number;
    paid_earnings: number;
    pending_earnings: number;
    referral_url: string;
};

export type Props = {
    isAffiliate: boolean;
    affiliate: AffiliateData | null;
    commissions: Commission[];
    referrals: Referral[];
    payouts: Payout[];
};

export default function ManageAffiliate({ isAffiliate, affiliate, commissions, referrals, payouts }: Props) {
    const [copied, setCopied] = useState(false);
    const [showPayoutForm, setShowPayoutForm] = useState(false);

    const joinForm = useForm({});
    const payoutForm = useForm({
        amount: '',
        payment_method: 'paypal',
        payment_details: '',
    });

    const copyLink = () => {
        if (!affiliate) {
return;
}

        navigator.clipboard.writeText(affiliate.referral_url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const submitPayout = () => {
        payoutForm.post('/settings/affiliate/payout', {
            onSuccess: () => {
                setShowPayoutForm(false);
                payoutForm.reset();
            },
        });
    };

    if (!isAffiliate) {
        return (
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Affiliate program"
                    description="Earn commissions by referring new customers to PrintPandora"
                />
                <div className="flex flex-col items-start space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Join our affiliate program and earn {10}% commission on every order made by customers you refer. Share your unique referral link and start earning today.
                    </p>
                    <Button
                        type="button"
                        onClick={() => {
                            joinForm.post('/settings/affiliate', {
                                preserveScroll: true,
                            });
                        }}
                        disabled={joinForm.processing}
                    >
                        {joinForm.processing && <Spinner />}
                        Become an affiliate
                    </Button>
                </div>
            </div>
        );
    }

    if (!affiliate) {
return null;
}

    return (
        <div className="space-y-12">
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Affiliate program"
                    description="Share your referral link and earn commissions"
                />

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="flex items-center gap-4 rounded-lg border p-4">
                        <div className="rounded-full bg-amber-100 p-2 dark:bg-amber-900">
                            <TrendingUp className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Commission rate</p>
                            <p className="text-lg font-bold">{affiliate.commission_rate}%</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4 rounded-lg border p-4">
                        <div className="rounded-full bg-green-100 p-2 dark:bg-green-900">
                            <span className="flex h-5 w-5 items-center justify-center text-sm font-bold text-green-600 dark:text-green-400">$</span>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Total earnings</p>
                            <p className="text-lg font-bold">${affiliate.total_earnings.toFixed(2)}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4 rounded-lg border p-4">
                        <div className="rounded-full bg-blue-100 p-2 dark:bg-blue-900">
                            <Users className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Pending payout</p>
                            <p className="text-lg font-bold">${affiliate.pending_earnings.toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Your referral link"
                    description="Share this link with others to earn commissions"
                />

                <div className="flex items-center gap-2">
                    <Input
                        value={affiliate.referral_url}
                        readOnly
                        className="font-mono text-sm"
                    />
                    <Button variant="outline" onClick={copyLink} className="shrink-0">
                        <Copy className="h-4 w-4" />
                        <span className="ml-2">{copied ? 'Copied!' : 'Copy'}</span>
                    </Button>
                </div>
                <p className="text-xs text-muted-foreground">
                    Referral code: <strong>{affiliate.referral_code}</strong>
                </p>
            </div>

            {affiliate.pending_earnings >= 10 && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Request payout"
                        description={`You have $${affiliate.pending_earnings.toFixed(2)} available for payout`}
                    />

                    {!showPayoutForm ? (
                        <Button variant="outline" onClick={() => setShowPayoutForm(true)}>
                            Request payout
                        </Button>
                    ) : (
                        <div className="max-w-md space-y-4 rounded-lg border p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="payout_amount">Amount ($)</Label>
                                <Input
                                    id="payout_amount"
                                    type="number"
                                    step="0.01"
                                    min="10"
                                    max={affiliate.pending_earnings}
                                    value={payoutForm.data.amount}
                                    onChange={(e) => payoutForm.setData('amount', e.target.value)}
                                    placeholder={`Max $${affiliate.pending_earnings.toFixed(2)}`}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="payout_method">Payment method</Label>
                                <select
                                    id="payout_method"
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                    value={payoutForm.data.payment_method}
                                    onChange={(e) => payoutForm.setData('payment_method', e.target.value)}
                                >
                                    <option value="paypal">PayPal</option>
                                    <option value="bank_transfer">Bank transfer</option>
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="payout_details">Payment details</Label>
                                <Input
                                    id="payout_details"
                                    value={payoutForm.data.payment_details}
                                    onChange={(e) => payoutForm.setData('payment_details', e.target.value)}
                                    placeholder="PayPal email or bank account info"
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    onClick={submitPayout}
                                    disabled={payoutForm.processing}
                                >
                                    {payoutForm.processing && <Spinner />}
                                    Submit request
                                </Button>
                                <Button
                                    variant="ghost"
                                    onClick={() => {
                                        setShowPayoutForm(false);
                                        payoutForm.reset();
                                    }}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {commissions.length > 0 && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Recent commissions"
                        description="Your latest earned commissions"
                    />

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Order</th>
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Amount</th>
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Rate</th>
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Status</th>
                                    <th className="pb-2 font-medium text-muted-foreground">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {commissions.map((c) => (
                                    <tr key={c.id} className="border-b">
                                        <td className="py-2 pr-4">#{c.order_id}</td>
                                        <td className="py-2 pr-4 font-medium">${c.amount.toFixed(2)}</td>
                                        <td className="py-2 pr-4">{c.rate}%</td>
                                        <td className="py-2 pr-4">
                                            <span className="inline-block rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                                                {c.status}
                                            </span>
                                        </td>
                                        <td className="py-2 text-muted-foreground">{c.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {referrals.length > 0 && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Referred users"
                        description="Users who signed up through your referral link"
                    />

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Name</th>
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Email</th>
                                    <th className="pb-2 font-medium text-muted-foreground">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                {referrals.map((r) => (
                                    <tr key={r.id} className="border-b">
                                        <td className="py-2 pr-4">{r.user_name}</td>
                                        <td className="py-2 pr-4 text-muted-foreground">{r.user_email}</td>
                                        <td className="py-2 text-muted-foreground">{r.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {payouts.length > 0 && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Payout history"
                        description="Your payout requests"
                    />

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Amount</th>
                                    <th className="pb-2 pr-4 font-medium text-muted-foreground">Status</th>
                                    <th className="pb-2 font-medium text-muted-foreground">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payouts.map((p) => (
                                    <tr key={p.id} className="border-b">
                                        <td className="py-2 pr-4 font-medium">${p.amount.toFixed(2)}</td>
                                        <td className="py-2 pr-4">
                                            <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${
                                                p.status === 'paid'
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                                    : p.status === 'rejected'
                                                      ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                                                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
                                            }`}>
                                                {p.status}
                                            </span>
                                        </td>
                                        <td className="py-2 text-muted-foreground">{p.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
}
