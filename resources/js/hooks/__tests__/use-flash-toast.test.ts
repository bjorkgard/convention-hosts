import { renderHook } from '@testing-library/react';
import { toast } from 'sonner';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('sonner', () => ({
    toast: {
        success: vi.fn(),
        error: vi.fn(),
    },
}));

vi.mock('@inertiajs/react', () => ({
    usePage: vi.fn(),
}));

import { usePage } from '@inertiajs/react';
import { useFlashToast } from '@/hooks/use-flash-toast';

describe('useFlashToast', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('calls toast.success when flash.success is set', () => {
        vi.mocked(usePage).mockReturnValue({
            props: { flash: { success: 'Occupancy updated.', error: null } },
        } as ReturnType<typeof usePage>);

        renderHook(() => useFlashToast());

        expect(toast.success).toHaveBeenCalledWith('Occupancy updated.');
        expect(toast.error).not.toHaveBeenCalled();
    });

    it('calls toast.error when flash.error is set', () => {
        vi.mocked(usePage).mockReturnValue({
            props: { flash: { success: null, error: 'Something went wrong.' } },
        } as ReturnType<typeof usePage>);

        renderHook(() => useFlashToast());

        expect(toast.error).toHaveBeenCalledWith('Something went wrong.');
        expect(toast.success).not.toHaveBeenCalled();
    });

    it('does not call toast when flash is empty', () => {
        vi.mocked(usePage).mockReturnValue({
            props: { flash: { success: null, error: null } },
        } as ReturnType<typeof usePage>);

        renderHook(() => useFlashToast());

        expect(toast.success).not.toHaveBeenCalled();
        expect(toast.error).not.toHaveBeenCalled();
    });
});
