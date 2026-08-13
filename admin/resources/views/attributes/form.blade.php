@extends('admin::layouts.app')

@section('title', $mode === 'create' ? 'Add Attribute' : 'Edit Attribute')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">{{ $mode === 'create' ? 'Add Attribute' : 'Edit Attribute' }}</h1>
            <p class="page-lead">Define the attribute and its dropdown options. Then enable selected options on each product.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.attributes.index') }}">Back</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.attributes.store') : route('admin.attributes.update', $attribute) }}" id="attribute-form">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="form-panel">
            <h2 class="section-title">Attribute Details</h2>
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="label">Label</label>
                    <input class="form-input" id="label" name="label" type="text" value="{{ old('label', $attribute->label) }}" required placeholder="Size">
                    @error('label')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="code">Attribute Code</label>
                    <input class="form-input" id="code" name="code" type="text" value="{{ old('code', $attribute->code) }}" placeholder="size">
                    <p class="form-hint">Lowercase code used in API/cart (auto from label if empty).</p>
                    @error('code')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="frontend_input">Catalog Input Type</label>
                    <select class="form-select" id="frontend_input" name="frontend_input" required>
                        @foreach (\Admin\Models\ProductAttribute::INPUT_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('frontend_input', $attribute->frontend_input) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('frontend_input')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input class="form-input" id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $attribute->sort_order ?? 0) }}" required>
                    @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        @foreach (\Admin\Models\ProductAttribute::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $attribute->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label">Flags</label>
                    <label class="checkbox-item" style="margin-top:0.55rem">
                        <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $attribute->is_required))>
                        Required on product page
                    </label>
                    <label class="checkbox-item">
                        <input type="checkbox" name="is_visible_on_frontend" value="1" @checked(old('is_visible_on_frontend', $attribute->is_visible_on_frontend))>
                        Visible on storefront
                    </label>
                </div>
            </div>
        </section>

        <section class="form-panel">
            <div class="page-header" style="margin-bottom:1rem">
                <h2 class="section-title" style="margin:0">Manage Options</h2>
                <button class="btn btn-outline btn-sm" type="button" id="add-option-row">Add Option</button>
            </div>

            <div class="table-wrap">
                <table class="data-table" id="options-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Value</th>
                            <th>Sort</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $oldOptions = old('options');
                            $rows = is_array($oldOptions) ? collect($oldOptions) : $options;
                        @endphp
                        @forelse ($rows as $index => $option)
                            @php
                                $optionId = is_array($option) ? ($option['id'] ?? '') : $option->id;
                                $optionLabel = is_array($option) ? ($option['label'] ?? '') : $option->label;
                                $optionValue = is_array($option) ? ($option['value'] ?? '') : $option->value;
                                $optionSort = is_array($option) ? ($option['sort_order'] ?? $index) : $option->sort_order;
                                $optionStatus = is_array($option) ? ($option['status'] ?? 'enabled') : $option->status;
                            @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="options[{{ $index }}][id]" value="{{ $optionId }}">
                                    <input class="form-input" name="options[{{ $index }}][label]" type="text" value="{{ $optionLabel }}" placeholder="500g">
                                </td>
                                <td>
                                    <input class="form-input" name="options[{{ $index }}][value]" type="text" value="{{ $optionValue }}" placeholder="500g">
                                </td>
                                <td>
                                    <input class="form-input" name="options[{{ $index }}][sort_order]" type="number" min="0" value="{{ $optionSort }}">
                                </td>
                                <td>
                                    <select class="form-select" name="options[{{ $index }}][status]">
                                        @foreach (\Admin\Models\ProductAttributeOption::STATUSES as $value => $label)
                                            <option value="{{ $value }}" @selected($optionStatus === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-outline btn-sm" type="button" data-remove-option>&times;</button>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="form-hint" style="margin-top:0.75rem">Add options like 500g / 1kg for Size, or Chocolate / Vanilla for Flavor.</p>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $mode === 'create' ? 'Create Attribute' : 'Save Changes' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.attributes.index') }}">Back to list</a>
        </div>
    </form>

    <template id="option-row-template">
        <tr>
            <td>
                <input type="hidden" name="options[__INDEX__][id]" value="">
                <input class="form-input" name="options[__INDEX__][label]" type="text" placeholder="Option label">
            </td>
            <td>
                <input class="form-input" name="options[__INDEX__][value]" type="text" placeholder="option_value">
            </td>
            <td>
                                <input class="form-input" name="options[__INDEX__][sort_order]" type="number" min="0" value="0">
            </td>
            <td>
                <select class="form-select" name="options[__INDEX__][status]">
                    <option value="enabled" selected>Enabled</option>
                    <option value="disabled">Disabled</option>
                </select>
            </td>
            <td>
                <button class="btn btn-outline btn-sm" type="button" data-remove-option>&times;</button>
            </td>
        </tr>
    </template>

    <script>
        (function () {
            var tableBody = document.querySelector('#options-table tbody');
            var template = document.getElementById('option-row-template');
            var addBtn = document.getElementById('add-option-row');
            var nextIndex = tableBody ? tableBody.querySelectorAll('tr').length : 0;

            function bindRemove(btn) {
                btn.addEventListener('click', function () {
                    var row = btn.closest('tr');
                    if (row) row.remove();
                });
            }

            document.querySelectorAll('[data-remove-option]').forEach(bindRemove);

            addBtn.addEventListener('click', function () {
                var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));
                var wrapper = document.createElement('tbody');
                wrapper.innerHTML = html.trim();
                var row = wrapper.firstElementChild;
                tableBody.appendChild(row);
                bindRemove(row.querySelector('[data-remove-option]'));
            });
        })();
    </script>
@endsection
