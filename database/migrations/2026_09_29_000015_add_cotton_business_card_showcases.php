<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    private const CATEGORY_SLUG = 'cotton-paper-business-cards';

    private const IMAGE_DIRECTORY = 'images/showcases/cotton-business-cards';

    public function up(): void
    {
        $categoryId = DB::table('showcase_categories')
            ->where('slug', self::CATEGORY_SLUG)
            ->value('id');

        if ($categoryId === null) {
            return;
        }

        $directory = public_path(self::IMAGE_DIRECTORY);

        if (! File::isDirectory($directory)) {
            return;
        }

        $files = array_values(array_filter(
            File::files($directory),
            fn (SplFileInfo $file): bool => strtolower($file->getExtension()) === 'webp',
        ));

        usort(
            $files,
            fn (SplFileInfo $left, SplFileInfo $right): int => strnatcasecmp(
                $left->getFilename(),
                $right->getFilename(),
            ),
        );

        $now = now();
        $showcases = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();

            $showcases[] = [
                'image_name' => pathinfo($filename, PATHINFO_FILENAME),
                'link' => null,
                'image_url' => '/'.self::IMAGE_DIRECTORY.'/'.rawurlencode($filename),
                'category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($showcases !== []) {
            DB::table('showcases')->insert($showcases);
        }
    }

    public function down(): void
    {
        DB::table('showcases')
            ->where('image_url', 'like', '/'.self::IMAGE_DIRECTORY.'/%')
            ->delete();
    }
};
