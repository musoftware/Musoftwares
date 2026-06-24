<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\BankReconciliation;

class BankReconciliationRepository
{
    protected $model;

    public function __construct(BankReconciliation $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
