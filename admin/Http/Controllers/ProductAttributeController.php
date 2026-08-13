<?php

namespace Admin\Http\Controllers;

use Admin\Models\ProductAttribute;
use Admin\Models\ProductAttributeOption;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductAttributeController extends Controller
{
    public function index(): View
    {
        $attributes = ProductAttribute::query()
            ->withCount('options')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('admin::attributes.index', compact('attributes'));
    }

    public function create(): View
    {
        return view('admin::attributes.form', [
            'attribute' => new ProductAttribute([
                'frontend_input' => 'select',
                'is_required' => true,
                'is_visible_on_frontend' => true,
                'sort_order' => 0,
                'status' => 'enabled',
            ]),
            'options' => collect(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $options = $data['options'] ?? [];
        unset($data['options']);

        $attribute = ProductAttribute::create($data);
        $this->syncOptions($attribute, $options);

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute created successfully.');
    }

    public function edit(ProductAttribute $attribute): View
    {
        $attribute->load(['options' => fn ($q) => $q->orderBy('sort_order')->orderBy('label')]);

        return view('admin::attributes.form', [
            'attribute' => $attribute,
            'options' => $attribute->options,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, ProductAttribute $attribute): RedirectResponse
    {
        $data = $this->validated($request, $attribute);
        $options = $data['options'] ?? [];
        unset($data['options']);

        $attribute->update($data);
        $this->syncOptions($attribute, $options);

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy(ProductAttribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return redirect()
            ->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }

    private function validated(Request $request, ?ProductAttribute $attribute = null): array
    {
        $code = ProductAttribute::makeCode(
            (string) $request->input('label', ''),
            $request->input('code')
        );
        $request->merge(['code' => $code]);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('product_attributes', 'code')->ignore($attribute?->id),
            ],
            'frontend_input' => ['required', Rule::in(array_keys(ProductAttribute::INPUT_TYPES))],
            'is_required' => ['nullable', 'boolean'],
            'is_visible_on_frontend' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(array_keys(ProductAttribute::STATUSES))],
            'options' => ['nullable', 'array'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.label' => ['nullable', 'string', 'max:100'],
            'options.*.value' => ['nullable', 'string', 'max:100'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*.status' => ['nullable', Rule::in(array_keys(ProductAttributeOption::STATUSES))],
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['is_visible_on_frontend'] = $request->boolean('is_visible_on_frontend');

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(ProductAttribute $attribute, array $options): void
    {
        $keptIds = [];

        foreach ($options as $index => $optionData) {
            $label = trim((string) ($optionData['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $value = ProductAttributeOption::makeValue($label, $optionData['value'] ?? null);
            $payload = [
                'label' => $label,
                'value' => $this->uniqueOptionValue($attribute, $value, isset($optionData['id']) ? (int) $optionData['id'] : null),
                'sort_order' => is_numeric($optionData['sort_order'] ?? null) ? (int) $optionData['sort_order'] : $index,
                'status' => ($optionData['status'] ?? 'enabled') === 'disabled' ? 'disabled' : 'enabled',
            ];

            $existingId = isset($optionData['id']) ? (int) $optionData['id'] : null;
            $existing = $existingId
                ? $attribute->options()->where('id', $existingId)->first()
                : null;

            if ($existing) {
                $existing->update($payload);
                $keptIds[] = $existing->id;
            } else {
                $created = $attribute->options()->create($payload);
                $keptIds[] = $created->id;
            }
        }

        $attribute->options()->when($keptIds !== [], fn ($q) => $q->whereNotIn('id', $keptIds))->delete();
    }

    private function uniqueOptionValue(ProductAttribute $attribute, string $value, ?int $ignoreId = null): string
    {
        $base = $value !== '' ? $value : 'option';
        $candidate = $base;
        $i = 1;

        while (
            ProductAttributeOption::query()
                ->where('attribute_id', $attribute->id)
                ->where('value', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base.'_'.$i;
            $i++;
        }

        return $candidate;
    }
}
