<?php

namespace Admin\Http\Controllers;

use Admin\Models\Menu;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $location = $request->query('location');

        $menus = Menu::query()
            ->when($location && array_key_exists($location, Menu::LOCATIONS), fn ($q) => $q->where('location', $location))
            ->orderBy('location')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('admin::menus.index', [
            'menus' => $menus,
            'location' => $location,
        ]);
    }

    public function create(): View
    {
        return view('admin::menus.form', [
            'menu' => new Menu([
                'location' => 'header',
                'status' => 'enabled',
                'sort_order' => 0,
                'open_in_new_tab' => false,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Menu::create($this->validated($request));

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu item created successfully.');
    }

    public function edit(Menu $menu): View
    {
        return view('admin::menus.form', [
            'menu' => $menu,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $menu->update($this->validated($request));

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu item updated successfully.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu item deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:500'],
            'location' => ['required', Rule::in(array_keys(Menu::LOCATIONS))],
            'status' => ['required', Rule::in(array_keys(Menu::STATUSES))],
            'sort_order' => ['required', 'integer', 'min:0'],
            'open_in_new_tab' => ['nullable', 'boolean'],
        ]);

        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');

        return $data;
    }
}
