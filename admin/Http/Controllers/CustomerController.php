<?php

namespace Admin\Http\Controllers;

use Admin\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'id_from' => $request->query('id_from'),
            'id_to' => $request->query('id_to'),
            'name' => $request->query('name'),
            'email' => $request->query('email'),
            'phone' => $request->query('phone'),
            'status' => $request->query('status'),
            'updated_from' => $request->query('updated_from'),
            'updated_to' => $request->query('updated_to'),
        ];

        $hasActiveFilters = collect($filters)->contains(fn ($value) => filled($value));

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->when(filled($filters['id_from']) && is_numeric($filters['id_from']), fn ($q) => $q->where('id', '>=', (int) $filters['id_from']))
            ->when(filled($filters['id_to']) && is_numeric($filters['id_to']), fn ($q) => $q->where('id', '<=', (int) $filters['id_to']))
            ->when(filled($filters['name']), fn ($q) => $q->where('name', 'ilike', '%'.$filters['name'].'%'))
            ->when(filled($filters['email']), fn ($q) => $q->where('email', 'ilike', '%'.$filters['email'].'%'))
            ->when(filled($filters['phone']), fn ($q) => $q->where('phone', 'ilike', '%'.$filters['phone'].'%'))
            ->when(filled($filters['status']) && array_key_exists($filters['status'], Customer::STATUSES), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['updated_from']), fn ($q) => $q->whereDate('updated_at', '>=', $filters['updated_from']))
            ->when(filled($filters['updated_to']), fn ($q) => $q->whereDate('updated_at', '<=', $filters['updated_to']))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin::customers.index', [
            'customers' => $customers,
            'filters' => $filters,
            'hasActiveFilters' => $hasActiveFilters,
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'orders' => fn ($q) => $q->withCount('items')->latest(),
        ]);

        $stats = [
            'orders_count' => $customer->orders->count(),
            'total_spent' => (float) $customer->orders->sum('total'),
            'last_order_at' => $customer->orders->first()?->created_at,
        ];

        return view('admin::customers.show', compact('customer', 'stats'));
    }

    public function loginAs(Customer $customer): RedirectResponse
    {
        if (! $customer->isEnabled()) {
            return back()->withErrors(['customer' => 'Cannot log in as a disabled customer.']);
        }

        $token = $customer->issueImpersonateToken();
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return redirect()->away($frontendUrl.'/account/impersonate?token='.urlencode($token));
    }

    public function updateStatus(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Customer::STATUSES))],
        ]);

        $customer->update($data);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer status updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $name = $customer->name;

        $customer->clearApiToken();
        $customer->clearImpersonateToken();
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', "Customer \"{$name}\" deleted successfully.");
    }
}
