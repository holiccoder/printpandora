<?php

namespace App\Filament\Pages;

use App\Services\ProductImageResolver;
use App\Services\ProductImageUploadService;
use App\Services\SiteSettingsService;
use App\Support\HardcodedContent;
use App\Support\ProductImagePolicy;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property Schema $form
 */
class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = '设置';

    protected static ?string $navigationLabel = '设置';

    protected static string|\UnitEnum|null $navigationGroup = '商城管理';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(HardcodedContent $content, SiteSettingsService $settings): void
    {
        $homepage = $content->section('home_page', []);
        $storedHomepage = $settings->homepage();
        $resolvedSlides = Arr::get($homepage, 'hero_carousel.slides', []);
        $storedSlides = Arr::get($storedHomepage, 'hero_carousel.slides', []);

        $slides = [];

        foreach (is_array($resolvedSlides) ? $resolvedSlides : [] as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $storedSlide = is_array($storedSlides[$index] ?? null)
                ? $storedSlides[$index]
                : [];

            $slides[] = [
                ...array_replace($slide, $storedSlide),
                'original_index' => $index,
            ];
        }

        $storedGeneral = Arr::get($settings->all(), 'general', []);

        $this->form->fill([
            'general' => [
                'site_name' => is_array($storedGeneral)
                    ? ($storedGeneral['site_name'] ?? 'InkPavo')
                    : 'InkPavo',
                'support_email' => is_array($storedGeneral)
                    ? ($storedGeneral['support_email'] ?? '')
                    : '',
            ],
            'homepage' => [
                'seo' => [
                    'page_title' => Arr::get($homepage, 'seo.page_title', ''),
                    'page_description' => Arr::get($homepage, 'seo.page_description', ''),
                ],
                'hero_carousel' => [
                    'aria_label' => Arr::get($homepage, 'hero_carousel.aria_label', 'Featured products'),
                    'prev_button_label' => Arr::get($homepage, 'hero_carousel.prev_button_label', 'Previous slide'),
                    'next_button_label' => Arr::get($homepage, 'hero_carousel.next_button_label', 'Next slide'),
                    'slides' => $slides,
                ],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('general')
                            ->label('常规')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make('常规设置')
                                    ->description('保存网站的基本信息。')
                                    ->schema([
                                        TextInput::make('general.site_name')
                                            ->label('网站名称')
                                            ->required()
                                            ->maxLength(120),
                                        TextInput::make('general.support_email')
                                            ->label('客服邮箱')
                                            ->email()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('homepage')
                            ->label('首页设置')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Section::make('首页 SEO 设置')
                                    ->description('以下内容将用于首页标题和元描述。')
                                    ->schema([
                                        TextInput::make('homepage.seo.page_title')
                                            ->label('首页标题')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('homepage.seo.page_description')
                                            ->label('元描述')
                                            ->required()
                                            ->rows(3)
                                            ->maxLength(320)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Section::make('首页轮播图')
                                    ->description('编辑首页顶部轮播图。上传的横幅会保留原图，并自动生成 WebP 优化图。')
                                    ->schema([
                                        TextInput::make('homepage.hero_carousel.aria_label')
                                            ->label('轮播图无障碍标签')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('homepage.hero_carousel.prev_button_label')
                                            ->label('上一张按钮文本')
                                            ->required()
                                            ->maxLength(120),
                                        TextInput::make('homepage.hero_carousel.next_button_label')
                                            ->label('下一张按钮文本')
                                            ->required()
                                            ->maxLength(120),
                                        Repeater::make('homepage.hero_carousel.slides')
                                            ->label('轮播图幻灯片')
                                            ->schema([
                                                Hidden::make('original_index'),
                                                TextInput::make('eyebrow')
                                                    ->label('眉题')
                                                    ->maxLength(120),
                                                Textarea::make('headline')
                                                    ->label('标题')
                                                    ->required()
                                                    ->rows(2)
                                                    ->maxLength(255),
                                                Textarea::make('subheadline')
                                                    ->label('描述')
                                                    ->required()
                                                    ->rows(3)
                                                    ->maxLength(1000),
                                                TextInput::make('cta_text')
                                                    ->label('按钮文字')
                                                    ->required()
                                                    ->maxLength(120),
                                                TextInput::make('cta_href')
                                                    ->label('按钮链接')
                                                    ->required()
                                                    ->maxLength(500),
                                                static::bannerUpload(),
                                                TextInput::make('alt')
                                                    ->label('横幅替代文本')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->reorderable()
                                            ->addActionLabel('添加轮播图')
                                            ->itemLabel(function (array $state): string {
                                                $headline = trim((string) ($state['headline'] ?? ''));

                                                return $headline !== ''
                                                    ? Str::limit(str_replace("\n", ' ', $headline), 60)
                                                    : '轮播图';
                                            })
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(
        HardcodedContent $content,
        SiteSettingsService $settings,
    ): void {
        $state = $this->form->getState();
        $homepage = is_array($state['homepage'] ?? null) ? $state['homepage'] : [];
        $currentSlides = Arr::get($content->section('home_page', []), 'hero_carousel.slides', []);
        $submittedSlides = Arr::get($homepage, 'hero_carousel.slides', []);
        $slides = [];

        foreach (is_array($submittedSlides) ? $submittedSlides : [] as $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $originalIndex = filter_var($slide['original_index'] ?? null, FILTER_VALIDATE_INT);
            $baseSlide = is_int($originalIndex) && is_array($currentSlides[$originalIndex] ?? null)
                ? $currentSlides[$originalIndex]
                : [];

            unset($slide['original_index']);
            $slides[] = array_replace($baseSlide, $slide);
        }

        Arr::set($homepage, 'hero_carousel.slides', $slides);

        $settings->saveSections([
            'general' => is_array($state['general'] ?? null) ? $state['general'] : [],
            'homepage' => $homepage,
        ]);

        $content->forget();

        Notification::make()
            ->title('设置已保存')
            ->body('首页 SEO 和轮播图内容已保存到数据库。')
            ->success()
            ->send();
    }

    public function getMaxContentWidth(): string|Width|null
    {
        return 'full';
    }

    protected static function bannerUpload(): FileUpload
    {
        return FileUpload::make('image_url')
            ->label('横幅图片')
            ->image()
            ->acceptedFileTypes(ProductImagePolicy::ALLOWED_MIME_TYPES)
            ->maxSize(10240)
            ->disk('public')
            ->directory('home/carousel')
            ->visibility('public')
            ->fetchFileInformation(false)
            ->preventFilePathTampering(false)
            ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {
                return app(ProductImageUploadService::class)->store(
                    $file,
                    $component->getDirectory() ?? 'home/carousel',
                    $component->getDiskName(),
                    $component->getVisibility(),
                );
            })
            ->getUploadedFileUsing(function (
                FileUpload $component,
                string $file,
                string|array|null $storedFileNames,
            ): array {
                if (Str::startsWith($file, ['http://', 'https://', '//', '/'])) {
                    return [
                        'name' => basename((string) parse_url($file, PHP_URL_PATH)),
                        'size' => 0,
                        'type' => 'image/*',
                        'url' => $file,
                    ];
                }

                $uploadedFile = $component->getUploadedFile($file, $storedFileNames) ?? [
                    'name' => basename($file),
                    'size' => 0,
                    'type' => 'image/*',
                ];
                $uploadedFile['url'] = app(ProductImageResolver::class)->url($file);

                return $uploadedFile;
            });
    }
}
