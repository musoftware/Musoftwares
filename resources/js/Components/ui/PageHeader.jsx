import { Breadcrumbs } from './Breadcrumbs';

export function PageHeader({
    title,
    subtitle,
    icon: Icon,
    actions,
    breadcrumbs,
}) {
    return (
        <div className="mb-6 flex flex-col space-y-4">
            {breadcrumbs && breadcrumbs.length > 0 && (
                <Breadcrumbs items={breadcrumbs} />
            )}
            <div className="flex items-start justify-between">
                <div className="flex items-center space-x-4">
                    {Icon && (
                        <div className="bg-primary-light flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                            <Icon className="text-primary h-5 w-5" />
                        </div>
                    )}
                    <div>
                        <h1 className="font-sora text-text-primary text-[18px] font-semibold">
                            {title}
                        </h1>
                        {subtitle && (
                            <p className="text-text-muted mt-1 font-sans text-[13px]">
                                {subtitle}
                            </p>
                        )}
                    </div>
                </div>
                {actions && (
                    <div className="flex shrink-0 items-center space-x-3">
                        {actions}
                    </div>
                )}
            </div>
        </div>
    );
}
