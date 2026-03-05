import { Circle } from 'lucide-react';

import { getOccupancyColorClass } from '@/hooks/use-occupancy-color';
import { cn } from '@/lib/utils';

interface OccupancyIndicatorProps {
    occupancy: number;
    showLabel?: boolean;
    size?: 'sm' | 'md';
}

export default function OccupancyIndicator({ occupancy, showLabel = false, size = 'md' }: OccupancyIndicatorProps) {
    const colorClass = getOccupancyColorClass(occupancy);
    const iconSize = size === 'sm' ? 'size-3' : 'size-4';

    return (
        <span className="inline-flex items-center gap-1.5" aria-label={`Occupancy ${occupancy}%`}>
            <Circle className={cn(iconSize, 'fill-current', colorClass)} />
            {showLabel && <span className={cn('text-sm', colorClass)}>{occupancy}%</span>}
        </span>
    );
}
