/**
 * English status values sent by OpsController's circleMemberPayload(), mapped
 * to their Dutch i18n source labels (passed to t()).
 */
const CIRCLE_MEMBER_STATUS_LABELS: Record<string, string> = {
    Active: 'Actief',
    Reserved: 'Gereserveerd',
};

export function translateMemberStatus(
    status: string,
    t: (key: string) => string,
): string {
    return t(CIRCLE_MEMBER_STATUS_LABELS[status] ?? status);
}
