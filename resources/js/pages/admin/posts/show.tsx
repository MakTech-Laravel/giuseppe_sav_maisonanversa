import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, File, FileText, Pencil } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { usePermission } from '@/hooks/use-permissions';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import posts from '@/routes/admin/posts';
import { PERMISSIONS } from '@/types/permissions';

interface Attachment {
    id: number;
    original_name: string;
    label: string | null;
    mime_type: string;
    size: number;
    url: string;
}

interface Post {
    id: number;
    title: string;
    attachments: Attachment[];
}

export default function ShowPost({ post }: { post: Post }) {
    const { can } = usePermission();

    return (
        <>
            <Head title={post.title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={post.title}
                    description="Post details and attached files."
                    icon={FileText}
                >
                    <Button variant="outline" asChild>
                        <Link href={posts.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> Back to posts
                        </Link>
                    </Button>
                    {can(PERMISSIONS.POSTS.EDIT) && (
                        <Button asChild>
                            <Link
                                href={posts.edit({
                                    locale: wayfinderLocale(),
                                    post: post.id,
                                })}
                            >
                                <Pencil className="h-4 w-4" /> Edit post
                            </Link>
                        </Button>
                    )}
                </AdminPageHeader>
                <div className="w-full rounded-xl border bg-card p-6 shadow-sm md:p-8">
                    <h2 className="mb-4 text-sm font-semibold">Attachments</h2>
                    {post.attachments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No attachments.
                        </p>
                    ) : (
                        <div className="divide-y">
                            {post.attachments.map((attachment) => (
                                <a
                                    key={attachment.id}
                                    href={attachment.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="flex items-center gap-3 py-3 hover:text-primary"
                                >
                                    <File className="h-4 w-4" />
                                    <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                        {attachment.label ||
                                            attachment.original_name}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {Math.ceil(attachment.size / 1024)} KB
                                    </span>
                                </a>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

ShowPost.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Posts', href: posts.index(wayfinderLocale()) },
        { title: 'Details', href: posts.index(wayfinderLocale()) },
    ],
};
