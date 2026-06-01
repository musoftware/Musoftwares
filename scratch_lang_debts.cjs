const fs = require('fs');

const enKeys = {
    "debts_management": "Debts Management",
    "debts_and_loans": "Debts and Loans",
    "debts_description": "Track outstanding balances you owe to clients and what clients owe to you.",
    "total_owed_to_me": "Total Owed to Me",
    "total_i_owe": "Total I Owe",
    "search_clients": "Search clients...",
    "client_owes_you": "Client owes you",
    "you_owe_client": "You owe client",
    "settled": "Settled",
    "no_debt_records_found": "No debt records found.",
    "client_debt_details": "Client Debt Details",
    "current_debt_balance": "Current Debt Balance",
    "debt_transactions_history": "Debt Transactions History",
    "debt_given_to_client": "Debt given to client",
    "debt_received_from_client": "Debt received from client",
    "no_debt_transactions_found": "No debt transactions found."
};

const arKeys = {
    "debts_management": "إدارة الديون",
    "debts_and_loans": "الديون والقروض",
    "debts_description": "تتبع الأرصدة المستحقة التي تدين بها للعملاء وما يدين به العملاء لك.",
    "total_owed_to_me": "إجمالي ما لي",
    "total_i_owe": "إجمالي ما علي",
    "search_clients": "البحث عن عملاء...",
    "client_owes_you": "العميل يدين لك",
    "you_owe_client": "أنت تدين للعميل",
    "settled": "مسدد",
    "no_debt_records_found": "لا توجد سجلات ديون.",
    "client_debt_details": "تفاصيل دين العميل",
    "current_debt_balance": "رصيد الدين الحالي",
    "debt_transactions_history": "سجل حركات الديون",
    "debt_given_to_client": "دين أُعطي للعميل",
    "debt_received_from_client": "دين تم استلامه من العميل",
    "no_debt_transactions_found": "لا توجد حركات ديون."
};

function updateLangFile(filePath, newKeys) {
    let content = fs.readFileSync(filePath, 'utf8');
    let injectStr = '\n    // ERP Debts translations\n';
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

updateLangFile('lang/en/erp.php', enKeys);
updateLangFile('lang/ar/erp.php', arKeys);
