const fs = require('fs');

let f1 = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/resources/js/Pages/Tools/WhatsAppOS/AccountsTab.tsx';
let c1 = fs.readFileSync(f1, 'utf8');
c1 = c1.replace('export default function AccountsTab({ runtimePort })', 'export default function AccountsTab({ runtimePort }: { runtimePort: number })');
fs.writeFileSync(f1, c1);

let f2 = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/resources/js/Pages/Tools/WhatsAppOS/CampaignsTab.tsx';
let c2 = fs.readFileSync(f2, 'utf8');
c2 = c2.replace('export default function CampaignsTab({ runtimePort })', 'export default function CampaignsTab({ runtimePort }: { runtimePort: number })');
fs.writeFileSync(f2, c2);

let f3 = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/resources/js/Pages/Tools/WhatsAppOS/CampaignWizard.tsx';
let c3 = fs.readFileSync(f3, 'utf8');
c3 = c3.replace('export default function CampaignWizard({ onClose })', 'export default function CampaignWizard({ onClose }: { onClose: () => void })');
fs.writeFileSync(f3, c3);

let f4 = 'd:/Projects/1AOrganized/PhpProject/MusoftwareBusiness/newmusoftware/resources/js/Pages/Tools/WhatsAppSenderRunner.tsx';
let c4 = fs.readFileSync(f4, 'utf8');
c4 = c4.replace('<ToolsPublicLayout tool={tool} subscription={subscription} pluginSlug={pluginSlug}>', '<ToolsPublicLayout title="WhatsApp Campaign OS" toolSlug={pluginSlug}>');
fs.writeFileSync(f4, c4);

console.log('Fixed types');
