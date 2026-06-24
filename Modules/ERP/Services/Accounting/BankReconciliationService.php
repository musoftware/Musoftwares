<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\BankReconciliationRepository;

class BankReconciliationService
{
    protected $repository;

    public function __construct(BankReconciliationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
