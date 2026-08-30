<?php

namespace App\Models;

use App\Enums\GuardEnum;
use App\Enums\RoleEnum;
use App\Enums\UserGender;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'username', 'gender', 'type', 'password', 'avatar', 'locale', 'marketing_consent_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    public function guardName(): string
    {
        return GuardEnum::WEB->value;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function isCustomer(): bool
    {
        return $this->type === UserType::Customer;
    }

    public function isAdmin(): bool
    {
        return $this->type === UserType::Admin;
    }

    public function isFoundingCircle(): bool
    {
        return $this->hasRole(RoleEnum::FOUNDING_CIRCLE->value) || $this->isAdmin();
    }

    public function syncTypeFromRoles(): void
    {
        $hasStaffRole = $this->hasAnyRole(UserType::staffRoleValues());

        $this->forceFill([
            'type' => $hasStaffRole ? UserType::Admin : UserType::Customer,
        ])->save();
    }

    public static function generateUsername(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::lower($name), '_');
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'member';
        $base = Str::limit($base, 20, '');

        $candidate = $base;
        $suffix = 1;

        while (
            static::query()
                ->where('username', $candidate)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $candidate = $base.'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<NewsletterSubscriber, $this>
     */
    public function newsletterSubscribers(): HasMany
    {
        return $this->hasMany(NewsletterSubscriber::class);
    }

    /**
     * @return HasMany<Inquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'type' => UserType::class,
            'gender' => UserGender::class,
        ];
    }
}
