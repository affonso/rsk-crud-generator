import { Head, Link, router } from '@inertiajs/react';
import { Database, Plus, Trash2 } from 'lucide-react';
import { useCallback, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Icon, type IconName } from '@/components/ui/icon-picker';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, CrudInfo, CrudManagerPageProps } from '@/types';

import DeleteDialog from './DeleteDialog';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Gerenciar CRUDs', href: '/admin/crud-manager' },
];

export default function CrudManagerIndex({
    cruds,
    stats,
}: CrudManagerPageProps) {
    const [togglingRoutes, setTogglingRoutes] = useState<Set<string>>(
        new Set(),
    );
    const [deleteDialog, setDeleteDialog] = useState<{
        open: boolean;
        crud: CrudInfo | null;
    }>({ open: false, crud: null });
    const [isDeleting, setIsDeleting] = useState(false);

    const handleToggle = useCallback((crud: CrudInfo) => {
        setTogglingRoutes((prev) => new Set(prev).add(crud.routeName));

        router.post(
            '/admin/crud-manager/toggle',
            { route: `${crud.routeName}.index` },
            {
                preserveScroll: true,
                onFinish: () => {
                    setTogglingRoutes((prev) => {
                        const next = new Set(prev);
                        next.delete(crud.routeName);
                        return next;
                    });
                },
            },
        );
    }, []);

    const handleDeleteClick = useCallback((crud: CrudInfo) => {
        setDeleteDialog({ open: true, crud });
    }, []);

    const handleDeleteConfirm = useCallback(
        (deleteFiles: boolean) => {
            if (!deleteDialog.crud) return;

            setIsDeleting(true);

            router.delete(
                `/admin/crud-manager/${deleteDialog.crud.routeName}`,
                {
                    data: { deleteFiles },
                    preserveScroll: true,
                    onSuccess: () => {
                        setDeleteDialog({ open: false, crud: null });
                    },
                    onFinish: () => {
                        setIsDeleting(false);
                    },
                },
            );
        },
        [deleteDialog.crud],
    );

    const countExistingFiles = (crud: CrudInfo): number => {
        return Object.values(crud.filesExist).filter(Boolean).length;
    };

    const renderIcon = (iconName: string) => {
        return (
            <Icon
                name={iconName as IconName}
                className="size-5 text-muted-foreground"
            />
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gerenciar CRUDs" />

            <div className="mx-auto max-w-5xl p-6">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Gerenciar CRUDs</h1>
                        <p className="text-muted-foreground">
                            Habilite, desabilite ou exclua CRUDs gerados.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/crud-generator">
                            <Plus className="mr-2 size-4" />
                            Novo CRUD
                        </Link>
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="mb-8 grid grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Total
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.total}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Habilitados
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {stats.enabled}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Desabilitados
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-yellow-600">
                                {stats.disabled}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* CRUDs Table */}
                {cruds.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Database className="mb-4 size-12 text-muted-foreground/50" />
                            <h3 className="mb-2 text-lg font-medium">
                                Nenhum CRUD gerado ainda
                            </h3>
                            <p className="mb-4 text-sm text-muted-foreground">
                                Comece criando seu primeiro CRUD usando o
                                gerador.
                            </p>
                            <Button asChild>
                                <Link href="/admin/crud-generator">
                                    <Plus className="mr-2 size-4" />
                                    Criar CRUD
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[80px]">
                                        Status
                                    </TableHead>
                                    <TableHead>Nome</TableHead>
                                    <TableHead className="w-[80px] text-center">
                                        Icone
                                    </TableHead>
                                    <TableHead className="w-[100px] text-center">
                                        Arquivos
                                    </TableHead>
                                    <TableHead className="w-[80px] text-right">
                                        Acoes
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {cruds.map((crud) => (
                                    <TableRow key={crud.routeName}>
                                        <TableCell>
                                            <Switch
                                                checked={crud.enabled}
                                                onCheckedChange={() =>
                                                    handleToggle(crud)
                                                }
                                                disabled={togglingRoutes.has(
                                                    crud.routeName,
                                                )}
                                            />
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {crud.title}
                                            <span className="ml-2 text-xs text-muted-foreground">
                                                ({crud.name})
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-center">
                                                {renderIcon(crud.icon)}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-center">
                                                <Badge
                                                    variant={
                                                        countExistingFiles(
                                                            crud,
                                                        ) === 6
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {countExistingFiles(crud)}/6
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    handleDeleteClick(crud)
                                                }
                                                className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Card>
                )}
            </div>

            <DeleteDialog
                open={deleteDialog.open}
                onOpenChange={(open) =>
                    setDeleteDialog((prev) => ({ ...prev, open }))
                }
                crud={deleteDialog.crud}
                onConfirm={handleDeleteConfirm}
                isDeleting={isDeleting}
            />
        </AppLayout>
    );
}
