<?php

// Applies the 2026-08-03 plan to qualifying business-card product JSONs:
//  1. special_finish "no special finish" -> /images/product-options/no-foil.png
//  2. default gallery with >= 3 images: swap images[0] and images[2]
// Other special finish paths and non-default galleries stay untouched.

$dirs = [
    __DIR__.'/../content/product-options/business-cards',
    __DIR__.'/../content/product-options/cotton-business-cards',
    __DIR__.'/../content/product-options/pvc-business-cards',
];

foreach ($dirs as $dir) {
    foreach (glob("$dir/*.json") as $file) {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);

        if (! is_array($data)) {
            echo basename($file)."  !! parse error, skipped\n";
            continue;
        }

        $has = fn (string $k) => isset($data[$k]) && is_array($data[$k]) && count($data[$k]) > 0;

        if (! ($has('sizes') && $has('paper_finish') && $has('corners'))) {
            continue;
        }

        $changes = [];

        foreach ($data['galleries'] ?? [] as $i => $gallery) {
            // json_decode turns {} into []; encode it back as an object.
            if (array_key_exists('match', $gallery) && $gallery['match'] === []) {
                $data['galleries'][$i]['match'] = new stdClass;
            }
        }

        foreach ($data['special_finish'] ?? [] as $i => $finish) {
            if (strtolower($finish['name'] ?? '') === 'no special finish'
                && ($finish['swatch_image'] ?? null) !== '/images/product-options/no-foil.png') {
                $changes[] = "no-foil: {$finish['swatch_image']} -> /images/product-options/no-foil.png";
                $data['special_finish'][$i]['swatch_image'] = '/images/product-options/no-foil.png';
            }
        }

        foreach ($data['galleries'] ?? [] as $i => $gallery) {
            if (empty($gallery['is_default'])) {
                continue;
            }

            $images = $gallery['images'] ?? [];

            if (count($images) >= 3) {
                [$images[0], $images[2]] = [$images[2], $images[0]];
                $data['galleries'][$i]['images'] = $images;
                $changes[] = 'default gallery swapped [0]<->[2]';
            } else {
                $changes[] = 'default gallery <3 images, untouched';
            }
        }

        if ($changes === []) {
            echo basename($file)."  no changes\n";
            continue;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($file, $json."\n");
        echo basename($file)."\n";

        foreach ($changes as $change) {
            echo "  - $change\n";
        }
    }
}
