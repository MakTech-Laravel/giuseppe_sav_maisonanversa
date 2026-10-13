import { getXsrfToken } from '@/echo';

export type JsonResult = {
    ok: boolean;
    status: number;
    json: Record<string, unknown>;
};

/** Diagnostic JSON call that keeps the raw status for the console panels. */
export async function callJson(
    url: string,
    method: 'get' | 'post',
    body?: Record<string, unknown>,
): Promise<JsonResult> {
    const response = await fetch(url, {
        method: method.toUpperCase(),
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrfToken(),
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined,
    });

    const json = (await response.json().catch(() => ({}))) as Record<
        string,
        unknown
    >;

    return { ok: response.ok, status: response.status, json };
}
