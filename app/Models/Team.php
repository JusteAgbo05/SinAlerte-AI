<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_available' => 'boolean',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** Initiales affichées dans la pastille (ex. "Aqua Maintenance · Équipe 2" => "AM"). */
    public function initials(): string
    {
        $words = preg_split('/[\s·\-]+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(implode('', array_map(
            fn (string $w) => mb_substr($w, 0, 1),
            array_slice($words, 0, 2),
        )));
    }
}
