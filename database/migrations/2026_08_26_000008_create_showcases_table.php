<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const IMAGE_DIRECTORY = 'images/showcases';

    public function up(): void
    {
        Schema::create('showcases', function (Blueprint $table): void {
            $table->id();
            $table->string('image_name')->nullable();
            $table->string('link')->nullable();
            $table->string('image_url');
            $table->timestamps();
        });

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
        Schema::dropIfExists('showcases');
    }
};
