import { OctagonAlert } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import type { Section } from '@/types/convention';

interface FullButtonProps {
    section: Section;
    onUpdate: () => void;
}

export default function FullButton({ section, onUpdate }: FullButtonProps) {
    const { t } = useTranslation();
    const isAlreadyFull = section.occupancy === 100;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="destructive"
                    size="lg"
                    className="w-full cursor-pointer rounded-xl py-6 text-lg font-bold uppercase tracking-wider"
                    disabled={isAlreadyFull}
                    onClick={onUpdate}
                >
                    <OctagonAlert className="size-6" />
                    {t('section.full_button.label')}
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {isAlreadyFull ? t('section.full_button.already_full') : t('section.full_button.tooltip')}
            </TooltipContent>
        </Tooltip>
    );
}
