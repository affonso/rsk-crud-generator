import { Head } from '@inertiajs/react';
import axios from 'axios';
import { CheckIcon } from 'lucide-react';
import { useCallback, useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    CrudGeneratorPageProps,
    FieldConfig,
    GenerateOptions,
    GenerateResponse,
    ModelConfig,
} from '@/types';

import Step1ModelSelection from './Step1ModelSelection';
import Step2FieldConfiguration from './Step2FieldConfiguration';
import Step3GenerationResult from './Step3GenerationResult';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '#',
    },
    {
        title: 'CRUD Generator',
        href: '/admin/crud-generator',
    },
];

const STEPS = [
    { id: 1, name: 'Model' },
    { id: 2, name: 'Configuração' },
    { id: 3, name: 'Resultado' },
];

export default function CrudGeneratorIndex({ models }: CrudGeneratorPageProps) {
    const [currentStep, setCurrentStep] = useState(1);
    const [selectedModel, setSelectedModel] = useState('');
    const [modelConfig, setModelConfig] = useState<ModelConfig | null>(null);
    const [fields, setFields] = useState<FieldConfig[]>([]);
    const [options, setOptions] = useState<GenerateOptions>({
        force: false,
        withRequests: false,
        addRoutes: true,
        addNavItem: true,
        navIcon: 'database',
    });
    const [isLoadingConfig, setIsLoadingConfig] = useState(false);
    const [isGenerating, setIsGenerating] = useState(false);
    const [result, setResult] = useState<GenerateResponse | null>(null);

    const loadModelConfig = useCallback(async () => {
        if (!selectedModel) return;

        setIsLoadingConfig(true);
        try {
            const response = await axios.get<ModelConfig>(
                `/admin/crud-generator/model/${selectedModel}`,
            );
            setModelConfig(response.data);
            setFields(response.data.fields);
            setCurrentStep(2);
        } catch (error) {
            console.error('Error loading model config:', error);
        } finally {
            setIsLoadingConfig(false);
        }
    }, [selectedModel]);

    const handleFieldChange = useCallback(
        (index: number, updates: Partial<FieldConfig>) => {
            setFields((prev) =>
                prev.map((field, i) =>
                    i === index ? { ...field, ...updates } : field,
                ),
            );
        },
        [],
    );

    const handleOptionsChange = useCallback(
        (updates: Partial<GenerateOptions>) => {
            setOptions((prev) => ({ ...prev, ...updates }));
        },
        [],
    );

    const handleGenerate = useCallback(async () => {
        setIsGenerating(true);
        setCurrentStep(3);

        try {
            const response = await axios.post<GenerateResponse>(
                '/admin/crud-generator/generate',
                {
                    model: selectedModel,
                    fields,
                    options,
                },
            );
            setResult(response.data);
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.data) {
                setResult(error.response.data as GenerateResponse);
            } else {
                setResult({
                    success: false,
                    message: 'Erro desconhecido ao gerar CRUD.',
                });
            }
        } finally {
            setIsGenerating(false);
        }
    }, [selectedModel, fields, options]);

    const handleReset = useCallback(() => {
        setCurrentStep(1);
        setSelectedModel('');
        setModelConfig(null);
        setFields([]);
        setOptions({
            force: false,
            withRequests: false,
            addRoutes: true,
            addNavItem: true,
            navIcon: 'database',
        });
        setResult(null);
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="CRUD Generator" />

            <div className="mx-auto max-w-5xl p-6">
                <div className="mb-8">
                    <h1 className="text-2xl font-bold">CRUD Generator</h1>
                    <p className="text-muted-foreground">
                        Gere automaticamente Controller, Views e rotas para seus
                        Models.
                    </p>
                </div>

                {/* Stepper */}
                <nav aria-label="Progress" className="mb-8">
                    <ol className="flex items-center">
                        {STEPS.map((step, stepIdx) => (
                            <li
                                key={step.name}
                                className={`relative ${stepIdx !== STEPS.length - 1 ? 'flex-1 pr-8' : ''}`}
                            >
                                {stepIdx !== STEPS.length - 1 && (
                                    <div
                                        className={`absolute top-5 left-5 z-10 -ml-px h-0.5 w-full ${
                                            currentStep > step.id
                                                ? 'bg-primary'
                                                : 'bg-muted-foreground/30'
                                        }`}
                                        aria-hidden="true"
                                    />
                                )}
                                <div className="flex items-center">
                                    <div
                                        className={`z-20 flex size-10 shrink-0 items-center justify-center rounded-full border-2 bg-black ${
                                            currentStep > step.id
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : currentStep === step.id
                                                  ? 'border-primary text-primary'
                                                  : 'border-muted-foreground/30 text-muted-foreground/50'
                                        }`}
                                    >
                                        {currentStep > step.id ? (
                                            <CheckIcon className="size-5" />
                                        ) : (
                                            <span className="text-sm font-medium">
                                                {step.id}
                                            </span>
                                        )}
                                    </div>
                                    <span
                                        className={`z-20 ml-3 rounded bg-black px-2 py-1 text-sm font-medium ${
                                            currentStep >= step.id
                                                ? 'text-foreground'
                                                : 'text-muted-foreground/50'
                                        }`}
                                    >
                                        {step.name}
                                    </span>
                                </div>
                            </li>
                        ))}
                    </ol>
                </nav>

                {/* Step Content */}
                {currentStep === 1 && (
                    <Step1ModelSelection
                        models={models}
                        selectedModel={selectedModel}
                        onModelChange={setSelectedModel}
                        onNext={loadModelConfig}
                        isLoading={isLoadingConfig}
                    />
                )}

                {currentStep === 2 && modelConfig && (
                    <Step2FieldConfiguration
                        modelName={modelConfig.modelStudly}
                        fields={fields}
                        options={options}
                        onFieldChange={handleFieldChange}
                        onOptionsChange={handleOptionsChange}
                        onBack={() => setCurrentStep(1)}
                        onNext={handleGenerate}
                    />
                )}

                {currentStep === 3 && (
                    <Step3GenerationResult
                        isGenerating={isGenerating}
                        result={result}
                        onReset={handleReset}
                    />
                )}
            </div>
        </AppLayout>
    );
}
