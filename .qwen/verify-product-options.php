<?php

// Section-10 automated checks for product option JSONs:
//  - every JSON parses
//  - every swatch_image that is a URL maps to a real file under public/
//  - special finish paths unchanged except "no special finish" => no-foil.png

$dirs = [
    __DIR__.'/../content/product-options/business-cards',
    __DIR__.'/../content/product-options/cotton-business-cards',
    __DIR__.'/../content/product-options/pvc-business-cards',
];

$publicRoot = realpath(__DIR__.'/../public');
$problems = 0;

foreach ($dirs as $dir) {
    foreach (glob("$dir/*.json") as $file) {
        $data = json_decode(file_get_contents($file), true);

        if (! is_array($data)) {
            echo 'PARSE FAIL: '.basename($file)."\n";
            $problems++;
            continue;
        }

        foreach (['sizes', 'paper_finish', 'corners', 'special_finish'] as $group) {
            foreach ($data[$group] ?? [] as $option) {
                $swatch = $option['swatch_image'] ?? '';

                if (! is_string($swatch) || $swatch === '' || str_starts_with($swatch, '<svg')) {
                    continue;
                }

                if (! str_starts_with($swatch, '/images/')) {
                    continue; // remote URL (unsplash galleries etc.)
                }

                if (! is_file($publicRoot.substr($swatch, strlen('/images/')))) {
                    // normalize: substr keeps leading slash; public path = public + swatch
                    if (! is_file($publicRoot.$swatch)) {
                        echo 'MISSING FILE: '.basename($file)." [$group] {$option['name']} -> $swatch\n";
                        $problems++;
                    }
                }

                if ($group === 'special_finish'
                    && strtolower($option['name']) !== 'no special finish'
                    && ! str_starts_with($swatch, '/images/product-options/business-cards/swatches/')) {
                    echo 'UNEXPECTED SPECIAL FINISH PATH: '.basename($file)." {$option['name']} -> $swatch\n";
                    $problems++;
                }

                if ($group === 'special_finish'
                    && strtolower($option['name']) === 'no special finish'
                    && $swatch !== '/images/product-options/no-foil.png') {
                    echo 'NO-FOIL NOT APPLIED: '.basename($file)." -> $swatch\n";
                    $problems++;
                }
            }
        }

        foreach ($data['galleries'] ?? [] as $gallery) {
            foreach ($gallery['images'] ?? [] as $image) {
                if (str_starts_with($image, '/images/') && ! is_file($publicRoot.$image)) {
                    echo 'MISSING GALLERY FILE: '.basename($file)." -> $image\n";
                    $problems++;
                }
            }
        }
    }
}

echo $problems === 0 ? "ALL OK\n" : "$problems problem(s)\n";
exit($problems === 0 ? 0 : 1);
