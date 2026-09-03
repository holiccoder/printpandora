<?php

namespace App\Providers;

use App\Events\OrderPaid;
use App\Listeners\GenerateOrderInvoice;
use App\Support\HardcodedContent;
use Carbon\CarbonImmutable;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Storefront content loader — singleton so the in-memory memo is
        // shared across all consumers within a single request.
        $this->app->singleton(HardcodedContent::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::listen(OrderPaid::class, GenerateOrderInvoice::class);

        // Floating human-support chat bubble inside the Filament admin panel.
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => view('filament.ai-chat-bubble')->render(),
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => Password::min(8)
            ->letters()
            ->numbers()
        );
    }
}
