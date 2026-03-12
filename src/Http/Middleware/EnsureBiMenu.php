<?php

namespace iEducar\Packages\Bis\Http\Middleware;

use App\Menu;
use App\Services\MenuCacheService;
use Closure;
use iEducar\Packages\Bis\BisProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class EnsureBiMenu
{
    /**
     * Garante que menu lateral e superior estejam disponíveis em todas as rotas do pacote BI.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $menu = $user ? app(MenuCacheService::class)->getMenuByUser($user) : collect();

        $biProcess = BisProcess::menuBi();

        $topmenu = Menu::query()->where('process', $biProcess)->first();
        $ancestors = $topmenu ? Menu::getMenuAncestors($topmenu) : [];

        if ($topmenu) {
            View::share([
                'mainmenu' => $topmenu->root()->getKey(),
                'currentMenu' => $topmenu,
                'menuPaths' => $ancestors,
            ]);
        }

        View::share([
            'menu' => $menu,
            'root' => $topmenu?->root()?->getKey(),
        ]);

        return $next($request);
    }
}

