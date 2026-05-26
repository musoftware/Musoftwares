<?php 
require __DIR__.'/vendor/autoload.php'; 
$app = require_once __DIR__.'/bootstrap/app.php'; 
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 
$users = App\Models\User::where('name', 'like', '%Hossam%')->take(1)->get();
$request = Illuminate\Http\Request::create('/admin/users', 'GET');
foreach($users as $user) {
    echo json_encode((new App\Http\Resources\UserResource($user))->toArray($request));
}
