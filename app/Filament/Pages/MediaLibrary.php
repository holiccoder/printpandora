<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;

class MediaLibrary extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected string $view = 'filament.pages.media-library';

    protected static ?string $title = '媒体库';

    protected static ?string $navigationLabel = '媒体库';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                FileUpload::make('uploaded_file')
                    ->label('上传图片到媒体库')
                    ->image()
                    ->directory('uploads')
                    ->disk('public')
                    ->preserveFilenames()
                    ->required()
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        
        $this->form->fill();

        Notification::make()
            ->title('上传成功')
            ->body('图片已成功保存到媒体库！')
            ->success()
            ->send();
    }

    public function deleteFile(string $path): void
    {
        // Path is like '/storage/uploads/filename.png', we want 'storage/uploads/filename.png'
        $cleanedPath = ltrim($path, '/');
        $fullPath = public_path($cleanedPath);

        if (File::exists($fullPath)) {
            File::delete($fullPath);

            Notification::make()
                ->title('删除成功')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('删除失败')
                ->body('找不到指定的文件')
                ->danger()
                ->send();
        }
    }

    public function getFiles(): array
    {
        $dir = public_path('storage/uploads');

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $files = File::files($dir);
        $result = [];

        foreach ($files as $file) {
            $relativePath = 'storage/uploads/' . $file->getFilename();
            $result[] = [
                'name' => $file->getFilename(),
                'path' => '/' . $relativePath,
                'url' => asset($relativePath),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'time' => date('Y-m-d H:i:s', $file->getMTime()),
            ];
        }

        // Sort by modification time desc
        usort($result, fn($a, $b) => strcmp($b['time'], $a['time']));

        return $result;
    }
}
