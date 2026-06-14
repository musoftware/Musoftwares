const fs = require('fs');
const path = require('path');

const OLD_VIEWS_DIR = 'D:\\Projects\\1AOrganized\\PhpProject\\MusoftwareBusiness\\musoftwares.com\\resources\\views\\tools';
const NEW_PAGES_DIR = path.join(__dirname, '../../resources/js/Pages/WebTools/Legacy');

if (!fs.existsSync(NEW_PAGES_DIR)) {
    fs.mkdirSync(NEW_PAGES_DIR, { recursive: true });
}

function toCamelCase(str) {
    return str.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); });
}

function toPascalCase(str) {
    return str.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join('');
}

function processDirectory(directory) {
    const files = fs.readdirSync(directory);
    for (const file of files) {
        const fullPath = path.join(directory, file);
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory()) {
            processDirectory(fullPath);
        } else if (file.endsWith('.blade.php')) {
            const relativePath = path.relative(OLD_VIEWS_DIR, fullPath);
            const category = path.dirname(relativePath);
            const toolName = path.basename(file, '.blade.php');
            
            // Generate React Component
            const reactComponentName = toPascalCase(toolName);
            const reactFileContent = `import React, { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import axios from 'axios';

export default function ${reactComponentName}() {
    const [result, setResult] = useState<any>(null);
    const [loading, setLoading] = useState(false);
    const [text, setText] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        try {
            const response = await axios.post('/tools/legacy/${toolName}', { text });
            setResult(response.data.result);
        } catch (error) {
            console.error("Error processing tool", error);
        }
        setLoading(false);
    };

    return (
        <AppLayout title="${reactComponentName}">
            <div className="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>${reactComponentName} Tool</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="text">Input Text / Data</Label>
                                <Textarea 
                                    id="text" 
                                    value={text} 
                                    onChange={(e) => setText(e.target.value)} 
                                    placeholder="Enter your input here..."
                                    rows={5}
                                />
                            </div>
                            <Button type="submit" disabled={loading}>
                                {loading ? 'Processing...' : 'Process'}
                            </Button>
                        </form>
                        
                        {result && (
                            <div className="mt-8 space-y-2">
                                <Label>Result</Label>
                                <div className="p-4 bg-gray-50 dark:bg-gray-900 rounded-md border whitespace-pre-wrap">
                                    {typeof result === 'object' ? JSON.stringify(result, null, 2) : result}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
`;
            
            const categoryDir = path.join(NEW_PAGES_DIR, category);
            if (!fs.existsSync(categoryDir)) {
                fs.mkdirSync(categoryDir, { recursive: true });
            }
            
            fs.writeFileSync(path.join(categoryDir, `${reactComponentName}.tsx`), reactFileContent);
        }
    }
}

console.log("Starting auto-generation of 467 tools...");
processDirectory(OLD_VIEWS_DIR);
console.log("Completed generating React components.");
