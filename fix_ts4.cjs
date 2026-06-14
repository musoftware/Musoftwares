const fs = require('fs');

function replaceInFile(filePath, searchRegex, replacement) {
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(searchRegex, replacement);
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Replaced in ${filePath}`);
    } else {
        console.log(`Not found: ${filePath}`);
    }
}

const baseDir = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\';

// 1. Dashboard.tsx issues
let dashboardPath = baseDir + 'resources/js/Pages/Client/Dashboard.tsx';
if (fs.existsSync(dashboardPath)) {
    let dashContent = fs.readFileSync(dashboardPath, 'utf8');
    dashContent = dashContent.replace(/import sBanner from '\.\/Dashboard\/Components\/sBanner';/g, "import PendingInvoicesBanner from './Dashboard/Components/PendingInvoicesBanner';");
    dashContent = dashContent.replace(/<sBanner/g, "<PendingInvoicesBanner");
    dashContent = dashContent.replace(/const \{ auth \} = usePage<\{ auth: \{ user: \{ id: number; name: string \} \} \}>\(\)\.props;/g, "const { auth } = usePage<{ auth: { user: { id: number; name: string; email: string } } }>().props;");
    fs.writeFileSync(dashboardPath, dashContent, 'utf8');
}

// 2. GoldIndicator.tsx never issue
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/GoldIndicator.tsx', /const \[chartData, setChartData\] = useState\(\[\]\);/g, "const [chartData, setChartData] = useState<any[]>([]);");

// 3. PayoutUsd.tsx never issue
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/PayoutUsd.tsx', /const \[gateways, setGateways\] = useState\(\[\s*\{\s*label:/g, "const [gateways, setGateways] = useState<any[]>([{ label:");

// 4. PayGuest.tsx string|null issue
// In PayGuest.tsx we have things like onValueChange={(val: string | null) => setCountry(val)}
// Let's just catch all setCountry(val) or setGateway(val) inside onValueChange
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/PayGuest.tsx', /setCountry\(val\)/g, "setCountry(val || '')");
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/PayGuest.tsx', /setGateway\(val\)/g, "setGateway(val || '')");
// Actually, wait, it says "Argument of type 'string | null' is not assignable to parameter of type 'string'."
// Meaning we need to replace `setCountry(val)` with `setCountry(val as string)` or similar.
// And `val` itself might be used elsewhere. Let's just do an empty string fallback `|| ''`.

// 5. WithdrawInstapay.tsx string|null issue
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/WithdrawInstapay.tsx', /setMethod\(val\)/g, "setMethod(val || '')");
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/WithdrawInstapay.tsx', /setProvider\(val\)/g, "setProvider(val || '')");

// Let's also run a generic one for string|null in both files
const genericReplacer = (content) => {
    return content.replace(/set([A-Za-z]+)\((val|value)\)/g, "set$1($2 || '')");
}
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/PayGuest.tsx', /set([A-Za-z]+)\(val\)/g, "set$1(val || '')");
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/WithdrawInstapay.tsx', /set([A-Za-z]+)\(val\)/g, "set$1(val || '')");
replaceInFile(baseDir + 'resources/js/Pages/WebTools/Financial/WithdrawInstapay.tsx', /set([A-Za-z]+)\(value\)/g, "set$1(value || '')");

console.log("TS Fixes Pass 4 Applied");
