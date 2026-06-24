<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\ChartOfAccountRepository;

class ChartOfAccountService
{
    protected $repository;

    public function __construct(ChartOfAccountRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
