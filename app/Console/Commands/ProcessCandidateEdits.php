<?php

namespace App\Console\Commands;

use App\Models\ItemCandidateEdit;
use App\Services\CandidateEdits\CandidateEditDecisionService;
use Illuminate\Console\Command;

class ProcessCandidateEdits extends Command
{
    protected $signature = 'osw:process-candidate-edits {--id=}';

    protected $description = 'Process expired candidate edits and apply or resolve them based on voting thresholds.';

    public function handle(CandidateEditDecisionService $decisionService): int
    {
        $query = ItemCandidateEdit::query()
            ->where('status', ItemCandidateEdit::STATUS_OPEN)
            ->with(['item', 'baseRevision']);

        if ($id = $this->option('id')) {
            $query->whereKey($id);
        } else {
            $query->where('vote_window_ends_at', '<=', now());
        }

        $processed = 0;

        $query->orderBy('vote_window_ends_at')->chunkById(50, function ($candidateEdits) use ($decisionService, &$processed): void {
            foreach ($candidateEdits as $candidateEdit) {
                $previous = $candidateEdit->status;
                $newStatus = $decisionService->process($candidateEdit);

                $this->line(sprintf(
                    '%s: %s -> %s',
                    $candidateEdit->getKey(),
                    $previous,
                    $newStatus
                ));

                $processed++;
            }
        }, 'id');

        $this->info("Processed {$processed} candidate edit(s).");

        return self::SUCCESS;
    }
}
