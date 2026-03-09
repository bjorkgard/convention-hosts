import { useCookieConsent } from '@/hooks/use-cookie-consent';

export default function CookieConsentBanner() {
    const { pending, accept, decline } = useCookieConsent();

    if (!pending) return null;

    return (
        <div
            role="region"
            aria-label="Cookie consent"
            className="fixed bottom-0 left-0 right-0 z-50 border-t border-border bg-card p-4 shadow-lg md:p-6"
        >
            <div className="mx-auto flex max-w-5xl flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div className="space-y-3 text-sm">
                    <p className="font-semibold text-foreground">We use cookies</p>
                    <p className="text-muted-foreground">
                        <span className="font-medium text-foreground">Essential cookies</span>{' '}
                        are always on: they keep you logged in and protect against cross-site
                        attacks. These are required to use the app.
                    </p>
                    <p className="text-muted-foreground">
                        <span className="font-medium text-foreground">Preference cookies</span>{' '}
                        are optional: they remember your colour theme, light/dark mode, and
                        sidebar state between visits.
                    </p>
                </div>
                <div className="flex shrink-0 gap-2">
                    <button
                        type="button"
                        onClick={decline}
                        className="rounded-md border border-border bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                    >
                        Decline
                    </button>
                    <button
                        type="button"
                        onClick={accept}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Accept all
                    </button>
                </div>
            </div>
        </div>
    );
}
