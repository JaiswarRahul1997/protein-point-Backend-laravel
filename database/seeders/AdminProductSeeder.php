<?php

namespace Database\Seeders;

use Admin\Models\Category;
use Admin\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductSeeder extends Seeder
{
    /** @var list<string> */
    private array $imagePaths = [];

    public function run(): void
    {
        $categories = $this->ensureCategories();
        $this->imagePaths = $this->ensureProteinImages();

        $brands = ['Protein Point', 'FitFuel', 'MuscleMax', 'NutriCore', 'PowerLab'];
        $attributeSets = ['Default', 'Supplements', 'Apparel', 'Accessories'];

        $productNames = [
            'simple' => [
                'Whey Protein Isolate',
                'Casein Night Protein',
                'Plant Protein Blend',
                'Mass Gainer Chocolate',
                'BCAA Recovery Drink',
                'Creatine Monohydrate',
                'Pre-Workout Ignite',
                'Protein Cookie Pack',
                'Collagen Peptides',
                'Low Carb Protein Bar',
            ],
            'configurable' => [
                'Whey Protein – Flavor Pack',
                'Protein Shake – Size Options',
                'Muscle Tee – Color Variants',
                'Gym Bottle – Cap Styles',
                'Protein Pancake Mix Kit',
                'Amino Caps – Strength Levels',
                'Resistance Band Set',
                'Protein Oats Bowl Kit',
                'Electrolyte Mix – Variants',
                'Fit Cap – Size Range',
            ],
            'bundle' => [
                'Lean Muscle Starter Bundle',
                'Bulk Season Stack',
                'Morning Protein Bundle',
                'Recovery Duo Pack',
                'Shred Stack Bundle',
                'Gym Essentials Bundle',
                'Plant Power Bundle',
                'Night Recovery Bundle',
                'Athlete Fuel Bundle',
                'Office Protein Bundle',
            ],
            'grouped' => [
                'Protein Snack Group',
                'Workout Fuel Group',
                'Daily Protein Group',
                'Supplement Essentials Group',
                'Post-Gym Recovery Group',
                'Breakfast Protein Group',
                'Travel Protein Group',
                'Lean Cut Group',
                'Mass Builder Group',
                'Hydration + Protein Group',
            ],
            'virtual' => [
                'Online Protein Meal Plan',
                'Virtual Trainer Session',
                'Macro Coaching Plan',
                'Protein Recipe Ebook',
                'Nutrition Video Course',
                'Supplement Guide Download',
                'Fitness Tracker Access',
                'Diet Consultation Call',
                'Gym Form Checklist PDF',
                'Protein Timing Webinar',
            ],
        ];

        foreach (Product::TYPES as $type => $typeLabel) {
            for ($i = 1; $i <= 10; $i++) {
                $name = $productNames[$type][$i - 1] ?? "{$typeLabel} Product {$i}";
                $sku = strtoupper($type).'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
                $urlKey = Str::slug($name).'-'.$sku;
                $brand = $brands[($i - 1) % count($brands)];
                $images = $this->imagesForIndex(($i - 1) + (array_search($type, array_keys(Product::TYPES), true) * 10));

                $product = Product::query()->updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $name,
                        'type' => $type,
                        'attribute_set' => $attributeSets[($i - 1) % count($attributeSets)],
                        'stock_status' => $i % 4 === 0 ? 'out_of_stock' : 'in_stock',
                        'price' => round(19.99 + ($i * 5.5) + (array_search($type, array_keys(Product::TYPES), true) * 10), 2),
                        'quantity' => $i % 4 === 0 ? 0 : ($i * 12),
                        'visibility' => 'catalog_search',
                        'status' => $i % 5 === 0 ? 'disabled' : 'enabled',
                        'url_key' => $urlKey,
                        'brand' => $brand,
                        'thumbnail' => $images[0] ?? null,
                        'images' => $images,
                        'videos' => [],
                        'meta_title' => $name.' | Protein Point',
                        'meta_keywords' => strtolower("{$name}, protein, supplements, {$brand}"),
                        'meta_description' => "Buy {$name} from Protein Point — quality protein and fitness nutrition.",
                        'description' => "{$name} from {$brand}. Protein-focused nutrition made for training, recovery, and everyday fitness goals.",
                    ]
                );

                $categoryIds = $categories->pluck('id')->all();
                shuffle($categoryIds);
                $product->categories()->sync(array_slice($categoryIds, 0, min(2, count($categoryIds))));
            }
        }

        // Attach protein images to any other products that still lack them.
        Product::query()
            ->where(function ($query) {
                $query->whereNull('thumbnail')
                    ->orWhere('thumbnail', '')
                    ->orWhereNull('images')
                    ->orWhereRaw("images::text = '[]'");
            })
            ->orderBy('id')
            ->get()
            ->each(function (Product $product, int $index) {
                $images = $this->imagesForIndex($index + 100);
                $product->update([
                    'thumbnail' => $images[0] ?? null,
                    'images' => $images,
                ]);
            });
    }

    /**
     * @return list<string>
     */
    private function imagesForIndex(int $index): array
    {
        if ($this->imagePaths === []) {
            return [];
        }

        $count = count($this->imagePaths);
        $primary = $this->imagePaths[$index % $count];
        $secondary = $this->imagePaths[($index + 3) % $count];
        $tertiary = $this->imagePaths[($index + 7) % $count];

        return array_values(array_unique([$primary, $secondary, $tertiary]));
    }

    /**
     * Download protein-related demo images into public storage.
     *
     * @return list<string>
     */
    private function ensureProteinImages(): array
    {
        $directory = 'products/images';
        Storage::disk('public')->makeDirectory($directory);

        $sources = [
            'protein-shake-1.jpg' => 'https://images.unsplash.com/photo-1593095948071-474c17686617?auto=format&fit=crop&w=800&q=80',
            'protein-powder-1.jpg' => 'https://images.unsplash.com/photo-1579722821273-0f6c7d44362f?auto=format&fit=crop&w=800&q=80',
            'whey-scoop-1.jpg' => 'https://images.unsplash.com/photo-1579722820308-d74e571900a6?auto=format&fit=crop&w=800&q=80',
            'fitness-protein-1.jpg' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80',
            'gym-bottle-1.jpg' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=800&q=80',
            'healthy-meal-1.jpg' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=800&q=80',
            'protein-bars-1.jpg' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&w=800&q=80',
            'workout-1.jpg' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
            'supplements-1.jpg' => 'https://images.unsplash.com/photo-1550572017-edd951aa8efa?auto=format&fit=crop&w=800&q=80',
            'athlete-1.jpg' => 'https://images.unsplash.com/photo-1517963879433-6ad2b65677ce?auto=format&fit=crop&w=800&q=80',
        ];

        $paths = [];

        foreach ($sources as $filename => $url) {
            $path = $directory.'/'.$filename;

            if (! Storage::disk('public')->exists($path) || Storage::disk('public')->size($path) < 1000) {
                try {
                    $response = Http::timeout(30)->withHeaders([
                        'User-Agent' => 'ProteinPointSeeder/1.0',
                    ])->get($url);

                    if ($response->successful() && strlen($response->body()) > 1000) {
                        Storage::disk('public')->put($path, $response->body());
                    } else {
                        $this->writeFallbackImage($path, $filename);
                    }
                } catch (\Throwable) {
                    $this->writeFallbackImage($path, $filename);
                }
            }

            if (Storage::disk('public')->exists($path)) {
                $paths[] = $path;
            }
        }

        if ($paths === []) {
            for ($i = 1; $i <= 6; $i++) {
                $path = $directory."/protein-fallback-{$i}.jpg";
                $this->writeFallbackImage($path, "Protein {$i}");
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private function writeFallbackImage(string $path, string $label): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            Storage::disk('public')->put($path, $this->minimalJpegBytes());

            return;
        }

        $image = imagecreatetruecolor(800, 800);
        $bg = imagecolorallocate($image, 20, 20, 20);
        $fg = imagecolorallocate($image, 255, 255, 255);
        $accent = imagecolorallocate($image, 230, 230, 230);
        imagefilledrectangle($image, 0, 0, 800, 800, $bg);
        imagefilledrectangle($image, 40, 40, 760, 760, $accent);
        imagefilledrectangle($image, 60, 60, 740, 740, $bg);

        $title = 'PROTEIN POINT';
        $subtitle = Str::upper(Str::before($label, '.'));
        imagestring($image, 5, 300, 360, $title, $fg);
        imagestring($image, 4, 310, 400, $subtitle, $fg);

        ob_start();
        imagejpeg($image, null, 85);
        $bytes = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $bytes ?: $this->minimalJpegBytes());
    }

    private function minimalJpegBytes(): string
    {
        return base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBUQEBAVFRUVFRUVFRUVFRUVFRUWFxUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAQMBIgACEQEDEQH/xAAbAAACAwEBAQAAAAAAAAAAAAAFBgAEBwIDAf/EAD0QAAIBAwMCBAMFBgQHAAAAAAECAwAEEQUSITFBBhNRYQcicYEUMpGhFSNCUrHBM2Jy0fAWJGOiscLh/8QAGQEAAwEBAQAAAAAAAAAAAAAAAAECAwQF/8QAJBEAAgICAgMAAgMAAAAAAAAAAAECEQMhEjFBBFEiYRMycRP/2gAMAwEAAhEDEQAAD8A9pREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREAREQBERAEREB//Z'
        );
    }

    private function ensureCategories()
    {
        $defs = [
            ['name' => 'Protein', 'url_key' => 'protein'],
            ['name' => 'Vitamins', 'url_key' => 'vitamins'],
            ['name' => 'Accessories', 'url_key' => 'accessories'],
            ['name' => 'Pre-Workout', 'url_key' => 'pre-workout'],
            ['name' => 'Recovery', 'url_key' => 'recovery'],
        ];

        foreach ($defs as $index => $data) {
            Category::query()->firstOrCreate(
                ['url_key' => $data['url_key']],
                [
                    'name' => $data['name'],
                    'description' => $data['name'].' category for protein and fitness products.',
                    'status' => true,
                    'position' => $index + 1,
                ]
            );
        }

        return Category::query()->orderBy('position')->get();
    }
}
