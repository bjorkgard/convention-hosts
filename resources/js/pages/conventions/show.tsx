import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, ClipboardList, MapPin, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { start } from '@/actions/App/Http/Controllers/AttendanceController';
import { destroy, index, show } from '@/actions/App/Http/Controllers/ConventionController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import AttendanceReportBanner from '@/components/conventions/attendance-report-banner';
import ExportDropdown from '@/components/conventions/export-dropdown';
import FloorRow from '@/components/conventions/floor-row';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useAttendanceReport } from '@/hooks/use-attendance-report';
import { useConventionRole } from '@/hooks/use-convention-role';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import type { AttendancePeriod, Convention, Floor } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';
import type { ConventionUser } from '@/types/user';

interface ConventionsShowProps {
    convention: Convention;
    floors: Floor[];
    attendancePeriods: AttendancePeriod[];
    users: ConventionUser[];
}

function formatDateRange(startDate: string, endDate: string): string {
    // Slice to YYYY-MM-DD to handle full ISO timestamps; use noon to avoid DST edge cases
    const start = new Date(startDate.slice(0, 10) + 'T12:00:00');
    const end = new Date(endDate.slice(0, 10) + 'T12:00:00');

    const fmt = (d: Date, opts: Intl.DateTimeFormatOptions) => new Intl.DateTimeFormat('sv-SE', opts).format(d);

    if (startDate.slice(0, 10) === endDate.slice(0, 10)) {
        return fmt(start, { day: 'numeric', month: 'long', year: 'numeric' });
    }

    if (start.getFullYear() === end.getFullYear() && start.getMonth() === end.getMonth()) {
        return `${start.getDate()}–${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`;
    }

    if (start.getFullYear() === end.getFullYear()) {
        return `${fmt(start, { day: 'numeric', month: 'long' })} – ${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`;
    }

    return `${fmt(start, { day: 'numeric', month: 'long', year: 'numeric' })} – ${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`;
}

export default function ConventionsShow({ convention, floors }: ConventionsShowProps) {
    useFlashToast();
    const { isOwner, isConventionUser } = useConventionRole();
    const { activePeriod, canStart, canStop, reportedCount, totalCount } = useAttendanceReport();
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const isManager = isOwner || isConventionUser;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Conventions', href: index.url() },
        { title: convention.name, href: show.url(convention.id) },
    ];

    function handleDelete() {
        setDeleting(true);
        router.delete(destroy.url(convention.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    function handleStartAttendance() {
        router.post(start.url(convention.id));
    }

    // Calculate total attendance from active period reports
    const totalAttendance = activePeriod?.reports?.reduce((sum, r) => sum + r.attendance, 0) ?? 0;

    // Determine the primary user role for FloorRow
    const userRole = isOwner ? 'Owner' : isConventionUser ? 'ConventionUser' : 'FloorUser';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={convention.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-start gap-2">
                        <Button variant="ghost" size="icon" asChild className="mt-0.5 shrink-0">
                            <Link href={index.url()}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <div className="flex flex-col gap-1">
                            <h1 className="text-2xl font-semibold tracking-tight">{convention.name}</h1>
                            <div className="text-muted-foreground flex flex-col gap-0.5 text-sm">
                                <span className="flex items-center gap-1.5">
                                    <Calendar className="size-4 shrink-0" />
                                    {formatDateRange(convention.start_date, convention.end_date)}
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <MapPin className="size-4 shrink-0" />
                                    {convention.city}, {convention.country}
                                    {convention.address && ` — ${convention.address}`}
                                </span>
                            </div>
                            {convention.other_info && (
                                <p className="text-muted-foreground mt-1 text-sm">{convention.other_info}</p>
                            )}
                        </div>
                    </div>

                    {/* Owner actions */}
                    {isOwner && (
                        <div className="flex items-center gap-2 self-start sm:self-auto">
                            <ExportDropdown convention={convention} />
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        className="cursor-pointer gap-1.5"
                                        onClick={() => setShowDeleteDialog(true)}
                                    >
                                        <Trash2 className="size-4" />
                                        Delete
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Permanently delete this convention and all its data</TooltipContent>
                            </Tooltip>
                        </div>
                    )}
                </div>

                {/* Attendance report banner or start button */}
                {isManager && activePeriod && canStop && (
                    <AttendanceReportBanner
                        convention={convention}
                        activePeriod={activePeriod}
                        totalAttendance={totalAttendance}
                        reportedCount={reportedCount}
                        totalCount={totalCount}
                    />
                )}

                {isManager && !activePeriod && canStart && (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="outline"
                                className="cursor-pointer gap-1.5 self-start"
                                onClick={handleStartAttendance}
                            >
                                <ClipboardList className="size-4" />
                                Start attendance report
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Begin collecting attendance from each section for this period</TooltipContent>
                    </Tooltip>
                )}

                {/* Floors list */}
                <div className="flex flex-col gap-2">
                    <h2 className="text-lg font-medium">Floors</h2>
                    <p className="text-muted-foreground text-sm">
                        Expand a floor to see its sections and current occupancy. Tap a section to update its status.
                    </p>
                    {floors.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                            <p className="text-muted-foreground">No floors yet.</p>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-2">
                            {floors.map((floor) => (
                                <FloorRow
                                    key={floor.id}
                                    floor={floor}
                                    sections={floor.sections ?? []}
                                    userRole={userRole}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Delete confirmation dialog */}
            <ConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                title="Delete Convention"
                description={`Are you sure you want to delete "${convention.name}"? This action cannot be undone. All floors, sections, users, and attendance data will be permanently removed.`}
                confirmLabel="Delete convention"
                variant="destructive"
                loading={deleting}
                onConfirm={handleDelete}
            />
        </AppLayout>
    );
}
