import { ImagePlus, SendHorizontal, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent, KeyboardEvent } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Attachment,
    AttachmentAction,
    AttachmentActions,
    AttachmentGroup,
    AttachmentMedia,
} from '@/components/ui/attachment';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupTextarea,
} from '@/components/ui/input-group';
import { cn } from '@/lib/utils';

type ChatComposerProps = {
    onSend: (body: string, files: File[]) => void;
    disabled?: boolean;
    placeholder?: string;
    maxImages?: number;
    className?: string;
};

type PendingImage = {
    id: string;
    file: File;
    previewUrl: string;
};

/**
 * Text plus image attachments. Enter sends, Shift+Enter starts a new line.
 */
export function ChatComposer({
    onSend,
    disabled = false,
    placeholder,
    maxImages = 4,
    className,
}: ChatComposerProps) {
    const { t } = useTranslation();
    const [body, setBody] = useState('');
    const [images, setImages] = useState<PendingImage[]>([]);
    const fileInput = useRef<HTMLInputElement>(null);
    const latestImages = useRef<PendingImage[]>([]);

    useEffect(() => {
        latestImages.current = images;
    }, [images]);

    useEffect(
        () => () =>
            latestImages.current.forEach((image) =>
                URL.revokeObjectURL(image.previewUrl),
            ),
        [],
    );

    const canSend = !disabled && (body.trim() !== '' || images.length > 0);
    const canAddImages = !disabled && images.length < maxImages;

    function addFiles(files: FileList | null) {
        if (!files) {
            return;
        }

        const incoming = Array.from(files)
            .filter((file) => file.type.startsWith('image/'))
            .slice(0, maxImages - images.length)
            .map((file) => ({
                id: `${file.name}-${file.lastModified}-${Math.random().toString(36).slice(2)}`,
                file,
                previewUrl: URL.createObjectURL(file),
            }));

        setImages((current) => [...current, ...incoming]);
    }

    function removeImage(id: string) {
        const target = images.find((image) => image.id === id);

        if (target) {
            URL.revokeObjectURL(target.previewUrl);
        }

        setImages(images.filter((image) => image.id !== id));
    }

    function send() {
        if (!canSend) {
            return;
        }

        onSend(
            body.trim(),
            images.map((image) => image.file),
        );

        images.forEach((image) => URL.revokeObjectURL(image.previewUrl));
        setBody('');
        setImages([]);
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        send();
    }

    function handleKeyDown(event: KeyboardEvent<HTMLTextAreaElement>) {
        if (
            event.key === 'Enter' &&
            !event.shiftKey &&
            !event.nativeEvent.isComposing
        ) {
            event.preventDefault();
            send();
        }
    }

    return (
        <form
            onSubmit={handleSubmit}
            className={cn(
                'border-t border-gold/20 bg-choc3 px-4 py-4 md:px-6',
                className,
            )}
        >
            <input
                ref={fileInput}
                type="file"
                accept="image/*"
                multiple
                hidden
                onChange={(event) => {
                    addFiles(event.target.files);
                    event.target.value = '';
                }}
            />

            <InputGroup
                data-disabled={disabled}
                className="rounded-none border-gold/25 bg-choc2 shadow-none has-[[data-slot=input-group-control]:focus-visible]:border-gold has-[[data-slot=input-group-control]:focus-visible]:ring-0"
            >
                {images.length > 0 && (
                    <InputGroupAddon align="block-start" className="pb-0">
                        <AttachmentGroup>
                            {images.map((image) => (
                                <Attachment
                                    key={image.id}
                                    orientation="vertical"
                                    size="xs"
                                    className="w-20 rounded-none border-gold/25 bg-choc3"
                                >
                                    <AttachmentMedia
                                        variant="image"
                                        className="rounded-none"
                                    >
                                        <img
                                            src={image.previewUrl}
                                            alt={image.file.name}
                                        />
                                    </AttachmentMedia>
                                    <AttachmentActions className="top-1.5 right-1.5">
                                        <AttachmentAction
                                            aria-label={t(
                                                'Afbeelding verwijderen',
                                            )}
                                            onClick={() =>
                                                removeImage(image.id)
                                            }
                                            className="rounded-none bg-choc/90 text-cream hover:bg-choc hover:text-gold"
                                        >
                                            <X />
                                        </AttachmentAction>
                                    </AttachmentActions>
                                </Attachment>
                            ))}
                        </AttachmentGroup>
                    </InputGroupAddon>
                )}

                <InputGroupTextarea
                    value={body}
                    onChange={(event) => setBody(event.target.value)}
                    onKeyDown={handleKeyDown}
                    disabled={disabled}
                    rows={1}
                    placeholder={placeholder ?? t('Schrijf een bericht...')}
                    aria-label={t('Chatbericht')}
                    className="max-h-40 min-h-12 px-4 font-sans text-[14px] text-cream placeholder:text-sand"
                />

                <InputGroupAddon
                    align="block-end"
                    className="justify-between border-t border-gold/15 pt-2"
                >
                    <InputGroupButton
                        size="icon-sm"
                        aria-label={t('Afbeelding toevoegen')}
                        title={t('Afbeelding toevoegen')}
                        disabled={!canAddImages}
                        onClick={() => fileInput.current?.click()}
                        className="rounded-none text-cream hover:bg-gold/10 hover:text-gold"
                    >
                        <ImagePlus />
                    </InputGroupButton>

                    <InputGroupButton
                        type="submit"
                        size="sm"
                        variant="default"
                        disabled={!canSend}
                        className="rounded-none bg-gold px-4 font-sans text-[10px] tracking-[0.2em] text-choc uppercase hover:bg-gold2"
                    >
                        {t('Versturen')}
                        <SendHorizontal aria-hidden="true" />
                    </InputGroupButton>
                </InputGroupAddon>
            </InputGroup>
        </form>
    );
}
