<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Customer extends Model
{
    public const STATUSES = [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'status',
        'api_token',
        'impersonate_token',
        'impersonate_token_expires_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'api_token',
        'impersonate_token',
    ];

    protected function casts(): array
    {
        return [
            'impersonate_token_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isEnabled(): bool
    {
        return $this->status === 'enabled';
    }

    public function fullAddress(): string
    {
        return collect([
            $this->shipping_address,
            $this->city,
            $this->state,
            $this->pincode,
        ])->filter()->implode(', ');
    }

    public function issueApiToken(): string
    {
        $token = Str::random(60);
        $this->forceFill(['api_token' => hash('sha256', $token)])->save();

        return $token;
    }

    public function clearApiToken(): void
    {
        $this->forceFill(['api_token' => null])->save();
    }

    public function issueImpersonateToken(): string
    {
        $token = Str::random(60);

        $this->forceFill([
            'impersonate_token' => hash('sha256', $token),
            'impersonate_token_expires_at' => now()->addMinutes(5),
        ])->save();

        return $token;
    }

    public function clearImpersonateToken(): void
    {
        $this->forceFill([
            'impersonate_token' => null,
            'impersonate_token_expires_at' => null,
        ])->save();
    }

    public function markLoggedIn(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }

    public function verifyPassword(string $password): bool
    {
        if (! filled($this->password)) {
            return false;
        }

        return Hash::check($password, $this->password);
    }

    /**
     * @param  array{
     *     customer_name: string,
     *     customer_email: string,
     *     customer_phone?: string|null,
     *     shipping_address?: string|null,
     *     city?: string|null,
     *     state?: string|null,
     *     pincode?: string|null
     * }  $data
     */
    public static function upsertFromCheckout(array $data): self
    {
        $email = strtolower(trim($data['customer_email']));

        $customer = static::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        $payload = [
            'name' => $data['customer_name'],
            'email' => $data['customer_email'],
            'phone' => $data['customer_phone'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
        ];

        if ($customer) {
            $customer->fill(array_filter($payload, fn ($value) => $value !== null && $value !== ''));
            if (! $customer->name) {
                $customer->name = $data['customer_name'];
            }
            $customer->save();

            return $customer;
        }

        return static::create(array_merge($payload, [
            'status' => 'enabled',
        ]));
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'shipping_address' => $this->shipping_address,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
