import { Eye, EyeOff } from 'lucide-react';
import type { ComponentProps } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { modalInputClassName } from '@/components/maison/modals/maison-modal';
import { cn } from '@/lib/utils';

type ModalPasswordInputProps = Omit<ComponentProps<'input'>, 'type'> & {
    inputClassName?: string;
};

export function ModalPasswordInput({
    className,
    inputClassName,
    ...props
}: ModalPasswordInputProps) {
    const { t } = useTranslation();
    const [showPassword, setShowPassword] = useState(false);

    return (
        <div className={cn('relative', className)}>
            <input
                type={showPassword ? 'text' : 'password'}
                className={cn(modalInputClassName, 'pr-12', inputClassName)}
                {...props}
            />
            <button
                type="button"
                onClick={() => setShowPassword((prev) => !prev)}
                className="absolute inset-y-0 right-0 flex items-center px-3.5 text-stone transition-colors hover:text-choc"
                aria-label={
                    showPassword ? t('Wachtwoord verbergen') : t('Wachtwoord tonen')
                }
                tabIndex={-1}
            >
                {showPassword ? (
                    <EyeOff className="size-4" aria-hidden />
                ) : (
                    <Eye className="size-4" aria-hidden />
                )}
            </button>
        </div>
    );
}
