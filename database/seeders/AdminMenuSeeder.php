<?php

namespace Database\Seeders;

use Admin\Models\Menu;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'label' => 'Home',
                'url' => '/#home',
                'location' => 'header',
                'sort_order' => 1,
            ],
            [
                'label' => 'Browse Categories',
                'url' => '/#categories',
                'location' => 'header',
                'sort_order' => 2,
            ],
            [
                'label' => 'Trending Products',
                'url' => '/#trending',
                'location' => 'header',
                'sort_order' => 3,
            ],
            [
                'label' => 'Discount Products',
                'url' => '/#offers',
                'location' => 'header',
                'sort_order' => 4,
            ],
            [
                'label' => 'Cart',
                'url' => '/cart',
                'location' => 'header',
                'sort_order' => 5,
            ],
            [
                'label' => 'Support',
                'url' => '/#support',
                'location' => 'header',
                'sort_order' => 6,
            ],
        ];

        foreach ($items as $item) {
            Menu::query()->updateOrCreate(
                [
                    'location' => $item['location'],
                    'label' => $item['label'],
                ],
                [
                    'url' => $item['url'],
                    'status' => 'enabled',
                    'sort_order' => $item['sort_order'],
                    'open_in_new_tab' => false,
                ]
            );
        }

        // Remove outdated default labels that no longer match homepage sections.
        Menu::query()
            ->where('location', 'header')
            ->whereIn('label', ['Shop', 'Offers', 'Brand', 'Browse Full Catalog'])
            ->delete();
    }
}
