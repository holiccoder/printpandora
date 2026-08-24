import { MessageCircle, Paperclip, SendHorizonal, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useContent } from '@/hooks/use-content';
import { cn } from '@/lib/utils';

type ChatMessage = {
    id?: number;
    role: 'user' | 'assistant' | 'admin';
    content: string;
    attachment_url?: string | null;
    attachment_name?: string | null;
};

const SESSION_STORAGE_KEY = 'ai_chat_session_id';
const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;

function getSessionId(): string {
    try {
        const existing = window.localStorage.getItem(SESSION_STORAGE_KEY);

        if (existing) {
            return existing;
        }

        const id = crypto.randomUUID();
        window.localStorage.setItem(SESSION_STORAGE_KEY, id);

        return id;
    } catch {
        return crypto.randomUUID();
    }
}

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

function isImageAttachment(url?: string | null): boolean {
    return !!url && /\.(jpe?g|png|gif|webp)$/i.test(url);
}

/**
 * Storefront support chat. AI mode streams replies from POST /ai/chat (SSE);
 * human mode (after the customer asks for a human) posts plain messages and
 * polls for admin replies. translate="no" keeps Google Translate away from
 * the streaming/polling DOM updates.
 */
export function AiChatWidget() {
    const c = useContent('ai_chat');

    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [streaming, setStreaming] = useState(false);
    const [failed, setFailed] = useState(false);
    const [mode, setMode] = useState<'ai' | 'human'>('ai');
    const [attachment, setAttachment] = useState<File | null>(null);
    const scrollRef = useRef<HTMLDivElement | null>(null);
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const lastIdRef = useRef(0);
    const loadedRef = useRef(false);

    useEffect(() => {
        scrollRef.current?.scrollTo({
            top: scrollRef.current.scrollHeight,
            behavior: 'smooth',
        });
    }, [messages, open]);

    // Load history once, then poll for admin replies while the panel is open.
    useEffect(() => {
        if (!open) {
            return;
        }

        let cancelled = false;

        const poll = async () => {
            try {
                const params = new URLSearchParams({
                    session_id: getSessionId(),
                    after_id: String(lastIdRef.current),
                });
                const response = await fetch(`/ai/chat/poll?${params}`);

                if (!response.ok || cancelled) {
                    return;
                }

                const data = await response.json();

                if (cancelled) {
                    return;
                }

                setMode(data.mode === 'human' ? 'human' : 'ai');

                const incoming = (data.messages ?? []) as ChatMessage[];

                if (incoming.length > 0) {
                    lastIdRef.current =
                        incoming[incoming.length - 1].id ?? lastIdRef.current;
                    setMessages((current) => {
                        const known = new Set(
                            current.map((m) => m.id).filter(Boolean),
                        );
                        const fresh = incoming.filter((m) => !known.has(m.id));

                        return fresh.length > 0
                            ? [...current, ...fresh]
                            : current;
                    });
                }

                loadedRef.current = true;
            } catch {
                // Polling failures are silent — the next tick retries.
            }
        };

        poll();
        const timer = window.setInterval(poll, 4000);

        return () => {
            cancelled = true;
            window.clearInterval(timer);
        };
    }, [open]);

    const appendMessage = (message: ChatMessage) => {
        if (message.id) {
            lastIdRef.current = Math.max(lastIdRef.current, message.id);
        }

        setMessages((current) => [...current, message]);
    };

    const requestHuman = async () => {
        setFailed(false);

        try {
            const response = await fetch('/ai/chat/handoff', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ session_id: getSessionId() }),
            });

            if (!response.ok) {
                throw new Error(`Handoff failed (${response.status})`);
            }

            setMode('human');
            appendMessage({
                role: 'assistant',
                content: c.human_mode_notice,
            });
        } catch {
            setFailed(true);
        }
    };

    const sendHumanMessage = async (text: string) => {
        const body = new FormData();
        body.append('session_id', getSessionId());
        body.append('message', text);

        if (attachment) {
            body.append('attachment', attachment);
        }

        const response = await fetch('/ai/chat/message', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            body,
        });

        if (!response.ok) {
            throw new Error(`Message failed (${response.status})`);
        }

        const data = await response.json();
        setAttachment(null);
        appendMessage(data.message as ChatMessage);
    };

    const send = async (raw: string) => {
        const text = raw.trim();

        if ((!text && !attachment) || streaming) {
            return;
        }

        setFailed(false);
        setInput('');
        setStreaming(true);

        if (mode === 'human') {
            try {
                await sendHumanMessage(text);
            } catch {
                setFailed(true);
            } finally {
                setStreaming(false);
            }

            return;
        }

        const history = messages.slice(-10);
        const base: ChatMessage[] = [
            ...messages,
            { role: 'user', content: text },
        ];
        // Placeholder assistant message that deltas stream into.
        setMessages([...base, { role: 'assistant', content: '' }]);

        try {
            const response = await fetch('/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    message: text,
                    session_id: getSessionId(),
                    history,
                }),
            });

            if (response.status === 409) {
                // The conversation was handed over to a human elsewhere.
                setMode('human');
                setMessages(base);

                return;
            }

            if (!response.ok || !response.body) {
                throw new Error(`Chat request failed (${response.status})`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            for (;;) {
                const { done, value } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });

                const blocks = buffer.split('\n\n');
                buffer = blocks.pop() ?? '';

                for (const block of blocks) {
                    const line = block
                        .split('\n')
                        .find((l) => l.startsWith('data:'));

                    if (!line) {
                        continue;
                    }

                    const payload = line.slice(5).trim();

                    if (payload === '[DONE]') {
                        continue;
                    }

                    try {
                        const event = JSON.parse(payload);

                        if (event.type === 'text_delta' && event.delta) {
                            const delta = String(event.delta);
                            setMessages((current) => {
                                const next = [...current];
                                const last = next[next.length - 1];

                                if (last?.role === 'assistant') {
                                    next[next.length - 1] = {
                                        ...last,
                                        content: last.content + delta,
                                    };
                                }

                                return next;
                            });
                        }
                    } catch {
                        // Incomplete JSON chunk — ignore and keep streaming.
                    }
                }
            }
        } catch {
            setFailed(true);
            setMessages((current) => current.slice(0, -1));
        } finally {
            setStreaming(false);
        }
    };

    const pickFile = (file: File | null) => {
        if (!file) {
            return;
        }

        if (file.size > MAX_ATTACHMENT_BYTES) {
            setFailed(true);

            return;
        }

        setAttachment(file);
    };

    const renderAttachment = (message: ChatMessage) => {
        if (!message.attachment_url) {
            return null;
        }

        if (isImageAttachment(message.attachment_url)) {
            return (
                <a
                    href={message.attachment_url}
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <img
                        src={message.attachment_url}
                        alt={message.attachment_name ?? 'attachment'}
                        className="mt-1 max-h-40 rounded"
                    />
                </a>
            );
        }

        return (
            <a
                href={message.attachment_url}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-1 flex items-center gap-1 text-xs underline"
            >
                <Paperclip className="size-3" />
                {message.attachment_name ?? 'attachment'}
            </a>
        );
    };

    return (
        <div translate="no" className="notranslate">
            {/* Launcher */}
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-label={c.button_label}
                className="fixed right-5 bottom-5 z-50 flex size-13 items-center justify-center rounded-full bg-[#800020] p-3.5 text-white shadow-lg transition hover:bg-[#800020]/90 focus-visible:ring-2 focus-visible:ring-[#800020] focus-visible:ring-offset-2 focus-visible:outline-none"
            >
                {open ? (
                    <X className="size-6" />
                ) : (
                    <MessageCircle className="size-6" />
                )}
            </button>

            {/* Panel */}
            {open && (
                <div className="fixed right-5 bottom-20 z-50 flex h-[540px] w-[calc(100vw-2.5rem)] max-w-sm flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-2xl">
                    {/* Header */}
                    <div className="border-b border-neutral-100 bg-[#800020] px-4 py-3 text-white">
                        <p className="text-sm font-bold">{c.panel_title}</p>
                        <p className="text-xs text-white/80">
                            {c.panel_subtitle}
                        </p>
                    </div>

                    {/* Messages */}
                    <div
                        ref={scrollRef}
                        className="flex-1 space-y-3 overflow-y-auto px-4 py-3"
                    >
                        <div className="max-w-[85%] rounded-lg rounded-tl-none bg-neutral-100 px-3 py-2 text-sm text-neutral-800">
                            {c.greeting}
                        </div>

                        {messages.length === 0 &&
                            c.quick_questions?.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {c.quick_questions.map((question) => (
                                        <button
                                            key={question}
                                            type="button"
                                            onClick={() => send(question)}
                                            className="rounded-full border border-[#800020]/30 px-3 py-1.5 text-left text-xs text-[#800020] transition hover:bg-[#800020]/5"
                                        >
                                            {question}
                                        </button>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={requestHuman}
                                        className="rounded-full border border-[#800020] px-3 py-1.5 text-left text-xs font-semibold text-[#800020] transition hover:bg-[#800020]/5"
                                    >
                                        {c.handoff_label}
                                    </button>
                                </div>
                            )}

                        {messages.map((message, i) => (
                            <div
                                key={message.id ?? `local-${i}`}
                                className={cn(
                                    'max-w-[85%] rounded-lg px-3 py-2 text-sm break-words whitespace-pre-wrap',
                                    message.role === 'user'
                                        ? 'ml-auto rounded-tr-none bg-[#800020] text-white'
                                        : 'rounded-tl-none bg-neutral-100 text-neutral-800',
                                )}
                            >
                                {message.role === 'admin' && (
                                    <p className="mb-0.5 text-[10px] font-bold tracking-wide text-[#800020] uppercase">
                                        {c.admin_name ?? 'Support'}
                                    </p>
                                )}
                                {message.content}
                                {renderAttachment(message)}
                            </div>
                        ))}

                        {streaming &&
                            mode === 'ai' &&
                            messages[messages.length - 1]?.content === '' && (
                                <div className="max-w-[85%] rounded-lg rounded-tl-none bg-neutral-100 px-3 py-2 text-sm text-neutral-500">
                                    {c.thinking}
                                </div>
                            )}

                        {failed && (
                            <p className="text-xs text-red-500">
                                {c.error_message}
                            </p>
                        )}
                    </div>

                    {/* Attachment chip */}
                    {attachment && (
                        <div className="flex items-center gap-2 border-t border-neutral-100 px-3 py-1.5 text-xs text-neutral-600">
                            <Paperclip className="size-3" />
                            <span className="truncate">{attachment.name}</span>
                            <button
                                type="button"
                                onClick={() => setAttachment(null)}
                                className="ml-auto text-neutral-400 hover:text-neutral-700"
                                aria-label="Remove attachment"
                            >
                                <X className="size-3" />
                            </button>
                        </div>
                    )}

                    {/* Input */}
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            send(input);
                        }}
                        className="flex items-center gap-2 border-t border-neutral-100 px-3 py-2"
                    >
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                            className="hidden"
                            onChange={(e) =>
                                pickFile(e.target.files?.[0] ?? null)
                            }
                        />
                        <button
                            type="button"
                            onClick={() => fileInputRef.current?.click()}
                            aria-label={c.attach_label}
                            title={c.attach_label}
                            className="flex size-9 items-center justify-center rounded-md border border-neutral-200 text-neutral-500 transition hover:border-[#800020] hover:text-[#800020]"
                        >
                            <Paperclip className="size-4" />
                        </button>
                        <input
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            placeholder={c.input_placeholder}
                            maxLength={1000}
                            className="flex-1 rounded-md border border-neutral-200 px-3 py-2 text-sm focus:border-[#800020] focus:ring-1 focus:ring-[#800020] focus:outline-none"
                        />
                        <button
                            type="submit"
                            disabled={
                                streaming || (!input.trim() && !attachment)
                            }
                            aria-label={c.send_label}
                            className="flex size-9 items-center justify-center rounded-md bg-[#800020] text-white transition hover:bg-[#800020]/90 disabled:opacity-40"
                        >
                            <SendHorizonal className="size-4" />
                        </button>
                    </form>

                    {/* Human support + disclaimer */}
                    <div className="border-t border-neutral-100 px-4 py-2 text-center">
                        {mode === 'ai' ? (
                            <button
                                type="button"
                                onClick={requestHuman}
                                className="text-xs font-semibold text-[#800020] hover:underline"
                            >
                                {c.handoff_label}
                            </button>
                        ) : (
                            <p className="text-xs font-semibold text-[#800020]">
                                {c.human_mode_active ??
                                    'You are chatting with human support.'}
                            </p>
                        )}
                        <p className="mt-1 text-[10px] text-neutral-400">
                            {c.disclaimer}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}

export default AiChatWidget;
