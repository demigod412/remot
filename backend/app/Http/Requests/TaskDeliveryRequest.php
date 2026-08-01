<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates the task package admin uploads when approving an application.
 *
 * Extension and client mime type are both attacker-controlled, so neither is
 * trusted. The file is opened as a zip archive and must actually parse.
 */
class TaskDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_instructions' => ['required', 'string', 'min:20', 'max:20000'],
            'task_files'        => ['required', 'array', 'min:1', 'max:5'],
            'task_files.*'      => ['file', 'mimes:zip', 'mimetypes:application/zip,application/x-zip-compressed', 'max:51200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->hasFile('task_files')) {
                return;
            }

            foreach ($this->file('task_files') as $i => $file) {
                if (! $file->isValid()) {
                    $v->errors()->add("task_files.{$i}", 'Upload failed, please try again.');
                    continue;
                }

                if (! $this->isRealZip($file->getRealPath())) {
                    $v->errors()->add(
                        "task_files.{$i}",
                        'This file is not a readable zip archive. Renaming another file type to .zip will not work.'
                    );
                    continue;
                }

                if ($this->hasUnsafeEntries($file->getRealPath())) {
                    $v->errors()->add(
                        "task_files.{$i}",
                        'This archive contains unsafe paths (absolute or parent-directory entries). Rebuild it and try again.'
                    );
                }
            }
        });
    }

    /**
     * Local zip file header magic, then a real parse attempt.
     */
    protected function isRealZip(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $magic = fread($handle, 4);
        fclose($handle);

        // PK\x03\x04 normal, PK\x05\x06 empty archive, PK\x07\x08 spanned.
        $valid = ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"];
        if (! in_array($magic, $valid, true)) {
            return false;
        }

        if (! class_exists(\ZipArchive::class)) {
            // Without the zip extension the magic check is all we have.
            return true;
        }

        $zip = new \ZipArchive();
        $ok  = $zip->open($path, \ZipArchive::CHECKCONS) === true;
        if ($ok) {
            $zip->close();
        }

        return $ok;
    }

    /**
     * Rejects zip-slip style entries before the archive is ever handed to a worker.
     */
    protected function hasUnsafeEntries(string $path): bool
    {
        if (! class_exists(\ZipArchive::class)) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return true;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            if (str_starts_with($name, '/')
                || str_contains($name, '..')
                || preg_match('#^[a-zA-Z]:[\\\\/]#', $name)) {
                $zip->close();
                return true;
            }
        }

        $zip->close();
        return false;
    }
}
