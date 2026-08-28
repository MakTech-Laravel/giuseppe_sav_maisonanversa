import Highlight from '@tiptap/extension-highlight';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { TableKit } from '@tiptap/extension-table/kit';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import { EditorContent, useEditor, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    Bold,
    Code2,
    Eraser,
    Eye,
    Heading2,
    Heading3,
    Heading4,
    Highlighter,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Strikethrough,
    Table,
    Underline as UnderlineIcon,
    Undo2,
} from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LegalHtmlSourceEditor } from '@/components/admin/legal-html-source-editor';
import { LegalHtml } from '@/components/maison/legal/legal-html';
import { Button } from '@/components/ui/button';
import { Toggle } from '@/components/ui/toggle';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    isBlankLegalHtml,
    sanitizeLegalHtml,
} from '@/lib/legal-html';
import { cn } from '@/lib/utils';

type LegalRichTextEditorProps = {
    value: string;
    onChange: (html: string) => void;
    error?: string;
};

function ToolbarButton({
    label,
    pressed,
    onClick,
    children,
}: {
    label: string;
    pressed?: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Toggle
                    size="sm"
                    pressed={pressed}
                    onPressedChange={() => onClick()}
                    aria-label={label}
                    className="size-8"
                >
                    {children}
                </Toggle>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

export function LegalRichTextEditor({
    value,
    onChange,
    error,
}: LegalRichTextEditorProps) {
    const { t } = useTranslation();
    const [htmlMode, setHtmlMode] = useState(false);
    const [preview, setPreview] = useState(false);
    const [htmlDraft, setHtmlDraft] = useState(value);
    const [htmlError, setHtmlError] = useState<string | null>(null);
    const lastEmitted = useRef(value);
    const editorRef = useRef<Editor | null>(null);

    const editor = useEditor({
        immediatelyRender: false,
        shouldRerenderOnTransaction: true,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
            }),
            Underline,
            Highlight,
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Link.configure({
                openOnClick: false,
                autolink: true,
                HTMLAttributes: {
                    rel: 'noopener noreferrer',
                },
            }),
            TableKit.configure({
                table: { resizable: false },
            }),
            Placeholder.configure({
                placeholder: t('Schrijf de juridische tekst…'),
            }),
        ],
        content: value,
        editorProps: {
            attributes: {
                class: 'legal-prose px-4 py-3',
            },
            handlePaste: (_view, event) => {
                const pasted = event.clipboardData?.getData('text/html');
                const instance = editorRef.current;

                if (!pasted || !instance) {
                    return false;
                }

                const sanitized = sanitizeLegalHtml(pasted);

                if (sanitized === '') {
                    return false;
                }

                instance.commands.insertContent(sanitized);
                return true;
            },
        },
        onUpdate: ({ editor: instance }) => {
            const html = sanitizeLegalHtml(instance.getHTML());
            lastEmitted.current = html;
            onChange(html);
        },
    });

    editorRef.current = editor;

    useEffect(() => {
        if (!editor || htmlMode) {
            return;
        }

        if (value === lastEmitted.current) {
            return;
        }

        lastEmitted.current = value;
        editor.commands.setContent(value, { emitUpdate: false });
    }, [editor, value, htmlMode]);

    function enterHtmlMode() {
        const html = editor ? sanitizeLegalHtml(editor.getHTML()) : value;
        setHtmlDraft(html);
        setHtmlError(null);
        setPreview(false);
        setHtmlMode(true);
    }

    function leaveHtmlMode() {
        const sanitized = sanitizeLegalHtml(htmlDraft);

        if (isBlankLegalHtml(sanitized)) {
            setHtmlError(
                t('De HTML is leeg of ongeldig na beveiligingscontrole.'),
            );
            return;
        }

        editor?.commands.setContent(sanitized, { emitUpdate: false });
        onChange(sanitized);
        setHtmlError(null);
        setHtmlMode(false);
    }

    function setLink() {
        if (!editor) {
            return;
        }

        const previous = editor.getAttributes('link').href as string | undefined;
        const next = window.prompt(t('Link-URL'), previous ?? 'https://');

        if (next === null) {
            return;
        }

        const trimmed = next.trim();

        if (trimmed === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }

        editor
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href: trimmed })
            .run();
    }

    return (
        <TooltipProvider>
            <div className="space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap items-center gap-1">
                        <Toggle
                            size="sm"
                            pressed={htmlMode}
                            onPressedChange={(next) => {
                                if (next) {
                                    enterHtmlMode();
                                    return;
                                }

                                leaveHtmlMode();
                            }}
                            aria-label={t('HTML')}
                        >
                            <Code2 className="size-3.5" />
                            {t('HTML')}
                        </Toggle>
                        <Toggle
                            size="sm"
                            pressed={preview}
                            onPressedChange={setPreview}
                            aria-label={t('Voorbeeld')}
                        >
                            <Eye className="size-3.5" />
                            {t('Voorbeeld')}
                        </Toggle>
                    </div>
                </div>

                {preview ? (
                    <div className="legal-editor legal-preview-surface rounded-md border p-4">
                        <LegalHtml html={htmlMode ? htmlDraft : value} />
                    </div>
                ) : htmlMode ? (
                    <div
                        className={cn(
                            'legal-editor rounded-md border p-3',
                            error && 'border-destructive',
                        )}
                    >
                        <LegalHtmlSourceEditor
                            value={htmlDraft}
                            onChange={(next) => {
                                setHtmlDraft(next);
                                setHtmlError(null);
                                onChange(next);
                            }}
                            error={htmlError}
                        />
                    </div>
                ) : (
                    <div
                        className={cn(
                            'legal-editor overflow-hidden rounded-md border',
                            error && 'border-destructive',
                        )}
                    >
                        {editor ? (
                            <>
                                <div className="flex flex-wrap gap-0.5 border-b bg-muted/30 p-1.5">
                                    <ToolbarButton
                                        label={t('Ongedaan maken')}
                                        onClick={() =>
                                            editor.chain().focus().undo().run()
                                        }
                                    >
                                        <Undo2 />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Opnieuw')}
                                        onClick={() =>
                                            editor.chain().focus().redo().run()
                                        }
                                    >
                                        <Redo2 />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Kop 2')}
                                        pressed={editor.isActive('heading', {
                                            level: 2,
                                        })}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleHeading({ level: 2 })
                                                .run()
                                        }
                                    >
                                        <Heading2 />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Kop 3')}
                                        pressed={editor.isActive('heading', {
                                            level: 3,
                                        })}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleHeading({ level: 3 })
                                                .run()
                                        }
                                    >
                                        <Heading3 />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Kop 4')}
                                        pressed={editor.isActive('heading', {
                                            level: 4,
                                        })}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleHeading({ level: 4 })
                                                .run()
                                        }
                                    >
                                        <Heading4 />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Vet')}
                                        pressed={editor.isActive('bold')}
                                        onClick={() =>
                                            editor.chain().focus().toggleBold().run()
                                        }
                                    >
                                        <Bold />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Cursief')}
                                        pressed={editor.isActive('italic')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleItalic()
                                                .run()
                                        }
                                    >
                                        <Italic />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Onderstrepen')}
                                        pressed={editor.isActive('underline')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleUnderline()
                                                .run()
                                        }
                                    >
                                        <UnderlineIcon />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Doorhalen')}
                                        pressed={editor.isActive('strike')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleStrike()
                                                .run()
                                        }
                                    >
                                        <Strikethrough />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Markeren')}
                                        pressed={editor.isActive('highlight')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleHighlight()
                                                .run()
                                        }
                                    >
                                        <Highlighter />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Lijst')}
                                        pressed={editor.isActive('bulletList')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleBulletList()
                                                .run()
                                        }
                                    >
                                        <List />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Genummerde lijst')}
                                        pressed={editor.isActive('orderedList')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleOrderedList()
                                                .run()
                                        }
                                    >
                                        <ListOrdered />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Citaat')}
                                        pressed={editor.isActive('blockquote')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleBlockquote()
                                                .run()
                                        }
                                    >
                                        <Quote />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Scheidingslijn')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .setHorizontalRule()
                                                .run()
                                        }
                                    >
                                        <Minus />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Link')}
                                        pressed={editor.isActive('link')}
                                        onClick={setLink}
                                    >
                                        <LinkIcon />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Tabel')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .insertTable({
                                                    rows: 3,
                                                    cols: 3,
                                                    withHeaderRow: true,
                                                })
                                                .run()
                                        }
                                    >
                                        <Table />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Links uitlijnen')}
                                        pressed={editor.isActive({
                                            textAlign: 'left',
                                        })}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .setTextAlign('left')
                                                .run()
                                        }
                                    >
                                        <AlignLeft />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Centreren')}
                                        pressed={editor.isActive({
                                            textAlign: 'center',
                                        })}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .setTextAlign('center')
                                                .run()
                                        }
                                    >
                                        <AlignCenter />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Rechts uitlijnen')}
                                        pressed={editor.isActive({
                                            textAlign: 'right',
                                        })}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .setTextAlign('right')
                                                .run()
                                        }
                                    >
                                        <AlignRight />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Uitvullen')}
                                        pressed={editor.isActive({
                                            textAlign: 'justify',
                                        })}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .setTextAlign('justify')
                                                .run()
                                        }
                                    >
                                        <AlignJustify />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Opmaak wissen')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .unsetAllMarks()
                                                .clearNodes()
                                                .run()
                                        }
                                    >
                                        <Eraser />
                                    </ToolbarButton>
                                </div>
                                <EditorContent editor={editor} />
                            </>
                        ) : (
                            <div className="min-h-88 animate-pulse bg-muted/40" />
                        )}
                    </div>
                )}
            </div>
        </TooltipProvider>
    );
}
