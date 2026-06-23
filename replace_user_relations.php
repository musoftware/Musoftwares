<?php
$files = glob('Modules/ERP/Models/*.php');
foreach($files as $file) {
    if (in_array(basename($file), ['Tenant.php', 'TenantClient.php', 'BranchManager.php', 'BranchAuditLog.php', 'ProductStockLog.php'])) continue;
    $content = file_get_contents($file);
    $content = preg_replace('/belongsTo\(\\\\?App\\\\Models\\\\User::class,\s*\'(created_by|assigned_to|uploaded_by|reviewed_by|paid_by|started_by|stopped_by|requested_by|approved_by|invited_by|causer_id)\'\)/', 'belongsTo(\\\\Modules\\\\ERP\\\\Models\\\\TeamMember::class, \'$1\')', $content);
    $content = preg_replace('/belongsTo\(User::class,\s*\'(created_by|assigned_to|uploaded_by|reviewed_by|paid_by|started_by|stopped_by|requested_by|approved_by|invited_by|causer_id)\'\)/', 'belongsTo(\\\\Modules\\\\ERP\\\\Models\\\\TeamMember::class, \'$1\')', $content);
    file_put_contents($file, $content);
}
echo "Done replacing relations.\n";
