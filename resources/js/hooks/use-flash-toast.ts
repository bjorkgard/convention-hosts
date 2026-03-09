import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

interface FlashProps {
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}

export function useFlashToast(): void {
    const { flash } = usePage<FlashProps>().props;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);
}
