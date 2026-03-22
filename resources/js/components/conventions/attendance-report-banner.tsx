import { router } from '@inertiajs/react';
import { AlertTriangle, Square, Users } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { stop } from '@/actions/App/Http/Controllers/AttendanceController';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { AttendancePeriod, Convention } from '@/types/convention';

interface AttendanceReportBannerProps {
    convention: Convention;
    activePeriod: AttendancePeriod;
    totalAttendance: number;
    reportedCount: number;
    totalCount: number;
}

export default function AttendanceReportBanner({
    convention,
    activePeriod,
    totalAttendance,
    reportedCount,
    totalCount,
}: AttendanceReportBannerProps) {
    const { t } = useTranslation();
    const [showConfirm, setShowConfirm] = useState(false);
    const allReported = reportedCount >= totalCount;

    function handleStop() {
        if (allReported) {
            submitStop();
        } else {
            setShowConfirm(true);
        }
    }

    function submitStop() {
        router.post(
            stop.url({
                convention: convention.id,
                attendancePeriod: activePeriod.id,
            }),
        );
        setShowConfirm(false);
    }

    return (
        <>
            <Alert className="rounded-xl border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30">
                <AlertDescription className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <span className="flex items-center gap-1.5 text-sm font-medium text-blue-900 dark:text-blue-200">
                            <Users className="size-4" />
                            {t('attendance.banner.sections_reported', {
                                reported: reportedCount,
                                total: totalCount,
                            })}
                        </span>
                        <span className="text-sm text-blue-700 dark:text-blue-300">
                            {t('attendance.banner.total_attendance', {
                                count: totalAttendance,
                            })}
                        </span>
                    </div>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="cursor-pointer gap-1.5 border-blue-300 text-blue-800 hover:bg-blue-100 dark:border-blue-700 dark:text-blue-200 dark:hover:bg-blue-900/30"
                                onClick={handleStop}
                            >
                                <Square className="size-3.5 fill-current" />
                                {t('attendance.banner.stop_button')}
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            {t('attendance.banner.stop_tooltip')}
                        </TooltipContent>
                    </Tooltip>
                </AlertDescription>
            </Alert>

            <Dialog open={showConfirm} onOpenChange={setShowConfirm}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="size-5 text-amber-500" />
                            {t('attendance.incomplete_dialog.title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('attendance.incomplete_dialog.description', {
                                reported: reportedCount,
                                total: totalCount,
                            })}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button
                                variant="outline"
                                className="cursor-pointer"
                            >
                                {t('attendance.incomplete_dialog.cancel')}
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            className="cursor-pointer"
                            onClick={submitStop}
                        >
                            {t('attendance.incomplete_dialog.confirm')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
