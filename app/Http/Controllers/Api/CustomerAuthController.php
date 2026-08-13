<?php

namespace App\Http\Controllers\Api;

use Admin\Models\Customer;
use Admin\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
        ]);

        $email = strtolower(trim($data['email']));
        $existing = Customer::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing && filled($existing->password)) {
            return response()->json([
                'message' => 'An account with this email already exists. Please sign in.',
            ], 422);
        }

        if ($existing) {
            $existing->fill([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? $existing->phone,
                'password' => $data['password'],
                'shipping_address' => $data['shipping_address'] ?? $existing->shipping_address,
                'city' => $data['city'] ?? $existing->city,
                'state' => $data['state'] ?? $existing->state,
                'pincode' => $data['pincode'] ?? $existing->pincode,
                'status' => 'enabled',
            ])->save();

            $customer = $existing;
        } else {
            $customer = Customer::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'shipping_address' => $data['shipping_address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'status' => 'enabled',
            ]);
        }

        $token = $customer->issueApiToken();
        $customer->markLoggedIn();

        return response()->json([
            'message' => 'Account created successfully.',
            'token' => $token,
            'customer' => $customer->fresh()->toPublicArray(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])
            ->first();

        if (! $customer || ! $customer->verifyPassword($data['password'])) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }

        if (! $customer->isEnabled()) {
            return response()->json(['message' => 'This customer account is disabled.'], 403);
        }

        $token = $customer->issueApiToken();
        $customer->markLoggedIn();

        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $token,
            'customer' => $customer->toPublicArray(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $customer = $this->customerFromRequest($request);

        if ($customer) {
            $customer->clearApiToken();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $customer = $this->customerFromRequest($request);

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $customer->load(['orders' => fn ($q) => $q->with('items')->latest()->limit(50)]);

        return response()->json([
            'customer' => $customer->toPublicArray(),
            'orders' => $customer->orders->map(fn ($order) => $this->transformOrder($order))->values(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $customer = $this->customerFromRequest($request);

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
        ]);

        $customer->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'customer' => $customer->fresh()->toPublicArray(),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $customer = $this->customerFromRequest($request);

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if (filled($customer->password)) {
            if (! filled($data['current_password'] ?? null) || ! $customer->verifyPassword($data['current_password'])) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
        }

        $customer->update(['password' => $data['password']]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function order(Request $request, Order $order): JsonResponse
    {
        $customer = $this->customerFromRequest($request);

        if (! $customer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ((int) $order->customer_id !== (int) $customer->id) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->load('items');

        return response()->json([
            'order' => $this->transformOrder($order),
        ]);
    }

    public function impersonate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:20'],
        ]);

        $hashed = hash('sha256', $data['token']);

        $customer = Customer::query()
            ->where('impersonate_token', $hashed)
            ->where('impersonate_token_expires_at', '>', now())
            ->first();

        if (! $customer) {
            return response()->json(['message' => 'Invalid or expired impersonation token.'], 422);
        }

        if (! $customer->isEnabled()) {
            return response()->json(['message' => 'This customer account is disabled.'], 403);
        }

        $customer->clearImpersonateToken();
        $token = $customer->issueApiToken();
        $customer->markLoggedIn();

        return response()->json([
            'message' => 'Logged in as customer.',
            'impersonated' => true,
            'token' => $token,
            'customer' => $customer->toPublicArray(),
        ]);
    }

    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'subtotal' => (float) $order->subtotal,
            'total' => (float) $order->total,
            'total_formatted' => '₹'.number_format((float) $order->total, 0),
            'shipping_address' => $order->shipping_address,
            'city' => $order->city,
            'state' => $order->state,
            'pincode' => $order->pincode,
            'customer_phone' => $order->customer_phone,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'created_at_formatted' => $order->created_at?->format('d M Y, h:i A'),
            'items_count' => $order->items->count(),
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
                'line_total_formatted' => '₹'.number_format((float) $item->line_total, 0),
                'thumbnail' => method_exists($item, 'thumbnailUrl') ? $item->thumbnailUrl() : null,
            ])->values(),
        ];
    }

    private function customerFromRequest(Request $request): ?Customer
    {
        $header = (string) $request->bearerToken();

        if ($header === '') {
            return null;
        }

        return Customer::query()
            ->where('api_token', hash('sha256', $header))
            ->where('status', 'enabled')
            ->first();
    }
}
