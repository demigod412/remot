<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_log';

    protected $fillable = [
        'admin_id', 'action', 'subject_type', 'subject_id', 'meta', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',
            'subject_id' => 'integer',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * The record this entry is about, if the class still exists.
     */
    public function subject()
    {
        return $this->morphTo(null, 'subject_type', 'subject_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForSubject($query, string $type, int $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * "membership.approve" reads as "Membership Approve" in the log table.
     */
    public function getActionLabelAttribute(): string
    {
        return ucwords(str_replace(['.', '_'], ' ', (string) $this->action));
    }

    public function getSubjectLabelAttribute(): string
    {
        if (! $this->subject_type) {
            return '-';
        }

        return class_basename($this->subject_type) . ' #' . $this->subject_id;
    }
}
