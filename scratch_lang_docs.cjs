const fs = require('fs');

const enKeys = {
    "document_storage": "Document Storage",
    "manage_your_secure_cloud_documents": "Manage your secure cloud documents and files.",
    "configure_storage": "Configure Storage",
    "upload_file": "Upload File",
    "storage_provider_warning_message": "You must configure an AWS S3 compatible storage provider before uploading files.",
    "file_name": "File Name",
    "category": "Category",
    "size": "Size",
    "uploaded_by": "Uploaded By",
    "date": "Date",
    "actions": "Actions",
    "no_files_found": "No files found.",
    "download": "Download",
    "delete": "Delete",
    "are_you_sure_you_want_to_delete_this_file": "Are you sure you want to delete this file?",
    "upload_document": "Upload Document",
    "upload_a_new_file_to_your_tenant_storage": "Upload a new file to your secure workspace storage.",
    "file_upload": "File Upload",
    "select_a_file_from_your_computer_and_assign_a_category": "Select a file from your computer and assign it a category.",
    "document_category": "Document Category",
    "select_category": "Select a category",
    "design_asset": "Design Asset",
    "invoice_pdf": "Invoice PDF"
};

const arKeys = {
    "document_storage": "تخزين المستندات",
    "manage_your_secure_cloud_documents": "إدارة مستنداتك وملفاتك السحابية الآمنة.",
    "configure_storage": "إعداد التخزين",
    "upload_file": "رفع ملف",
    "storage_provider_warning_message": "يجب عليك إعداد مزود تخزين متوافق مع AWS S3 قبل رفع الملفات.",
    "file_name": "اسم الملف",
    "category": "الفئة",
    "size": "الحجم",
    "uploaded_by": "تم الرفع بواسطة",
    "date": "التاريخ",
    "actions": "الإجراءات",
    "no_files_found": "لا توجد ملفات.",
    "download": "تحميل",
    "delete": "حذف",
    "are_you_sure_you_want_to_delete_this_file": "هل أنت متأكد من أنك تريد حذف هذا الملف؟",
    "upload_document": "رفع مستند",
    "upload_a_new_file_to_your_tenant_storage": "قم برفع ملف جديد إلى تخزين مساحة العمل الآمنة الخاصة بك.",
    "file_upload": "رفع الملف",
    "select_a_file_from_your_computer_and_assign_a_category": "اختر ملفاً من حاسوبك وعيّن له فئة.",
    "document_category": "فئة المستند",
    "select_category": "اختر فئة",
    "design_asset": "أصل تصميم",
    "invoice_pdf": "فاتورة PDF"
};

function updateLangFile(filePath, newKeys) {
    let content = fs.readFileSync(filePath, 'utf8');
    let injectStr = '\n    // ERP Document Storage translations\n';
    let added = false;
    for (let key in newKeys) {
        if (!content.includes('\'' + key + '\' =>')) {
            let val = newKeys[key].replace(/'/g, "\\'");
            injectStr += '    \'' + key + '\' => \'' + val + '\',\n';
            added = true;
        }
    }
    
    if (added) {
        content = content.replace(/];\s*$/, injectStr + '];\n');
        fs.writeFileSync(filePath, content);
        console.log('Updated', filePath);
    } else {
        console.log('No new keys for', filePath);
    }
}

updateLangFile('lang/en/general.php', enKeys);
updateLangFile('lang/ar/general.php', arKeys);
