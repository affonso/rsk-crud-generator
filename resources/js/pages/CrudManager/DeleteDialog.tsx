import { useState } from 'react';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-react';
import type { CrudInfo } from '@/types';

interface DeleteDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    crud: CrudInfo | null;
    onConfirm: (deleteFiles: boolean) => void;
    isDeleting: boolean;
}

export default function DeleteDialog({
    open,
    onOpenChange,
    crud,
    onConfirm,
    isDeleting,
}: DeleteDialogProps) {
    const [deleteFiles, setDeleteFiles] = useState(false);

    const handleConfirm = () => {
        onConfirm(deleteFiles);
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen) {
            setDeleteFiles(false);
        }
        onOpenChange(newOpen);
    };

    if (!crud) return null;

    return (
        <AlertDialog open={open} onOpenChange={handleOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        Excluir CRUD "{crud.title}"?
                    </AlertDialogTitle>
                    <AlertDialogDescription asChild>
                        <div className="space-y-3">
                            <p>Esta acao ira remover:</p>
                            <ul className="list-inside list-disc space-y-1 text-sm">
                                <li>Rota do arquivo routes/rsk-crud.php</li>
                                <li>Link da navegacao</li>
                            </ul>

                            <div className="flex items-center space-x-2 pt-2">
                                <Checkbox
                                    id="deleteFiles"
                                    checked={deleteFiles}
                                    onCheckedChange={(checked) =>
                                        setDeleteFiles(checked === true)
                                    }
                                    disabled={isDeleting}
                                />
                                <Label
                                    htmlFor="deleteFiles"
                                    className="text-sm font-normal"
                                >
                                    Tambem excluir arquivos gerados (Controller,
                                    Views, Types)
                                </Label>
                            </div>
                        </div>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isDeleting}>
                        Cancelar
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleConfirm}
                        disabled={isDeleting}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {isDeleting ? (
                            <>
                                <Loader2 className="mr-2 size-4 animate-spin" />
                                Excluindo...
                            </>
                        ) : (
                            'Excluir'
                        )}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
