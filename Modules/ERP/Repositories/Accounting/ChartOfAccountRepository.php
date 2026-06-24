<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\ChartOfAccount;

class ChartOfAccountRepository
{
    protected $model;

    public function __construct(ChartOfAccount $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
