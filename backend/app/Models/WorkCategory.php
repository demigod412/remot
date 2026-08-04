<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkCategory extends Model
{
    protected $fillable = [
        'name', 'icon', 'status',
        'commission_percent', 'application_cost', 'daily_application_limit',
        'eligible_user_type', 'description',
        'result_schema', 'schema_strict',
    ];

    /**
     * Applications one worker may make in this category per calendar day.
     * 0 means unlimited, so existing categories are unaffected.
     */
    public function hasDailyLimit(): bool
    {
        return (int) $this->daily_application_limit > 0;
    }

    protected function casts(): array
    {
        return [
            'daily_application_limit' => 'integer',
            'status'             => 'integer',
            'commission_percent' => 'decimal:2',
            'application_cost'   => 'decimal:2',
            'eligible_user_type' => 'integer',
            'result_schema'      => 'array',
            'schema_strict'      => 'boolean',
        ];
    }

    public const ELIGIBLE_BOTH       = 0;
    public const ELIGIBLE_INDIVIDUAL = 1;
    public const ELIGIBLE_BUSINESS   = 2;

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function subcategories()
    {
        return $this->hasMany(WorkSubcategory::class, 'category_id');
    }

    public function works()
    {
        return $this->hasMany(Work::class, 'category_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Categories a given user_type is allowed to apply in.
     */
    public function scopeForUserType($query, int $userType)
    {
        return $query->whereIn('eligible_user_type', [self::ELIGIBLE_BOTH, $userType]);
    }

    // -------------------------------------------------------------------------
    // Commission
    // -------------------------------------------------------------------------

    /**
     * Platform cut on a gross worker payout.
     *
     * Rounded to 2dp to match the decimal(10,2) precision on works.coins_per_worker,
     * so commission + net always reconciles exactly against the gross figure.
     * Mirrors the existing calcCommission() approach used for contracts.
     */
    public function calculateCommission(float $amount): float
    {
        $percent = (float) $this->commission_percent;

        if ($percent <= 0 || $amount <= 0) {
            return 0.0;
        }

        $percent = min($percent, 100.0);

        return round($amount * $percent / 100, 2);
    }

    /**
     * What the worker actually receives, gross minus commission.
     * Always use this alongside calculateCommission() so the two halves agree.
     */
    public function calculateNetPayout(float $amount): float
    {
        return round($amount - $this->calculateCommission($amount), 2);
    }

    // -------------------------------------------------------------------------
    // Eligibility
    // -------------------------------------------------------------------------

    public function allowsUserType(?int $userType): bool
    {
        if ($this->eligible_user_type === self::ELIGIBLE_BOTH) {
            return true;
        }

        return $this->eligible_user_type === (int) $userType;
    }

    public function allowsUser(?User $user): bool
    {
        return $user !== null && $this->allowsUserType($user->user_type);
    }

    // -------------------------------------------------------------------------
    // Result schema
    // -------------------------------------------------------------------------

    public function hasResultSchema(): bool
    {
        return is_array($this->result_schema) && $this->result_schema !== [];
    }

    /**
     * Pretty-printed schema for the admin textarea.
     */
    public function resultSchemaJson(): string
    {
        return $this->hasResultSchema()
            ? json_encode($this->result_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : '';
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getEligibleUserTypeLabelAttribute(): string
    {
        return match ($this->eligible_user_type) {
            self::ELIGIBLE_INDIVIDUAL => 'Individuals only',
            self::ELIGIBLE_BUSINESS   => 'Businesses only',
            default                   => 'Individuals and businesses',
        };
    }
}
