export const ORDER_STATUS_LABELS: Record<string, string> = {
    pending: '待付款',
    confirmed: '已确认',
    pending_modification: '待修改',
    pending_production: '待生产',
    production: '生产中',
    pending_shipment: '待发货',
    shipped: '已发货',
    cancelled: '已取消',
};

export const ORDER_STATUS_COLORS: Record<string, string> = {
    pending:
        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    pending_modification:
        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
    pending_production:
        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
    production:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100',
    pending_shipment:
        'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-100',
    shipped:
        'bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-100',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
};

export function orderStatusLabel(status: string): string {
    return ORDER_STATUS_LABELS[status] ?? status;
}
