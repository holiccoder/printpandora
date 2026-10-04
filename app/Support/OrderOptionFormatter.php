<?php

namespace App\Support;

use Illuminate\Support\Str;

final class OrderOptionFormatter
{
    /**
     * Filament is the Chinese-language dashboard, so option keys and side
     * labels should remain readable there instead of falling back to English
     * code names.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'sizes' => '尺寸',
        'size' => '尺寸',
        'shape' => '形状',
        'material' => '材质',
        'corners' => '圆角',
        'corner' => '圆角',
        'thickness' => '厚度',
        'texture' => '纹理',
        'paper' => '纸张',
        'paper_finish' => '纸张表面处理',
        'finish' => '表面处理',
        'folding' => '折叠方式',
        'uv_finish' => 'UV 工艺',
        'special_finish' => '特殊工艺',
        'special_finish_on_sides' => '特殊工艺单双面',
        'hot_foil' => '烫金',
        'hot_foil_on_sides' => '烫金单双面',
        'cold_foil' => '冷烫金',
        'print_code' => '印刷代码',
        'print_code_or_magnetic_stripe' => '印刷代码或磁条',
        'print_sides' => '印刷面',
        'drill' => '打孔',
        'quantity' => '数量',
        'custom_width' => '自定义宽度',
        'custom_height' => '自定义高度',
        'width' => '宽度',
        'height' => '高度',
        'one_side' => '单面',
        'both_sides' => '双面',
        'code' => '代码',
        'name' => '名称',
        'label' => '标签',
        'description' => '说明',
    ];

    public static function options(mixed $options): string
    {
        if (! is_array($options)) {
            return '';
        }

        return collect($options)
            ->reject(static fn (mixed $value, string|int $key): bool => $key === 'design_service_request_id')
            ->filter(static fn (mixed $value): bool => self::hasValue($value))
            ->map(
                static fn (mixed $value, string|int $key): string => self::label($key).': '.self::value($value),
            )
            ->implode(', ');
    }

    public static function value(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return collect($value)
                    ->filter(static fn (mixed $entry): bool => self::hasValue($entry))
                    ->map(static fn (mixed $entry): string => self::value($entry))
                    ->implode(', ');
            }

            return collect($value)
                ->filter(static fn (mixed $entry): bool => self::hasValue($entry))
                ->map(
                    static fn (mixed $entry, string|int $key): string => self::label($key).': '.self::value($entry),
                )
                ->implode(', ');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return self::label((string) $value);
    }

    public static function label(string|int $value): string
    {
        $normalized = strtolower(str_replace([' ', '-'], '_', (string) $value));

        return self::LABELS[$normalized]
            ?? Str::headline(str_replace(['_', '-'], ' ', (string) $value));
    }

    private static function hasValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_array($value)) {
            return collect($value)->contains(
                static fn (mixed $entry): bool => self::hasValue($entry),
            );
        }

        return true;
    }
}
