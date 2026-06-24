<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\FiscalYearRepository;

class FiscalYearService
{
    protected $repository;

    public function __construct(FiscalYearRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
