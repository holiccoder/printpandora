export function formatOrderOptions(options: Record<string, unknown>): string {
    return Object.entries(options)
        .filter(
            ([key, value]) =>
                key !== 'design_service_request_id' && hasOptionValue(value),
        )
        .map(
            ([key, value]) =>
                `${humanizeOptionText(key)}: ${formatOrderOptionValue(value)}`,
        )
        .join(', ');
}

export function formatOrderOptionValue(value: unknown): string {
    if (Array.isArray(value)) {
        return value.map(formatOrderOptionValue).join(', ');
    }

    if (typeof value === 'object' && value !== null) {
        return Object.entries(value as Record<string, unknown>)
            .filter(([, nestedValue]) => hasOptionValue(nestedValue))
            .map(
                ([key, nestedValue]) =>
                    `${humanizeOptionText(key)}: ${formatOrderOptionValue(nestedValue)}`,
            )
            .join(', ');
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    return humanizeOptionText(String(value));
}

export function humanizeOptionText(value: string): string {
    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function hasOptionValue(value: unknown): boolean {
    if (value === null || value === undefined || value === '') {
        return false;
    }

    if (Array.isArray(value)) {
        return value.some(hasOptionValue);
    }

    if (typeof value === 'object') {
        return Object.values(value as Record<string, unknown>).some(
            hasOptionValue,
        );
    }

    return true;
}
