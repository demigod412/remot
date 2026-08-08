<?php

namespace App\Services;

/**
 * Validates an uploaded task JSON file against what the annotation console can
 * actually render, and produces the browser-safe copy.
 *
 * VALIDATION IS NOT OPTIONAL POLITENESS HERE
 *
 * A task that passes upload but fails in the console fails in front of a worker who
 * has already paid a non-refundable fee to be there. Every rule below exists because
 * breaking it produces either a blank screen or a question that cannot be answered:
 * an unknown type falls through to a text box, a choice question with no options
 * renders nothing, a likert with min >= max produces no scale.
 *
 * The renderer names are taken from the console's own RENDERERS map. If a type is
 * added there, add it here or uploads of it will be refused.
 */
class TaskDataValidator
{
    public const TYPES = [
        'single_choice',
        'multi_choice',
        'likert',
        'rating_scale',
        'free_text',
        'ranking',
        'pairwise_comparison',
        'span_highlight',
    ];

    private const MAX_QUESTIONS = 200;

    /**
     * @return array{ok: bool, errors: array<int,string>, data: array|null, questions: int}
     */
    public function validate(string $json): array
    {
        $errors = [];

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->fail(['Not valid JSON: ' . json_last_error_msg()]);
        }

        if (! is_array($decoded)) {
            return $this->fail(['The file must contain a JSON object.']);
        }

        // Keys beginning with an underscore are documentation in the template and
        // are stripped rather than rejected, so the annotated template can be
        // edited and uploaded directly.
        $decoded = $this->stripComments($decoded);

        // ---- meta ----
        $meta = $decoded['meta'] ?? null;

        if (! is_array($meta)) {
            $errors[] = 'Missing "meta" object.';
        } else {
            if (blank($meta['title'] ?? null)) {
                $errors[] = 'meta.title is required — it is what the worker sees as the task heading.';
            }
            if (isset($meta['estimated_minutes']) && ! is_numeric($meta['estimated_minutes'])) {
                $errors[] = 'meta.estimated_minutes must be a number.';
            }
        }

        // ---- questions ----
        $questions = $decoded['questions'] ?? null;

        if (! is_array($questions) || $questions === []) {
            $errors[] = 'Missing "questions" array, or it is empty.';
            return $this->fail($errors);
        }

        if (count($questions) > self::MAX_QUESTIONS) {
            $errors[] = sprintf('That is %d questions. The limit is %d.', count($questions), self::MAX_QUESTIONS);
        }

        $seenIds = [];

        foreach ($questions as $i => $q) {
            $n = $i + 1;

            if (! is_array($q)) {
                $errors[] = "Question {$n} is not an object.";
                continue;
            }

            $id = $q['id'] ?? null;

            if (blank($id)) {
                $errors[] = "Question {$n}: missing \"id\".";
            } elseif (isset($seenIds[$id])) {
                // Duplicate ids silently overwrite each other in the answer map, so
                // one question's answer would replace another's.
                $errors[] = "Question {$n}: duplicate id \"{$id}\" — ids must be unique.";
            } else {
                $seenIds[$id] = true;
            }

            $type = $q['type'] ?? null;

            if (! in_array($type, self::TYPES, true)) {
                $errors[] = sprintf(
                    'Question %d: type "%s" is not supported. Use one of: %s.',
                    $n,
                    is_string($type) ? $type : gettype($type),
                    implode(', ', self::TYPES)
                );
                continue;
            }

            if (blank($q['prompt'] ?? null)) {
                $errors[] = "Question {$n}: missing \"prompt\".";
            }

            foreach ($this->typeErrors($q, $type, $n) as $e) {
                $errors[] = $e;
            }
        }

        if ($errors !== []) {
            return $this->fail($errors);
        }

        return [
            'ok'        => true,
            'errors'    => [],
            'data'      => $decoded,
            'questions' => count($questions),
        ];
    }

    /**
     * @return array<int,string>
     */
    private function typeErrors(array $q, string $type, int $n): array
    {
        $errors = [];

        if (in_array($type, ['single_choice', 'multi_choice', 'ranking'], true)) {
            $options = $q['options'] ?? null;

            if (! is_array($options) || count($options) < 2) {
                $errors[] = "Question {$n}: \"{$type}\" needs at least 2 options.";
            } else {
                $values = [];
                foreach ($options as $j => $opt) {
                    if (! is_array($opt) || blank($opt['value'] ?? null) || blank($opt['label'] ?? null)) {
                        $errors[] = "Question {$n}, option " . ($j + 1) . ': each option needs "value" and "label".';
                        continue;
                    }
                    if (isset($values[$opt['value']])) {
                        // The answer is stored by value, so duplicates make the
                        // response ambiguous.
                        $errors[] = "Question {$n}: duplicate option value \"{$opt['value']}\".";
                    }
                    $values[$opt['value']] = true;
                }
            }
        }

        if (in_array($type, ['likert', 'rating_scale'], true)) {
            $scale = $q['scale'] ?? null;

            if (! is_array($scale) || ! isset($scale['min'], $scale['max'])) {
                $errors[] = "Question {$n}: \"{$type}\" needs a \"scale\" with min and max.";
            } elseif ((float) $scale['min'] >= (float) $scale['max']) {
                $errors[] = "Question {$n}: scale.min must be less than scale.max.";
            } elseif (isset($scale['step']) && (float) $scale['step'] <= 0) {
                $errors[] = "Question {$n}: scale.step must be greater than zero.";
            }
        }

        if ($type === 'pairwise_comparison') {
            foreach (['response_a', 'response_b'] as $field) {
                if (blank($q[$field] ?? null)) {
                    $errors[] = "Question {$n}: \"pairwise_comparison\" needs \"{$field}\".";
                }
            }
        }

        if ($type === 'span_highlight' && blank($q['context'] ?? null)) {
            // The context IS the clickable material. Without it the question renders
            // a prompt and nothing to click.
            $errors[] = "Question {$n}: \"span_highlight\" needs \"context\" — that text is what the worker clicks.";
        }

        if ($type === 'free_text') {
            $min = $q['min_length'] ?? null;
            $max = $q['max_length'] ?? null;

            if ($min !== null && $max !== null && (int) $min > (int) $max) {
                $errors[] = "Question {$n}: min_length is greater than max_length, so no answer can ever be valid.";
            }
        }

        if (isset($q['code'])) {
            if (! is_array($q['code']) || blank($q['code']['content'] ?? null)) {
                $errors[] = "Question {$n}: \"code\" needs a \"content\" string.";
            }
        }

        return $errors;
    }

    /**
     * The copy sent to the browser.
     *
     * Anything an answer could be derived from is removed here rather than trusted
     * to stay unrendered. The console never displayed gold answers, but "not shown"
     * and "not sent" are very different things once this is served over HTTP —
     * devtools shows the whole payload.
     *
     * Gold questions were dropped from the format, so this currently strips fields
     * that should not appear at all. It stays because a future format change that
     * reintroduces them must not silently ship answer keys.
     */
    public function forBrowser(array $data): array
    {
        $stripped = ['is_gold', 'expected_answer', 'gold_tolerance', 'answer', 'correct'];

        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $i => $q) {
                foreach ($stripped as $key) {
                    unset($data['questions'][$i][$key]);
                }
            }
        }

        return $data;
    }

    private function stripComments(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && str_starts_with($key, '_')) {
                unset($data[$key]);
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->stripComments($value);
            }
        }

        return $data;
    }

    private function fail(array $errors): array
    {
        return ['ok' => false, 'errors' => $errors, 'data' => null, 'questions' => 0];
    }
}
