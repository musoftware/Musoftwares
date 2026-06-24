<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\JournalEntry;

class JournalEntryRepository
{
    protected $model;

    public function __construct(JournalEntry $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
