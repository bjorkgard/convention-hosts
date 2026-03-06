import { Download, FileSpreadsheet, FileText, Hash } from 'lucide-react';

import { exportMethod } from '@/actions/App/Http/Controllers/ConventionController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Convention } from '@/types/convention';

const EXPORT_FORMATS = [
    { format: 'xlsx', label: '.xlsx (Excel)', icon: FileSpreadsheet },
    { format: 'docx', label: '.docx (Word)', icon: FileText },
    { format: 'md', label: 'Markdown', icon: Hash },
] as const;

interface ExportDropdownProps {
    convention: Convention;
}

export default function ExportDropdown({ convention }: ExportDropdownProps) {
    function handleExport(format: string) {
        const url = exportMethod.url(convention.id, { query: { format } });
        window.open(url, '_self');
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className="cursor-pointer gap-1.5">
                    <Download className="size-4" />
                    Export
                </Button>
            </DropdownMenuTrigger>
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
