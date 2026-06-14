const fs = require('fs');
const path = require('path');

const keysToAdd = [
"common.currency_options_not_configured",
"common.email",
"common.log_in_to_continue_payment",
"common.login_url",
"common.payment_link_session_expired",
"common.your_musoftwares_account",
"general.about",
"general.become_a_seller",
"general.blog",
"general.buying_on_musoftware",
"general.careers",
"general.categories",
"general.community",
"general.community_standards",
"general.digital_marketing",
"general.explore",
"general.forum",
"general.graphics_design",
"general.help_support",
"general.investor_relations",
"general.join",
"general.more_from_musoftware",
"general.orders",
"general.podcast",
"general.profile",
"general.programming_tech",
"general.selling_on_musoftware",
"general.support",
"general.trust_safety",
"general.video_animation",
"general.writing_translation",
"payment.equiv",
"general.amount",
"payment.amount_egp",
"general.password"
];

function updateLangFile(locale, domain, keyStr) {
    const filePath = path.join(__dirname, 'lang', locale, `${domain}.php`);
    if (!fs.existsSync(filePath)) {
        fs.writeFileSync(filePath, `<?php\n\nreturn [\n    '${keyStr}' => '${keyStr.replace(/_/g, ' ')}'\n];\n`);
        return;
    }
    let content = fs.readFileSync(filePath, 'utf8');
    
    // Check if key already exists
    if (content.includes(`'${keyStr}'`)) return;
    
    // Find the last ]
    const lastBracketIdx = content.lastIndexOf(']');
    if (lastBracketIdx !== -1) {
        const before = content.substring(0, lastBracketIdx).trim();
        let appended = before;
        if (!appended.endsWith(',') && !appended.endsWith('[')) {
            appended += ',';
        }
        appended += `\n    '${keyStr}' => '${keyStr.replace(/_/g, ' ')}'\n];\n`;
        fs.writeFileSync(filePath, appended);
    }
}

keysToAdd.forEach(key => {
    const parts = key.split('.');
    const domain = parts[0];
    const keyStr = parts.slice(1).join('.');
    updateLangFile('en', domain, keyStr);
    updateLangFile('ar', domain, keyStr);
});
