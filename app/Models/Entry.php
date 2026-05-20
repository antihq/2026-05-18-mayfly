<?php

namespace App\Models;

use App\Enums\EntryStatus;
use App\Enums\EntryType;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'user_id', 'type', 'content', 'status', 'expires_at'])]
class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => EntryType::class,
            'status' => EntryStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        $query->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function timeRemaining(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        $diff = now()->diff($this->expires_at);
        $totalMinutes = ($diff->d * 24 * 60) + ($diff->h * 60) + $diff->i;

        if ($totalMinutes >= 60) {
            $hours = (int) floor($totalMinutes / 60);

            return $hours.'h left';
        }

        return $totalMinutes.'m left';
    }
}
