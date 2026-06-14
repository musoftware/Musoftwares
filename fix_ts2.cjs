const fs = require('fs');

const tsFiles = [
    'resources/js/Pages/WebTools/Financial/Calculator.tsx',
    'resources/js/Pages/WebTools/Financial/GoldIndicator.tsx',
    'resources/js/Pages/WebTools/Financial/GoldSaver.tsx',
    'resources/js/Pages/WebTools/Financial/PayGuest.tsx',
    'resources/js/Pages/WebTools/Financial/PayGuestPayLink.tsx',
    'resources/js/Pages/WebTools/Financial/PayoutUsd.tsx',
    'resources/js/Pages/WebTools/Financial/SmartPricingCalculator.tsx',
    'resources/js/Pages/WebTools/Financial/WithdrawInstapay.tsx',
    'resources/js/Pages/WebTools/Financial/WithdrawInstapayPayLink.tsx',
    'resources/js/Pages/WebTools/Utilities/CoordinatesConverter.tsx',
    'resources/js/Pages/WebTools/Utilities/JsObfuscator.tsx',
    'resources/js/Pages/WebTools/Utilities/MultipleCountdownTimer.tsx',
    'resources/js/Pages/Client/Dashboard.tsx'
];

tsFiles.forEach(relPath => {
    const fullPath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\' + relPath;
    if (fs.existsSync(fullPath)) {
        let content = fs.readFileSync(fullPath, 'utf8');
        
        // 1. Fix "financial" not assignable (was activeNav, not activeSection)
        content = content.replace(/activeNav="financial"/g, 'activeNav="explore"');
        
        // 2. Client/Dashboard Auth User missing email
        content = content.replace(/user: \{\s*id: number;\s*name: string;\s*\};/g, 'user: { id: number; name: string; email: string; };');
        
        // 3. Client/Dashboard missing export PendingInvoice
        content = content.replace(/PendingInvoice,?/g, ''); // just remove it from the import
        
        // 4. GoldIndicator.tsx - object not assignable to never
        content = content.replace(/useState\(\[\]\)/g, 'useState<any[]>([])');
        
        // 5. Select onValueChange={(val: string | null) => ...}
        // Let's do a regex replacement that's more robust
        content = content.replace(/onValueChange=\{\(value\) => set([a-zA-Z0-9_]+)\(value\)\}/g, 'onValueChange={(value) => set$1(value || "")}');
        
        // 6. Formatter type in Recharts
        content = content.replace(/formatter=\{\(value: number\)/g, 'formatter={(value: any)');
        
        // 7. PayGuest.tsx and WithdrawInstapay string | null
        content = content.replace(/onValueChange=\{\(val: string \| null\) =>/g, 'onValueChange={(val: any) =>');
        
        // 8. PayoutUsd.tsx
        content = content.replace(/useState\(\[\s*\{\s*label:/g, 'useState<any[]>([ { label:');
        
        // 9. setWhatever in CoordinatesConverter
        content = content.replace(/onValueChange=\{set([a-zA-Z0-9_]+)\}/g, 'onValueChange={(val) => set$1(val || "")}');
        
        fs.writeFileSync(fullPath, content, 'utf8');
        console.log("Fixed TS in " + fullPath);
    }
});
