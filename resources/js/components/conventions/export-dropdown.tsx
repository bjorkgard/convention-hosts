import { Download, FileSpreadsheet, FileText, Hash } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { exportMethod } from '@/actions/App/Http/Controllers/ConventionController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Convention } from '@/types/convention';

interface ExportDropdownProps {
    convention: Convention;
}

export default function ExportDropdown({ convention }: ExportDropdownProps) {
    const { t } = useTranslation();

    const EXPORT_FORMATS = [
        { format: 'xlsx', label: t('export.xlsx'), icon: FileSpreadsheet },
        { format: 'docx', label: t('export.docx'), icon: FileText },
        { format: 'md', label: t('export.md'), icon: Hash },
    ] as const;

    function handleExport(format: string) {
        const url = exportMethod.url(convention.id, { query: { format } });
        window.open(url, '_self');
    }

    return (
        <DropdownMenu>
            <Tooltip>
                <TooltipTrigger asChild>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="outline"
                            size="sm"
                            className="cursor-pointer gap-1.5"
                        >
                            <Download className="size-4" />
                            {t('export.button')}
                        </Button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent>{t('export.tooltip')}</TooltipContent>
            </Tooltip>
            <DropdownMenuContent align="end">
                {EXPORT_FORMATS.map(({ format, label, icon: Icon }) => (
                    <DropdownMenuItem
                        key={format}
                        className="cursor-pointer gap-2"
                        onClick={() => handleExport(format)}
                    >
                        <Icon className="size-4" />
                        {label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
