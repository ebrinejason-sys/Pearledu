<?php

namespace App\Services\Assessment;

use App\Models\AssessmentPeriod;
use Illuminate\Validation\ValidationException;

class AssessmentPeriodWorkflow
{
    public const STATUSES = [
        'draft',
        'mark_entry_open',
        'mark_entry_closed',
        'review',
        'published',
        'locked',
    ];

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['mark_entry_open'],
        'mark_entry_open' => ['mark_entry_closed'],
        'mark_entry_closed' => ['review', 'mark_entry_open'],
        'review' => ['published', 'mark_entry_open'],
        'published' => ['locked', 'mark_entry_open'],
        'locked' => ['mark_entry_open'],
    ];

    public function canEnterMarks(AssessmentPeriod $period): bool
    {
        return in_array($period->status, ['draft', 'mark_entry_open'], true) && ! $period->is_locked;
    }

    public function isVisibleToParents(AssessmentPeriod $period): bool
    {
        return in_array($period->status, ['published', 'locked'], true);
    }

    public function advance(AssessmentPeriod $period, string $to): AssessmentPeriod
    {
        $from = $period->status ?: 'draft';
        $allowed = self::TRANSITIONS[$from] ?? [];
        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move this period from {$from} to {$to}.",
            ]);
        }

        $period->status = $to;
        $period->is_locked = $to === 'locked';
        if ($to === 'published' && ! $period->published_at) {
            $period->published_at = now();
        }
        if ($to === 'locked') {
            $period->locked_at = now();
            if (! $period->published_at) {
                $period->published_at = now();
            }
        }
        if ($to === 'mark_entry_open') {
            $period->is_locked = false;
            $period->locked_at = null;
        }
        $period->save();

        return $period;
    }

    /**
     * @return list<array{to:string,label:string}>
     */
    public function nextActions(AssessmentPeriod $period): array
    {
        $from = $period->status ?: 'draft';

        $labels = [
            'mark_entry_open' => $from === 'locked' || $from === 'published' || $from === 'review' || $from === 'mark_entry_closed'
                ? 'Reopen mark entry'
                : 'Open mark entry',
            'mark_entry_closed' => 'Close mark entry',
            'review' => 'Send to review',
            'published' => 'Publish to parents',
            'locked' => 'Lock results',
        ];

        $actions = [];
        foreach (self::TRANSITIONS[$from] ?? [] as $to) {
            $actions[] = ['to' => $to, 'label' => $labels[$to] ?? $to];
        }

        return $actions;
    }
}
