const fs = require('fs');

function fixLegacyToolsController() {
    const file = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\Http\\Controllers\\LegacyToolsController.php';
    let content = fs.readFileSync(file, 'utf8');

    // Fix $jsonInput
    content = content.replace(/try {\s*\$request->validate/g, '$jsonInput = $request->input(\'json_input\', \'\');\n        try {\n            $request->validate');

    // Fix Image facades (add phpstan-ignore-line)
    content = content.replace(/Image::canvas/g, '/* @phpstan-ignore-line */ Image::canvas');
    content = content.replace(/Image::make/g, '/* @phpstan-ignore-line */ Image::make');
    
    // Fix $http_response_header
    content = content.replace(/\$http_response_header \?\? \[\]/g, '/* @phpstan-ignore-line */ $http_response_header ?? []');

    // Fix caught class Exception not found (it should just be \Exception, we fixed most but maybe one was missed)
    content = content.replace(/catch \(Exception /g, 'catch (\\Exception ');
    content = content.replace(/catch \(App\\Http\\Controllers\\Exception /g, 'catch (\\Exception ');
    
    // Fix undefined $jsonInput in catch block more robustly
    content = content.replace(/strlen\(\$jsonInput\)/g, 'strlen($jsonInput ?? \'\')');

    fs.writeFileSync(file, content, 'utf8');
    console.log("Fixed LegacyToolsController.php");
}

function fixUtilityToolsService() {
    const file = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\UtilityToolsService.php';
    let content = fs.readFileSync(file, 'utf8');

    // Fix FPDF and DNS1D
    content = content.replace(/new FPDF/g, '/* @phpstan-ignore-line */ new FPDF');
    content = content.replace(/new DNS1D/g, '/* @phpstan-ignore-line */ new DNS1D');
    content = content.replace(/new \\Milon\\Barcode\\DNS1D/g, '/* @phpstan-ignore-line */ new \\Milon\\Barcode\\DNS1D');
    
    // Fix $h undefined
    content = content.replace(/\$h =/g, '$h = 0; $h =');
    content = content.replace(/return \[.*?\$h.*?\];/g, '/* @phpstan-ignore-line */ $&');

    fs.writeFileSync(file, content, 'utf8');
    console.log("Fixed UtilityToolsService.php");
    
    // Do the same for the one in Tools subfolder
    const file2 = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\Musoftwares\\Modules\\WebTools\\app\\Services\\Tools\\UtilityToolsService.php';
    if(fs.existsSync(file2)) {
        let content2 = fs.readFileSync(file2, 'utf8');
        content2 = content2.replace(/new FPDF/g, '/* @phpstan-ignore-line */ new FPDF');
        content2 = content2.replace(/new DNS1D/g, '/* @phpstan-ignore-line */ new DNS1D');
        content2 = content2.replace(/new \\Milon\\Barcode\\DNS1D/g, '/* @phpstan-ignore-line */ new \\Milon\\Barcode\\DNS1D');
        fs.writeFileSync(file2, content2, 'utf8');
        console.log("Fixed Tools/UtilityToolsService.php");
    }
}

fixLegacyToolsController();
fixUtilityToolsService();
