import { Head, Link, router } from '@inertiajs/react';
import { Accessibility, ArrowLeft, Clock, Heart, Send, Trash2, Users } from 'lucide-react';
import { useState } from 'react';

import { report } from '@/actions/App/Http/Controllers/AttendanceController';
import { index as conventionsIndex, show as conventionShow } from '@/actions/App/Http/Controllers/ConventionController';
import { index as floorsIndex } from '@/actions/App/Http/Controllers/FloorController';
import { destroy, setFull, show as sectionShow, updateOccupancy } from '@/actions/App/Http/Controllers/SectionController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import AvailableSeatsInput from '@/components/conventions/available-seats-input';
import FullButton from '@/components/conventions/full-button';
import OccupancyDropdown from '@/components/conventions/occupancy-dropdown';
import OccupancyGauge from '@/components/conventions/occupancy-gauge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useConventionRole } from '@/hooks/use-convention-role';
import AppLayout from '@/layouts/app-layout';
import type { AttendancePeriod, Convention, Floor, Section } from '@/types/convention';
import type { BreadcrumbItem } from '@/types/navigation';

interface SectionsShowProps {
    section: Section;
    floor: Floor;
    convention: Convention;
    userRoles: string[];
    activePeriod: AttendancePeriod | null;
}

export default function SectionsShow({ section, floor, convention, activePeriod }: SectionsShowProps) {
    const [attendanceValue, setAttendanceValue] = useState('');
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const { isOwner, isConventionUser, isFloorUser, hasFloorAccess } = useConventionRole();

    const canDeleteSection = isOwner || isConventionUser || (isFloorUser && hasFloorAccess(floor.id));

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Conventions', href: conventionsIndex.url() },
        { title: convention.name, href: conventionShow.url(convention.id) },
        { title: 'Floors', href: floorsIndex.url(convention.id) },
        { title: floor.name, href: floorsIndex.url(convention.id) },
        { title: section.name, href: sectionShow.url(section.id) },
    ];

    function handleOccupancyUpdate(occupancy: number) {
        router.patch(updateOccupancy.url(section.id), { occupancy }, { preserveScroll: true });
    }

    function handleSetFull() {
        router.post(setFull.url(section.id), {}, { preserveScroll: true });
    }

    function handleAvailableSeatsUpdate(availableSeats: number) {
        router.patch(updateOccupancy.url(section.id), { available_seats: availableSeats }, { preserveScroll: true });
    }

    function handleAttendanceSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (!activePeriod) return;

        const attendance = Number(attendanceValue);
        if (isNaN(attendance) || attendance < 0) return;

        router.post(
            report.url({ section: section.id, attendancePeriod: activePeriod.id }),
            { attendance },
            { preserveScroll: true, onSuccess: () => setAttendanceValue('') },
        );
    }

    function handleDeleteSection() {
        setDeleting(true);
        router.delete(destroy.url(section.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    }

    function formatLastUpdate(): string | null {
        if (!section.last_occupancy_updated_at) return null;

        const date = new Date(section.last_occupancy_updated_at);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    const lastUpdateTime = formatLastUpdate();
    const lastUpdatedByName = section.last_updated_by
        ? `${section.last_updated_by.first_name} ${section.last_updated_by.last_name}`
        : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${section.name} — ${convention.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex items-start justify-between gap-2">
                    <div className="flex items-start gap-2">
                        <Button variant="ghost" size="icon" asChild className="mt-0.5 shrink-0">
                            <Link href={floorsIndex.url(convention.id)}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <div className="flex flex-col gap-1">
                            <h1 className="text-2xl font-semibold tracking-tight">{section.name}</h1>
                            <p className="text-muted-foreground text-sm">
                                {floor.name} · {convention.name}
                            </p>
                        </div>
                    </div>

                    {canDeleteSection && (
                        <Button
                            variant="destructive"
                            size="sm"
                            className="cursor-pointer gap-1.5 shrink-0"
                            onClick={() => setShowDeleteDialog(true)}
                        >
                            <Trash2 className="size-4" />
                            Delete
                        </Button>
                    )}
                </div>

                {/* Section info card */}
                <Card className="rounded-xl border border-border shadow-sm">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-lg">Section Details</CardTitle>
                            <OccupancyGauge occupancy={section.occupancy} size={48} />
                        </div>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        {/* Seats & accessibility */}
                        <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                            <div className="flex items-center gap-1.5 text-sm">
                                <Users className="text-muted-foreground size-4" />
                                <span className="font-medium">{section.number_of_seats}</span>
                                <span className="text-muted-foreground">seats</span>
                            </div>

                            {section.elder_friendly && (
                                <Badge variant="secondary" className="gap-1">
                                    <Heart className="size-3" />
                                    Elder-friendly
                                </Badge>
                            )}
                            {section.handicap_friendly && (
                                <Badge variant="secondary" className="gap-1">
                                    <Accessibility className="size-3" />
                                    Handicap-friendly
                                </Badge>
                            )}
                        </div>

                        {section.information && (
                            <p className="text-muted-foreground text-sm">{section.information}</p>
                        )}

                        <Separator />

                        {/* Occupancy help text */}
                        <p className="text-muted-foreground text-sm">
                            Set occupancy by selecting a percentage, entering available seats, or pressing FULL.<br/>You can update these as often as needed throughout the day. All numbers reset automatically every night.
                        </p>

                        {/* Occupancy controls */}
                        <div className="flex flex-col gap-4">
                            <OccupancyDropdown currentOccupancy={section.occupancy} onUpdate={handleOccupancyUpdate} />
                            <FullButton section={section} onUpdate={handleSetFull} />
                            <AvailableSeatsInput section={section} onUpdate={handleAvailableSeatsUpdate} />
                        </div>
                    </CardContent>

                    {/* Last update footer */}
                    {(lastUpdatedByName || lastUpdateTime) && (
                        <CardFooter className="text-muted-foreground flex items-center gap-1.5 text-xs">
                            <Clock className="size-3.5 shrink-0" />
                            <span>
                                Last updated
                                {lastUpdatedByName && <> by {lastUpdatedByName}</>}
                                {lastUpdateTime && <> at {lastUpdateTime}</>}
                            </span>
                        </CardFooter>
                    )}
                </Card>

                {/* Attendance reporting section */}
                {activePeriod && (
                    <Card className="rounded-xl border border-border shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-lg">Attendance Report</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleAttendanceSubmit} className="space-y-2">
                                <Label htmlFor="attendance-input">
                                    Attendance ({activePeriod.period})
                                </Label>
                                <div className="flex items-center gap-2">
                                    <Input
                                        id="attendance-input"
                                        type="number"
                                        min={0}
                                        placeholder="Enter attendance count"
                                        value={attendanceValue}
                                        onChange={(e) => setAttendanceValue(e.target.value)}
                                        className="flex-1"
                                    />
                                    <Button type="submit" className="cursor-pointer gap-1.5">
                                        <Send className="size-4" />
                                        Send
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Delete section confirmation dialog */}
            <ConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                title="Delete Section"
                description={`Are you sure you want to delete "${section.name}"? All occupancy data and attendance reports for this section will be permanently removed. This action cannot be undone.`}
                confirmLabel="Delete section"
                variant="destructive"
                loading={deleting}
                onConfirm={handleDeleteSection}
            />
        </AppLayout>
    );
}
