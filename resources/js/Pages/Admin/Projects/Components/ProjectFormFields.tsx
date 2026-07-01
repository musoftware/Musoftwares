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
    project_balance: string;
    budget: string;
    hour_rate: string;
    percentage: string;
    status: ProjectStatus;
    date_start: string;
    date_end: string;
    hide_future_tasks: boolean;
};

export const EMPTY_PROJECT_FORM: ProjectFormState = {
    user_id: '',
    project_name: '',
    description: '',
    project_balance: '',
    budget: '',
    hour_rate: '',
    percentage: '',
    status: 'open',
    date_start: '',
    date_end: '',
    hide_future_tasks: true,
};

export function projectToForm(project: {
    user_id?: number | null;
    project_name?: string | null;
    description?: string | null;
    project_balance?: string | number | null;
    budget?: string | number | null;
    hour_rate?: string | number | null;
    percentage?: number | string | null;
    status?: string | null;
    date_start?: string | null;
    date_end?: string | null;
    hide_future_tasks?: boolean | null;
} | null | undefined): ProjectFormState {
    if (!project) return { ...EMPTY_PROJECT_FORM };
    return {
        user_id: project.user_id ? String(project.user_id) : '',
        project_name: project.project_name ?? '',
        description: project.description ?? '',
        project_balance: project.project_balance != null ? String(project.project_balance) : '',
        budget: project.budget != null ? String(project.budget) : '',
        hour_rate: project.hour_rate != null ? String(project.hour_rate) : '',
        percentage: project.percentage != null ? String(project.percentage) : '',
        status: ((project.status as ProjectStatus) ?? 'open') as ProjectStatus,
        date_start: project.date_start ?? '',
        date_end: project.date_end ?? '',
        hide_future_tasks: Boolean(project.hide_future_tasks),
    };
}

export function formToPayload(form: ProjectFormState): Record<string, string | number | boolean> {
    const payload: Record<string, string | number | boolean | null> = {
        user_id: form.user_id,
        project_name: form.project_name,
        description: form.description || null,
        project_balance: form.project_balance || null,
        budget: form.budget || null,
        hour_rate: form.hour_rate || null,
        percentage: form.percentage || null,
        status: form.status,
        date_start: form.date_start || null,
        date_end: form.date_end || null,
        hide_future_tasks: form.hide_future_tasks,
    };
    Object.keys(payload).forEach((k) => {
        if (payload[k] === '' || payload[k] === null) delete payload[k];
    });
    return payload as Record<string, string | number | boolean>;
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
                <Label htmlFor="project_balance">{__('general.project_balance')}</Label>
                <Input
                    id="project_balance"
                    type="number"
                    step="0.01"
                    value={form.project_balance}
                    onChange={(e) => setForm((prev) => ({ ...prev, project_balance: e.target.value }))}
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
        </div>
    );
}
