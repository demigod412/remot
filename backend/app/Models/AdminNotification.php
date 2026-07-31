<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = ['admin_id', 'title', 'message', 'type', 'url', 'is_read'];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', 0);
    }

    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public static function notify(int $adminId, string $title, string $message, string $type = 'info', ?string $url = null): static
    {
        return static::create([
            'admin_id' => $adminId,
            'title'    => $title,
            'message'  => $message,
            'type'     => $type,
            'url'      => $url,
            'is_read'  => false,
        ]);
    }
}
