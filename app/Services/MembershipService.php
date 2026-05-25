<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipProgram;
use App\Models\MembershipUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MembershipService
{
    public function createMembership(array $data): Membership
    {
        return DB::transaction(function () use ($data) {
            $createData = [
                'name'             => $data['name'],
                'description'      => $data['description'] ?? null,
                'amount'           => $data['amount'],
                'currency'         => $data['currency'],
            ];

            if (Schema::hasColumn('memberships', 'is_active')) {
                $createData['is_active'] = $data['is_active'] ?? false;
            }

            if (Schema::hasColumn('memberships', 'color_hue_degree')) {
                $createData['color_hue_degree'] = $data['color_hue_degree'] ?? 200;
            }

            $membership = Membership::create($createData);

            if (!empty($data['software_programs'])) {
                foreach ($data['software_programs'] as $programId) {
                    MembershipProgram::create([
                        'membership_id'       => $membership->id,
                        'software_program_id' => $programId,
                    ]);
                }
            }

            return $membership;
        });
    }

    public function updateMembership(Membership $membership, array $data): Membership
    {
        return DB::transaction(function () use ($membership, $data) {
            $updateData = [
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'amount'      => $data['amount'],
                'currency'    => $data['currency'],
            ];

            if (Schema::hasColumn('memberships', 'is_active') && isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            if (Schema::hasColumn('memberships', 'color_hue_degree') && isset($data['color_hue_degree'])) {
                $updateData['color_hue_degree'] = $data['color_hue_degree'];
            }

            $membership->update($updateData);

            if (isset($data['software_programs'])) {
                $membership->programs()->delete();
                foreach ($data['software_programs'] as $programId) {
                    MembershipProgram::create([
                        'membership_id'       => $membership->id,
                        'software_program_id' => $programId,
                    ]);
                }
            }

            return $membership;
        });
    }

    public function deleteMembership(Membership $membership): void
    {
        $activeUsers = $membership->users()->where('expires_at', '>', now())->count();

        if ($activeUsers > 0) {
            throw new \Exception('Cannot delete membership with active users.');
        }

        $membership->delete();
    }

    public function assignUser(User $user, Membership $membership, int $durationDays): void
    {
        $existingMember = MembershipUser::where('membership_id', $membership->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            if (strtotime($existingMember->expires_at) < time()) {
                $existingMember->expires_at = date('Y-m-d', strtotime(' +' . $durationDays . ' days'));
            } else {
                $existingMember->expires_at = date('Y-m-d', strtotime($existingMember->expires_at . ' +' . $durationDays . ' days'));
            }
            $existingMember->save();
        } else {
            $membership->users()->create([
                'user_id'    => $user->id,
                'currency'   => $user->currency,
                'amount'     => $membership->amount,
                'serial'     => sha1(uniqid() . uniqid()) . '-' . uniqid(),
                'expires_at' => date('Y-m-d', strtotime(' +' . $durationDays . ' days')),
            ]);
        }
    }
}
