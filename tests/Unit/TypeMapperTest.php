<?php

declare(strict_types=1);

use Rsk\CrudGenerator\Services\TypeMapper;

beforeEach(function () {
    $this->mapper = new TypeMapper();
});

describe('TypeMapper::mapDatabaseType()', function () {
    it('maps integer types to number input', function () {
        $result = $this->mapper->mapDatabaseType('integer');
        expect($result['type'])->toBe('number');
        expect($result['input'])->toBe('number');
    });

    it('maps bigint type to number input', function () {
        $result = $this->mapper->mapDatabaseType('bigint');
        expect($result['type'])->toBe('number');
        expect($result['input'])->toBe('number');
    });

    it('maps smallint type to number input', function () {
        $result = $this->mapper->mapDatabaseType('smallint');
        expect($result['type'])->toBe('number');
        expect($result['input'])->toBe('number');
    });

    it('maps decimal types to number input', function () {
        $result = $this->mapper->mapDatabaseType('decimal');
        expect($result['type'])->toBe('number');
        expect($result['input'])->toBe('number');
    });

    it('maps float type to number input', function () {
        $result = $this->mapper->mapDatabaseType('float');
        expect($result['type'])->toBe('number');
        expect($result['input'])->toBe('number');
    });

    it('maps double type to number input', function () {
        $result = $this->mapper->mapDatabaseType('double');
        expect($result['type'])->toBe('number');
        expect($result['input'])->toBe('number');
    });

    it('maps boolean type to checkbox input', function () {
        $result = $this->mapper->mapDatabaseType('boolean');
        expect($result['type'])->toBe('boolean');
        expect($result['input'])->toBe('checkbox');
    });

    it('maps date type to date input', function () {
        $result = $this->mapper->mapDatabaseType('date');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('date');
    });

    it('maps datetime type to datetime-local input', function () {
        $result = $this->mapper->mapDatabaseType('datetime');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('datetime-local');
    });

    it('maps timestamp type to datetime-local input', function () {
        $result = $this->mapper->mapDatabaseType('timestamp');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('datetime-local');
    });

    it('maps time type to time input', function () {
        $result = $this->mapper->mapDatabaseType('time');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('time');
    });

    it('maps text type to textarea input', function () {
        $result = $this->mapper->mapDatabaseType('text');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('textarea');
    });

    it('maps longtext type to textarea input', function () {
        $result = $this->mapper->mapDatabaseType('longtext');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('textarea');
    });

    it('maps mediumtext type to textarea input', function () {
        $result = $this->mapper->mapDatabaseType('mediumtext');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('textarea');
    });

    it('maps json type to textarea input', function () {
        $result = $this->mapper->mapDatabaseType('json');
        expect($result['type'])->toBe('object');
        expect($result['input'])->toBe('textarea');
    });

    it('maps unknown types to string text input', function () {
        $result = $this->mapper->mapDatabaseType('varchar');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('text');
    });

    it('maps unknown type to string text input (default)', function () {
        $result = $this->mapper->mapDatabaseType('custom_type');
        expect($result['type'])->toBe('string');
        expect($result['input'])->toBe('text');
    });

    it('returns array with type and input keys', function () {
        $result = $this->mapper->mapDatabaseType('integer');
        expect($result)->toHaveKeys(['type', 'input']);
    });
});

describe('TypeMapper::getValidationRules()', function () {
    it('returns numeric validation for number input', function () {
        $rules = $this->mapper->getValidationRules('number');
        expect($rules)->toBe('required|numeric');
    });

    it('returns boolean validation for checkbox input', function () {
        $rules = $this->mapper->getValidationRules('checkbox');
        expect($rules)->toBe('boolean');
    });

    it('returns date validation for date input', function () {
        $rules = $this->mapper->getValidationRules('date');
        expect($rules)->toBe('required|date');
    });

    it('returns date validation for datetime-local input', function () {
        $rules = $this->mapper->getValidationRules('datetime-local');
        expect($rules)->toBe('required|date');
    });

    it('returns email validation for email input', function () {
        $rules = $this->mapper->getValidationRules('email');
        expect($rules)->toBe('required|email');
    });

    it('returns nullable string validation for textarea input', function () {
        $rules = $this->mapper->getValidationRules('textarea');
        expect($rules)->toBe('nullable|string');
    });

    it('returns default string validation for text input', function () {
        $rules = $this->mapper->getValidationRules('text');
        expect($rules)->toBe('required|string|max:255');
    });

    it('returns default validation for unknown input types', function () {
        $rules = $this->mapper->getValidationRules('unknown_type');
        expect($rules)->toBe('required|string|max:255');
    });

    it('returns string for valid input types', function () {
        $rules = $this->mapper->getValidationRules('number');
        expect($rules)->toBeString();
    });
});

describe('TypeMapper::getTypeScriptType()', function () {
    it('returns number for integer type', function () {
        $type = $this->mapper->getTypeScriptType('integer');
        expect($type)->toBe('number');
    });

    it('returns number for bigint type', function () {
        $type = $this->mapper->getTypeScriptType('bigint');
        expect($type)->toBe('number');
    });

    it('returns number for decimal type', function () {
        $type = $this->mapper->getTypeScriptType('decimal');
        expect($type)->toBe('number');
    });

    it('returns boolean for boolean type', function () {
        $type = $this->mapper->getTypeScriptType('boolean');
        expect($type)->toBe('boolean');
    });

    it('returns string for date type', function () {
        $type = $this->mapper->getTypeScriptType('date');
        expect($type)->toBe('string');
    });

    it('returns string for datetime type', function () {
        $type = $this->mapper->getTypeScriptType('datetime');
        expect($type)->toBe('string');
    });

    it('returns string for timestamp type', function () {
        $type = $this->mapper->getTypeScriptType('timestamp');
        expect($type)->toBe('string');
    });

    it('returns string for time type', function () {
        $type = $this->mapper->getTypeScriptType('time');
        expect($type)->toBe('string');
    });

    it('returns string for text type', function () {
        $type = $this->mapper->getTypeScriptType('text');
        expect($type)->toBe('string');
    });

    it('returns string for longtext type', function () {
        $type = $this->mapper->getTypeScriptType('longtext');
        expect($type)->toBe('string');
    });

    it('returns string for mediumtext type', function () {
        $type = $this->mapper->getTypeScriptType('mediumtext');
        expect($type)->toBe('string');
    });

    it('returns object for json type', function () {
        $type = $this->mapper->getTypeScriptType('json');
        expect($type)->toBe('object');
    });

    it('returns string for unknown types', function () {
        $type = $this->mapper->getTypeScriptType('varchar');
        expect($type)->toBe('string');
    });

    it('returns string type for any unknown database type', function () {
        $type = $this->mapper->getTypeScriptType('custom_type');
        expect($type)->toBe('string');
    });
});
