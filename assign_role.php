<?php
$user = App\Models\User::where('email', 'mahmoudmn810@gmail.com')->first();
if ($user) {
    $user->assignRole('Admin');
    echo "Role assigned successfully.\n";
} else {
    echo "User not found.\n";
}
