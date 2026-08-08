<?php

// Inventory scan for the 2026-08-03 business card detail update plan.
// Reports: qualifying products (sizes + paper_finish + corners), their
// option counts/names, special_finish entries, and default gallery size.

$dirs = [
    __DIR__.'/../content/product-options/business-cards',
    __DIR__.'/../content/product-options/cotton-business-cards',
    __DIR__.'/../content/product-options/pvc-business-cards',
];

foreach ($dirs as $dir) {
    echo "\n=== $dir ===\n";
    foreach (glob("$dir/*.json") as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (! is_array($data)) {
            echo basename($file)."  !! JSON PARSE ERROR\n";
            continue;
        }

        $has = fn (string $k) => isset($data[$k]) && is_array($data[$k]) && count($data[$k]) > 0;
        $qualifies = $has('sizes') && $has('paper_finish') && $has('corners');

        echo basename($file).'  '.($qualifies ? 'QUALIFIES' : 'skipped')
            .'  sizes=['.implode(',', array_column($data['sizes'] ?? [], 'name')).']'
            .'  finishes=['.implode(',', array_column($data['paper_finish'] ?? [], 'name')).']'
            .'  corners=['.implode(',', array_column($data['corners'] ?? [], 'name')).']'
            ."\n";

        if (! $qualifies) {
            continue;
        }

        echo '  special_finish=['.implode(' | ', array_column($data['special_finish'] ?? [], 'name'))."]\n";

        foreach ($data['galleries'] ?? [] as $gallery) {
            if (! empty($gallery['is_default'])) {
                echo '  default_gallery_count='.count($gallery['images'])."\n";
                foreach ($gallery['images'] as $i => $image) {
                    echo "    [$i] $image\n";
                }
            }
        }

        // swatch image samples
        echo '  size_swatch[0]='.($data['sizes'][0]['swatch_image'] ?? 'none')."\n";
        echo '  finish_swatch[0]='.($data['paper_finish'][0]['swatch_image'] ?? 'none')."\n";
        echo '  corner_swatch[0]='.substr($data['corners'][0]['swatch_image'] ?? 'none', 0, 80)."\n";
        $noSpecial = array_values(array_filter($data['special_finish'] ?? [], fn ($f) => strtolower($f['name'] ?? '') === 'no special finish'));
        if ($noSpecial) {
            echo '  no_special_finish_swatch='.($noSpecial[0]['swatch_image'] ?? 'none')."\n";
        }
    }
}
