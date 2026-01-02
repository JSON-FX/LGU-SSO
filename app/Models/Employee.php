<?php

namespace App\Models;

use App\Enums\AppRole;
use App\Enums\CivilStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Employee extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthday',
        'civil_status',
        'province_code',
        'city_code',
        'barangay_code',
        'residence',
        'block_number',
        'building_floor',
        'house_number',
        'nationality',
        'email',
        'password',
        'is_active',
        'office_id',
        'position',
        'date_employed',
        'date_terminated',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'civil_status' => CivilStatus::class,
            'is_active' => 'boolean',
            'password' => 'hashed',
            'date_employed' => 'date',
            'date_terminated' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->uuid)) {
                $employee->uuid = (string) Str::uuid();
            }
        });
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $parts = array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
            ]);

            return implode('.', array_map(fn ($part) => strtoupper(substr($part, 0, 1)), $parts));
        });
    }

    protected function age(): Attribute
    {
        return Attribute::get(function () {
            return $this->birthday ? Carbon::parse($this->birthday)->age : null;
        });
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(function () {
            $parts = array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
                $this->suffix,
            ]);

            return implode(' ', $parts);
        });
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay_code', 'code');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'employee_application')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(OAuthToken::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasAccessTo(Application $application): bool
    {
        return $this->applications()->where('application_id', $application->id)->exists();
    }

    public function getRoleFor(Application $application): ?AppRole
    {
        $pivot = $this->applications()->where('application_id', $application->id)->first();

        return $pivot ? AppRole::from($pivot->pivot->role) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
