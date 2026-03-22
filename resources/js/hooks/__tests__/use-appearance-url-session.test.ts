import { renderHook } from '@testing-library/react';
import * as fc from 'fast-check';
import { describe, expect, it, vi } from 'vitest';

import type { UrlSession } from '@/types';

// Mock dependencies
const mockProps = vi.fn<
    () => { urlSession?: UrlSession | null; consent?: unknown }
>(() => ({}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: mockProps() }),
}));

vi.mock('@/hooks/use-consent', () => ({
    useConsent: () => ({ state: 'accepted', allowOptionalStorage: true }),
}));

vi.mock('@/lib/consent/optional-storage', () => ({
    SAFE_APPEARANCE: 'system',
    isOptionalStorageAllowed: () => true,
    readOptionalLocalStorage: () => 'system',
    removeOptionalCookie: vi.fn(),
    removeOptionalLocalStorage: vi.fn(),
    writeOptionalCookie: vi.fn(),
    writeOptionalLocalStorage: vi.fn(),
}));

import { useAppearance } from '../use-appearance';

// Mock matchMedia for jsdom
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});

function setPageProps(props: { urlSession?: UrlSession | null }) {
    mockProps.mockReturnValue(props);
}

describe('useAppearance — URL session override', () => {
    describe('Property 11: URL session Apple theme', () => {
        it('forces light appearance for any URL session state', () => {
            const urlSessionArb = fc.record({
                convention_id: fc.uuid(),
                type: fc.constant('section' as const),
            });

            fc.assert(
                fc.property(urlSessionArb, (urlSession) => {
                    setPageProps({ urlSession });
                    const { result } = renderHook(() => useAppearance());

                    // When URL session is active, appearance must resolve to light
                    expect(result.current.resolvedAppearance).toBe('light');
                    expect(result.current.appearance).toBe('light');
                }),
                { numRuns: 100 },
            );
        });

        it('updateAppearance is a no-op when URL session is active', () => {
            setPageProps({
                urlSession: { convention_id: 'test-id', type: 'section' },
            });
            const { result } = renderHook(() => useAppearance());

            // Calling updateAppearance should not change the resolved appearance
            result.current.updateAppearance('dark');
            expect(result.current.resolvedAppearance).toBe('light');
        });
    });

    it('allows normal appearance when no URL session', () => {
        setPageProps({ urlSession: null });
        const { result } = renderHook(() => useAppearance());

        // Without URL session, the hook should return the default (system)
        expect(result.current.appearance).toBe('system');
    });
});
