<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\FiscalYear;

class FiscalYearRepository
{
    protected $model;

    public function __construct(FiscalYear $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
