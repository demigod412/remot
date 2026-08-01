<?php

namespace App\Services;

/**
 * Validates a worker's decoded JSON result against a per-category schema.
 *
 * This is a deliberately small subset of JSON Schema, not the real thing. A full
 * implementation would mean adding a Composer package; this covers the shapes a
 * microtask result actually takes and can be written by hand without tooling.
 *
 * Supported keywords:
 *
 *   type        string | number | integer | boolean | array | object | any
 *   required    array of key names that must be present and not null
 *   properties  map of key => nested schema
 *   items       schema applied to every element of an array
 *   enum        array of allowed exact values
 *   min / max            numeric bounds (inclusive)
 *   min_length / max_length   string length bounds
 *   min_items / max_items     array length bounds
 *   pattern     PCRE, applied to strings, WITHOUT delimiters
 *   nullable    true to allow an explicit null
 *
 * Example schema:
 *
 * {
 *   "type": "object",
 *   "required": ["task_id", "results"],
 *   "properties": {
 *     "task_id": { "type": "string", "pattern": "^[A-Z0-9-]{4,32}$" },
 *     "results": {
 *       "type": "array",
 *       "min_items": 1,
 *       "items": {
 *         "type": "object",
 *         "required": ["label"],
 *         "properties": {
 *           "label":      { "type": "string", "enum": ["yes", "no", "unclear"] },
 *           "confidence": { "type": "number", "min": 0, "max": 1 }
 *         }
 *       }
 *     }
 *   }
 * }
 */
class ResultSchemaValidator
{
    protected const TYPES = ['string', 'number', 'integer', 'boolean', 'array', 'object', 'any'];

    /** Guard against a pathologically nested schema or payload. */
    protected const MAX_DEPTH = 12;

    /**
     * Validate a decoded payload against a schema.
     *
     * @return array<int, string> Human-readable errors, empty when valid.
     */
    public function validate(mixed $payload, array $schema, bool $strict = false): array
    {
        return $this->check($payload, $schema, 'result', $strict, 0);
    }

    // -------------------------------------------------------------------------
    // Schema self-validation
    // -------------------------------------------------------------------------

    /**
     * Check that a schema is itself well-formed, so admin cannot save a broken
     * schema that silently rejects every submission afterwards.
     *
     * @return array<int, string>
     */
    public function validateSchema(array $schema, string $path = 'schema', int $depth = 0): array
    {
        $errors = [];

        if ($depth > self::MAX_DEPTH) {
            return ["{$path}: schema is nested too deeply."];
        }

        if (isset($schema['type']) && ! in_array($schema['type'], self::TYPES, true)) {
            $errors[] = "{$path}.type: '{$schema['type']}' is not a known type. Use one of: "
                      . implode(', ', self::TYPES) . '.';
        }

        foreach (['required', 'enum'] as $key) {
            if (isset($schema[$key]) && ! is_array($schema[$key])) {
                $errors[] = "{$path}.{$key}: must be an array.";
            }
        }

        if (isset($schema['required'])) {
            foreach ((array) $schema['required'] as $name) {
                if (! is_string($name)) {
                    $errors[] = "{$path}.required: every entry must be a key name (string).";
                    break;
                }
            }
        }

        foreach (['min', 'max', 'min_length', 'max_length', 'min_items', 'max_items'] as $key) {
            if (isset($schema[$key]) && ! is_numeric($schema[$key])) {
                $errors[] = "{$path}.{$key}: must be a number.";
            }
        }

        if (isset($schema['pattern'])) {
            if (! is_string($schema['pattern'])) {
                $errors[] = "{$path}.pattern: must be a string.";
            } elseif (@preg_match('/' . $schema['pattern'] . '/u', '') === false) {
                $errors[] = "{$path}.pattern: not a valid regular expression.";
            }
        }

        if (isset($schema['properties'])) {
            if (! is_array($schema['properties'])) {
                $errors[] = "{$path}.properties: must be an object.";
            } else {
                foreach ($schema['properties'] as $key => $sub) {
                    if (! is_array($sub)) {
                        $errors[] = "{$path}.properties.{$key}: must be an object describing the field.";
                        continue;
                    }
                    $errors = array_merge(
                        $errors,
                        $this->validateSchema($sub, "{$path}.properties.{$key}", $depth + 1)
                    );
                }
            }
        }

        if (isset($schema['items'])) {
            if (! is_array($schema['items'])) {
                $errors[] = "{$path}.items: must be an object describing each element.";
            } else {
                $errors = array_merge(
                    $errors,
                    $this->validateSchema($schema['items'], "{$path}.items", $depth + 1)
                );
            }
        }

        // A required list with no properties block is almost always a mistake.
        if (! empty($schema['required']) && empty($schema['properties'])) {
            $errors[] = "{$path}: 'required' is set but 'properties' is missing, so nothing is described.";
        }

        return $errors;
    }

    // -------------------------------------------------------------------------
    // Payload checking
    // -------------------------------------------------------------------------

    /**
     * @return array<int, string>
     */
    protected function check(mixed $value, array $schema, string $path, bool $strict, int $depth): array
    {
        if ($depth > self::MAX_DEPTH) {
            return ["{$path}: nested too deeply to validate."];
        }

        if ($value === null) {
            return ! empty($schema['nullable']) ? [] : ["{$path}: must not be null."];
        }

        $type   = $schema['type'] ?? 'any';
        $errors = [];

        if ($type !== 'any' && ! $this->isType($value, $type)) {
            // Type is wrong, so every other check would produce noise.
            return ["{$path}: expected {$type}, got " . $this->describe($value) . '.'];
        }

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            if (! in_array($value, $schema['enum'], true)) {
                $allowed = implode(', ', array_map(
                    fn ($v) => is_scalar($v) ? var_export($v, true) : gettype($v),
                    $schema['enum']
                ));
                $errors[] = "{$path}: must be one of [{$allowed}].";
            }
        }

        if (is_string($value)) {
            $length = mb_strlen($value);

            if (isset($schema['min_length']) && $length < (int) $schema['min_length']) {
                $errors[] = "{$path}: must be at least {$schema['min_length']} characters.";
            }
            if (isset($schema['max_length']) && $length > (int) $schema['max_length']) {
                $errors[] = "{$path}: must be at most {$schema['max_length']} characters.";
            }
            if (isset($schema['pattern']) && ! @preg_match('/' . $schema['pattern'] . '/u', $value)) {
                $errors[] = "{$path}: does not match the required format.";
            }
        }

        if (is_int($value) || is_float($value)) {
            if (isset($schema['min']) && $value < $schema['min']) {
                $errors[] = "{$path}: must be at least {$schema['min']}.";
            }
            if (isset($schema['max']) && $value > $schema['max']) {
                $errors[] = "{$path}: must be at most {$schema['max']}.";
            }
        }

        if ($type === 'array' || ($type === 'any' && $this->isList($value))) {
            $errors = array_merge($errors, $this->checkArray($value, $schema, $path, $strict, $depth));
        } elseif ($type === 'object' || ($type === 'any' && is_array($value))) {
            $errors = array_merge($errors, $this->checkObject($value, $schema, $path, $strict, $depth));
        }

        return $errors;
    }

    protected function checkArray(mixed $value, array $schema, string $path, bool $strict, int $depth): array
    {
        if (! is_array($value)) {
            return [];
        }

        $errors = [];
        $count  = count($value);

        if (isset($schema['min_items']) && $count < (int) $schema['min_items']) {
            $errors[] = "{$path}: needs at least {$schema['min_items']} item(s), found {$count}.";
        }
        if (isset($schema['max_items']) && $count > (int) $schema['max_items']) {
            $errors[] = "{$path}: allows at most {$schema['max_items']} item(s), found {$count}.";
        }

        if (! empty($schema['items']) && is_array($schema['items'])) {
            foreach ($value as $i => $item) {
                $errors = array_merge(
                    $errors,
                    $this->check($item, $schema['items'], "{$path}[{$i}]", $strict, $depth + 1)
                );

                // Stop after a handful so one bad export does not produce a wall of text.
                if (count($errors) > 25) {
                    $errors[] = "{$path}: further errors suppressed.";
                    break;
                }
            }
        }

        return $errors;
    }

    protected function checkObject(mixed $value, array $schema, string $path, bool $strict, int $depth): array
    {
        if (! is_array($value)) {
            return [];
        }

        $errors     = [];
        $properties = (array) ($schema['properties'] ?? []);

        foreach ((array) ($schema['required'] ?? []) as $key) {
            if (! array_key_exists($key, $value) || $value[$key] === null) {
                $errors[] = "{$path}: missing required key '{$key}'.";
            }
        }

        foreach ($properties as $key => $sub) {
            if (! array_key_exists($key, $value) || ! is_array($sub)) {
                continue; // Absence is handled by 'required' above.
            }
            $errors = array_merge(
                $errors,
                $this->check($value[$key], $sub, "{$path}.{$key}", $strict, $depth + 1)
            );
        }

        if ($strict && $properties !== []) {
            $unknown = array_diff(array_keys($value), array_keys($properties));
            foreach ($unknown as $key) {
                $errors[] = "{$path}: unexpected key '{$key}'.";
            }
        }

        return $errors;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function isType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string'  => is_string($value),
            // json_decode gives ints for whole numbers, so integers pass as numbers.
            'number'  => (is_int($value) || is_float($value)) && ! is_bool($value),
            'integer' => is_int($value) && ! is_bool($value),
            'boolean' => is_bool($value),
            'array'   => is_array($value) && $this->isList($value),
            'object'  => is_array($value) && ! $this->isList($value),
            default   => true,
        };
    }

    /**
     * json_decode(..., true) turns both JSON arrays and objects into PHP arrays,
     * so a sequential integer-keyed array is what a JSON array looks like.
     * An empty array is treated as a list.
     */
    protected function isList(mixed $value): bool
    {
        return is_array($value) && ($value === [] || array_is_list($value));
    }

    protected function describe(mixed $value): string
    {
        if (is_array($value)) {
            return $this->isList($value) ? 'array' : 'object';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'number';
        }
        return is_string($value) ? 'string' : gettype($value);
    }
}
