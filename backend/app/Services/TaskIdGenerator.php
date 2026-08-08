<?php

namespace App\Services;

use App\Models\Work;

/**
 * Public task references, e.g. TASK-8K3QF.
 *
 * The alphabet excludes 0/O and 1/I/L. These IDs get read aloud on calls, retyped
 * from screenshots and pasted into support tickets, and "was that a one or an ell"
 * costs more than the two extra characters of entropy.
 *
 * 5 characters from a 31-character alphabet is about 28.6 million combinations —
 * ample for a task list, and short enough to say out loud in one breath.
 */
class TaskIdGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    private const LENGTH   = 5;
    private const PREFIX   = 'TASK-';

    public function generate(): string
    {
        // Bounded rather than while(true): a collision at this size is vanishingly
        // unlikely, so if it somehow cannot find a free one, something is wrong and
        // an exception is more useful than a hang.
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $id = self::PREFIX;

            for ($i = 0; $i < self::LENGTH; $i++) {
                $id .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            if (! Work::where('task_id', $id)->exists()) {
                return $id;
            }
        }

        throw new \RuntimeException('Could not generate a unique task ID after 20 attempts.');
    }
}
