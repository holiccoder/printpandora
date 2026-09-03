import { FileUp } from 'lucide-react';
import { useRef, useState } from 'react';
import type { ChangeEventHandler } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useContent } from '@/hooks/use-content';

const ACCEPTED_FILE_TYPES = '.ai,.eps,.pdf,.jpg,.jpeg,.png,.psd,.svg,.tiff';
const MAX_FILE_SIZE = 75 * 1024 * 1024;

interface UploadFilesModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function UploadFilesModal({
    open,
    onOpenChange,
}: UploadFilesModalProps) {
    const content = useContent('upload_files_modal');
    const inputRef = useRef<HTMLInputElement>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleOpenChange = (nextOpen: boolean) => {
        if (!nextOpen) {
            setSelectedFile(null);
            setError(null);

            if (inputRef.current) {
                inputRef.current.value = '';
            }
        }

        onOpenChange(nextOpen);
    };

    const handleFileChange: ChangeEventHandler<HTMLInputElement> = (event) => {
        const file = event.target.files?.[0] ?? null;

        if (file && file.size > MAX_FILE_SIZE) {
            setSelectedFile(null);
            setError(content.file_input_error);
            event.target.value = '';

            return;
        }

        setError(null);
        setSelectedFile(file);
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{content.title}</DialogTitle>
                </DialogHeader>

                <div className="mt-4 space-y-8">
                    <section aria-label={content.title} className="space-y-5">
                        <ul className="list-disc space-y-2 pl-5 text-sm leading-relaxed text-neutral-700">
                            {content.accepted_formats.map((format) => (
                                <li key={format}>
                                    <FormatInstruction value={format} />
                                </li>
                            ))}
                        </ul>

                        <div className="space-y-3 border-t border-neutral-100 pt-5 text-sm leading-relaxed text-neutral-700">
                            <h3 className="font-bold text-neutral-900">
                                {content.please_note_heading}
                            </h3>
                            {content.please_note_paragraphs.map((paragraph) => (
                                <p key={paragraph}>{paragraph}</p>
                            ))}
                            <p>
                                {content.contact_prefix}
                                <a
                                    href={content.contact_link_href}
                                    className="font-semibold text-primary hover:underline"
                                >
                                    {content.contact_link_label}
                                </a>
                                .
                            </p>
                        </div>
                    </section>

                    <section className="border-t border-neutral-200 pt-8">
                        <label
                            htmlFor="category-upload-file"
                            className="flex min-h-48 w-full cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-primary px-6 py-10 text-center text-primary transition-colors hover:bg-primary/5"
                        >
                            <FileUp className="size-10" />
                            <span className="text-base font-semibold">
                                {content.file_input_label}
                            </span>
                            <span className="text-sm font-normal text-neutral-500">
                                {content.file_input_help}
                            </span>
                            <input
                                id="category-upload-file"
                                ref={inputRef}
                                type="file"
                                accept={ACCEPTED_FILE_TYPES}
                                onChange={handleFileChange}
                                className="sr-only"
                            />
                        </label>

                        {error && (
                            <p
                                className="mt-2 text-sm text-red-600"
                                role="alert"
                            >
                                {error}
                            </p>
                        )}
                        {selectedFile && (
                            <p
                                className="mt-2 text-sm break-all text-neutral-700"
                                aria-live="polite"
                            >
                                {selectedFile.name}
                            </p>
                        )}
                    </section>
                </div>
            </DialogContent>
        </Dialog>
    );
}

function FormatInstruction({ value }: { value: string }) {
    const separator = value.indexOf(':');

    if (separator === -1) {
        return value;
    }

    return (
        <>
            <strong>{value.slice(0, separator + 1)}</strong>
            {value.slice(separator + 1)}
        </>
    );
}
