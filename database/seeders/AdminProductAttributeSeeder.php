<?php

namespace Database\Seeders;

use Admin\Models\Product;
use Admin\Models\ProductAttribute;
use Admin\Models\ProductAttributeOption;
use Illuminate\Database\Seeder;

class AdminProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $existingBrands = Product::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->all();

        $brandOptions = array_values(array_unique(array_filter(array_merge(
            ['Protein Point', 'FitFuel', 'MuscleMax', 'NutriCore', 'PowerLab'],
            $existingBrands
        ))));

        $defs = [
            [
                'code' => 'size',
                'label' => 'Size',
                'sort_order' => 1,
                'options' => ['500g', '1kg', '2kg'],
            ],
            [
                'code' => 'flavor',
                'label' => 'Flavor',
                'sort_order' => 2,
                'options' => ['Chocolate', 'Vanilla', 'Strawberry', 'Unflavored'],
            ],
            [
                'code' => 'brand',
                'label' => 'Brand',
                'sort_order' => 0,
                'options' => $brandOptions,
            ],
        ];

        foreach ($defs as $def) {
            $attribute = ProductAttribute::query()->updateOrCreate(
                ['code' => $def['code']],
                [
                    'label' => $def['label'],
                    'frontend_input' => 'select',
                    'is_required' => true,
                    'is_visible_on_frontend' => true,
                    'sort_order' => $def['sort_order'],
                    'status' => 'enabled',
                ]
            );

            foreach ($def['options'] as $index => $label) {
                $value = ProductAttributeOption::makeValue($label);
                ProductAttributeOption::query()->updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'value' => $value,
                    ],
                    [
                        'label' => $label,
                        'sort_order' => $index,
                        'status' => 'enabled',
                    ]
                );
            }
        }

        $sizeAttr = ProductAttribute::query()->where('code', 'size')->with('options')->first();
        $flavorAttr = ProductAttribute::query()->where('code', 'flavor')->with('options')->first();
        $brandAttr = ProductAttribute::query()->where('code', 'brand')->with('options')->first();

        Product::query()->with('attributeOptions.attribute')->orderBy('id')->chunkById(50, function ($products) use ($sizeAttr, $flavorAttr, $brandAttr) {
            foreach ($products as $product) {
                $ids = $product->attributeOptions->pluck('id')->all();

                $ids = array_merge(
                    $ids,
                    $this->optionIdsForAttribute($product, $sizeAttr, is_array($product->sizes) ? $product->sizes : null),
                    $this->optionIdsForAttribute($product, $flavorAttr, is_array($product->flavors) ? $product->flavors : null),
                    $this->optionIdsForAttribute(
                        $product,
                        $brandAttr,
                        filled($product->brand) ? [$product->brand] : null
                    )
                );

                if ($ids !== []) {
                    $product->attributeOptions()->sync(array_values(array_unique($ids)));
                }
            }
        });
    }

    /**
     * @param  array<int, string>|null  $preferredLabels
     * @return array<int, int>
     */
    private function optionIdsForAttribute(Product $product, ?ProductAttribute $attribute, ?array $preferredLabels): array
    {
        if (! $attribute) {
            return [];
        }

        $alreadyHas = $product->attributeOptions
            ->contains(fn ($option) => (int) $option->attribute_id === (int) $attribute->id);

        if ($alreadyHas) {
            return [];
        }

        $labels = is_array($preferredLabels) && $preferredLabels !== []
            ? $preferredLabels
            : $attribute->options->pluck('label')->all();

        $ids = [];

        foreach ($labels as $label) {
            $value = ProductAttributeOption::makeValue((string) $label);
            $option = $attribute->options->first(function ($item) use ($label, $value) {
                return $item->label === $label || $item->value === $value || $item->value === $label;
            });

            if ($option) {
                $ids[] = $option->id;
            }
        }

        // If preferred brand/size didn't match, fall back to all options for that attribute.
        if ($ids === [] && $preferredLabels) {
            $ids = $attribute->options->pluck('id')->all();
        }

        return $ids;
    }
}
