<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PRIORITIZED = 'prioritized';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_RESOLVED = 'resolved';

    /** Ordre du parcours, utilisé pour la frise de suivi. */
    public const STATUS_ORDER = [
        self::STATUS_RECEIVED,
        self::STATUS_PRIORITIZED,
        self::STATUS_DISPATCHED,
        self::STATUS_RESOLVED,
    ];

    /** Statuts d'un signalement encore à traiter. */
    public const OPEN_STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_PRIORITIZED,
        self::STATUS_DISPATCHED,
    ];

    /** Fuseau d'affichage des dates (stockage en UTC). */
    public const DISPLAY_TZ = 'Africa/Porto-Novo';

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'score' => 'integer',
        'score_reasons' => 'array',
        'dispatched_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReportEvent::class)->orderBy('id');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function statusIndex(): int
    {
        $index = array_search($this->status, self::STATUS_ORDER, true);

        return $index === false ? 0 : $index;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_RECEIVED, self::STATUS_PRIORITIZED => 'À affecter',
            self::STATUS_DISPATCHED => 'Équipe en route',
            self::STATUS_RESOLVED => 'Terminé',
            default => $this->status,
        };
    }

    public function levelLabel(): string
    {
        return match ($this->level) {
            'high' => 'Priorité élevée',
            'medium' => 'Priorité moyenne',
            default => 'Priorité faible',
        };
    }

    /** Couleur d'affichage (classes CSS : red / orange / yellow). */
    public function levelColor(): string
    {
        return match ($this->level) {
            'high' => 'red',
            'medium' => 'orange',
            default => 'yellow',
        };
    }

    public function title(): string
    {
        return match ($this->perceived_severity) {
            'high' => 'Fuite importante signalée',
            'medium' => 'Écoulement continu signalé',
            default => 'Petite fuite signalée',
        };
    }

    public function place(): string
    {
        return $this->location_hint ?: 'Position GPS';
    }

    public function ago(): string
    {
        return $this->created_at->copy()->locale('fr')->diffForHumans();
    }

    public static function formatDate($date): ?string
    {
        return $date?->copy()->timezone(self::DISPLAY_TZ)->format('d/m/Y à H:i');
    }
}
