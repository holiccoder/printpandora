import { Download } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export type OrderFile = {
    id: string;
    filename: string;
    label: string;
    size: number | null;
    uploaded_at: string | null;
    download_url: string | null;
};

export type OrderFileSections = {
    uploaded_files: OrderFile[];
    awaiting_confirmation: OrderFile[];
    confirmed_files: OrderFile[];
};

type OrderFilesModalProps = {
    orderId: number;
    files: OrderFileSections;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    content: {
        title: string;
        description: string;
        uploaded_files: string;
        awaiting_confirmation: string;
        confirmed_files: string;
        file_name: string;
        size: string;
        date: string;
        download: string;
        unavailable: string;
        empty: string;
    };
};

export default function OrderFilesModal({
    orderId,
    files,
    open,
    onOpenChange,
    content,
}: OrderFilesModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {content.title} #{orderId}
                    </DialogTitle>
                    <DialogDescription>{content.description}</DialogDescription>
                </DialogHeader>

                <div className="space-y-8">
                    <FileSection
                        title={content.uploaded_files}
                        files={files.uploaded_files}
                        content={content}
                    />
                    <FileSection
                        title={content.awaiting_confirmation}
                        files={files.awaiting_confirmation}
                        content={content}
                    />
                    <FileSection
                        title={content.confirmed_files}
                        files={files.confirmed_files}
                        content={content}
                    />
                </div>
            </DialogContent>
        </Dialog>
    );
}

function FileSection({
    title,
    files,
    content,
}: {
    title: string;
    files: OrderFile[];
    content: OrderFilesModalProps['content'];
}) {
    return (
        <section aria-label={title} className="space-y-3">
            <h2 className="text-base font-semibold text-neutral-900">
                {title}
            </h2>

            <div className="overflow-hidden rounded-lg border border-neutral-200">
                <table className="w-full min-w-[620px] text-left text-sm">
                    <thead className="bg-neutral-50 text-xs tracking-wide text-neutral-500 uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">
                                {content.file_name}
                            </th>
                            <th className="px-4 py-3 font-medium">
                                {content.size}
                            </th>
                            <th className="px-4 py-3 font-medium">
                                {content.date}
                            </th>
                            <th className="px-4 py-3 text-right font-medium">
                                {content.download}
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-100">
                        {files.length > 0 ? (
                            files.map((file) => (
                                <tr key={file.id}>
                                    <td className="max-w-[20rem] px-4 py-3">
                                        <div
                                            className="truncate font-medium text-neutral-900"
                                            title={file.filename}
                                        >
                                            {file.filename}
                                        </div>
                                        {file.label !== file.filename && (
                                            <div className="mt-0.5 text-xs text-neutral-500">
                                                {file.label}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap text-neutral-600">
                                        {formatFileSize(file.size)}
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap text-neutral-600">
                                        {formatDateTime(file.uploaded_at)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {file.download_url ? (
                                            <a
                                                href={file.download_url}
                                                download
                                                className="inline-flex items-center gap-1.5 font-semibold hover:underline"
                                                style={{ color: '#800020' }}
                                            >
                                                {content.download}
                                                <Download className="size-3.5" />
                                            </a>
                                        ) : (
                                            <span className="text-neutral-400">
                                                {content.unavailable}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    colSpan={4}
                                    className="px-4 py-8 text-center text-sm text-neutral-500"
                                >
                                    {content.empty}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

function formatFileSize(size: number | null): string {
    if (size === null) {
        return '—';
    }

    if (size < 1024) {
        return `${size} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = size / 1024;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[unitIndex]}`;
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
