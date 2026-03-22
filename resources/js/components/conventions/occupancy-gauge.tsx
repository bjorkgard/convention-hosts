import { useTranslation } from 'react-i18next';

import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { getOccupancyLevel } from '@/hooks/use-occupancy-color';

const colorMap = {
    green: { stroke: '#22c55e', track: '#dcfce7' },
    'dark-green': { stroke: '#047857', track: '#d1fae5' },
    yellow: { stroke: '#eab308', track: '#fef9c3' },
    orange: { stroke: '#f97316', track: '#fff7ed' },
    red: { stroke: '#ef4444', track: '#fef2f2' },
};

const levelKeys = {
    green: 'section.occupancy.gauge_low',
    'dark-green': 'section.occupancy.gauge_moderate',
    yellow: 'section.occupancy.gauge_high',
    orange: 'section.occupancy.gauge_very_high',
    red: 'section.occupancy.gauge_at_capacity',
};

interface OccupancyGaugeProps {
    occupancy: number;
    size?: number;
}

export default function OccupancyGauge({
    occupancy,
    size = 32,
}: OccupancyGaugeProps) {
    const { t } = useTranslation();
    const level = getOccupancyLevel(occupancy);
    const { stroke, track } = colorMap[level];
    const label = t(levelKeys[level]);

    const strokeWidth = size * 0.15;
    const radius = (size - strokeWidth) / 2;
    const cx = size / 2;
    const cy = size / 2;

    const halfCircumference = Math.PI * radius;
    const filled = (occupancy / 100) * halfCircumference;

    const arcPath = `M ${cx - radius} ${cy} A ${radius} ${radius} 0 0 1 ${cx + radius} ${cy}`;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <svg
                    width={size}
                    height={size / 2 + strokeWidth}
                    viewBox={`0 0 ${size} ${size / 2 + strokeWidth}`}
                    aria-label={`${label} ${occupancy}%`}
                    role="img"
                    className="shrink-0 cursor-default"
                >
                    <path
                        d={arcPath}
                        fill="none"
                        stroke={track}
                        strokeWidth={strokeWidth}
                        strokeLinecap="round"
                    />
                    <path
                        d={arcPath}
                        fill="none"
                        stroke={stroke}
                        strokeWidth={strokeWidth}
                        strokeLinecap="round"
                        strokeDasharray={`${halfCircumference}`}
                        strokeDashoffset={`${halfCircumference - filled}`}
                    />
                    <text
                        x={cx}
                        y={cy}
                        textAnchor="middle"
                        fontSize={size * 0.25}
                        fill={stroke}
                        fontWeight="600"
                    >
                        {occupancy}%
                    </text>
                </svg>
            </TooltipTrigger>
            <TooltipContent>
                <p className="font-medium">{label}</p>
                <p className="text-xs text-muted-foreground">
                    {t('section.occupancy.gauge_occupancy', {
                        percent: occupancy,
                    })}
                </p>
            </TooltipContent>
        </Tooltip>
    );
}
