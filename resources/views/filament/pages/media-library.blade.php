<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Upload Form Card -->
        <div class="rounded-xl border border-neutral-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-neutral-900">
            <form wire:submit="save" class="space-y-4">
                {{ $this->form }}
                
                <div class="flex justify-end">
                    <x-filament::button type="submit" color="success">
                        上传图片
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Media Grid Title -->
        <div class="border-b border-neutral-100 pb-2 dark:border-white/10">
            <h3 class="text-base font-bold text-neutral-800 dark:text-white">媒体文件列表</h3>
        </div>

        <!-- Files Grid -->
        @php $files = $this->getFiles(); @endphp

        @if (count($files) === 0)
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-200 py-12 dark:border-white/10">
                <p class="text-sm text-neutral-400">暂无图片，请在上方上传新图片</p>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                @foreach ($files as $file)
                    <div class="group relative rounded-xl border border-neutral-100 bg-white p-3 shadow-sm transition-all hover:shadow-md dark:border-white/10 dark:bg-neutral-900">
                        <!-- Image Preview -->
                        <div class="aspect-square w-full rounded-lg bg-neutral-50 overflow-hidden relative">
                            <img src="{{ $file['path'] }}" alt="{{ $file['name'] }}" class="h-full w-full object-contain" />
                        </div>

                        <!-- File Details -->
                        <div class="mt-3 space-y-1">
                            <p class="truncate text-xs font-semibold text-neutral-800 dark:text-neutral-200" title="{{ $file['name'] }}">
                                {{ $file['name'] }}
                            </p>
                            <p class="text-[10px] text-neutral-400">
                                {{ $file['size'] }} | {{ $file['time'] }}
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-3 flex gap-2">
                            <button 
                                type="button"
                                onclick="navigator.clipboard.writeText('{{ $file['path'] }}'); alert('已成功复制相对路径：{{ $file['path'] }}');"
                                class="flex-1 rounded bg-neutral-100 py-1.5 text-center text-[10px] font-semibold text-[#800020] hover:bg-[#800020]/10 transition-colors dark:bg-neutral-800 dark:text-[#a04050]"
                            >
                                复制路径
                            </button>
                            <button
                                type="button"
                                wire:click="deleteFile('{{ $file['path'] }}')"
                                wire:confirm="确定要删除这张图片吗？"
                                class="rounded bg-red-50 p-1.5 text-red-500 hover:bg-red-100 transition-colors dark:bg-red-950/20 dark:text-red-400 dark:hover:bg-red-900/30"
                                title="删除"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
