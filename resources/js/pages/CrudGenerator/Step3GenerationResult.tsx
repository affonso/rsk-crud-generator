import { CheckCircle2Icon, Loader2Icon, XCircleIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { GenerateResponse } from '@/types';

interface Step3Props {
    isGenerating: boolean;
    result: GenerateResponse | null;
    onReset: () => void;
}

export default function Step3GenerationResult({ isGenerating, result, onReset }: Step3Props) {
    if (isGenerating) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Loader2Icon className="size-5 animate-spin" />
                        Gerando CRUD...
                    </CardTitle>
                    <CardDescription>
                        Aguarde enquanto os arquivos são gerados.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-3">
                        <Skeleton className="h-4 w-3/4" />
                        <Skeleton className="h-4 w-1/2" />
                        <Skeleton className="h-4 w-2/3" />
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (!result) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    {result.success ? (
                        <>
                            <CheckCircle2Icon className="size-5 text-green-600" />
                            CRUD Gerado com Sucesso
                        </>
                    ) : (
                        <>
                            <XCircleIcon className="size-5 text-red-600" />
                            Erro na Geração
                        </>
                    )}
                </CardTitle>
                <CardDescription>
                    {result.success
                        ? 'Todos os arquivos foram criados com sucesso.'
                        : 'Ocorreu um erro durante a geração dos arquivos.'}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <Alert variant={result.success ? 'default' : 'destructive'}>
                    {result.success ? (
                        <CheckCircle2Icon className="size-4" />
                    ) : (
                        <XCircleIcon className="size-4" />
                    )}
                    <AlertTitle>{result.success ? 'Sucesso' : 'Erro'}</AlertTitle>
                    <AlertDescription>{result.message}</AlertDescription>
                </Alert>

                {result.output && (
                    <div className="rounded-lg border bg-muted p-4">
                        <h4 className="mb-2 font-medium">Output do Comando</h4>
                        <pre className="max-h-64 overflow-auto whitespace-pre-wrap text-sm">
                            {result.output}
                        </pre>
                    </div>
                )}

                {result.success && (
                    <div className="rounded-lg border bg-blue-50 p-4 dark:bg-blue-950">
                        <h4 className="mb-2 font-medium text-blue-800 dark:text-blue-200">
                            Próximos Passos
                        </h4>
                        <ol className="list-inside list-decimal space-y-1 text-sm text-blue-700 dark:text-blue-300">
                            <li>Adicione as rotas em <code className="rounded bg-blue-100 px-1 dark:bg-blue-900">routes/web.php</code></li>
                            <li>Adicione o link de navegação na sidebar</li>
                            <li>Execute <code className="rounded bg-blue-100 px-1 dark:bg-blue-900">bun run build</code> ou <code className="rounded bg-blue-100 px-1 dark:bg-blue-900">bun run dev</code></li>
                        </ol>
                    </div>
                )}

                <div className="flex justify-end">
                    <Button onClick={onReset}>
                        Gerar Outro CRUD
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
