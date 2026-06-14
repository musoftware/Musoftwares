const fs = require('fs');

function fixFile(file, replacer) {
    const fullPath = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\' + file;
    if (fs.existsSync(fullPath)) {
        let content = fs.readFileSync(fullPath, 'utf8');
        content = replacer(content);
        fs.writeFileSync(fullPath, content, 'utf8');
        console.log("Fixed: " + file);
    }
}

// 1. GoldIndicator
fixFile('resources/js/Pages/WebTools/Financial/GoldIndicator.tsx', content => {
    // useState([
    return content.replace(/useState\(\[\]\)/g, 'useState<any[]>([])');
});

// 2. PayGuest
fixFile('resources/js/Pages/WebTools/Financial/PayGuest.tsx', content => {
    // onValueChange={(val: string | null) => setCountry(val)}
    // The error says: Argument of type 'string | null' is not assignable to parameter of type 'string'.
    // That means `setCountry(val)` where `setCountry` is `Dispatch<SetStateAction<string>>` and `val` is `string | null`.
    // Let's replace setCountry(val) with setCountry(val || '')
    return content.replace(/set([A-Za-z]+)\((val|value)\)/g, 'set$1($2 || "")');
});

// 3. WithdrawInstapay
fixFile('resources/js/Pages/WebTools/Financial/WithdrawInstapay.tsx', content => {
    return content.replace(/set([A-Za-z]+)\((val|value)\)/g, 'set$1($2 || "")');
});

// 4. PayoutUsd
fixFile('resources/js/Pages/WebTools/Financial/PayoutUsd.tsx', content => {
    // const [gateways, setGateways] = useState([
    return content.replace(/useState\(\[\s*\{/g, 'useState<any[]>([{');
});

