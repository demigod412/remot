<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // NOTE: 'coin_balance' is deliberately NOT mass-assignable. Balance only ever
    // changes through CoinService / explicit increment-decrement money flows, never
    // from request input. Trusted server code (e.g. seeders) uses forceFill/unguard.
    protected $fillable = [
        'firstname', 'lastname', 'username', 'email',
        'country_code', 'mobile', 'referred_by',
        'password', 'image', 'address', 'status',
        'kyc_data', 'kyc_status', 'email_verified', 'phone_verified',
        'onboarding_step', 'verify_code', 'verify_code_sent_at',
        'two_fa_enabled', 'two_fa_verified', 'two_fa_secret', 'ban_reason',
        'avatar', 'password_reset_token', 'locale', 'account_type',
        'firebase_uid',
    ];

    protected $hidden = ['password', 'remember_token', 'two_fa_secret'];

    protected function casts(): array
    {
        return [
            'password'            => 'hashed',
            'address'             => 'array',
            'kyc_data'            => 'array',
            'coin_balance'        => 'decimal:8',
            'kyc_status'          => 'integer',
            'verify_code_sent_at' => 'datetime',
            'email_verified'      => 'boolean',
            'phone_verified'      => 'boolean',
            'two_fa_enabled'      => 'boolean',
            'two_fa_verified'     => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getFullnameAttribute(): string
    {
        return trim("{$this->firstname} {$this->lastname}");
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function coinTopups()
    {
        return $this->hasMany(CoinTopup::class);
    }

    public function cashouts()
    {
        return $this->hasMany(Cashout::class);
    }

    public function workSubmissions()
    {
        return $this->hasMany(WorkSubmission::class, 'worker_id');
    }

    public function works()
    {
        return $this->hasMany(Work::class, 'poster_id')->where('poster_type', 2);
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function helpTickets()
    {
        return $this->hasMany(HelpTicket::class);
    }

    public function referralEarnings()
    {
        return $this->hasMany(ReferralEarning::class, 'earner_id');
    }

    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function adminNotifications()
    {
        return $this->hasMany(AdminNotification::class);
    }

    public function jobListings()
    {
        return $this->hasMany(JobListing::class, 'employer_id');
    }

    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class, 'applicant_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'user_skills');
    }

    public function contractsAsEmployer()
    {
        return $this->hasMany(Contract::class, 'employer_id');
    }

    public function contractsAsWorker()
    {
        return $this->hasMany(Contract::class, 'worker_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    public function jobBookmarks()
    {
        return $this->hasMany(JobBookmark::class);
    }

    public function workBookmarks()
    {
        return $this->hasMany(WorkBookmark::class);
    }

    public function ratingsGiven()
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'ratee_id');
    }

    public function publicRatingData(): array
    {
        $row = \DB::table('ratings as r1')
            ->where('r1.ratee_id', $this->id)
            ->whereExists(fn($q) => $q->from('ratings as r2')
                ->whereColumn('r2.rater_id', 'r1.ratee_id')
                ->whereColumn('r2.ratee_id', 'r1.rater_id')
                ->whereColumn('r2.ratable_id', 'r1.ratable_id')
                ->whereColumn('r2.ratable_type', 'r1.ratable_type')
            )
            ->selectRaw('ROUND(AVG(r1.rating), 1) as avg_r, COUNT(*) as cnt')
            ->first();

        return [
            'avg'   => $row && $row->cnt > 0 ? (float) $row->avg_r : null,
            'count' => (int) ($row?->cnt ?? 0),
        ];
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', 0);
    }

    public function scopeEmailVerified($query)
    {
        return $query->where('email_verified', 1);
    }

    public function scopeEmailUnverified($query)
    {
        return $query->where('email_verified', 0);
    }

    public function scopePhoneVerified($query)
    {
        return $query->where('phone_verified', 1);
    }

    public function scopePhoneUnverified($query)
    {
        return $query->where('phone_verified', 0);
    }

    public function scopeKycVerified($query)
    {
        return $query->where('kyc_status', 1);
    }

    public function scopeKycPending($query)
    {
        return $query->where('kyc_status', 2);
    }

    public function scopeKycUnverified($query)
    {
        return $query->where('kyc_status', 0);
    }

    public function scopeWithBalance($query)
    {
        return $query->where('coin_balance', '>', 0);
    }
}
