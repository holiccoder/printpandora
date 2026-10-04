import { router, useForm } from '@inertiajs/react';
import { Check, Download, FileUp, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
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
    delete_url?: string | null;
    can_delete?: boolean;
    version?: number;
    is_current?: boolean;
    source?: string;
};

export type OrderFileSections = {
    // Kept for compatibility with existing order payloads. The workflow UI
    // renders the two sections requested by the order review process.
    uploaded_files: OrderFile[];
    awaiting_confirmation: OrderFile[];
    confirmed_files: OrderFile[];
    latest_rejection: {
        version: number;
        reason: string;
        reviewed_at: string | null;
    } | null;
};

type OrderFilesModalProps = {
    orderId: number;
    files: OrderFileSections;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    content: {
        title: string;
        description: string;
        awaiting_confirmation: string;
        confirmed_files: string;
        file_name: string;
        size: string;
        date: string;
        download: string;
        unavailable: string;
        empty: string;
        upload?: string;
        confirm?: string;
        confirm_help?: string;
        delete?: string;
        current?: string;
        version?: string;
        confirm_warning?: string;
        upload_help?: string;
        review_rejected?: string;
        review_reason?: string;
    };
    canManage?: boolean;
    canConfirm?: boolean;
    isPendingConfirmation?: boolean;
    showConfirmButton?: boolean;
    onFileDownloaded?: () => void;
    uploadUrl?: string | null;
    confirmUrl?: string | null;
};

export default function OrderFilesModal({
    orderId,
    files,
    open,
    onOpenChange,
    content,
    canManage = false,
    canConfirm = false,
    isPendingConfirmation = false,
    showConfirmButton = false,
    onFileDownloaded,
    uploadUrl = null,
    confirmUrl = null,
}: OrderFilesModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>
                        {content.title} #{orderId}
                    </DialogTitle>
                    <DialogDescription>{content.description}</DialogDescription>
                </DialogHeader>

                <div className="space-y-8">
                    {files.latest_rejection?.reason && (
                        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                            <p className="font-semibold">
                                {content.review_rejected ??
                                    'Files need to be re-uploaded'}{' '}
                                (v{files.latest_rejection.version})
                            </p>
                            <p className="mt-1 whitespace-pre-wrap">
                                {content.review_reason ?? 'Review reason'}:{' '}
                                {files.latest_rejection.reason}
                            </p>
                        </div>
                    )}
                    <FileSection
                        title={content.awaiting_confirmation}
                        files={files.awaiting_confirmation}
                        content={content}
                        canManage={canManage}
                        canConfirm={canConfirm}
                        isPendingConfirmation={isPendingConfirmation}
                        showConfirmButton={showConfirmButton}
                        onFileDownloaded={onFileDownloaded}
                        uploadUrl={uploadUrl}
                        confirmUrl={confirmUrl}
                    />
                    <FileSection
                        title={content.confirmed_files}
                        files={files.confirmed_files}
                        content={content}
                        canManage={false}
                        canConfirm={false}
                        isPendingConfirmation={false}
                        showConfirmButton={false}
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
    canManage,
    canConfirm,
    isPendingConfirmation,
    showConfirmButton,
    onFileDownloaded,
    uploadUrl,
    confirmUrl,
}: {
    title: string;
    files: OrderFile[];
    content: NonNullable<OrderFilesModalProps['content']>;
    canManage: boolean;
    canConfirm: boolean;
    isPendingConfirmation: boolean;
    showConfirmButton: boolean;
    onFileDownloaded?: () => void;
    uploadUrl?: string | null;
    confirmUrl?: string | null;
}) {
    const isAwaitingSection = uploadUrl !== undefined;
    const [hasDownloadedFile, setHasDownloadedFile] = useState(false);
    const confirmationReady = canConfirm || hasDownloadedFile;
    const confirmationEnabled = isPendingConfirmation && confirmationReady;

    return (
        <section aria-label={title} className="space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-base font-semibold text-neutral-900">
                    {title}
                </h2>
                {isAwaitingSection && canManage && uploadUrl && (
                    <UploadFilesButton
                        content={content}
                        uploadUrl={uploadUrl}
                    />
                )}
            </div>

            {isAwaitingSection &&
                isPendingConfirmation &&
                files.length > 0 &&
                !confirmationReady && (
                    <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        {content.confirm_help ??
                            'Please download and review the file first. The Confirm files button will appear after you view it.'}
                    </p>
                )}

            {isAwaitingSection && showConfirmButton && confirmUrl && (
                <button
                    type="button"
                    disabled={!confirmationEnabled}
                    onClick={() => {
                        const warning = content.confirm_warning;

                        if (warning && !window.confirm(warning)) {
                            return;
                        }

                        router.post(confirmUrl, undefined, {
                            preserveScroll: true,
                        });
                    }}
                    className={`inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-semibold transition ${
                        confirmationEnabled
                            ? 'bg-[#800020] text-white hover:bg-[#650019]'
                            : 'cursor-not-allowed bg-neutral-200 text-neutral-400'
                    }`}
                >
                    <Check className="size-4" />
                    {content.confirm ?? 'Confirm files'}
                </button>
            )}

            <div className="overflow-hidden rounded-lg border border-neutral-200">
                <table className="w-full min-w-[680px] text-left text-sm">
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
                                    <td className="max-w-[22rem] px-4 py-3">
                                        <div
                                            className="truncate font-medium text-neutral-900"
                                            title={file.filename}
                                        >
                                            {file.filename}
                                        </div>
                                        <div className="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                                            {file.label !== file.filename && (
                                                <span>{file.label}</span>
                                            )}
                                            {file.version !== undefined && (
                                                <span>
                                                    {content.version ??
                                                        'Version'}{' '}
                                                    {file.version}
                                                </span>
                                            )}
                                            {file.is_current && (
                                                <span className="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">
                                                    {content.current ??
                                                        'Current'}
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap text-neutral-600">
                                        {formatFileSize(file.size)}
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap text-neutral-600">
                                        {formatDateTime(file.uploaded_at)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex flex-wrap justify-end gap-3">
                                            {file.download_url ? (
                                                <a
                                                    href={file.download_url}
                                                    download
                                                    onClick={() => {
                                                        if (isAwaitingSection) {
                                                            setHasDownloadedFile(
                                                                true,
                                                            );
                                                            onFileDownloaded?.();
                                                        }
                                                    }}
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
                                            {canManage &&
                                                file.can_delete &&
                                                file.delete_url && (
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            if (
                                                                window.confirm(
                                                                    content.delete ??
                                                                        'Delete this file?',
                                                                )
                                                            ) {
                                                                router.delete(
                                                                    file.delete_url!,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                );
                                                            }
                                                        }}
                                                        className="inline-flex items-center gap-1.5 font-semibold text-red-700 hover:underline"
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                        {content.delete ??
                                                            'Delete'}
                                                    </button>
                                                )}
                                        </div>
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

function UploadFilesButton({
    content,
    uploadUrl,
}: {
    content: NonNullable<OrderFilesModalProps['content']>;
    uploadUrl: string;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, reset } = useForm<{
        files: File[];
    }>({ files: [] });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (data.files.length === 0) {
            inputRef.current?.click();

            return;
        }

        post(uploadUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();

                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
            <input
                ref={inputRef}
                type="file"
                multiple
                accept=".ai,.eps,.pdf,.jpg,.jpeg,.png,.psd,.svg,.tiff,.webp"
                className="sr-only"
                onChange={(event) =>
                    setData('files', Array.from(event.target.files ?? []))
                }
            />
            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                className="inline-flex items-center gap-1.5 rounded-md border border-[#800020] px-3 py-2 text-sm font-semibold text-[#800020] hover:bg-[#800020]/5"
            >
                <FileUp className="size-4" />
                {content.upload ?? 'Upload files'}
            </button>
            {data.files.length > 0 && (
                <button
                    type="submit"
                    disabled={processing}
                    className="inline-flex items-center gap-1.5 rounded-md bg-[#800020] px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"
                >
                    <Upload className="size-4" />
                    {content.upload ?? 'Upload files'} ({data.files.length})
                </button>
            )}
        </form>
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
