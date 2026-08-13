<?php

use Admin\Models\Customer;
use Admin\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('status')->default('enabled');
            $table->string('api_token', 80)->nullable()->unique();
            $table->string('impersonate_token', 80)->nullable()->unique();
            $table->timestamp('impersonate_token_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('phone');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')
                ->nullable()
                ->after('id')
                ->constrained('customers')
                ->nullOnDelete();
        });

        $this->syncCustomersFromOrders();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::dropIfExists('customers');
    }

    private function syncCustomersFromOrders(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('customers')) {
            return;
        }

        Order::query()
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Order $order) => strtolower(trim((string) $order->customer_email)))
            ->each(function ($orders, string $email) {
                if ($email === '') {
                    return;
                }

                /** @var Order $latest */
                $latest = $orders->sortByDesc('id')->first();

                $customer = Customer::query()->updateOrCreate(
                    ['email' => $latest->customer_email],
                    [
                        'name' => $latest->customer_name,
                        'phone' => $latest->customer_phone,
                        'shipping_address' => $latest->shipping_address,
                        'city' => $latest->city,
                        'state' => $latest->state,
                        'pincode' => $latest->pincode,
                        'status' => 'enabled',
                    ]
                );

                Order::query()
                    ->whereIn('id', $orders->pluck('id'))
                    ->update(['customer_id' => $customer->id]);
            });
    }
};
