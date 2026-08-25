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

        if (
            ! is_array($slides)
            || ! is_array($slides[0] ?? null)
            || ! is_array(data_get($slides[0], 'offer'))
        ) {
            return;
        }

        $slides[0] = array_replace($slides[0], [
            'headline' => 'New Registered Users',
            'subheadline' => 'Register and log in to your account to enjoy an exclusive offer on your first order.',
            'cta_text' => 'Register Now',
            'offer' => [
                'pretitle' => 'NEW MEMBER OFFER',
                'headline' => 'New Registered Users',
                'discount_label' => 'First Order',
                'discount' => '15% Off',
                'description' => 'Register and log in to your account to enjoy an exclusive offer on your first order.',
                'steps' => [
                    [
                        'number' => '01',
                        'label' => 'Register an account',
                        'icon' => 'user-plus',
                    ],
                    [
                        'number' => '02',
                        'label' => 'Place your first order after logging in',
                        'icon' => 'arrow-right',
                    ],
                    [
                        'number' => '03',
                        'label' => 'Automatically receive 15% off at checkout',
                        'icon' => 'tag',
                    ],
                ],
                'terms' => 'One use per account · Cannot be combined with other offers',
                'cta_text' => 'Register Now',
                'cta_href' => '/register',
            ],
        ]);

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
            || data_get($slides[0], 'offer.headline') !== 'New Registered Users'
        ) {
            return;
        }

        $slides[0] = array_replace($slides[0], [
            'headline' => '新注册用户',
            'subheadline' => '注册并登录账号，首次下单即可享受专属优惠',
            'cta_text' => '立即注册',
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

        data_set($value, 'homepage.hero_carousel.slides', array_values($slides));
        $setting->value = $value;
        $setting->save();
    }
};
