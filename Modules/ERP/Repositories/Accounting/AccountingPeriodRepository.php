<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\AccountingPeriod;

class AccountingPeriodRepository
{
    protected $model;

    public function __construct(AccountingPeriod $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
