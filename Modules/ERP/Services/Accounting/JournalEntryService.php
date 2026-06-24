<?php

namespace Modules\ERP\Services\Accounting;

use Modules\ERP\Repositories\Accounting\JournalEntryRepository;

class JournalEntryService
{
    protected $repository;

    public function __construct(JournalEntryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        return $this->repository->all();
    }
}
