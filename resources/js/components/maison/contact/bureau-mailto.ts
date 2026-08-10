/**
 * Builds a mailto link from a bureau form and navigates to it.
 *
 * Mirrors the prototype's `bureauMailto` helper: each filled field becomes a
 * `name: value` line in the body so the concierge receives structured context.
 */
export function submitBureauMailto(
    form: HTMLFormElement,
    subject: string,
    email = 'hello@maisonanversa.com',
): void {
    const lines: string[] = [];

    new FormData(form).forEach((value, key) => {
        if (value) {
            lines.push(`${key}: ${value}`);
        }
    });

    const body = encodeURIComponent(lines.join('\n'));
    window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${body}`;
}
