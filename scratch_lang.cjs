const fs = require('fs');

const enKeys = {
    "active": "Active",
    "pending": "Pending",
    "suspended": "Suspended",
    "rejected": "Rejected",
    "all_services": "All Services",
    "marketplace_services": "Marketplace Services",
    "all_services_marketplace": "All Services — Marketplace",
    "manage_approve_moderate_services": "Manage, approve, and moderate marketplace services.",
    "total": "Total",
    "featured": "Featured",
    "search_title_seller": "Search by title or seller...",
    "all_statuses": "All Statuses",
    "all_categories": "All Categories",
    "clear": "Clear",
    "category": "Category",
    "packages_price": "Packages / Price",
    "orders": "Orders",
    "date": "Date",
    "unknown": "Unknown",
    "uncategorized": "Uncategorized",
    "no_packages": "No packages",
    "pkg": "pkg",
    "pkgs": "pkgs",
    "view_service": "View Service",
    "edit_service": "Edit Service",
    "unfeature": "Unfeature",
    "feature": "Feature",
    "restore": "Restore",
    "approve": "Approve",
    "reject": "Reject",
    "suspend": "Suspend",
    "no_services_found": "No services found",
    "clear_filters": "Clear filters",
    "showing": "Showing",
    "of": "of",
    "services_count": "services",
    "approve_service": "Approve Service",
    "approve_service_desc": "\":title\" will become publicly visible on the marketplace immediately.",
    "reject_service": "Reject Service",
    "reject_service_desc": "\":title\" will be marked as rejected. The seller will not be able to resubmit without changes.",
    "suspend_service": "Suspend Service",
    "suspend_service_desc": "\":title\" will be hidden from the marketplace. Existing orders won't be affected.",
    "toggle_featured": "Toggle Featured",
    "toggle_featured_desc": "Update the featured status for \":title\".",
    "delete_service": "Delete Service",
    "delete_service_desc": "This will permanently delete \":title\" and all its packages. This action cannot be undone.",
    "delete_permanently": "Delete Permanently",
    "confirm": "Confirm"
};

const arKeys = {
    "active": "نشط",
    "pending": "قيد الانتظار",
    "suspended": "موقوف",
    "rejected": "مرفوض",
    "all_services": "جميع الخدمات",
    "marketplace_services": "خدمات السوق",
    "all_services_marketplace": "جميع الخدمات — السوق",
    "manage_approve_moderate_services": "إدارة واعتماد ومراقبة خدمات السوق.",
    "total": "الإجمالي",
    "featured": "مميز",
    "search_title_seller": "البحث بالعنوان أو البائع...",
    "all_statuses": "جميع الحالات",
    "all_categories": "جميع الفئات",
    "clear": "مسح",
    "category": "الفئة",
    "packages_price": "الباقات / السعر",
    "orders": "الطلبات",
    "date": "التاريخ",
    "unknown": "غير معروف",
    "uncategorized": "غير مصنف",
    "no_packages": "لا توجد باقات",
    "pkg": "باقة",
    "pkgs": "باقات",
    "view_service": "عرض الخدمة",
    "edit_service": "تعديل الخدمة",
    "unfeature": "إزالة التمييز",
    "feature": "تمييز",
    "restore": "استعادة",
    "approve": "اعتماد",
    "reject": "رفض",
    "suspend": "إيقاف",
    "no_services_found": "لا توجد خدمات",
    "clear_filters": "مسح الفلاتر",
    "showing": "عرض",
    "of": "من",
    "services_count": "خدمات",
    "approve_service": "اعتماد الخدمة",
    "approve_service_desc": "الخدمة \":title\" ستصبح مرئية للجميع في السوق فوراً.",
    "reject_service": "رفض الخدمة",
    "reject_service_desc": "الخدمة \":title\" سيتم رفضها. لن يتمكن البائع من إعادة التقديم بدون تعديلات.",
    "suspend_service": "إيقاف الخدمة",
    "suspend_service_desc": "الخدمة \":title\" سيتم إخفاؤها من السوق. لن تتأثر الطلبات الحالية.",
    "toggle_featured": "تبديل التمييز",
    "toggle_featured_desc": "تحديث حالة التمييز للخدمة \":title\".",
    "delete_service": "حذف الخدمة",
    "delete_service_desc": "سيتم حذف الخدمة \":title\" وجميع باقاتها نهائياً. لا يمكن التراجع عن هذا الإجراء.",
    "delete_permanently": "حذف نهائي",
    "confirm": "تأكيد"
};

function updateLangFile(filePath, newKeys) {
    let content = fs.readFileSync(filePath, 'utf8');
    let injectStr = '\n    // Marketplace Services translations\n';
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

updateLangFile('lang/en/admin.php', enKeys);
updateLangFile('lang/ar/admin.php', arKeys);
