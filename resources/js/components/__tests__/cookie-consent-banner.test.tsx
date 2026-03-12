import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

vi.mock('../authenticated-consent-prompt', () => ({
    default: () => <div data-testid="authenticated-consent-prompt" />,
}));

import CookieConsentBanner from '../cookie-consent-banner';

describe('CookieConsentBanner', () => {
    it('delegates to the authenticated consent prompt', () => {
        render(<CookieConsentBanner />);
        expect(screen.getByTestId('authenticated-consent-prompt')).toBeInTheDocument();
    });
});
