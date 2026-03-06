<?php

namespace App\Concerns;

trait SanitizesInput
{
    /**
     * Fields that should only be trimmed (not have HTML stripped).
     * These fields may contain rich text content.
     *
     * Override in the Form Request to customize.
     *
     * @return array<int, string>
     */
    protected function richTextFields(): array
    {
        return [];
    }

    /**
     * Fields that should be excluded from any sanitization.
     * Useful for passwords, tokens, etc.
     *
     * Override in the Form Request to customize.
     *
     * @return array<int, string>
     */
    protected function excludedFromSanitization(): array
    {
        return [
            'password',
            'password_confirmation',
            'current_password',
        ];
    }

    /**
     * Sanitize input data before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeInputs();
    }

    /**
     * Sanitize all string inputs in the request.
     */
    protected function sanitizeInputs(): void
    {
        $excluded = $this->excludedFromSanitization();
        $richText = $this->richTextFields();
        $sanitized = [];

        foreach ($this->all() as $key => $value) {
            if (in_array($key, $excluded, true)) {
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if (in_array($key, $richText, true)) {
                $sanitized[$key] = $this->sanitizeRichText($value);
            } else {
                $sanitized[$key] = $this->sanitizeString($value);
            }
        }

        if (! empty($sanitized)) {
            $this->merge($sanitized);
        }
    }

    /**
     * Sanitize a plain string: trim whitespace and strip all HTML tags.
     */
    protected function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    /**
     * Sanitize rich text: trim whitespace and strip dangerous tags/attributes
     * while preserving basic formatting tags.
     */
    protected function sanitizeRichText(string $value): string
    {
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';

        $value = trim(strip_tags($value, $allowedTags));

        // Remove any event handler attributes (onclick, onerror, etc.)
        $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);

        // Remove javascript: protocol from href attributes
        $value = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $value);

        return $value;
    }
}
