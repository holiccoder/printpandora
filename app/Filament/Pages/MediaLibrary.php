<?php

namespace App\Filament\Pages;

use App\Services\MediaLibraryCatalog;
use App\Services\MediaUsageService;
use App\Services\ProductImageUploadService;
use App\Support\ProductImagePolicy;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property Schema $form
 */
class MediaLibrary extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $title = '媒体库';

    protected static ?string $navigationLabel = '媒体库';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    protected static ?int $navigationSort = 80;

    protected string $view = 'filament.pages.media-library';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public string $search = '';

    public string $purposeFilter = 'all';

    public string $usageFilter = 'all';

    public string $sort = 'newest';

    public function mount(): void
    {
        $this->form->fill([
            'purpose' => 'general',
            'uploaded_files' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('purpose')
                    ->label('图片用途')
                    ->options(MediaLibraryCatalog::uploadPurposeOptions())
                    ->default('general')
                    ->required()
                    ->native(false),
                FileUpload::make('uploaded_files')
                    ->label('上传图片')
                    ->helperText('支持 JPEG、PNG、WebP；每张最大 10 MB。原图会立即保存，WebP 将在后台生成。')
                    ->image()
                    ->multiple()
                    ->storeFiles(false)
                    ->acceptedFileTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
                    ->maxSize(10240)
                    ->required(),
            ])
            ->columns([
                'default' => 1,
                'md' => 3,
            ])
            ->statePath('data');
    }

    public function save(
        ProductImageUploadService $uploader,
        MediaLibraryCatalog $catalog,
    ): void {
        $state = $this->form->getState();
        $purpose = (string) ($state['purpose'] ?? '');
        $directory = MediaLibraryCatalog::directoryForPurpose($purpose);

        if ($directory === null) {
            Notification::make()
                ->title('请选择有效的图片用途')
                ->danger()
                ->send();

            return;
        }

        $files = array_values(array_filter(
            Arr::wrap($state['uploaded_files'] ?? []),
            fn (mixed $file): bool => $file instanceof TemporaryUploadedFile,
        ));

        if ($files === []) {
            Notification::make()
                ->title('请选择要上传的图片')
                ->danger()
                ->send();

            return;
        }

        foreach ($files as $file) {
            $uploader->store($file, $directory);
        }

        $catalog->invalidate();
        $this->form->fill([
            'purpose' => $purpose,
            'uploaded_files' => [],
        ]);
        $this->resetPage();

        Notification::make()
            ->title('上传成功')
            ->body('已添加 '.count($files).' 张图片，WebP 正在后台处理。')
            ->success()
            ->send();
    }

    /**
     * @return array{
     *     paginator: LengthAwarePaginator<int, array<string, mixed>>,
     *     total: int,
     *     used: int,
     *     unused: int,
     *     total_size: string,
     *     purpose_options: array<string, string>,
     *     sort_options: array<string, string>
     * }
     */
    public function getLibraryState(): array
    {
        $catalog = app(MediaLibraryCatalog::class);
        $usageService = app(MediaUsageService::class);
        $assets = $catalog->assets();
        $usageIndex = $usageService->forAssets($assets);

        foreach ($assets as &$asset) {
            $asset['usages'] = $usageIndex[(string) $asset['id']] ?? [];
            $asset['usage_count'] = count($asset['usages']);
            $asset['is_used'] = $asset['usage_count'] > 0;
        }

        unset($asset);

        $total = count($assets);
        $used = count(array_filter($assets, fn (array $asset): bool => $asset['is_used']));
        $totalSize = array_sum(array_column($assets, 'total_size'));
        $filtered = array_values(array_filter($assets, function (array $asset): bool {
            if ($this->purposeFilter !== 'all' && $asset['purpose'] !== $this->purposeFilter) {
                return false;
            }

            if ($this->usageFilter === 'used' && ! $asset['is_used']) {
                return false;
            }

            if ($this->usageFilter === 'unused' && $asset['is_used']) {
                return false;
            }

            $search = trim(Str::lower($this->search));

            if ($search === '') {
                return true;
            }

            $variantPaths = array_values(array_filter(
                $asset['variant_paths'] ?? [],
                is_string(...),
            ));

            $haystack = Str::lower(implode(' ', array_filter([
                $asset['name'] ?? null,
                $asset['file_name'] ?? null,
                $asset['primary_path'] ?? null,
                $asset['source_path'] ?? null,
                implode(' ', $variantPaths),
                $asset['purpose_label'] ?? null,
                $asset['spatie_collection'] ?? null,
            ], is_string(...))));

            return Str::contains($haystack, $search);
        }));

        usort($filtered, fn (array $left, array $right): int => match ($this->sort) {
            'oldest' => ($left['modified_at'] <=> $right['modified_at']),
            'name_asc' => strnatcasecmp((string) $left['name'], (string) $right['name']),
            'name_desc' => strnatcasecmp((string) $right['name'], (string) $left['name']),
            'size_desc' => ($right['total_size'] <=> $left['total_size']),
            default => ($right['modified_at'] <=> $left['modified_at']),
        });

        $page = max(1, (int) $this->getPage());
        $lastPage = max(1, (int) ceil(count($filtered) / MediaLibraryCatalog::ITEMS_PER_PAGE));
        $page = min($page, $lastPage);
        $paginator = new LengthAwarePaginator(
            items: array_slice(
                $filtered,
                ($page - 1) * MediaLibraryCatalog::ITEMS_PER_PAGE,
                MediaLibraryCatalog::ITEMS_PER_PAGE,
            ),
            total: count($filtered),
            perPage: MediaLibraryCatalog::ITEMS_PER_PAGE,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        );

        return [
            'paginator' => $paginator,
            'total' => $total,
            'used' => $used,
            'unused' => $total - $used,
            'total_size' => MediaLibraryCatalog::formatBytes((int) $totalSize),
            'purpose_options' => MediaLibraryCatalog::filterPurposeOptions(),
            'sort_options' => [
                'newest' => '最新上传',
                'oldest' => '最早上传',
                'name_asc' => '名称 A–Z',
                'name_desc' => '名称 Z–A',
                'size_desc' => '文件最大',
            ],
        ];
    }

    public function refreshCatalog(MediaLibraryCatalog $catalog): void
    {
        $catalog->invalidate();
        $this->resetPage();

        Notification::make()
            ->title('媒体库已刷新')
            ->success()
            ->send();
    }

    public function deleteAsset(
        string $id,
        MediaLibraryCatalog $catalog,
        MediaUsageService $usageService,
    ): void {
        $asset = $catalog->find($id, fresh: true);

        if ($asset === null) {
            Notification::make()
                ->title('找不到这张图片')
                ->danger()
                ->send();

            return;
        }

        $usages = $usageService->forAssets([$asset])[$id] ?? [];

        if ($usages !== []) {
            Notification::make()
                ->title('图片仍在使用中，无法删除')
                ->body('请先从关联的产品、博客或设计服务记录中移除该图片。')
                ->warning()
                ->send();

            return;
        }

        if (! $catalog->delete($id)) {
            Notification::make()
                ->title('删除失败')
                ->danger()
                ->send();

            return;
        }

        $this->resetPage();

        Notification::make()
            ->title('图片已删除')
            ->body('优化图片、原文件和关联衍生文件已一并删除。')
            ->success()
            ->send();
    }

    public function downloadAsset(string $id, MediaLibraryCatalog $catalog): StreamedResponse
    {
        $asset = $catalog->find($id);
        $path = is_array($asset) ? ($asset['primary_path'] ?? null) : null;

        abort_unless(is_string($path) && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, basename($path));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPurposeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUsageFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }
}
