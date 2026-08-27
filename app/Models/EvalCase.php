<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvalCase extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'severity',
        'emergency',
        'split',
        'source_note',
    ];

    protected function casts(): array
    {
        return [
            'emergency' => 'boolean',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(EvalRun::class, 'eval_case_id');
    }
}
