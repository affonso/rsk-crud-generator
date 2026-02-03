import { DatabaseIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ModelInfo } from '@/types';

interface Step1Props {
    models: ModelInfo[];
    selectedModel: string;
    onModelChange: (model: string) => void;
    onNext: () => void;
    isLoading: boolean;
}

export default function Step1ModelSelection({
    models,
    selectedModel,
    onModelChange,
    onNext,
    isLoading,
}: Step1Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <DatabaseIcon className="size-5" />
                    Selecionar Model
                </CardTitle>
                <CardDescription>
                    Escolha o Model para o qual deseja gerar o CRUD completo.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="space-y-2">
                    <Label htmlFor="model">Model</Label>
                    <Select value={selectedModel} onValueChange={onModelChange}>
                        <SelectTrigger id="model" className="w-full">
                            <SelectValue placeholder="Selecione um model..." />
                        </SelectTrigger>
                        <SelectContent>
                            {models.map((model) => (
                                <SelectItem key={model.name} value={model.name}>
                                    {model.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {models.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Nenhum model encontrado em app/Models.
                        </p>
                    )}
                </div>

                <div className="flex justify-end">
                    <Button onClick={onNext} disabled={!selectedModel || isLoading}>
                        {isLoading ? 'Carregando...' : 'Continuar'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
