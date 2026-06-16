<?php

namespace App\Services\CandidateEdits;

use App\Models\ItemCandidateEdit;

class CandidateEditRiskService
{
    public function classify(array $diffPaths): string
    {
        foreach ($diffPaths as $path) {
            if (in_array($path, [
                'item.slug',
                'item.status',
                'item.publisher_id',
                'item.user_id',
                'item.brand_id',
            ], true)) {
                return ItemCandidateEdit::RISK_HIGH;
            }
        }

        foreach ($diffPaths as $path) {
            if (in_array($path, [
                'item.price',
                'item.currency',
                'item.product_number',
                'relationships.categories',
                'item.image',
                'item.images',
            ], true)) {
                return ItemCandidateEdit::RISK_MEDIUM;
            }
        }

        return ItemCandidateEdit::RISK_LOW;
    }
}
