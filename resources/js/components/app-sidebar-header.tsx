import { Breadcrumbs } from '@/components/breadcrumbs';
import { LanguageSwitcher } from '@/components/maison/shell/language-switcher';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex w-full items-center justify-between gap-3">
                <div className="flex min-w-0 items-center gap-2">
                    <SidebarTrigger className="-ml-1" />
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
                <LanguageSwitcher className="shrink-0 gap-1 [&_button]:min-h-8 [&_button]:min-w-8 [&_button]:border-border [&_button]:px-1.5 [&_button]:py-1 [&_button]:text-[9px] [&_button]:text-muted-foreground [&_button:hover]:border-primary/50 [&_button:hover]:text-foreground [&_button[aria-current=true]]:border-primary [&_button[aria-current=true]]:bg-primary/15 [&_button[aria-current=true]]:text-primary" />
            </div>
        </header>
    );
}
