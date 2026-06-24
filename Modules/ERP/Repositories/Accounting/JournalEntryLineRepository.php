<?php

namespace Modules\ERP\Repositories\Accounting;

use Modules\ERP\Models\Accounting\JournalEntryLine;

class JournalEntryLineRepository
{
    protected $model;

    public function __construct(JournalEntryLine $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }
}
