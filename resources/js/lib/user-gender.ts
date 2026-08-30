export type UserGenderOption = {
    value: 'male' | 'female' | 'mixed';
    label: string;
};

/**
 * English DB values with Dutch i18n source labels (passed to t()).
 * Keep in sync with App\Enums\UserGender.
 */
export const USER_GENDER_OPTIONS: UserGenderOption[] = [
    { value: 'male', label: 'Man' },
    { value: 'female', label: 'Vrouw' },
    { value: 'mixed', label: 'Gemengd' },
];
