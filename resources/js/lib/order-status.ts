export const ORDER_STATUS_LABELS: Record<string, string> = {
    pending: '待付款',
    pending_review: '待审核',
    pending_confirmation: '待确认',
    confirmed: '已确认',
    production: '生产中',
    shipped: '已发货',
};

export const ORDER_STATUS_COLORS: Record<string, string> = {
    pending:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    pending_review:
        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
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
