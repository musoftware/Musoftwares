<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\JournalEntryLineRepository;

class JournalEntryLineService
{
    protected $repository;

    public function __construct(JournalEntryLineRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
