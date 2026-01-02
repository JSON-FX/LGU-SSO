<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OAuthToken extends Model
{
    protected $table = 'oauth_tokens';

    protected $fillable = [
        'uuid',
        'employee_id',
        'application_id',
        'access_token',
        'revoked_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OAuthToken $token) {
            if (empty($token->uuid)) {
                $token->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    public function touchLastUsed(): bool
    {
        $this->last_used_at = now();

        return $this->save();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
