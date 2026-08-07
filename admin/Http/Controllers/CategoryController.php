<?php

namespace Admin\Http\Controllers;

use Admin\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->roots()
            ->with([
                'children' => fn ($q) => $q
                    ->withCount(['products', 'children'])
                    ->with(['children' => fn ($q2) => $q2->withCount('products')->orderBy('position')->orderBy('name')])
                    ->orderBy('position')
                    ->orderBy('name'),
            ])
            ->withCount(['products', 'children'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('admin::categories.index', compact('categories'));
    }

    public function create(Request $request): View
    {
        $parentId = $request->query('parent_id');

        return view('admin::categories.form', [
            'category' => new Category([
                'status' => true,
                'position' => 0,
                'parent_id' => $parentId,
            ]),
            'parentOptions' => $this->parentOptions(),
            'mode' => 'create',
            'isSubCategory' => filled($parentId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin::categories.form', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category),
            'mode' => 'edit',
            'isSubCategory' => ! $category->isRoot(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $urlKey = $request->input('url_key') ?: Str::slug($request->input('name', ''));
        $request->merge([
            'url_key' => $urlKey,
            'parent_id' => $request->filled('parent_id') ? $request->input('parent_id') : null,
        ]);

        $excludedParentIds = $category
            ? $category->descendantIds()->push($category->id)->all()
            : [];

        return $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                Rule::notIn($excludedParentIds),
            ],
            'name' => ['required', 'string', 'max:255'],
            'url_key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'url_key')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function parentOptions(?Category $exclude = null)
    {
        $excludedIds = $exclude
            ? $exclude->descendantIds()->push($exclude->id)->all()
            : [];

        return Category::query()
            ->roots()
            ->with(['children' => fn ($q) => $q->orderBy('position')->orderBy('name')])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->flatMap(function (Category $root) use ($excludedIds) {
                $options = collect();

                if (! in_array($root->id, $excludedIds, true)) {
                    $options->push([
                        'id' => $root->id,
                        'label' => $root->name,
                    ]);
                }

                foreach ($root->children as $child) {
                    if (! in_array($child->id, $excludedIds, true)) {
                        $options->push([
                            'id' => $child->id,
                            'label' => $root->name.' › '.$child->name,
                        ]);
                    }

                    $child->loadMissing('children');

                    foreach ($child->children as $grandChild) {
                        if (! in_array($grandChild->id, $excludedIds, true)) {
                            $options->push([
                                'id' => $grandChild->id,
                                'label' => $root->name.' › '.$child->name.' › '.$grandChild->name,
                            ]);
                        }
                    }
                }

                return $options;
            });
    }
}
