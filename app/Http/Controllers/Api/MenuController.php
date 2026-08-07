<?php

namespace App\Http\Controllers\Api;

use Admin\Models\Menu;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $location = $request->query('location', 'header');

        if ($location !== 'all' && ! array_key_exists($location, Menu::LOCATIONS)) {
            $location = 'header';
        }

        $query = Menu::query()
            ->enabled()
            ->orderBy('sort_order')
            ->orderBy('label');

        if ($location !== 'all') {
            $query->location($location);
        }

        $items = $query->get()->map(fn (Menu $menu) => [
            'id' => $menu->id,
            'label' => $menu->label,
            'url' => $menu->url,
            'location' => $menu->location,
            'sort_order' => $menu->sort_order,
            'open_in_new_tab' => (bool) $menu->open_in_new_tab,
        ])->values();

        return response()->json([
            'data' => $items,
        ]);
    }
}
