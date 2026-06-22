const fs = require('fs');
const parser = require('@babel/parser');
const traverse = require('@babel/traverse').default;
const generate = require('@babel/generator').default;

const files = [
'resources/js/Pages/Admin/BlogArticles/Index.tsx',
'resources/js/Pages/Admin/Business/RecurringIncome/Index.tsx',
'resources/js/Pages/Admin/Business/RecurringSalaries/Index.tsx',
'resources/js/Pages/Admin/CharityCounter/Index.tsx',
'resources/js/Pages/Admin/Coupons/Index.tsx',
'resources/js/Pages/Admin/ERP/Index.tsx',
'resources/js/Pages/Admin/Finance/Index.tsx',
'resources/js/Pages/Admin/Finance/PaymentLinks/Index.tsx',
'resources/js/Pages/Admin/Freelance/Contracts/Index.tsx',
'resources/js/Pages/Admin/Freelance/Jobs/Index.tsx',
'resources/js/Pages/Admin/Freelance/Profiles/Index.tsx',
'resources/js/Pages/Admin/Freelance/Proposals/Index.tsx',
'resources/js/Pages/Admin/Freelance/Skills/Index.tsx',
'resources/js/Pages/Admin/PaymentGateway/Index.tsx',
'resources/js/Pages/Admin/PointPackages/Index.tsx',
'resources/js/Pages/Admin/Points/Index.tsx',
'resources/js/Pages/Admin/Projects/Index.tsx',
'resources/js/Pages/Admin/Reports/Index.tsx',
'resources/js/Pages/Admin/SerialSoftwares/Index.tsx',
'resources/js/Pages/Admin/Settings/Index.tsx',
'resources/js/Pages/Admin/Vouchers/Index.tsx',
'resources/js/Pages/CRM/Campaigns/Index.tsx',
'resources/js/Pages/CRM/Leads/Index.tsx',
'resources/js/Pages/CRM/Reports/Index.tsx',
'resources/js/Pages/CRM/Sequences/Index.tsx',
'resources/js/Pages/ERP/Inventory/Index.tsx',
'resources/js/Pages/ERP/Payroll/Index.tsx',
'resources/js/Pages/ERP/Tasks/Index.tsx',
'resources/js/Pages/Fbmb/Index.tsx',
'resources/js/Pages/GoldSavers/Wallets/Index.tsx'
];

for (const file of files) {
    if (!fs.existsSync(file)) continue;
    let code = fs.readFileSync(file, 'utf-8');
    
    // Quick skip if file doesn't contain justify-between
    if (!code.includes('justify-between')) continue;

    try {
        const ast = parser.parse(code, {
            sourceType: 'module',
            plugins: ['jsx', 'typescript'],
        });

        let modified = false;

        traverse(ast, {
            JSXAttribute(path) {
                if (path.node.name.name === 'className' && path.node.value && path.node.value.type === 'StringLiteral') {
                    if (path.node.value.value.includes('justify-between')) {
                        // Replace justify-between with justify-end gap-4
                        path.node.value.value = path.node.value.value.replace(/\bjustify-between\b/g, 'justify-end gap-4');
                        modified = true;
                        
                        // Now let's try to add flex-1 or me-auto to the first child element
                        const openingElement = path.parent;
                        const jsxElement = path.parentPath.parent;
                        
                        if (jsxElement.type === 'JSXElement' && jsxElement.children && jsxElement.children.length > 0) {
                            // Find first valid JSXElement child
                            for (const child of jsxElement.children) {
                                if (child.type === 'JSXElement') {
                                    // Add className="me-auto" to this child
                                    let hasClassName = false;
                                    for (const attr of child.openingElement.attributes) {
                                        if (attr.type === 'JSXAttribute' && attr.name.name === 'className') {
                                            if (attr.value && attr.value.type === 'StringLiteral') {
                                                if (!attr.value.value.includes('me-auto') && !attr.value.value.includes('flex-1') && !attr.value.value.includes('ms-auto')) {
                                                    attr.value.value = 'me-auto ' + attr.value.value;
                                                }
                                                hasClassName = true;
                                            }
                                        }
                                    }
                                    if (!hasClassName) {
                                        child.openingElement.attributes.push({
                                            type: 'JSXAttribute',
                                            name: { type: 'JSXIdentifier', name: 'className' },
                                            value: { type: 'StringLiteral', value: 'me-auto' }
                                        });
                                    }
                                    break; // Only first child
                                }
                            }
                        }
                    }
                }
            }
        });

        if (modified) {
            const output = generate(ast, { retainLines: true }, code);
            fs.writeFileSync(file, output.code, 'utf-8');
            console.log(`Fixed ${file}`);
        }
    } catch (e) {
        console.error(`Error processing ${file}:`, e.message);
    }
}
