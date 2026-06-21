import React from 'react';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Lock, ArrowRight, LucideIcon } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { __ } from '@/lib/i18n';

interface FeatureItem {
    icon: LucideIcon;
    text: string;
}

interface UpgradeOverlayProps {
    title: string;
    description: string;
    icon: LucideIcon;
    module: string;
    priceText: string;
    features?: FeatureItem[];
    className?: string;
}

export function UpgradeOverlay({ 
    title, 
    description, 
    icon: Icon, 
    module, 
    priceText,
    features,
    className = "mt-6"
}: UpgradeOverlayProps) {
    return (
        <Card className={`border-primary/20 bg-primary/5 shadow-none overflow-hidden relative ${className}`}>
            <div className="absolute top-0 end-0 translate-x-12 -translate-y-12 opacity-5 pointer-events-none">
                <Icon className="h-64 w-64 text-primary" />
            </div>
            <CardContent className="p-8 md:p-10 relative z-10 space-y-6">
                <div className="flex items-center gap-2">
                    <Lock className="h-5 w-5 text-primary" />
                    <span className="font-semibold text-primary text-sm tracking-wide">{__('general.premium_feature')}</span>
                </div>
                <div className="space-y-3">
                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-foreground leading-tight">
                        {title}
                    </h1>
                    <p className="text-muted-foreground leading-relaxed max-w-2xl">
                        {description}
                    </p>
                </div>
                
                {features && features.length > 0 && (
                    <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                        {features.map((feature, idx) => {
                            const FeatureIcon = feature.icon;
                            return (
                                <div key={idx} className="flex items-center gap-2">
                                    <FeatureIcon className="h-4 w-4 text-primary" /> 
                                    {feature.text}
                                </div>
                            );
                        })}
                    </div>
                )}
                
                <div className="pt-2">
                    <Link href={route('subscriptions.plans', { module })}>
                        <Button className="shadow-none flex items-center gap-2 group h-11 px-8 transition-all">
                            {priceText}
                            <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                        </Button>
                    </Link>
                </div>
            </CardContent>
        </Card>
    );
}
