import { OctagonAlert } from 'lucide-react';

import { Button } from '@/components/ui/button';
import type { Section } from '@/types/convention';

interface FullButtonProps {
    section: Section;
    onUpdate: () => void;
}

export default function FullButton({ section, onUpdate }: FullButtonProps) {
    const isAlreadyFull = section.occupancy === 100;

    return (
        <Button
            variant="destructive"
            size="lg"
            className="w-full cursor-pointer rounded-xl py-6 text-lg font-bold uppercase tracking-wider"
            disabled={isAlreadyFull}
            onClick={onUpdate}
        >
            <OctagonAlert className="size-6" />
            Full
        </Button>
    );
}
