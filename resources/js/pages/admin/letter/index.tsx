import { Head } from '@inertiajs/react';
import { Mail, TriangleAlert } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import letter from '@/routes/admin/letter';

interface Subscriber {
    email: string;
    name: string;
    status: string;
    joined_at: string;
}

export default function LetterIndex({
    subscribers,
    letterConnected,
}: {
    subscribers: Subscriber[];
    letterConnected: boolean;
}) {
    return (
        <>
            <Head title="Heritage Letter" />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Heritage Letter"
                    description="View newsletter subscribers and delivery status."
                    icon={Mail}
                />
                {!letterConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>
                            Mailing service is not connected
                        </AlertTitle>
                        <AlertDescription>
                            Subscriber records below are demonstration data.
                        </AlertDescription>
                    </Alert>
                )}
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>Subscriber</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Joined
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {subscribers.map((subscriber) => (
                                <TableRow key={subscriber.email}>
                                    <TableCell>
                                        <span className="font-medium">
                                            {subscriber.name}
                                        </span>
                                        <p className="text-xs text-muted-foreground">
                                            {subscriber.email}
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {subscriber.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        {new Date(
                                            subscriber.joined_at,
                                        ).toLocaleDateString()}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

LetterIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Heritage Letter', href: letter.index(wayfinderLocale()) },
    ],
};
