<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $setting = SiteSetting::query()
            ->where('key', 'site')
            ->first();

        if ($setting === null) {
            return;
        }

        $value = is_array($setting->value) ? $setting->value : [];
        $slides = data_get($value, 'homepage.hero_carousel.slides');

        if (! is_array($slides) || ! is_array($slides[0] ?? null)) {
            return;
        }

        $slides[0] = array_replace($slides[0], [
            'eyebrow' => 'NEW MEMBER OFFER',
            'headline' => '新注册用户',
            'subheadline' => '注册并登录账号，首次下单即可享受专属优惠',
            'cta_text' => '立即注册',
            'cta_href' => '/register',
            'offer' => [
                'pretitle' => 'NEW MEMBER OFFER',
                'headline' => '新注册用户',
                'discount_label' => '首单',
                'discount' => '85 折',
                'description' => '注册并登录账号，首次下单即可享受专属优惠',
                'steps' => [
                    [
                        'number' => '01',
                        'label' => '注册账号',
                        'icon' => 'user-plus',
                    ],
                    [
                        'number' => '02',
                        'label' => '登录后首次下单',
                        'icon' => 'arrow-right',
                    ],
                    [
                        'number' => '03',
                        'label' => '结算时自动享受 85 折',
                        'icon' => 'tag',
                    ],
                ],
                'terms' => '每个账号限用一次 · 不与其他优惠同享',
                'cta_text' => '立即注册',
                'cta_href' => '/register',
            ],
        ]);

        unset($slides[0]['features']);

        data_set($value, 'homepage.hero_carousel.slides', array_values($slides));
        $setting->value = $value;
        $setting->save();
    }

    public function down(): void
    {
        $setting = SiteSetting::query()
            ->where('key', 'site')
            ->first();

        if ($setting === null) {
            return;
        }

        $value = is_array($setting->value) ? $setting->value : [];
        $slides = data_get($value, 'homepage.hero_carousel.slides');

        if (
            ! is_array($slides)
            || ! is_array($slides[0] ?? null)
            || data_get($slides[0], 'offer.pretitle') !== 'NEW MEMBER OFFER'
        ) {
            return;
        }

        $slides[0] = array_replace($slides[0], [
            'eyebrow' => 'FINE PRINT CRAFT',
            'headline' => "MADE TO BE\nREMEMBERED",
            'subheadline' => 'Exquisite letterpress craftsmanship that turns every connection into a lasting impression.',
            'cta_text' => 'EXPLORE COLLECTION',
            'cta_href' => '/shop',
            'features' => [
                [
                    'icon' => 'card',
                    'title' => 'BUSINESS CARDS',
                    'description' => 'Premium & Professional',
                ],
                [
                    'icon' => 'press',
                    'title' => 'FINE PRINT CRAFT',
                    'description' => 'Quality in Every Detail',
                ],
                [
                    'icon' => 'pencil',
                    'title' => 'CUSTOM DESIGN',
                    'description' => 'Tailored for You',
                ],
            ],
        ]);

        unset($slides[0]['offer']);

        data_set($value, 'homepage.hero_carousel.slides', array_values($slides));
        $setting->value = $value;
        $setting->save();
    }
};
