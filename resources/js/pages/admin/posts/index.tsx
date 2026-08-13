import { Head, Link, router } from '@inertiajs/react';
import { Eye, FileText, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DataPagination } from '@/components/admin/data-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermission } from '@/hooks/use-permissions';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import posts from '@/routes/admin/posts';
import type { Paginated } from '@/types/admin';
import { PERMISSIONS } from '@/types/permissions';

interface PostListItem {
    id: number;
    title: string;
    attachments_count: number;
    created_at: string;
}

export default function PostsIndex({
    posts: paginated,
    filters,
}: {
    posts: Paginated<PostListItem>;
    filters: { search: string };
}) {
    const { can } = usePermission();
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                posts.index(wayfinderLocale()).url,
                { search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <>
            <Head title="Posts" />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Posts"
                    description="Manage journal and editorial content."
                    icon={FileText}
                >
                    {can(PERMISSIONS.POSTS.CREATE) && (
                        <Button asChild>
                            <Link href={posts.create(wayfinderLocale())}>
                                <Plus className="h-4 w-4" /> Add post
                            </Link>
                        </Button>
                    )}
                </AdminPageHeader>
                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search posts…"
                        className="pl-9"
                    />
                </div>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>Title</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Attachments
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.map((post) => (
                                <TableRow key={post.id}>
                                    <TableCell className="font-medium">
                                        {post.title}
                                    </TableCell>
                                    <TableCell className="hidden text-muted-foreground sm:table-cell">
                                        {post.attachments_count}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-end gap-1">
                                            {can(PERMISSIONS.POSTS.VIEW) && (
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Link
                                                        href={posts.show({
                                                            locale: wayfinderLocale(),
                                                            post: post.id,
                                                        })}
                                                        title="View"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            )}
                                            {can(PERMISSIONS.POSTS.EDIT) && (
                                                <Button
                                                    asChild
                                                    variant="ghost"
                                                    size="icon"
                                                >
                                                    <Link
                                                        href={posts.edit({
                                                            locale: wayfinderLocale(),
                                                            post: post.id,
                                                        })}
                                                        title="Edit"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            )}
                                            {can(PERMISSIONS.POSTS.DELETE) && (
                                                <ConfirmDeleteDialog
                                                    description={`Permanently delete “${post.title}” and its attachments?`}
                                                    onConfirm={() =>
                                                        router.delete(
                                                            posts.destroy({
                                                                locale: wayfinderLocale(),
                                                                post: post.id,
                                                            }).url,
                                                        )
                                                    }
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-muted-foreground hover:text-destructive"
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </ConfirmDeleteDialog>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    {paginated.data.length === 0 && (
                        <div className="px-4 py-16 text-center text-sm text-muted-foreground">
                            No posts found.
                        </div>
                    )}
                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

PostsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Posts', href: posts.index(wayfinderLocale()) },
    ],
};
