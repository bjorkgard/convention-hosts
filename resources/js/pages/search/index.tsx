import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, SearchX } from 'lucide-react';
import { useCallback } from 'react';
import { useTranslation } from 'react-i18next';

import {
    index as conventionsIndex,
    show as conventionShow,
} from '@/actions/App/Http/Controllers/ConventionController';
import { index as searchIndex } from '@/actions/App/Http/Controllers/SearchController';
import { show as sectionShow } from '@/actions/App/Http/Controllers/SectionController';
import OccupancyGauge from '@/components/conventions/occupancy-gauge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    hearing_loop?: string;
}

interface SearchIndexProps {
    convention: Convention;
    sections: PaginatedSections;
    floors: Pick<Floor, 'id' | 'name'>[];
    filters: SearchFilters;
}

export default function SearchIndex({
    convention,
    sections,
    floors,
    filters,
}: SearchIndexProps) {
    const { t } = useTranslation();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('convention.index.heading'), href: conventionsIndex.url() },
        { title: convention.name, href: conventionShow.url(convention.id) },
        { title: t('search.heading'), href: searchIndex.url(convention.id) },
    ];

    const applyFilters = useCallback(
        (newFilters: Partial<SearchFilters>) => {
            const merged = { ...filters, ...newFilters };

            const query: Record<string, string> = {};
            if (merged.floor_id) query.floor_id = merged.floor_id;
            if (
                merged.elder_friendly === '1' ||
                merged.elder_friendly === 'true'
            )
                query.elder_friendly = '1';
            if (
                merged.handicap_friendly === '1' ||
                merged.handicap_friendly === 'true'
            )
                query.handicap_friendly = '1';
            if (merged.hearing_loop === '1' || merged.hearing_loop === 'true')
                query.hearing_loop = '1';

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

    function handleHearingLoopChange(checked: boolean | 'indeterminate') {
        applyFilters({ hearing_loop: checked === true ? '1' : undefined });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('search.title')} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center gap-3">
                    <Link
                        href={conventionShow.url(convention.id)}
                        aria-label={t('search.back_label')}
                    >
                        <ArrowLeft className="size-5 text-muted-foreground" />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {t('search.heading')}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t('search.description')}
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex flex-col gap-3 rounded-xl border border-border bg-card p-4 sm:flex-row sm:items-end sm:gap-4">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="floor-filter">
                            {t('search.floor_label')}
                        </Label>
                        <Select
                            value={filters.floor_id ?? 'all'}
                            onValueChange={handleFloorChange}
                        >
                            <SelectTrigger
                                id="floor-filter"
                                className="w-full sm:w-48"
                            >
                                <SelectValue
                                    placeholder={t('search.all_floors')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('search.all_floors')}
                                </SelectItem>
                                {floors.map((floor) => (
                                    <SelectItem
                                        key={floor.id}
                                        value={String(floor.id)}
                                    >
                                        {floor.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="elder-friendly"
                            checked={
                                filters.elder_friendly === '1' ||
                                filters.elder_friendly === 'true'
                            }
                            onCheckedChange={handleElderFriendlyChange}
                        />
                        <Label
                            htmlFor="elder-friendly"
                            className="cursor-pointer"
                        >
                            {t('search.elder_friendly')}
                        </Label>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="handicap-friendly"
                            checked={
                                filters.handicap_friendly === '1' ||
                                filters.handicap_friendly === 'true'
                            }
                            onCheckedChange={handleHandicapFriendlyChange}
                        />
                        <Label
                            htmlFor="handicap-friendly"
                            className="cursor-pointer"
                        >
                            {t('search.handicap_friendly')}
                        </Label>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="hearing-loop"
                            checked={
                                filters.hearing_loop === '1' ||
                                filters.hearing_loop === 'true'
                            }
                            onCheckedChange={handleHearingLoopChange}
                        />
                        <Label
                            htmlFor="hearing-loop"
                            className="cursor-pointer"
                        >
                            {t('search.hearing_loop')}
                        </Label>
                    </div>
                </div>

                {/* Results */}
                {sections.data.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                        <SearchX className="mb-2 size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">
                            {t('search.no_results')}
                        </p>
                    </div>
                ) : (
                    <>
                        <p className="text-sm text-muted-foreground">
                            {t('search.results_count', {
                                count: sections.total,
                            })}
                        </p>
                        <div className="flex flex-col gap-2">
                            {sections.data.map((section) => (
                                <Link
                                    key={section.id}
                                    href={sectionShow.url(section.id)}
                                    className="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl border border-border p-3 transition-colors duration-200 hover:border-primary/30 hover:bg-accent"
                                >
                                    <OccupancyGauge
                                        occupancy={section.occupancy}
                                        size={40}
                                    />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">
                                            {section.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {section.floor?.name ??
                                                t('search.unknown_floor')}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {/* Pagination */}
                        {sections.last_page > 1 && (
                            <nav
                                className="flex flex-wrap items-center justify-center gap-1 pt-2"
                                aria-label={t('search.pagination_label')}
                            >
                                {sections.links.map((link, i) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={i}
                                                className="min-h-[44px] min-w-[44px] px-3 py-2 text-center text-sm text-muted-foreground"
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
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
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
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
