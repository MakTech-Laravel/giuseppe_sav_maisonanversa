import Highlight from '@tiptap/extension-highlight';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { TableKit } from '@tiptap/extension-table/kit';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyleKit } from '@tiptap/extension-text-style';
import Underline from '@tiptap/extension-underline';
import { EditorContent, useEditor, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    AlignCenter,
    AlignJustify,
    AlignLeft,
    AlignRight,
    ALargeSmall,
    AlignVerticalSpaceAround,
    Bold,
    Code2,
    ChevronsDownUp,
    Columns3,
    Eraser,
    Eye,
    Heading,
    Highlighter,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Minus,
    PaintBucket,
    Palette,
    Quote,
    Redo2,
    Rows3,
    Strikethrough,
    Subscript as SubscriptIcon,
    Superscript as SuperscriptIcon,
    Table,
    Trash2,
    Type,
    Underline as UnderlineIcon,
    Undo2,
} from 'lucide-react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { LegalHtmlSourceEditor } from '@/components/admin/legal-html-source-editor';
import {
    LegalDetails,
    LegalSummary,
} from '@/components/admin/legal-details-extension';
import { LegalHtml } from '@/components/maison/legal/legal-html';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
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

const HEADING_LEVELS = [1, 2, 3, 4, 5, 6] as const;

const FONT_FAMILIES = [
    { value: 'Montserrat, sans-serif', name: 'Montserrat' },
    { value: 'Baskervville, Georgia, serif', name: 'Baskervville' },
    { value: 'Georgia, serif', name: 'Georgia' },
] as const;

const FONT_SIZES = ['12px', '14px', '16px', '18px', '20px', '24px', '28px'] as const;

const LINE_HEIGHTS = ['1.2', '1.5', '1.85', '2'] as const;

const TEXT_COLORS = [
    '#291c18',
    '#41332d',
    '#8d705a',
    '#745a48',
    '#8a7d72',
    '#000000',
    '#b42318',
    '#027a48',
] as const;

const FILL_COLORS = [
    '#f3ebe3',
    '#e8e0d5',
    '#b8a898',
    '#8d705a',
    '#fff3bf',
    '#d1fadf',
    '#dbeafe',
    '#ffffff',
] as const;

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

function ToolbarDivider() {
    return (
        <div
            className="mx-0.5 hidden h-6 w-px shrink-0 bg-border sm:block"
            aria-hidden
        />
    );
}

function ToolbarChoicePopover({
    label,
    icon,
    value,
    defaultValue,
    options,
    onSelect,
}: {
    label: string;
    icon: ReactNode;
    value: string;
    defaultValue: string;
    options: { value: string; label: string }[];
    onSelect: (value: string) => void;
}) {
    const active = value !== defaultValue;
    const currentLabel =
        options.find((option) => option.value === value)?.label ?? label;

    return (
        <Popover>
            <Tooltip>
                <TooltipTrigger asChild>
                    <PopoverTrigger asChild>
                        <Toggle
                            size="sm"
                            pressed={active}
                            aria-label={label}
                            className="size-8"
                        >
                            {icon}
                        </Toggle>
                    </PopoverTrigger>
                </TooltipTrigger>
                <TooltipContent>
                    {active ? `${label}: ${currentLabel}` : label}
                </TooltipContent>
            </Tooltip>
            <PopoverContent className="w-52 p-2">
                <p className="px-2 pb-1 text-xs font-medium text-choc">
                    {label}
                </p>
                <div className="flex flex-col gap-0.5">
                    {options.map((option) => (
                        <Button
                            key={option.value}
                            type="button"
                            variant={
                                option.value === value ? 'secondary' : 'ghost'
                            }
                            size="sm"
                            className="h-8 w-full justify-start px-2 text-sm"
                            onClick={() => onSelect(option.value)}
                        >
                            {option.label}
                        </Button>
                    ))}
                </div>
            </PopoverContent>
        </Popover>
    );
}

function ColorPopover({
    label,
    value,
    swatches,
    onChange,
    onClear,
    children,
}: {
    label: string;
    value?: string;
    swatches: readonly string[];
    onChange: (color: string) => void;
    onClear: () => void;
    children: ReactNode;
}) {
    const { t } = useTranslation();
    const pickerValue =
        value && /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i.test(value)
            ? value
            : '#291c18';

    return (
        <Popover>
            <Tooltip>
                <TooltipTrigger asChild>
                    <PopoverTrigger asChild>
                        <Toggle
                            size="sm"
                            pressed={Boolean(value)}
                            aria-label={label}
                            className="size-8"
                        >
                            {children}
                        </Toggle>
                    </PopoverTrigger>
                </TooltipTrigger>
                <TooltipContent>{label}</TooltipContent>
            </Tooltip>
            <PopoverContent className="w-52 p-3">
                <p className="mb-2 text-xs font-medium text-choc">{label}</p>
                <div className="grid grid-cols-4 gap-1.5">
                    {swatches.map((color) => (
                        <button
                            key={color}
                            type="button"
                            aria-label={color}
                            className={cn(
                                'size-7 rounded-sm border border-gold/30',
                                value === color && 'ring-2 ring-gold',
                            )}
                            style={{ backgroundColor: color }}
                            onClick={() => onChange(color)}
                        />
                    ))}
                </div>
                <label className="mt-2 flex items-center gap-2 text-xs text-stone">
                    <input
                        type="color"
                        value={pickerValue}
                        onChange={(event) => onChange(event.target.value)}
                        className="h-7 w-10 cursor-pointer rounded-sm border border-gold/30 bg-transparent p-0"
                    />
                    {t('Aangepaste kleur')}
                </label>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="mt-2 w-full"
                    onClick={onClear}
                >
                    {t('Kleur wissen')}
                </Button>
            </PopoverContent>
        </Popover>
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
                heading: { levels: [1, 2, 3, 4, 5, 6] },
            }),
            Underline,
            Highlight.configure({ multicolor: true }),
            Subscript,
            Superscript,
            TextStyleKit,
            LegalDetails,
            LegalSummary,
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

    const textStyle = editor?.getAttributes('textStyle') ?? {};
    const highlightColor = editor?.getAttributes('highlight').color as
        | string
        | undefined;
    const headingValue = HEADING_LEVELS.find((level) =>
        editor?.isActive('heading', { level }),
    );

    function applyHeading(next: string) {
        if (!editor) {
            return;
        }

        if (next === 'p') {
            editor.chain().focus().setParagraph().run();
            return;
        }

        const level = Number(next) as (typeof HEADING_LEVELS)[number];

        editor.chain().focus().setHeading({ level }).run();
    }

    const headingOptions = [
        { value: 'p', label: t('Paragraaf') },
        ...HEADING_LEVELS.map((level) => ({
            value: String(level),
            label: t(`Kop ${level}`),
        })),
    ];

    const fontFamilyOptions = [
        { value: '__default', label: t('Standaard') },
        ...FONT_FAMILIES.map((family) => ({
            value: family.value,
            label: family.name,
        })),
    ];

    const fontSizeOptions = [
        { value: '__default', label: t('Standaard') },
        ...FONT_SIZES.map((size) => ({ value: size, label: size })),
    ];

    const lineHeightOptions = [
        { value: '__default', label: t('Standaard') },
        ...LINE_HEIGHTS.map((height) => ({ value: height, label: height })),
    ];

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
                                <div className="flex flex-wrap items-center gap-0.5 border-b bg-muted/30 p-1.5">
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
                                    <ToolbarDivider />
                                    <ToolbarChoicePopover
                                        label={t('Kop')}
                                        icon={<Heading className="size-4" />}
                                        value={
                                            headingValue
                                                ? String(headingValue)
                                                : 'p'
                                        }
                                        defaultValue="p"
                                        options={headingOptions}
                                        onSelect={applyHeading}
                                    />
                                    <ToolbarChoicePopover
                                        label={t('Lettertype')}
                                        icon={<Type className="size-4" />}
                                        value={
                                            (textStyle.fontFamily as
                                                | string
                                                | undefined) ?? '__default'
                                        }
                                        defaultValue="__default"
                                        options={fontFamilyOptions}
                                        onSelect={(next) => {
                                            if (next === '__default') {
                                                editor
                                                    .chain()
                                                    .focus()
                                                    .unsetFontFamily()
                                                    .run();
                                                return;
                                            }

                                            editor
                                                .chain()
                                                .focus()
                                                .setFontFamily(next)
                                                .run();
                                        }}
                                    />
                                    <ToolbarChoicePopover
                                        label={t('Tekstgrootte')}
                                        icon={<ALargeSmall className="size-4" />}
                                        value={
                                            (textStyle.fontSize as
                                                | string
                                                | undefined) ?? '__default'
                                        }
                                        defaultValue="__default"
                                        options={fontSizeOptions}
                                        onSelect={(next) => {
                                            if (next === '__default') {
                                                editor
                                                    .chain()
                                                    .focus()
                                                    .unsetFontSize()
                                                    .run();
                                                return;
                                            }

                                            editor
                                                .chain()
                                                .focus()
                                                .setFontSize(next)
                                                .run();
                                        }}
                                    />
                                    <ToolbarChoicePopover
                                        label={t('Regelafstand')}
                                        icon={
                                            <AlignVerticalSpaceAround className="size-4" />
                                        }
                                        value={
                                            (textStyle.lineHeight as
                                                | string
                                                | undefined) ?? '__default'
                                        }
                                        defaultValue="__default"
                                        options={lineHeightOptions}
                                        onSelect={(next) => {
                                            if (next === '__default') {
                                                editor
                                                    .chain()
                                                    .focus()
                                                    .unsetLineHeight()
                                                    .run();
                                                return;
                                            }

                                            editor
                                                .chain()
                                                .focus()
                                                .setLineHeight(next)
                                                .run();
                                        }}
                                    />
                                    <ToolbarDivider />
                                    <ColorPopover
                                        label={t('Tekstkleur')}
                                        value={textStyle.color as string | undefined}
                                        swatches={TEXT_COLORS}
                                        onChange={(color) =>
                                            editor.chain().focus().setColor(color).run()
                                        }
                                        onClear={() =>
                                            editor.chain().focus().unsetColor().run()
                                        }
                                    >
                                        <Palette />
                                    </ColorPopover>
                                    <ColorPopover
                                        label={t('Achtergrondkleur')}
                                        value={
                                            textStyle.backgroundColor as string | undefined
                                        }
                                        swatches={FILL_COLORS}
                                        onChange={(color) =>
                                            editor
                                                .chain()
                                                .focus()
                                                .setBackgroundColor(color)
                                                .run()
                                        }
                                        onClear={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .unsetBackgroundColor()
                                                .run()
                                        }
                                    >
                                        <PaintBucket />
                                    </ColorPopover>
                                    <ToolbarDivider />
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
                                        label={t('Onderschrift')}
                                        pressed={editor.isActive('subscript')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleSubscript()
                                                .run()
                                        }
                                    >
                                        <SubscriptIcon />
                                    </ToolbarButton>
                                    <ToolbarButton
                                        label={t('Bovenschrift')}
                                        pressed={editor.isActive('superscript')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .toggleSuperscript()
                                                .run()
                                        }
                                    >
                                        <SuperscriptIcon />
                                    </ToolbarButton>
                                    <ColorPopover
                                        label={t('Markeren')}
                                        value={highlightColor}
                                        swatches={FILL_COLORS}
                                        onChange={(color) =>
                                            editor
                                                .chain()
                                                .focus()
                                                .setHighlight({ color })
                                                .run()
                                        }
                                        onClear={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .unsetHighlight()
                                                .run()
                                        }
                                    >
                                        <Highlighter />
                                    </ColorPopover>
                                    <ToolbarDivider />
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
                                        label={t('Uitklapbaar')}
                                        pressed={editor.isActive('legalDetails')}
                                        onClick={() =>
                                            editor
                                                .chain()
                                                .focus()
                                                .insertContent({
                                                    type: 'legalDetails',
                                                    content: [
                                                        {
                                                            type: 'legalSummary',
                                                            content: [
                                                                {
                                                                    type: 'text',
                                                                    text: t('Titel'),
                                                                },
                                                            ],
                                                        },
                                                        { type: 'paragraph' },
                                                    ],
                                                })
                                                .run()
                                        }
                                    >
                                        <ChevronsDownUp />
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
                                    {editor.isActive('table') ? (
                                        <>
                                            <ToolbarButton
                                                label={t('Rij toevoegen')}
                                                onClick={() =>
                                                    editor
                                                        .chain()
                                                        .focus()
                                                        .addRowAfter()
                                                        .run()
                                                }
                                            >
                                                <Rows3 />
                                            </ToolbarButton>
                                            <ToolbarButton
                                                label={t('Kolom toevoegen')}
                                                onClick={() =>
                                                    editor
                                                        .chain()
                                                        .focus()
                                                        .addColumnAfter()
                                                        .run()
                                                }
                                            >
                                                <Columns3 />
                                            </ToolbarButton>
                                            <ToolbarButton
                                                label={t('Tabel verwijderen')}
                                                onClick={() =>
                                                    editor
                                                        .chain()
                                                        .focus()
                                                        .deleteTable()
                                                        .run()
                                                }
                                            >
                                                <Trash2 />
                                            </ToolbarButton>
                                        </>
                                    ) : null}
                                    <ToolbarDivider />
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
