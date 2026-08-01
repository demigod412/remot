<?php

namespace App\Http\Requests;

use App\Models\WorkSubmission;
use App\Services\ResultSchemaValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates the result a worker submits.
 *
 * The result is JSON. A .json extension proves nothing, so the file is read and
 * actually decoded, and the decoded value must be an object or array rather than
 * a bare scalar like the string "done".
 */
class TaskResultRequest extends FormRequest
{
    /** Largest result file we will parse, in bytes. */
    protected const MAX_BYTES = 2097152; // 2 MB

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'result_file' => ['required', 'file', 'mimes:json,txt', 'max:2048'],
            'proof_note'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->hasFile('result_file')) {
                return;
            }

            $file = $this->file('result_file');

            if (! $file->isValid()) {
                $v->errors()->add('result_file', 'Upload failed, please try again.');
                return;
            }

            if ($file->getSize() > self::MAX_BYTES) {
                $v->errors()->add('result_file', 'Result file is too large.');
                return;
            }

            $raw = @file_get_contents($file->getRealPath());

            if ($raw === false || trim($raw) === '') {
                $v->errors()->add('result_file', 'The result file is empty.');
                return;
            }

            // Reject anything that is not valid UTF-8 before decoding, so we give a
            // clear message instead of a generic JSON error.
            if (! mb_check_encoding($raw, 'UTF-8')) {
                $v->errors()->add('result_file', 'The result file must be UTF-8 encoded text.');
                return;
            }

            $decoded = json_decode($raw, true, 64);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $v->errors()->add(
                    'result_file',
                    'This is not valid JSON: ' . json_last_error_msg() . '. Check it with a JSON validator and re-export.'
                );
                return;
            }

            if (! is_array($decoded)) {
                $v->errors()->add(
                    'result_file',
                    'The JSON must be an object or an array, not a single value.'
                );
                return;
            }

            if ($decoded === []) {
                $v->errors()->add('result_file', 'The JSON contains no data.');
                return;
            }

            // Category schema, if one is configured. Rejecting a malformed result
            // here is the whole point: it never reaches the admin review queue.
            foreach ($this->schemaErrors($decoded) as $message) {
                $v->errors()->add('result_file', $message);
            }

            // Hand the parsed value to the controller so it does not decode twice.
            $this->merge(['_decoded_result' => $decoded]);
        });
    }

    /**
     * Validate against the schema on the task's category, when one is set.
     *
     * With no schema configured this returns nothing, so behaviour is unchanged
     * and you can leave categories schema-less until the format is settled.
     *
     * @return array<int, string>
     */
    protected function schemaErrors(array $decoded): array
    {
        $submission = WorkSubmission::with('work.category')
            ->find($this->route('id'));

        $category = $submission?->work?->category;
        $schema   = $category?->result_schema;

        if (! is_array($schema) || $schema === []) {
            return [];
        }

        $errors = app(ResultSchemaValidator::class)
            ->validate($decoded, $schema, (bool) $category->schema_strict);

        if ($errors === []) {
            return [];
        }

        // Cap the list so a badly generated file does not flood the form.
        $shown = array_slice($errors, 0, 8);
        if (count($errors) > count($shown)) {
            $shown[] = '... and ' . (count($errors) - count($shown)) . ' more issue(s).';
        }

        return array_merge(
            ['This result does not match the required format for this task category:'],
            $shown
        );
    }

    /**
     * The validated, decoded JSON payload.
     */
    public function decodedResult(): array
    {
        return (array) $this->input('_decoded_result', []);
    }
}
