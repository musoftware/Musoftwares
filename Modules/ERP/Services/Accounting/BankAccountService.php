<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\BankAccountRepository;

class BankAccountService
{
    protected $repository;

    public function __construct(BankAccountRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
