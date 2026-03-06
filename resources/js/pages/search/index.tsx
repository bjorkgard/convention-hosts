import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, SearchX } from 'lucide-react';
import { useCallback } from 'react';

import { index as conventionsIndex, show as conventionShow } from '@/actions/App/Http/Controllers/ConventionController';
import { index as searchIndex } from '@/actions/App/Http/Controllers/SearchController';
import { show as sectionShow } from '@/actions/App/Http/Controllers/SectionController';
import OccupancyGauge from '@/components/conventions/occupancy-gauge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { Convention, Floor, Section } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';

interface PaginatedSections {
    data: (Section & { floor: Floor })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface SearchFilters {
    floor_id?: string;
    elder_friendly?: string;
    handicap_friendly?: string;
}

interface SearchIndexProps {
    convention: Convention;
    sections: PaginatedSections;
    floors: Pick<Floor, 'id' | 'name'>[];
    filters: SearchFilters;
}

export default function SearchIndex({ convention, sections, floors, filters }: SearchIndexProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Conventions', href: conventionsIndex.url() },
        { title: convention.name, href: conventionShow.url(convention.id) },
        { title: 'Search', href: searchIndex.url(convention.id) },
    ];

    const applyFilters = useCallback(
        (newFilters: Partial<SearchFilters>) => {
            const merged = { ...filters, ...newFilters };

            // Build clean query params (omit empty values)
            const query: Record<string, string> = {};
            if (merged.floor_id) query.floor_id = merged.floor_id;
            if (merged.elder_friendly === '1' || merged.elder_friendly === 'true') query.elder_friendly = '1';
            if (merged.handicap_friendly === '1' || merged.handicap_friendly === 'true') query.handicap_friendly = '1';

            router.get(searchIndex.url(convention.id), query, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [convention.id, filters],
    );

    function handleFloorChange(value: string) {
        if (value === 'all') {
            applyFilters({ floor_id: undefined });
        } else {
            applyFilters({ floor_id: value });
        }
    }

    function handleElderFriendlyChange(checked: boolean | 'indeterminate') {
        applyFilters({ elder_friendly: checked === true ? '1' : undefined });
    }

    function handleHandicapFriendlyChange(checked: boolean | 'indeterminate') {
        applyFilters({ handicap_friendly: checked === true ? '1' : undefined });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Search" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center gap-3">
                    <Link href={conventionShow.url(convention.id)} aria-label="Back to convention">
                        <ArrowLeft className="text-muted-foreground size-5" />
                    </Link>
                    <h1 className="text-2xl font-semibold tracking-tight">Search</h1>
                </div>

                {/* Filters */}
                <div className="flex flex-col gap-3 rounded-xl border border-border bg-card p-4 sm:flex-row sm:items-end sm:gap-4">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="floor-filter">Floor</Label>
                        <Select value={filters.floor_id ?? 'all'} onValueChange={handleFloorChange}>
                            <SelectTrigger id="floor-filter" className="w-full sm:w-48">
                                <SelectValue placeholder="All floors" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All floors</SelectItem>
                                {floors.map((floor) => (
                                    <SelectItem key={floor.id} value={String(floor.id)}>
                                        {floor.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="elder-friendly"
                            checked={filters.elder_friendly === '1' || filters.elder_friendly === 'true'}
                            onCheckedChange={handleElderFriendlyChange}
                        />
                        <Label htmlFor="elder-friendly" className="cursor-pointer">
                            Elder-friendly
                        </Label>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="handicap-friendly"
                            checked={filters.handicap_friendly === '1' || filters.handicap_friendly === 'true'}
                            onCheckedChange={handleHandicapFriendlyChange}
                        />
                        <Label htmlFor="handicap-friendly" className="cursor-pointer">
                            Handicap-friendly
                        </Label>
                    </div>
                </div>

                {/* Results */}
                {sections.data.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                        <SearchX className="text-muted-foreground mb-2 size-10" />
                        <p className="text-muted-foreground">No available sections found matching your filters.</p>
                    </div>
                ) : (
                    <>
                        <p className="text-muted-foreground text-sm">
                            {sections.total} {sections.total === 1 ? 'section' : 'sections'} available
                        </p>
                        <div className="flex flex-col gap-2">
                            {sections.data.map((section) => (
                                <Link
                                    key={section.id}
                                    href={sectionShow.url(section.id)}
                                    className="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition-colors duration-200 hover:border-primary/30 hover:bg-accent"
                                >
                                    <OccupancyGauge occupancy={section.occupancy} size={40} />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">{section.name}</p>
                                        <p className="text-muted-foreground truncate text-sm">{section.floor?.name ?? 'Unknown floor'}</p>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {/* Pagination */}
                        {sections.last_page > 1 && (
                            <nav className="flex flex-wrap items-center justify-center gap-1 pt-2" aria-label="Pagination">
                                {sections.links.map((link, i) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={i}
                                                className="text-muted-foreground min-h-[44px] min-w-[44px] px-3 py-2 text-center text-sm"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    }
                                    return (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            className={`min-h-[44px] min-w-[44px] rounded-md px-3 py-2 text-center text-sm transition-colors ${
                                                link.active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'hover:bg-accent'
                                            }`}
                                            preserveState
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </nav>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
