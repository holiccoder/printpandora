<?php

use App\Http\Controllers\Controller;
use Filament\PanelProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;

arch('controllers use the application controller base class')
    ->expect('App\\Http\\Controllers')
    ->classes()
    ->toExtend(Controller::class)
    ->ignoring(Controller::class);

arch('controllers follow the controller naming convention')
    ->expect('App\\Http\\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('form requests are dedicated Laravel form requests')
    ->expect('App\\Http\\Requests')
    ->classes()
    ->toHaveSuffix('Request')
    ->toExtend(FormRequest::class)
    ->toHaveMethod('rules');

arch('console commands follow the command contract')
    ->expect('App\\Console\\Commands')
    ->classes()
    ->toExtend(Command::class)
    ->toHaveMethod('handle');

arch('queued jobs expose queue lifecycle methods')
    ->expect('App\\Jobs')
    ->classes()
    ->toImplement(ShouldQueue::class)
    ->toHaveMethods(['handle', 'failed']);

arch('models are Eloquent models')
    ->expect('App\\Models')
    ->classes()
    ->toExtend(Model::class);

arch('models stay independent from HTTP and admin presentation layers')
    ->expect('App\\Models')
    ->not->toUse(['App\\Http', 'App\\Filament']);

arch('services stay independent from HTTP and admin presentation layers')
    ->expect('App\\Services')
    ->not->toUse(['App\\Http', 'App\\Filament']);

arch('support code stays independent from HTTP and admin presentation layers')
    ->expect('App\\Support')
    ->not->toUse(['App\\Http', 'App\\Filament']);

arch('application providers use the service provider naming convention')
    ->expect('App\\Providers')
    ->classes()
    ->toHaveSuffix('ServiceProvider')
    ->ignoring('App\\Providers\\Filament');

arch('application providers extend the Laravel service provider')
    ->expect('App\\Providers')
    ->classes()
    ->toExtend(ServiceProvider::class)
    ->ignoring('App\\Providers\\Filament');

arch('Filament providers extend the Filament panel provider')
    ->expect('App\\Providers\\Filament')
    ->classes()
    ->toHaveSuffix('PanelProvider')
    ->toExtend(PanelProvider::class);

arch('AI tools implement the AI tool contract')
    ->expect('App\\Ai\\Tools')
    ->classes()
    ->toImplement(Tool::class)
    ->toHaveMethod('handle');

arch('AI agents implement the agent capabilities they expose')
    ->expect('App\\Ai\\Agents')
    ->classes()
    ->toImplement([Agent::class, Conversational::class, HasTools::class]);

arch('application code does not contain debug helpers')
    ->expect(['dd', 'ddd', 'dump', 'env', 'print_r', 'ray', 'var_dump'])
    ->not->toBeUsed();
