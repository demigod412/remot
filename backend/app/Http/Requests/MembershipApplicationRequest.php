<?php

namespace App\Http\Requests;

use App\Models\MembershipApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $docTypes = implode(',', config('jobstation.membership.allowed_doc_types', ['pdf', 'doc', 'docx']));
        $maxKb    = (int) config('jobstation.membership.max_doc_size_kb', 5120);

        return [
            'full_name'      => ['required', 'string', 'min:3', 'max:120'],
            'email'          => ['required', 'email:rfc,dns', 'max:120', 'unique:membership_applications,email', 'unique:users,email'],
            'phone'          => ['nullable', 'string', 'max:40'],
            'country'        => ['required', 'string', 'max:80'],
            'applicant_type' => ['required', 'integer', 'in:1,2'],

            'resume'         => ['required', 'file', "mimes:{$docTypes}", "max:{$maxKb}"],
            'cover_letter'   => ['nullable', 'file', "mimes:{$docTypes}", "max:{$maxKb}"],

            // Conditionally required — see withValidator().
            'business_name'             => ['nullable', 'string', 'max:160'],
            'business_email'            => ['nullable', 'email:rfc', 'max:120'],
            'business_country'          => ['nullable', 'string', 'max:80'],
            'business_registration_doc' => ['nullable', 'file', "mimes:{$docTypes}", "max:{$maxKb}"],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ((int) $this->input('applicant_type') !== MembershipApplication::TYPE_BUSINESS) {
                return;
            }

            // Business applicants must actually supply the business block. Doing
            // this here rather than with required_if keeps the messages readable.
            foreach ([
                'business_name'             => 'Business name is required for a business application.',
                'business_email'            => 'Business email is required for a business application.',
                'business_country'          => 'Business country is required for a business application.',
                'business_registration_doc' => 'A business registration document is required.',
            ] as $field => $message) {
                if (blank($this->input($field)) && ! $this->hasFile($field)) {
                    $v->errors()->add($field, $message);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.unique'    => 'An application or account already exists for this email address.',
            'resume.required' => 'Please attach your CV or resume.',
        ];
    }
}
