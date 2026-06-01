import React, { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';

interface HiddenAmountProps {
    amount: React.ReactNode;
    defaultHidden?: boolean;
    hiddenText?: string;
    className?: string;
}

export default function HiddenAmount({ 
    amount, 
    defaultHidden = true,
    hiddenText = "Hidden",
    className = ""
}: HiddenAmountProps) {
    const [isHidden, setIsHidden] = useState(defaultHidden);

    return (
        <div 
            className={`cursor-pointer inline-flex items-center gap-2 select-none ${className}`} 
            onClick={() => setIsHidden(!isHidden)}
            title="Toggle visibility"
        >
            {isHidden ? (
                <Badge variant="secondary" className="font-jetbrains text-xs flex items-center gap-1 text-slate-500">
                    {hiddenText} <EyeOff size={12} />
                </Badge>
            ) : (
                <div className="flex items-center gap-1 font-bold text-slate-900 font-jetbrains">
                    {amount} <Eye size={12} className="text-slate-400" />
                </div>
            )}
        </div>
    );
}
