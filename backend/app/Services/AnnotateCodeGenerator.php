<?php

namespace App\Services;

use App\Models\WorkSubmission;

/**
 * Annotate codes, e.g. AN-4F9QZK2M.
 *
 * Extracted from BatchApplicationApprovalService, where it was private. That was the
 * bug: only the batch job could issue a code, so every application an admin approved
 * by hand produced a worker with no way into the console. The two approval paths must
 * behave identically, and the surest way to guarantee that is one implementation.
 *
 * The alphabet excludes 0/O and 1/I/L. These codes are read aloud, retyped from
 * screenshots and pasted into support threads, and "was that a one or an ell" costs
 * more than the entropy those characters would add.
 */
class AnnotateCodeGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    private const LENGTH   = 8;
    private const PREFIX   = 'AN-';

    public function generate(): string
    {
        // Bounded rather than while(true). A collision across 31^8 is vanishingly
        // unlikely, so failing to find a free code means something else is wrong and
        // an exception is more useful than a spinning process.
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = self::PREFIX;

            for ($i = 0; $i < self::LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            if (! WorkSubmission::where('annotate_code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique annotate code after 20 attempts.');
    }
}
