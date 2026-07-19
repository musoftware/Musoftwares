import React from 'react';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import { ClientAutocomplete } from '@/Components/ClientAutocomplete';
import type { ProjectStatus } from '@/types/project';

export const PROJECT_STATUS_OPTIONS: ProjectStatus[] = ['open', 'hold_on', 'closed'];

export type ProjectFormState = {
    user_id: string;
    project_name: string;
    description: string;
    budget: string;
    hour_rate: string;
    percentage: string;
    status: ProjectStatus;
    date_start: string;
    date_end: string;
    hide_future_tasks: boolean;
    
    // Portfolio fields
    show_on_landing_portfolio: boolean;
    portfolio_category: string;
    portfolio_title: string;
    portfolio_description: string;
    portfolio_tech: string;
    portfolio_live_url: string;
    portfolio_github_url: string;
    portfolio_sort_order: string;
    portfolio_image_file: File | null;
    portfolio_image_preview: string;
};

export const EMPTY_PROJECT_FORM: ProjectFormState = {
    user_id: '',
    project_name: '',
    description: '',
    budget: '',
    hour_rate: '',
    percentage: '',
    status: 'open',
    date_start: '',
    date_end: '',
    hide_future_tasks: true,
    
    // Portfolio fields
    show_on_landing_portfolio: false,
    portfolio_category: 'Platform',
    portfolio_title: '',
    portfolio_description: '',
    portfolio_tech: '',
    portfolio_live_url: '',
    portfolio_github_url: '',
    portfolio_sort_order: '0',
    portfolio_image_file: null,
    portfolio_image_preview: '',
};

export function projectToForm(project: any): ProjectFormState {
    if (!project) return { ...EMPTY_PROJECT_FORM };
    return {
        user_id: project.user_id ? String(project.user_id) : '',
        project_name: project.project_name ?? '',
        description: project.description ?? '',
        budget: project.budget != null ? String(project.budget) : '',
        hour_rate: project.hour_rate != null ? String(project.hour_rate) : '',
        percentage: project.percentage != null ? String(project.percentage) : '',
        status: ((project.status as ProjectStatus) ?? 'open') as ProjectStatus,
        date_start: project.date_start ?? '',
        date_end: project.date_end ?? '',
        hide_future_tasks: Boolean(project.hide_future_tasks),
        
        // Portfolio fields
        show_on_landing_portfolio: Boolean(project.show_on_landing_portfolio),
        portfolio_category: project.portfolio_category ?? 'Platform',
        portfolio_title: project.portfolio_title ?? '',
        portfolio_description: project.portfolio_description ?? '',
        portfolio_tech: Array.isArray(project.portfolio_tech) ? project.portfolio_tech.join(', ') : (project.portfolio_tech ?? ''),
        portfolio_live_url: project.portfolio_live_url ?? '',
        portfolio_github_url: project.portfolio_github_url ?? '',
        portfolio_sort_order: project.portfolio_sort_order != null ? String(project.portfolio_sort_order) : '0',
        portfolio_image_file: null,
        portfolio_image_preview: project.portfolio_image ?? '',
    };
}

export function formToPayload(form: ProjectFormState): Record<string, any> {
    const payload: Record<string, any> = {
        user_id: form.user_id,
        project_name: form.project_name,
        description: form.description || null,
        budget: form.budget || null,
        hour_rate: form.hour_rate || null,
        percentage: form.percentage || null,
        status: form.status,
        date_start: form.date_start || null,
        date_end: form.date_end || null,
        hide_future_tasks: form.hide_future_tasks,
        
        // Portfolio fields
        show_on_landing_portfolio: form.show_on_landing_portfolio ? 1 : 0,
        portfolio_category: form.portfolio_category || null,
        portfolio_title: form.portfolio_title || null,
        portfolio_description: form.portfolio_description || null,
        portfolio_tech: form.portfolio_tech ? form.portfolio_tech.split(',').map((t: string) => t.trim()).filter(Boolean) : null,
        portfolio_live_url: form.portfolio_live_url || null,
        portfolio_github_url: form.portfolio_github_url || null,
        portfolio_sort_order: form.portfolio_sort_order ? Number(form.portfolio_sort_order) : 0,
    };
    
    if (form.portfolio_image_file) {
        payload.portfolio_image_file = form.portfolio_image_file;
    }
    
    Object.keys(payload).forEach((k) => {
        if (payload[k] === '' || payload[k] === null) delete payload[k];
    });
    return payload;
}

interface ProjectFormFieldsProps {
    form: ProjectFormState;
    setForm: React.Dispatch<React.SetStateAction<ProjectFormState>>;
    includeClient?: boolean;
    disabled?: boolean;
    initialClient?: { id: number; name: string } | null;
}

export function ProjectFormFields({ form, setForm, includeClient, disabled, initialClient }: ProjectFormFieldsProps) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {includeClient && (
                <div className="z-50 relative sm:col-span-2">
                    <Label htmlFor="client_id">{__('general.client')}</Label>
                    <ClientAutocomplete
                        value={form.user_id}
                        onChange={(val) => setForm((prev) => ({ ...prev, user_id: val }))}
                        searchEndpoint={route('admin.projects.search-clients')}
                        className="mt-1"
                        initialClient={initialClient ?? null}
                    />
                </div>
            )}
            <div className="sm:col-span-2">
                <Label htmlFor="project_name">{__('general.project_name')}</Label>
                <Input
                    id="project_name"
                    value={form.project_name}
                    onChange={(e) => setForm((prev) => ({ ...prev, project_name: e.target.value }))}
                    required
                    disabled={disabled}
                />
            </div>
            <div className="sm:col-span-2">
                <Label htmlFor="description">{__('general.description')}</Label>
                <Textarea
                    id="description"
                    rows={3}
                    value={form.description}
                    onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
                    placeholder={__('general.project_description_placeholder')}
                    disabled={disabled}
                />
            </div>
            <div>
                <Label htmlFor="status">{__('general.status')}</Label>
                <select
                    id="status"
                    className="mt-1 flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:opacity-60"
                    value={form.status}
                    onChange={(e) => setForm((prev) => ({ ...prev, status: e.target.value as ProjectStatus }))}
                    disabled={disabled}
                >
                    {PROJECT_STATUS_OPTIONS.map((s) => (
                        <option key={s} value={s}>
                            {s.replace('_', ' ')}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <Label htmlFor="percentage">{__('general.percentage')}</Label>
                <Input
                    id="percentage"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    value={form.percentage}
                    onChange={(e) => setForm((prev) => ({ ...prev, percentage: e.target.value }))}
                    disabled={disabled}
                />
            </div>
            <div>
                <Label htmlFor="date_start">{__('general.start_date')}</Label>
                <Input
                    id="date_start"
                    type="date"
                    value={form.date_start}
                    onChange={(e) => setForm((prev) => ({ ...prev, date_start: e.target.value }))}
                    disabled={disabled}
                />
            </div>
            <div>
                <Label htmlFor="date_end">{__('general.end_date')}</Label>
                <Input
                    id="date_end"
                    type="date"
                    value={form.date_end}
                    onChange={(e) => setForm((prev) => ({ ...prev, date_end: e.target.value }))}
                    disabled={disabled}
                />
            </div>
            <div>
                <Label htmlFor="budget">{__('general.budget')}</Label>
                <Input
                    id="budget"
                    type="number"
                    step="0.01"
                    min="0"
                    value={form.budget}
                    onChange={(e) => setForm((prev) => ({ ...prev, budget: e.target.value }))}
                    disabled={disabled}
                />
            </div>
            <div>
                <Label htmlFor="hour_rate">{__('general.hour_rate')}</Label>
                <Input
                    id="hour_rate"
                    type="number"
                    step="0.001"
                    min="0"
                    value={form.hour_rate}
                    onChange={(e) => setForm((prev) => ({ ...prev, hour_rate: e.target.value }))}
                    disabled={disabled}
                />
            </div>
            <div className="flex items-center gap-2 sm:col-span-2">
                <Checkbox
                    id="hide_future_tasks"
                    checked={form.hide_future_tasks}
                    onCheckedChange={(checked) => setForm((prev) => ({ ...prev, hide_future_tasks: Boolean(checked) }))}
                    disabled={disabled}
                />
                <Label htmlFor="hide_future_tasks" className="cursor-pointer">
                    {__('general.hide_future_tasks')}
                </Label>
            </div>

            <div className="flex items-center gap-2 sm:col-span-2 border-t border-slate-100 pt-4 mt-2">
                <Checkbox
                    id="show_on_landing_portfolio"
                    checked={form.show_on_landing_portfolio}
                    onCheckedChange={(checked) => setForm((prev) => ({ ...prev, show_on_landing_portfolio: Boolean(checked) }))}
                    disabled={disabled}
                />
                <Label htmlFor="show_on_landing_portfolio" className="cursor-pointer font-semibold text-indigo-600">
                    {__('general.show_on_landing_portfolio') || 'Show on Landing Portfolio'}
                </Label>
            </div>

            {form.show_on_landing_portfolio && (
                <div className="sm:col-span-2 border-t border-slate-100 pt-4 mt-2 space-y-4">
                    <h3 className="text-sm font-bold text-slate-700">{__('general.portfolio_details') || 'Portfolio Details'}</h3>
                    
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor="portfolio_title">{__('general.portfolio_title') || 'Portfolio Title'}</Label>
                            <Input
                                id="portfolio_title"
                                value={form.portfolio_title}
                                onChange={(e) => setForm((prev) => ({ ...prev, portfolio_title: e.target.value }))}
                                placeholder={form.project_name || 'Acel Bay'}
                                disabled={disabled}
                            />
                        </div>
                        <div>
                            <Label htmlFor="portfolio_category">{__('general.portfolio_category') || 'Category'}</Label>
                            <Input
                                id="portfolio_category"
                                value={form.portfolio_category}
                                onChange={(e) => setForm((prev) => ({ ...prev, portfolio_category: e.target.value }))}
                                placeholder="Platform, SaaS, E-Commerce..."
                                disabled={disabled}
                            />
                        </div>
                        <div className="sm:col-span-2">
                            <Label htmlFor="portfolio_description">{__('general.portfolio_description') || 'Portfolio Description'}</Label>
                            <Textarea
                                id="portfolio_description"
                                rows={3}
                                value={form.portfolio_description}
                                onChange={(e) => setForm((prev) => ({ ...prev, portfolio_description: e.target.value }))}
                                placeholder={form.description || 'Describe the project outcomes and features...'}
                                disabled={disabled}
                            />
                        </div>
                        <div>
                            <Label htmlFor="portfolio_live_url">{__('general.portfolio_live_url') || 'Website Link (Live URL)'}</Label>
                            <Input
                                id="portfolio_live_url"
                                type="url"
                                value={form.portfolio_live_url}
                                onChange={(e) => setForm((prev) => ({ ...prev, portfolio_live_url: e.target.value }))}
                                placeholder="https://acelbay.com"
                                disabled={disabled}
                            />
                        </div>
                        <div>
                            <Label htmlFor="portfolio_github_url">{__('general.portfolio_github_url') || 'GitHub Link (Optional)'}</Label>
                            <Input
                                id="portfolio_github_url"
                                type="url"
                                value={form.portfolio_github_url}
                                onChange={(e) => setForm((prev) => ({ ...prev, portfolio_github_url: e.target.value }))}
                                placeholder="https://github.com/..."
                                disabled={disabled}
                            />
                        </div>
                        <div>
                            <Label htmlFor="portfolio_tech">{__('general.portfolio_tech') || 'Technologies (comma separated)'}</Label>
                            <Input
                                id="portfolio_tech"
                                value={form.portfolio_tech}
                                onChange={(e) => setForm((prev) => ({ ...prev, portfolio_tech: e.target.value }))}
                                placeholder="React, Laravel, Tailwind CSS"
                                disabled={disabled}
                            />
                        </div>
                        <div>
                            <Label htmlFor="portfolio_sort_order">{__('general.portfolio_sort_order') || 'Sort Order'}</Label>
                            <Input
                                id="portfolio_sort_order"
                                type="number"
                                value={form.portfolio_sort_order}
                                onChange={(e) => setForm((prev) => ({ ...prev, portfolio_sort_order: e.target.value }))}
                                disabled={disabled}
                            />
                        </div>
                        
                        <div className="sm:col-span-2">
                            <Label htmlFor="portfolio_image_file">{__('general.portfolio_image') || 'Website Screenshot (Large Image)'}</Label>
                            <Input
                                id="portfolio_image_file"
                                type="file"
                                accept="image/*"
                                onChange={(e) => {
                                    const file = e.target.files?.[0] || null;
                                    setForm((prev) => ({ ...prev, portfolio_image_file: file }));
                                }}
                                disabled={disabled}
                                className="mt-1"
                            />
                            <p className="text-xs text-slate-400 mt-1">
                                {__('general.portfolio_image_help') || 'Upload a full website screenshot. The system will automatically crop the top portion for previews and keep the full version for details.'}
                            </p>
                            {form.portfolio_image_preview && (
                                <div className="mt-2 relative w-48 h-32 border rounded overflow-hidden bg-slate-50">
                                    <img 
                                        src={form.portfolio_image_preview} 
                                        alt="Current preview" 
                                        className="w-full h-full object-cover" 
                                    />
                                    <span className="absolute bottom-1 right-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded">
                                        Current Image
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
