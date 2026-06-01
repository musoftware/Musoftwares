const fs = require('fs');
let content = fs.readFileSync('resources/js/Pages/Admin/Marketplace/All.tsx', 'utf8');

if (!content.includes('import { __ }')) {
    content = content.replace('import { Head, router } from \'@inertiajs/react\';', 'import { Head, router } from \'@inertiajs/react\';\nimport { __ } from \'@/lib/i18n\';');
}

// Extract hardcoded strings with simple replacements to ensure correctness.

const replacements = [
    [/<Badge className="bg-green-50 text-green-700 border border-green-200 font-medium capitalize">\s*<CheckCircle2 className="h-3 w-3 mr-1" \/>\s*Active\s*<\/Badge>/g, '<Badge className="bg-green-50 text-green-700 border border-green-200 font-medium capitalize\"><CheckCircle2 className="h-3 w-3 mr-1" />{__(\'admin.active\')}</Badge>'],
    [/<Badge className="bg-amber-50 text-amber-700 border border-amber-200 font-medium capitalize">\s*<Clock className="h-3 w-3 mr-1" \/>\s*Pending\s*<\/Badge>/g, '<Badge className="bg-amber-50 text-amber-700 border border-amber-200 font-medium capitalize\"><Clock className="h-3 w-3 mr-1" />{__(\'admin.pending\')}</Badge>'],
    [/<Badge className="bg-red-50 text-red-700 border border-red-200 font-medium capitalize">\s*<Ban className="h-3 w-3 mr-1" \/>\s*Suspended\s*<\/Badge>/g, '<Badge className="bg-red-50 text-red-700 border border-red-200 font-medium capitalize\"><Ban className="h-3 w-3 mr-1" />{__(\'admin.suspended\')}</Badge>'],
    [/<Badge className="bg-slate-100 text-slate-600 border border-slate-200 font-medium capitalize">\s*<XCircle className="h-3 w-3 mr-1" \/>\s*Rejected\s*<\/Badge>/g, '<Badge className="bg-slate-100 text-slate-600 border border-slate-200 font-medium capitalize\"><XCircle className="h-3 w-3 mr-1" />{__(\'admin.rejected\')}</Badge>'],
    
    [/title="All Services" header="Marketplace Services"/g, 'title={__(\'admin.all_services\')} header={__(\'admin.marketplace_services\')}'],
    [/<Head title="All Services — Marketplace" \/>/g, '<Head title={__(\'admin.all_services_marketplace\')} />'],
    [/<h1 className="text-2xl font-bold text-slate-900">All Services<\/h1>/g, '<h1 className="text-2xl font-bold text-slate-900">{__(\'admin.all_services\')}</h1>'],
    [/Manage, approve, and moderate marketplace services\./g, '{__(\'admin.manage_approve_moderate_services\')}'],
    [/\{services\.total \?\? 0\} total/g, '{services.total ?? 0} {__(\'admin.total\')}'],
    
    [/label="Total"/g, 'label={__(\'admin.total\')}'],
    [/label="Active"/g, 'label={__(\'admin.active\')}'],
    [/label="Pending"/g, 'label={__(\'admin.pending\')}'],
    [/label="Suspended"/g, 'label={__(\'admin.suspended\')}'],
    [/label="Rejected"/g, 'label={__(\'admin.rejected\')}'],
    [/label="Featured"/g, 'label={__(\'admin.featured\')}'],
    
    [/placeholder="Search by title or seller\.\.\."/g, 'placeholder={__(\'admin.search_title_seller\')}'],
    [/placeholder="Status"/g, 'placeholder={__(\'admin.status\')}'],
    [/<SelectItem value="all">All Statuses<\/SelectItem>/g, '<SelectItem value="all">{__(\'admin.all_statuses\')}</SelectItem>'],
    [/<SelectItem value="active">Active<\/SelectItem>/g, '<SelectItem value="active">{__(\'admin.active\')}</SelectItem>'],
    [/<SelectItem value="draft">Pending<\/SelectItem>/g, '<SelectItem value="draft">{__(\'admin.pending\')}</SelectItem>'],
    [/<SelectItem value="suspended">Suspended<\/SelectItem>/g, '<SelectItem value="suspended">{__(\'admin.suspended\')}</SelectItem>'],
    [/<SelectItem value="rejected">Rejected<\/SelectItem>/g, '<SelectItem value="rejected">{__(\'admin.rejected\')}</SelectItem>'],
    
    [/placeholder="Category"/g, 'placeholder={__(\'admin.category\')}'],
    [/<SelectItem value="all">All Categories<\/SelectItem>/g, '<SelectItem value="all">{__(\'admin.all_categories\')}</SelectItem>'],
    
    [/>\s*Clear\s*<\/Button>/g, '>{__(\'admin.clear\')}</Button>'],
    
    [/Service\n\s*<SortIcon col="title" \/>/g, '{__(\'admin.service\')}\n                                            <SortIcon col="title" />'],
    [/<TableHead>Seller<\/TableHead>/g, '<TableHead>{__(\'admin.seller\')}</TableHead>'],
    [/<TableHead>Category<\/TableHead>/g, '<TableHead>{__(\'admin.category\')}</TableHead>'],
    [/<TableHead>Packages \/ Price<\/TableHead>/g, '<TableHead>{__(\'admin.packages_price\')}</TableHead>'],
    
    [/Orders\n\s*<SortIcon col="orders_count" \/>/g, '{__(\'admin.orders\')}\n                                            <SortIcon col="orders_count" />'],
    [/Status\n\s*<SortIcon col="status" \/>/g, '{__(\'admin.status\')}\n                                            <SortIcon col="status" />'],
    [/Date\n\s*<SortIcon col="created_at" \/>/g, '{__(\'admin.date\')}\n                                            <SortIcon col="created_at" />'],
    [/<TableHead className="text-right w-\[60px\]">Actions<\/TableHead>/g, '<TableHead className="text-right w-[60px]">{__(\'admin.actions\')}</TableHead>'],
    
    [/>\s*Featured\s*<\/Badge>/g, '>{__(\'admin.featured\')}</Badge>'],
    [/>Unknown<\/span>/g, '>{__(\'admin.unknown\')}</span>'],
    [/\|\| \'Uncategorized\'\}/g, '|| __(\'admin.uncategorized\')}'],
    [/>No packages<\/span>/g, '>{__(\'admin.no_packages\')}</span>'],
    
    [/\{service\.packages\.length\} pkg\{service\.packages\.length !== 1 \? \'s\' : \'\'\}/g, '{service.packages.length} {service.packages.length !== 1 ? __(\'admin.pkgs\') : __(\'admin.pkg\')}'],
    
    [/>\s*<Eye className="h-4 w-4" \/>\s*View Service\s*<\/DropdownMenuItem>/g, '><Eye className="h-4 w-4" /> {__(\'admin.view_service\')}</DropdownMenuItem>'],
    [/>\s*<Pencil className="h-4 w-4" \/>\s*Edit Service\s*<\/DropdownMenuItem>/g, '><Pencil className="h-4 w-4" /> {__(\'admin.edit_service\')}</DropdownMenuItem>'],
    [/<><StarOff className="h-4 w-4 text-amber-500" \/> Unfeature<\/>/g, '<><StarOff className="h-4 w-4 text-amber-500" /> {__(\'admin.unfeature\')}</>'],
    [/<><Star className="h-4 w-4" \/> Feature<\/>/g, '<><Star className="h-4 w-4" /> {__(\'admin.feature\')}</>'],
    [/\{service\.status === \'suspended\' \? \'Restore\' : \'Approve\'\}/g, '{service.status === \'suspended\' ? __(\'admin.restore\') : __(\'admin.approve\')}'],
    [/>\s*<X className="h-4 w-4" \/>\s*Reject\s*<\/DropdownMenuItem>/g, '><X className="h-4 w-4" /> {__(\'admin.reject\')}</DropdownMenuItem>'],
    [/>\s*<Ban className="h-4 w-4" \/>\s*Suspend\s*<\/DropdownMenuItem>/g, '><Ban className="h-4 w-4" /> {__(\'admin.suspend\')}</DropdownMenuItem>'],
    [/>\s*<Trash2 className="h-4 w-4" \/>\s*Delete\s*<\/DropdownMenuItem>/g, '><Trash2 className="h-4 w-4" /> {__(\'admin.delete\')}</DropdownMenuItem>'],
    
    [/>No services found<\/p>/g, '>{__(\'admin.no_services_found\')}</p>'],
    [/>\s*Clear filters\s*<\/button>/g, '>{__(\'admin.clear_filters\')}</button>'],
    
    [/Showing\{\' \'\}\n\s*<span className="font-medium text-slate-900">\{services\.from \?\? 0\}<\/span>\n\s*\{\' \'\}–\{\' \'\}\n\s*<span className="font-medium text-slate-900">\{services\.to \?\? 0\}<\/span>\n\s*\{\' \'\}of\{\' \'\}\n\s*<span className="font-medium text-slate-900">\{services\.total\}<\/span>\n\s*\{\' \'\}services/g, '{__(\'admin.showing\')} {\' \'}<span className="font-medium text-slate-900">{services.from ?? 0}</span>{\' \'}-{\' \'}<span className="font-medium text-slate-900">{services.to ?? 0}</span>{\' \'}{__(\'admin.of\')}{\' \'}<span className="font-medium text-slate-900">{services.total}</span>{\' \'}{__(\'admin.services_count\')}'],
    
    [/title:\s*\'Approve Service\'/g, 'title: __(\'admin.approve_service\')'],
    [/description:\s*\`\"\$\{confirm\?\.title\}\" will become publicly visible on the marketplace immediately\.\`/g, 'description: __(\'admin.approve_service_desc\', { title: confirm?.title || \'\' })'],
    [/label:\s*\'Approve\'/g, 'label: __(\'admin.approve\')'],
    
    [/title:\s*\'Reject Service\'/g, 'title: __(\'admin.reject_service\')'],
    [/description:\s*\`\"\$\{confirm\?\.title\}\" will be marked as rejected\. The seller will not be able to resubmit without changes\.\`/g, 'description: __(\'admin.reject_service_desc\', { title: confirm?.title || \'\' })'],
    [/label:\s*\'Reject\'/g, 'label: __(\'admin.reject\')'],
    
    [/title:\s*\'Suspend Service\'/g, 'title: __(\'admin.suspend_service\')'],
    [/description:\s*\`\"\$\{confirm\?\.title\}\" will be hidden from the marketplace\. Existing orders won\'t be affected\.\`/g, 'description: __(\'admin.suspend_service_desc\', { title: confirm?.title || \'\' })'],
    [/label:\s*\'Suspend\'/g, 'label: __(\'admin.suspend\')'],
    
    [/title:\s*\'Toggle Featured\'/g, 'title: __(\'admin.toggle_featured\')'],
    [/description:\s*\`Update the featured status for \"\$\{confirm\?\.title\}\"\.\`/g, 'description: __(\'admin.toggle_featured_desc\', { title: confirm?.title || \'\' })'],
    [/label:\s*\'Confirm\'/g, 'label: __(\'admin.confirm\')'],
    
    [/title:\s*\'Delete Service\'/g, 'title: __(\'admin.delete_service\')'],
    [/description:\s*\`This will permanently delete \"\$\{confirm\?\.title\}\" and all its packages\. This action cannot be undone\.\`/g, 'description: __(\'admin.delete_service_desc\', { title: confirm?.title || \'\' })'],
    [/label:\s*\'Delete Permanently\'/g, 'label: __(\'admin.delete_permanently\')']
];

for (const [pattern, replacement] of replacements) {
    content = content.replace(pattern, replacement);
}

fs.writeFileSync('resources/js/Pages/Admin/Marketplace/All.tsx', content);
console.log('Replacements done.');
