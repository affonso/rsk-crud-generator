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
                    <div className="space-y-4">
                        <Alert className="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950">
                            <CheckCircle2Icon className="size-4 text-green-600 dark:text-green-400" />
                            <AlertTitle className="text-green-800 dark:text-green-200">
                                ✨ Arquivos Gerados com Sucesso!
                            </AlertTitle>
                            <AlertDescription className="text-green-700 dark:text-green-300">
                                Todos os arquivos foram criados. Siga os próximos passos abaixo para ativar o CRUD.
                            </AlertDescription>
                        </Alert>

                        <div className="rounded-lg border-2 border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
                            <h4 className="mb-4 text-lg font-semibold text-blue-900 dark:text-blue-100">
                                📋 Próximos Passos
                            </h4>
                            <ol className="space-y-4 text-sm text-blue-800 dark:text-blue-200">
                                <li className="flex gap-3">
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-200 font-semibold dark:bg-blue-900">1</span>
                                    <div>
                                        <strong>Adicione as rotas em routes/web.php:</strong>
                                        <pre className="mt-2 rounded bg-blue-100 p-2 font-mono text-xs dark:bg-blue-900">
                                            require __DIR__.'/rsk-crud.php';
                                        </pre>
                                    </div>
                                </li>
                                <li className="flex gap-3">
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-200 font-semibold dark:bg-blue-900">2</span>
                                    <div>
                                        <strong>Adicione o link de navegação na sidebar</strong>
                                        <p className="mt-1 text-xs">
                                            Importe o arquivo config/rsk-crud-navigation.php no seu layout
                                        </p>
                                    </div>
                                </li>
                                <li className="flex gap-3">
                                    <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-200 font-semibold dark:bg-blue-900">3</span>
                                    <div>
                                        <strong>Execute o build dos assets:</strong>
                                        <pre className="mt-2 rounded bg-blue-100 p-2 font-mono text-xs dark:bg-blue-900">
                                            bun run build
                                        </pre>
                                    </div>
                                </li>
                            </ol>
                            <div className="mt-4 rounded border border-yellow-300 bg-yellow-50 p-3 dark:border-yellow-800 dark:bg-yellow-950">
                                <p className="text-xs text-yellow-800 dark:text-yellow-200">
                                    💡 <strong>Dica:</strong> A página pode recarregar automaticamente devido ao hot reload do Vite.
                                    Se isso acontecer, você pode ver os detalhes da geração no terminal onde executou o comando.
                                </p>
                            </div>
                        </div>
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
