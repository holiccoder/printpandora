export const ORDER_STATUS_LABELS: Record<string, string> = {
    pending: 'Pending Payment',
    pending_review: 'Pending Review',
    needs_reupload: 'Needs File Re-upload',
    pending_confirmation: 'Pending Confirmation',
    confirmed: 'Confirmed',
    production: 'In Production',
    shipped: 'Shipped',
};

export const ORDER_STATUS_COLORS: Record<string, string> = {
    pending:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    pending_review:
        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
    needs_reupload: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
    pending_confirmation:
        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
    confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    production:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100',
    shipped:
        'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-100',
};

export function orderStatusLabel(status: string): string {
    return ORDER_STATUS_LABELS[status] ?? status;
}
