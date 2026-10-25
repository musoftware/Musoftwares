import React from 'react';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Circle, ExternalLink } from 'lucide-react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';

interface SetupItem {
    key: string;
    title: string;
    note: string | null;
    url: string | null;
    manual: boolean;
    done: boolean;
}

interface SetupGroup {
    key: string;
    title: string;
    items: SetupItem[];
}

type Texts = Record<string, string>;

function fill(text: string, values: Record<string, number>): string {
    return Object.entries(values).reduce((out, [name, value]) => out.replace(`:${name}`, String(value)), text);
}

function StepRow({ item, texts }: { item: SetupItem; texts: Texts }) {
    const toggle = () => router.post(`/admin/setup/${item.key}`, { done: !item.done }, { preserveScroll: true });
    const isExternal = item.url?.startsWith('http') ?? false;
    const Icon = item.done ? CheckCircle2 : Circle;

    return (
        <li className="flex gap-3 py-4 border-b last:border-b-0">
            <Icon className={`mt-0.5 h-5 w-5 shrink-0 ${item.done ? 'text-green-600' : 'text-muted-foreground'}`} aria-hidden />
            <div className="min-w-0 flex-1 space-y-2">
                <p className={`font-medium ${item.done ? 'text-muted-foreground line-through' : ''}`}>{item.title}</p>
                {item.note && <p className="text-sm text-muted-foreground break-words">{item.note}</p>}
                <div className="flex flex-wrap gap-2">
                    {item.url && (
                        <Button asChild size="sm" variant="outline">
                            <a href={item.url} target={isExternal ? '_blank' : undefined} rel={isExternal ? 'noopener noreferrer' : undefined}>
                                {texts.open}
                                {isExternal && <ExternalLink className="ms-1 h-3.5 w-3.5" aria-hidden />}
                            </a>
                        </Button>
                    )}
                    {item.manual && (
                        <Button size="sm" variant={item.done ? 'ghost' : 'default'} onClick={toggle}>
                            {item.done ? texts.mark_not_done : texts.mark_done}
                        </Button>
                    )}
                </div>
            </div>
        </li>
    );
}

export default function Setup({ groups, texts }: { groups: SetupGroup[]; texts: Texts }) {
    const items = groups.flatMap((group) => group.items);
    const doneCount = items.filter((item) => item.done).length;
    const allDone = doneCount === items.length;

    return (
        <AdminSidebarLayout>
            <Head title={texts.title} />
            <div className="p-4 sm:p-6 space-y-6 overflow-x-clip max-w-3xl">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold">{texts.title}</h1>
                    <p className="text-sm text-muted-foreground">{texts.intro}</p>
                    <p className="text-sm font-medium">{allDone ? texts.all_done : fill(texts.progress, { done: doneCount, total: items.length })}</p>
                </div>
                {groups.map((group) => (
                    <Card key={group.key}>
                        <CardHeader>
                            <CardTitle>{group.title}</CardTitle>
                            <CardDescription>{fill(texts.progress, { done: group.items.filter((item) => item.done).length, total: group.items.length })}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul>
                                {group.items.map((item) => <StepRow key={item.key} item={item} texts={texts} />)}
                            </ul>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AdminSidebarLayout>
    );
}
