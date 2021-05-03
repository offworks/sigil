<?php

namespace Sigil;

use App\Http\Controllers\RootController;
use Exedra\Exception\RouteNotFoundException;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Facade;

abstract class HttpKernel extends Kernel
{
//    abstract public function getSigilSetup() : SigilSetup;

    public function getSigilSetup()
    {
        $controller = config('sigil.root_controller');

        if (!$controller)
            return null;

        return new SigilSetup($controller,
            middlewares: array_merge($this->middleware, config('sigil.middlewares', [])),
            autoReload: config('sigil.auto_reload', true),
            decorators: config('sigil.decorators', [])
        );
    }

    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);

        Facade::clearResolvedInstance('request');

        $this->bootstrap();

        try {
            /** @var Sigil $sigil */
            $sigil = app(Sigil::class);
            $sigil->dispatch();
        } catch (RouteNotFoundException $e) {
            return (new Pipeline($this->app))
                ->send($request)
                ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                ->then($this->dispatchToRouter());
        }
    }
}
