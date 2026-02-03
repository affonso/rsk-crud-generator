import { Link, Settings as SettingsIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { IconPicker, type IconName } from '@/components/ui/icon-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { FieldConfig, GenerateOptions, InputType } from '@/types';

interface Step2Props {
    modelName: string;
    fields: FieldConfig[];
    options: GenerateOptions;
    onFieldChange: (index: number, field: Partial<FieldConfig>) => void;
    onOptionsChange: (options: Partial<GenerateOptions>) => void;
    onBack: () => void;
    onNext: () => void;
}

const INPUT_TYPES: { value: InputType; label: string }[] = [
    { value: 'text', label: 'Text' },
    { value: 'number', label: 'Number' },
    { value: 'email', label: 'Email' },
    { value: 'textarea', label: 'Textarea' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'date', label: 'Date' },
    { value: 'datetime-local', label: 'DateTime' },
    { value: 'time', label: 'Time' },
    { value: 'select', label: 'Select' },
    { value: 'relationship-select', label: 'Relacionamento (Select)' },
    { value: 'relationship-multi-select', label: 'Relacionamento (Multi)' },
];

export default function Step2FieldConfiguration({
    modelName,
    fields,
    options,
    onFieldChange,
    onOptionsChange,
    onBack,
    onNext,
}: Step2Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <SettingsIcon className="size-5" />
                    Configurar Campos - {modelName}
                </CardTitle>
                <CardDescription>
                    Ajuste a configuração dos campos conforme necessário. Você pode alterar labels,
                    tipos de input e regras de validação.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[160px]">Campo</TableHead>
                                <TableHead className="w-[160px]">Label</TableHead>
                                <TableHead className="w-[180px]">Tipo Input</TableHead>
                                <TableHead className="w-[80px] text-center">Obrigatório</TableHead>
                                <TableHead>Validação</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {fields.map((field, index) => (
                                <TableRow key={field.name}>
                                    <TableCell className="font-mono text-sm">
                                        <div className="flex flex-col gap-1">
                                            {field.name}
                                            <span className="text-xs text-muted-foreground">
                                                {field.dbType}
                                            </span>
                                            {field.isRelationship && (
                                                <Badge
                                                    variant="secondary"
                                                    className="mt-1 w-fit gap-1 text-xs"
                                                >
                                                    <Link className="size-3" />
                                                    {field.relationshipType} → {field.relatedModel}
                                                </Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            value={field.label}
                                            onChange={(e) =>
                                                onFieldChange(index, { label: e.target.value })
                                            }
                                            className="h-8"
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-2">
                                            <Select
                                                value={field.inputType}
                                                onValueChange={(value: InputType) =>
                                                    onFieldChange(index, { inputType: value })
                                                }
                                            >
                                                <SelectTrigger className="h-8">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {INPUT_TYPES.map((type) => (
                                                        <SelectItem key={type.value} value={type.value}>
                                                            {type.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {field.isRelationship && field.relatedColumns && (
                                                <Select
                                                    value={field.displayField}
                                                    onValueChange={(value) =>
                                                        onFieldChange(index, { displayField: value })
                                                    }
                                                >
                                                    <SelectTrigger className="h-8">
                                                        <SelectValue placeholder="Campo exibição" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {field.relatedColumns.map((col) => (
                                                            <SelectItem key={col} value={col}>
                                                                {col}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <Checkbox
                                            checked={field.required}
                                            onCheckedChange={(checked) =>
                                                onFieldChange(index, {
                                                    required: checked === true,
                                                })
                                            }
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            value={field.validation}
                                            onChange={(e) =>
                                                onFieldChange(index, { validation: e.target.value })
                                            }
                                            placeholder="required|string|max:255"
                                            className="h-8 font-mono text-xs"
                                        />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <div className="rounded-lg border bg-muted/50 p-4">
                    <h4 className="mb-3 font-medium">Opções de Geração</h4>
                    <div className="flex flex-wrap gap-x-6 gap-y-4">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="addRoutes"
                                checked={options.addRoutes}
                                onCheckedChange={(checked) =>
                                    onOptionsChange({ addRoutes: checked === true })
                                }
                            />
                            <Label htmlFor="addRoutes" className="cursor-pointer">
                                Adicionar rotas em routes/rsk-crud.php
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="addNavItem"
                                checked={options.addNavItem}
                                onCheckedChange={(checked) =>
                                    onOptionsChange({ addNavItem: checked === true })
                                }
                            />
                            <Label htmlFor="addNavItem" className="cursor-pointer">
                                Adicionar link na sidebar
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="withRequests"
                                checked={options.withRequests}
                                onCheckedChange={(checked) =>
                                    onOptionsChange({ withRequests: checked === true })
                                }
                            />
                            <Label htmlFor="withRequests" className="cursor-pointer">
                                Gerar Form Requests
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="force"
                                checked={options.force}
                                onCheckedChange={(checked) =>
                                    onOptionsChange({ force: checked === true })
                                }
                            />
                            <Label htmlFor="force" className="cursor-pointer">
                                Sobrescrever arquivos existentes
                            </Label>
                        </div>
                    </div>
                    {options.addNavItem && (
                        <div className="mt-4 flex items-center gap-3">
                            <Label htmlFor="navIcon">Ícone:</Label>
                            <IconPicker
                                value={options.navIcon as IconName}
                                onValueChange={(value) => onOptionsChange({ navIcon: value })}
                                triggerPlaceholder="Selecione um ícone"
                                searchPlaceholder="Buscar ícone..."
                            />
                        </div>
                    )}
                </div>

                <div className="flex justify-between">
                    <Button variant="outline" onClick={onBack}>
                        Voltar
                    </Button>
                    <Button onClick={onNext} disabled={fields.length === 0}>
                        Gerar CRUD
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
