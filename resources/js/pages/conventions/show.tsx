import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Check,
    ClipboardList,
    Copy,
    MapPin,
    RefreshCw,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { start } from '@/actions/App/Http/Controllers/AttendanceController';
import {
    destroy,
    index,
    regenerateUrlToken,
    show,
} from '@/actions/App/Http/Controllers/ConventionController';
import ConfirmationDialog from '@/components/confirmation-dialog';
import AttendanceReportBanner from '@/components/conventions/attendance-report-banner';
import ExportDropdown from '@/components/conventions/export-dropdown';
import FloorRow from '@/components/conventions/floor-row';
import { LocaleSelector } from '@/components/locale-selector';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAttendanceReport } from '@/hooks/use-attendance-report';
import { useClipboard } from '@/hooks/use-clipboard';
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
    section_url?: string;
}

function formatDateRange(startDate: string, endDate: string): string {
    const start = new Date(startDate.slice(0, 10) + 'T12:00:00');
    const end = new Date(endDate.slice(0, 10) + 'T12:00:00');
    const fmt = (d: Date, opts: Intl.DateTimeFormatOptions) =>
        new Intl.DateTimeFormat('sv-SE', opts).format(d);

    if (startDate.slice(0, 10) === endDate.slice(0, 10)) {
        return fmt(start, { day: 'numeric', month: 'long', year: 'numeric' });
    }
    if (
        start.getFullYear() === end.getFullYear() &&
        start.getMonth() === end.getMonth()
    ) {
        return `${start.getDate()}–${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`;
    }
    if (start.getFullYear() === end.getFullYear()) {
        return `${fmt(start, { day: 'numeric', month: 'long' })} – ${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`;
    }
    return `${fmt(start, { day: 'numeric', month: 'long', year: 'numeric' })} – ${fmt(end, { day: 'numeric', month: 'long', year: 'numeric' })}`;
}

export default function ConventionsShow({
    convention,
    floors,
    section_url,
}: ConventionsShowProps) {
    useFlashToast();
    const { t } = useTranslation();
    const { isOwner, isManager, isAdministrator } = useConventionRole();
    const { activePeriod, canStart, canStop, reportedCount, totalCount } =
        useAttendanceReport();
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [copiedUrl, copyToClipboard] = useClipboard();
    const [regenerating, setRegenerating] = useState(false);
    const [showRegenerateDialog, setShowRegenerateDialog] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('convention.index.heading'), href: index.url() },
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

    function handleRegenerateUrl() {
        setRegenerating(true);
        router.post(
            regenerateUrlToken.url(convention.id),
            {},
            {
                onFinish: () => {
                    setRegenerating(false);
                    setShowRegenerateDialog(false);
                },
            },
        );
    }

    const totalAttendance =
        activePeriod?.reports?.reduce((sum, r) => sum + r.attendance, 0) ?? 0;
    const userRole = isOwner
        ? 'Owner'
        : isAdministrator
          ? 'Administrator'
          : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={convention.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-start gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            asChild
                            className="mt-0.5 shrink-0"
                        >
                            <Link href={index.url()}>
                                <ArrowLeft />
                            </Link>
                        </Button>
                        <div className="flex flex-col gap-1">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {convention.name}
                            </h1>
                            <div className="flex flex-col gap-0.5 text-sm text-muted-foreground">
                                <span className="flex items-center gap-1.5">
                                    <Calendar className="size-4 shrink-0" />
                                    {formatDateRange(
                                        convention.start_date,
                                        convention.end_date,
                                    )}
                                </span>
                                <span className="flex items-center gap-1.5">
                                    <MapPin className="size-4 shrink-0" />
                                    {convention.city}, {convention.country}
                                    {convention.address &&
                                        ` — ${convention.address}`}
                                </span>
                            </div>
                            {convention.other_info && (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {convention.other_info}
                                </p>
                            )}
                        </div>
                    </div>

                    {isOwner && (
                        <div className="flex items-center gap-2 self-start sm:self-auto">
                            <LocaleSelector conventionId={convention.id} />
                            <ExportDropdown convention={convention} />
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        className="cursor-pointer gap-1.5"
                                        onClick={() =>
                                            setShowDeleteDialog(true)
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                        {t('convention.show.delete_button')}
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {t('convention.show.delete_tooltip')}
                                </TooltipContent>
                            </Tooltip>
                        </div>
                    )}
                </div>

                {/* URL Access Link */}
                {isManager && section_url && (
                    <div className="flex flex-col gap-3 rounded-xl border border-border p-4">
                        <h2 className="text-sm font-medium">
                            {t('convention.show.access_urls_heading')}
                        </h2>
                        <div className="flex flex-col gap-2">
                            <UrlCopyRow
                                label={t(
                                    'convention.show.section_url_label',
                                )}
                                url={section_url}
                                copied={copiedUrl === section_url}
                                onCopy={() => copyToClipboard(section_url)}
                                onRegenerate={() =>
                                    setShowRegenerateDialog(true)
                                }
                                t={t}
                            />
                        </div>
                    </div>
                )}

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
                                {t('convention.show.start_attendance')}
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            {t('convention.show.start_attendance_tooltip')}
                        </TooltipContent>
                    </Tooltip>
                )}

                {/* Floors list */}
                <div className="flex flex-col gap-2">
                    <h2 className="text-lg font-medium">
                        {t('convention.show.floors_heading')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {t('convention.show.floors_description')}
                    </p>
                    {floors.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border p-8 text-center">
                            <p className="text-muted-foreground">
                                {t('convention.show.no_floors')}
                            </p>
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

            <ConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                title={t('convention.show.delete_title')}
                description={t('convention.show.delete_description', {
                    name: convention.name,
                })}
                confirmLabel={t('convention.show.delete_confirm')}
                variant="destructive"
                loading={deleting}
                onConfirm={handleDelete}
            />

            <ConfirmationDialog
                open={showRegenerateDialog}
                onOpenChange={setShowRegenerateDialog}
                title={t('convention.show.regenerate_url_title')}
                description={t('convention.show.regenerate_url_description')}
                confirmLabel={t('convention.show.regenerate_url_confirm')}
                variant="destructive"
                loading={regenerating}
                onConfirm={handleRegenerateUrl}
            />
        </AppLayout>
    );
}

function UrlCopyRow({
    label,
    url,
    copied,
    onCopy,
    onRegenerate,
    t,
}: {
    label: string;
    url: string;
    copied: boolean;
    onCopy: () => void;
    onRegenerate: () => void;
    t: (key: string, opts?: Record<string, string>) => string;
}) {
    return (
        <div className="flex flex-col gap-1">
            <span className="text-xs font-medium text-muted-foreground">
                {label}
            </span>
            <div className="flex items-center gap-2">
                <code className="flex-1 truncate rounded bg-muted px-2 py-1 text-xs">
                    {url}
                </code>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7 shrink-0 cursor-pointer"
                            onClick={onCopy}
                            aria-label={t('convention.show.copy_label', {
                                label,
                            })}
                        >
                            {copied ? (
                                <Check className="size-3.5 text-green-500" />
                            ) : (
                                <Copy className="size-3.5" />
                            )}
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {copied
                            ? t('convention.show.copied')
                            : t('convention.show.copy_to_clipboard')}
                    </TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-7 shrink-0 cursor-pointer"
                            onClick={onRegenerate}
                            aria-label={t('convention.show.regenerate_url')}
                        >
                            <RefreshCw className="size-3.5" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {t('convention.show.regenerate_url')}
                    </TooltipContent>
                </Tooltip>
            </div>
        </div>
    );
}
