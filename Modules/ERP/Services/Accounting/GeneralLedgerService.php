<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\GeneralLedgerRepository;

class GeneralLedgerService
{
    protected $repository;

    public function __construct(GeneralLedgerRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
