<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['as_of_date', 'bank_statement_path', 'status', 'discrepancy_count', 'pending_count', 'issues', 'run_by_user_id'])]
class FinancialReconciliationRun extends Model
{
    protected function casts(): array
    {
        return ['as_of_date' => 'date', 'issues' => 'array', 'discrepancy_count' => 'integer', 'pending_count' => 'integer'];
    }
}
