@php
    $showUploader = $showUploader ?? false;
@endphp

<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
    @if (count($files) > 0)
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 font-medium">文件</th>
                    @if ($showUploader)
                        <th class="px-4 py-3 font-medium">上传用户</th>
                    @endif
                    <th class="px-4 py-3 font-medium">版本</th>
                    <th class="px-4 py-3 font-medium">上传时间</th>
                    <th class="px-4 py-3 text-right font-medium">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($files as $file)
                    <tr>
                        <td class="max-w-[24rem] px-4 py-3">
                            <div class="truncate font-semibold text-gray-950 dark:text-white" title="{{ $file['filename'] }}">
                                {{ $file['filename'] }}
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span>{{ $file['label'] }}</span>
                                @if ($file['is_current'] ?? false)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">当前版本</span>
                                @endif
                            </div>
                        </td>
                        @if ($showUploader)
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                @if (($file['uploader_type'] ?? null) === 'customer' && ($file['uploader_id'] ?? null) !== null)
                                    客户{{ $file['uploader_id'] }}
                                @elseif (($file['uploader_type'] ?? null) === 'admin' && ($file['uploader_id'] ?? null) !== null)
                                    管理员{{ $file['uploader_id'] }}
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                            第 {{ $file['version'] ?? 1 }} 版
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                            {{ $file['uploaded_at'] ? \Illuminate\Support\Carbon::parse($file['uploaded_at'])->format('Y-m-d H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-wrap justify-end gap-3">
                                @if ($file['download_url'])
                                    <a
                                        href="{{ $file['download_url'] }}"
                                        download
                                        class="inline-flex items-center gap-1.5 font-semibold text-primary-600 hover:underline"
                                    >
                                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="size-4" />
                                        下载
                                    </a>
                                @else
                                    <span class="text-gray-400">不可用</span>
                                @endif
                                @if ($deleteAction)
                                    {{ $deleteAction(['file' => $file['id']])->label('删除')->color('danger')->icon('heroicon-o-trash') }}
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="flex items-center gap-3 px-4 py-6 text-sm text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-document-text" class="size-5 text-gray-400" />
            {{ $empty }}
        </div>
    @endif
</div>
