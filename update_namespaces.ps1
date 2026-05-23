$base = "D:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\Modules\AutoSms\app"

function ReplaceInFile($file, $old, $new) {
    (Get-Content $file) -replace $old, $new | Set-Content $file
}

# Update Models
Get-ChildItem -Path "$base\Models" -Filter *.php -Recurse | ForEach-Object {
    ReplaceInFile $_.FullName 'namespace App\\Models;' 'namespace Modules\AutoSms\Models;'
}

# Update API Controllers
Get-ChildItem -Path "$base\Http\Controllers\Api" -Filter *.php -Recurse | ForEach-Object {
    ReplaceInFile $_.FullName 'namespace App\\Http\\Controllers\\Api;' 'namespace Modules\AutoSms\Http\Controllers\Api;'
    ReplaceInFile $_.FullName 'use App\\Models\\AutoSms' 'use Modules\AutoSms\Models\AutoSms'
    ReplaceInFile $_.FullName 'use App\\Services\\AutoSms' 'use Modules\AutoSms\Services\AutoSms'
    ReplaceInFile $_.FullName 'use App\\Models\\PaymentOrder;' 'use App\Models\PaymentOrder;'
}

# Update Client Controllers
Get-ChildItem -Path "$base\Http\Controllers" -Filter *.php | ForEach-Object {
    ReplaceInFile $_.FullName 'namespace App\\Http\\Controllers\\Client;' 'namespace Modules\AutoSms\Http\Controllers;'
    ReplaceInFile $_.FullName 'use App\\Models\\AutoSms' 'use Modules\AutoSms\Models\AutoSms'
}

# Update Services
Get-ChildItem -Path "$base\Services" -Filter *.php -Recurse | ForEach-Object {
    ReplaceInFile $_.FullName 'namespace App\\Services;' 'namespace Modules\AutoSms\Services;'
    ReplaceInFile $_.FullName 'use App\\Models\\AutoSms' 'use Modules\AutoSms\Models\AutoSms'
}

Write-Output "Namespaces updated successfully."
