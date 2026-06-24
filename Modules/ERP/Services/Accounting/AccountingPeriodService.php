<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\AccountingPeriodRepository;

class AccountingPeriodService
{
    protected $repository;

    public function __construct(AccountingPeriodRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
