import type { IconName } from 'lucide-react/dynamic';

export type InputType =
    | 'text'
    | 'number'
    | 'email'
    | 'textarea'
    | 'checkbox'
    | 'date'
    | 'datetime-local'
    | 'time'
    | 'select'
    | 'relationship-select'
    | 'relationship-multi-select';

export type RelationshipType = 'belongsTo' | 'hasMany' | 'belongsToMany';

export interface RelationshipInfo {
    method: string;
    foreignKey?: string;
    relatedModel: string;
    relatedModelClass?: string;
    relatedTable: string;
    displayField: string;
    pivotTable?: string;
}

export interface FieldConfig {
    name: string;
    label: string;
    dbType: string;
    inputType: InputType;
    tsType: string;
    required: boolean;
    validation: string;
    // Campos de relacionamento
    isRelationship?: boolean;
    relationshipType?: RelationshipType;
    relationshipMethod?: string;
    relatedModel?: string;
    relatedTable?: string;
    displayField?: string;
    relatedColumns?: string[];
}

export interface ModelInfo {
    name: string;
    class: string;
}

export interface RelationshipsConfig {
    belongsTo: RelationshipInfo[];
    hasMany: RelationshipInfo[];
    belongsToMany: RelationshipInfo[];
}

export interface ModelConfig {
    model: string;
    modelStudly: string;
    table: string;
    fields: FieldConfig[];
    relationships?: RelationshipsConfig;
}

export interface GenerateOptions {
    force: boolean;
    withRequests: boolean;
    addRoutes: boolean;
    addNavItem: boolean;
    navIcon: string;
}

export interface CrudNavItem {
    title: string;
    route: string;
    icon: IconName;
}

export interface GenerateRequest {
    model: string;
    fields: FieldConfig[];
    options: GenerateOptions;
}

export interface GenerateResponse {
    success: boolean;
    message: string;
    output?: string;
}

export interface CrudGeneratorPageProps {
    models: ModelInfo[];
}

// CrudManager types
export interface CrudFilesExist {
    controller: boolean;
    indexPage: boolean;
    formDialog: boolean;
    columns: boolean;
    dataTable: boolean;
    types: boolean;
}

export interface CrudInfo {
    name: string;
    routeName: string;
    title: string;
    icon: string;
    enabled: boolean;
    filesExist: CrudFilesExist;
}

export interface CrudManagerStats {
    total: number;
    enabled: number;
    disabled: number;
}

export interface CrudManagerPageProps {
    cruds: CrudInfo[];
    stats: CrudManagerStats;
}
