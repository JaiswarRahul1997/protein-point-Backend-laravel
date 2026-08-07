<?php

namespace Database\Seeders;

use Admin\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AdminBannerSeeder extends Seeder
{
    public function run(): void
    {
        $sourceImages = collect(Storage::disk('public')->files('products/images'))
            ->filter(fn ($path) => str_ends_with(strtolower($path), '.jpg') || str_ends_with(strtolower($path), '.jpeg') || str_ends_with(strtolower($path), '.png') || str_ends_with(strtolower($path), '.webp'))
            ->values();

        if ($sourceImages->isEmpty()) {
            $this->command?->warn('No product images found to seed banners. Upload banners from admin instead.');

            return;
        }

        Storage::disk('public')->makeDirectory('banners');

        $defs = [
            [
                'slot' => 'left',
                'title' => 'Lean Fuel',
                'subtitle' => 'Low-carb protein picks for cutting season.',
                'cta_text' => 'Shop Lean',
                'cta_link' => '#offers',
                'sort_order' => 1,
            ],
            [
                'slot' => 'center',
                'title' => 'Fuel the Work',
                'subtitle' => 'Premium protein stacks for serious training.',
                'cta_text' => 'Shop Trending',
                'cta_link' => '#trending',
                'sort_order' => 1,
            ],
            [
                'slot' => 'right',
                'title' => 'Recover Fast',
                'subtitle' => 'BCAA and recovery essentials.',
                'cta_text' => 'Shop Recovery',
                'cta_link' => '#shop',
                'sort_order' => 1,
            ],
        ];

        foreach ($defs as $index => $def) {
            $source = $sourceImages[$index % $sourceImages->count()];
            $target = 'banners/'.$def['slot'].'-'.basename($source);

            if (! Storage::disk('public')->exists($target)) {
                Storage::disk('public')->copy($source, $target);
            }

            Banner::query()->updateOrCreate(
                ['slot' => $def['slot'], 'title' => $def['title']],
                [
                    'subtitle' => $def['subtitle'],
                    'cta_text' => $def['cta_text'],
                    'cta_link' => $def['cta_link'],
                    'image' => $target,
                    'status' => 'enabled',
                    'sort_order' => $def['sort_order'],
                ]
            );
        }
    }
}
