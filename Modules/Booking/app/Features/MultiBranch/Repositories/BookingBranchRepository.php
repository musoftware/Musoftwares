<?php

namespace Modules\Booking\app\Features\MultiBranch\Repositories;

use Modules\Booking\app\Features\MultiBranch\Models\BookingBranch;

class BookingBranchRepository
{
    public function all()
    {
        return BookingBranch::all();
    }

    public function find(int $id)
    {
        return BookingBranch::findOrFail($id);
    }

    public function create(array $data)
    {
        return BookingBranch::create($data);
    }

    public function update(BookingBranch $branch, array $data)
    {
        $branch->update($data);
        return $branch;
    }

    public function delete(BookingBranch $branch)
    {
        return $branch->delete();
    }
}
