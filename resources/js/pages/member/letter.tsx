import { Head } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';
import { Button } from '@/components/ui/button';

type Preferences = {
    heritageLetter: boolean;
    productUpdates: boolean;
    events: boolean;
};

export default function MemberLetter({
    preferences,
}: {
    preferences: Preferences;
}) {
    const [data, setData] = useState(preferences);
    const [saved, setSaved] = useState(false);

    function onSubmit(event: FormEvent) {
        event.preventDefault();
        setSaved(true);
    }

    return (
        <>
            <Head title="Heritage Letter" />
            <MemberPageHeader
                eyebrow="Correspondence"
                title="Heritage Letter preferences"
                description="Choose what reaches you. Changes are prototype-only until the list provider is connected."
            />

            <MemberPanel className="max-w-xl">
                <form onSubmit={onSubmit} className="space-y-5">
                    {(
                        [
                            ['heritageLetter', 'Heritage Letter'],
                            ['productUpdates', 'Product updates'],
                            ['events', 'Sessions & events'],
                        ] as const
                    ).map(([key, label]) => (
                        <label
                            key={key}
                            className="flex cursor-pointer items-center gap-3"
                        >
                            <input
                                type="checkbox"
                                checked={data[key]}
                                onChange={(event) => {
                                    setSaved(false);
                                    setData((current) => ({
                                        ...current,
                                        [key]: event.target.checked,
                                    }));
                                }}
                                className="size-4 accent-gold"
                            />
                            <span className="font-sans text-[12px] tracking-[0.14em] text-cream uppercase">
                                {label}
                            </span>
                        </label>
                    ))}

                    <Button type="submit" className="mt-4">
                        Save preferences
                    </Button>

                    {saved && (
                        <p className="font-sans text-[11px] tracking-[0.12em] text-gold2 uppercase">
                            Preferences noted for this session (prototype)
                        </p>
                    )}
                </form>
            </MemberPanel>
        </>
    );
}
