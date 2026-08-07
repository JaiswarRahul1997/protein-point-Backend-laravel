<?php

namespace App\Http\Controllers\Api;

use Admin\Models\Banner;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = Banner::query()
            ->enabled()
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('slot');

        $slots = ['left', 'center', 'right'];
        $layout = [];

        foreach ($slots as $slot) {
            $banner = $banners->get($slot)?->first();

            $layout[$slot] = $banner ? $this->transform($banner) : null;
        }

        return response()->json([
            'data' => [
                'left' => $layout['left'],
                'center' => $layout['center'],
                'right' => $layout['right'],
            ],
        ]);
    }

    private function transform(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'cta_text' => $banner->cta_text,
            'cta_link' => $banner->cta_link ?: '#shop',
            'slot' => $banner->slot,
            'image' => $banner->imageUrl(),
        ];
    }
}
