import { useMemo } from 'react';

export type OccupancyLevel = 'green' | 'dark-green' | 'yellow' | 'orange' | 'red';

const colorClassMap: Record<OccupancyLevel, string> = {
    green: 'text-green-500 bg-green-50',
    'dark-green': 'text-emerald-700 bg-emerald-50',
    yellow: 'text-yellow-500 bg-yellow-50',
    orange: 'text-orange-500 bg-orange-50',
    red: 'text-red-500 bg-red-50',
};

export function getOccupancyLevel(occupancy: number): OccupancyLevel {
    if (occupancy <= 25) return 'green';
    if (occupancy <= 50) return 'dark-green';
    if (occupancy <= 75) return 'yellow';
    if (occupancy <= 90) return 'orange';
    return 'red';
}

export function getOccupancyColorClass(occupancy: number): string {
    return colorClassMap[getOccupancyLevel(occupancy)];
}

export function useOccupancyColor(occupancy: number): string {
    return useMemo(() => getOccupancyColorClass(occupancy), [occupancy]);
}
