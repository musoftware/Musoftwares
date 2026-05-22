<?php

$modelsDir = __DIR__ . '/app/Models';
if (!is_dir($modelsDir)) die("app/Models not found");

$categories = [
    'Finance' => [
        'Invoice', 'InvoiceCostAccrual', 'InvoiceCostLine', 'InvoiceItem', 'InvoiceItemTimer',
        'Transaction', 'CostTransaction', 'Currency', 'PaymentOrder', 'PaymentTransaction',
        'WalletTransfer', 'Earning', 'Coupon', 'CouponRedemption', 'Voucher', 'VoucherRedemption',
        'GoldPrice', 'GoldSaver', 'GoldWorldPrice', 'CurrenciesExchange', 'ConversionRate',
        'PointMoneyLog', 'RecurringCost', 'RecurringIncome', 'MusoftwarePayment', 'UserMoneyTransfer', 'LedgerCategory'
    ],
    'Marketplace' => [
        'Project', 'ProjectProposal', 'Contract', 'Bid', 'Task', 'KanbanTask', 'MerchantOrder',
        'Review', 'FreeDownload', 'Request'
    ],
    'CRM' => [
        'Lead', 'LeadSet', 'Campaign', 'CampaignContent', 'CampaignRecipient',
        'Sequence', 'SequenceState', 'SequenceStep', 'Note', 'NoteTag', 'UserNote',
        'PlatformLead', 'PlatformLeadSet', 'PlatformCampaign', 'PlatformCampaignRecipient',
        'PlatformSequence', 'PlatformSequenceState', 'PlatformSequenceStep'
    ],
    'Communication' => [
        'MessageActivity', 'MessageActivityRead', 'MessageFile', 'MessageImage', 'MessageMessage', 'MessageVoice',
        'UserThread', 'Community', 'CommunityMember', 'Comment', 'BlogArticle', 'CoworkerMessage', 'Notification'
    ],
    'Users' => [
        'Role', 'Permission', 'AdminPermission', 'KycDocument', 'AdminSettings', 'UserCredential',
        'UserActivity', 'DeviceToken', 'UserPaymentMethod', 'UserReferral', 'UserReferralRequestWithdraw',
        'PolicyAgreement', 'TermsAgreement', 'MusoftwareClient', 'Action', 'Favorite', 'Like'
    ],
    'Tools' => [
        'PremiumTool', 'PremiumToolUsage', 'SerialDevice', 'SerialSoftware', 'SoftwareProgram',
        'SoftwareProgramTranslation', 'PcSerial', 'SerialUserDevice', 'SoftwareCustomValue',
        'KuCoinApiKey', 'StrategyVersion', 'DeepLink', 'QrCode', 'ReverseOtpCallback', 'ReverseOtpVerification'
    ],
    'Operations' => [
        'EmployeeAttendance', 'CoWorker', 'Todo', 'TodoSwimlane', 'TodoAudio', 'TodoImage',
        'RecurringSalary', 'EmployeeRecurringTodo', 'EmployeeRecurringTodoTransaction',
        'RecurringBusyTime', 'CoTechGroup', 'CoTechGroupTag', 'CoTechTag', 'CharityCounter', 'CharityTransaction',
        'SavedCard', 'SavedReply', 'PointSupport', 'Business', 'Assignment', 'File', 'FileFolder', 'OrderDeliveryFile'
    ]
];

// Invert categories for easy lookup
$modelToCategory = [];
foreach ($categories as $category => $models) {
    if (!is_dir($modelsDir . '/' . $category)) {
        mkdir($modelsDir . '/' . $category, 0755, true);
    }
    foreach ($models as $model) {
        $modelToCategory[$model] = $category;
    }
}

$movedModels = []; // Format: ['ModelName' => 'Category/ModelName']

// 1. Move files
$iterator = new DirectoryIterator($modelsDir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $basename = $file->getFilename();
        $modelName = basename($basename, '.php');

        if ($modelName === 'User' || $modelName === 'TenantModel') continue;

        $category = $modelToCategory[$modelName] ?? 'Misc';
        
        if (!is_dir($modelsDir . '/' . $category)) {
            mkdir($modelsDir . '/' . $category, 0755, true);
        }

        $dest = $modelsDir . '/' . $category . '/' . $basename;
        rename($file->getRealPath(), $dest);
        
        $movedModels[$modelName] = $category;
        echo "Moved $modelName to $category\n";
        
        // Update namespace in the file itself
        $content = file_get_contents($dest);
        $content = preg_replace('/namespace\s+App\\\Models;/', "namespace App\\Models\\$category;", $content);
        file_put_contents($dest, $content);
    }
}

// 2. Global replace
$directoriesToScan = [
    __DIR__ . '/app',
    __DIR__ . '/Modules',
    __DIR__ . '/routes',
    __DIR__ . '/resources',
];

function scanAndReplace($dir, $movedModels) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'jsx', 'tsx'])) {
            $path = $file->getRealPath();
            $content = file_get_contents($path);
            $newContent = $content;

            foreach ($movedModels as $modelName => $category) {
                // PHP namespace usages
                $oldNamespace = "App\\Models\\$modelName";
                $newNamespace = "App\\Models\\$category\\$modelName";
                
                // Regular replace for fully qualified
                $newContent = str_replace($oldNamespace, $newNamespace, $newContent);
                
                // Also check for string usage like 'App\Models\ModelName'
                $oldString1 = "'App\\\\Models\\\\$modelName'";
                $newString1 = "'App\\\\Models\\\\$category\\\\$modelName'";
                $newContent = str_replace($oldString1, $newString1, $newContent);

                $oldString2 = '"App\\\\Models\\\\' . $modelName . '"';
                $newString2 = '"App\\\\Models\\\\' . $category . '\\\\' . $modelName . '"';
                $newContent = str_replace($oldString2, $newString2, $newContent);
            }
            
            if ($newContent !== $content) {
                file_put_contents($path, $newContent);
                echo "Updated references in " . $file->getFilename() . "\n";
            }
        }
    }
}

foreach ($directoriesToScan as $dir) {
    if (is_dir($dir)) {
        scanAndReplace($dir, $movedModels);
    }
}

echo "Done.\n";
