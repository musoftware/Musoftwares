<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\GeneralLedger;

class GeneralLedgerRepository
{
    protected $model;

    public function __construct(GeneralLedger $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
