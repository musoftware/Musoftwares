<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\BankAccount;

class BankAccountRepository
{
    protected $model;

    public function __construct(BankAccount $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
