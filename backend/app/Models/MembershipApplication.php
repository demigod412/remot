<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MembershipApplication extends Model
{
    protected $fillable = [
        'full_name', 'email', 'phone', 'country', 'applicant_type',
        'resume_path', 'cover_letter_path',
        'business_name', 'business_email', 'business_registration_doc', 'business_country',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
        'reference_code', 'ip_address', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'applicant_type' => 'integer',
            'status'         => 'integer',
            'reviewed_at'    => 'datetime',
            'submitted_at'   => 'datetime',
        ];
    }

    public const STATUS_PENDING  = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public const TYPE_INDIVIDUAL = 1;
    public const TYPE_BUSINESS   = 2;

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * The user account created when this application was approved, if any.
     * Matched on email because approval is what mints the account.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'email', 'email');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeIndividual($query)
    {
        return $query->where('applicant_type', self::TYPE_INDIVIDUAL);
    }

    public function scopeBusiness($query)
    {
        return $query->where('applicant_type', self::TYPE_BUSINESS);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default               => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            default               => 'gray',
        };
    }

    public function getApplicantTypeLabelAttribute(): string
    {
        return $this->applicant_type === self::TYPE_BUSINESS ? 'Business' : 'Individual';
    }

    public function getIsBusinessAttribute(): bool
    {
        return $this->applicant_type === self::TYPE_BUSINESS;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Human-friendly, non-guessable reference the applicant quotes to check
     * their status. Loops until it finds one that is actually free.
     */
    public static function generateReferenceCode(): string
    {
        do {
            $code = 'MA-' . now()->format('Ym') . '-' . strtoupper(Str::random(6));
        } while (static::where('reference_code', $code)->exists());

        return $code;
    }
}
