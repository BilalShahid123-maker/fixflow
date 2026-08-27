<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\Severity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvalRun extends Model
{
    protected $fillable = [
        'eval_case_id',
        'ai_run_id',
        'category_expected',
        'category_predicted',
        'severity_expected',
        'severity_predicted',
        'category_correct',
        'severity_correct',
        'critical_expected',
        'critical_correct',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'category_expected' => IssueCategory::class,
            'category_predicted' => IssueCategory::class,
            'severity_expected' => Severity::class,
            'severity_predicted' => Severity::class,
            'category_correct' => 'boolean',
            'severity_correct' => 'boolean',
            'critical_expected' => 'boolean',
            'critical_correct' => 'boolean',
            'confidence' => 'float',
        ];
    }

    public function evalCase(): BelongsTo
    {
        return $this->belongsTo(EvalCase::class, 'eval_case_id');
    }

    public function aiRun(): BelongsTo
    {
        return $this->belongsTo(AiRun::class);
    }
}
