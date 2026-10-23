<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreSimulationOrderRequest extends FormRequest
{
    public function rules()
    {
        return [
            'course_title' => 'required|string|max:120',
            'organization' => 'required|string|max:120',

            // faculty_id required: dropdown must be chosen; '0' == Other
            'faculty_id' => 'required',

            // When Other is selected, require both name & email
            'faculty_name' => 'required_if:faculty_id,0|nullable|string|max:120',
            'faculty_email' => 'required_if:faculty_id,0|nullable|email|max:120',

            'learners' => 'required|integer|min:1|max:10000',
            'fee_per_learner' => 'required|numeric|min:0',
            'quarters' => 'required|integer|min:1|max:50',
            'teams' => 'required|integer|min:1|max:200',
            'start_date' => 'required|date',
            'payment_responsibility' => 'required|in:learner,institution',
            'time_zone' => 'required|timezone',
            'special_instructions' => 'nullable|string|max:250',
            'settings' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'faculty_id.required' => 'Please assign a faculty member (choose from the list or select "Other").',

            'faculty_name.required_if' => 'Please provide the faculty name when "Other" is selected.',
            'faculty_name.max' => 'Faculty name may not exceed 120 characters.',

            'faculty_email.required_if' => 'Please provide the faculty email when "Other" is selected.',
            'faculty_email.email' => 'Please provide a valid email address.',
            'faculty_email.max' => 'Faculty email may not exceed 120 characters.',

            // keep other messages you already had (course_title, organization, numeric rules, etc.)
            'course_title.required' => 'Please enter the course or simulation title.',
            'organization.required' => 'Please enter the name of the institution or organization.',
            'learners.required' => 'Please enter the number of learners.',
            'fee_per_learner.required' => 'Please enter the fee per learner (USD).',
            'quarters.required' => 'Please specify the number of quarters.',
            'quarter_duration.required' => 'Please specify the number of days per quarter.',
            'teams.required' => 'Please enter the number of firms/groups.',
            'start_date.required' => 'Please select a course start date.',
            'payment_responsibility.required' => 'Please select who will be billed.',
            'time_zone.required' => 'Please select a U.S. time zone.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'course_title' => $this->input('course_title') ? trim($this->input('course_title')) : null,
            'organization' => $this->input('organization') ? trim($this->input('organization')) : null,
            'faculty_name' => $this->input('faculty_name') ? trim($this->input('faculty_name')) : null,
            'faculty_email' => $this->input('faculty_email') ? trim($this->input('faculty_email')) : null,
            'time_zone' => $this->input('time_zone') ? trim($this->input('time_zone')) : null,
            'special_instructions' => $this->input('special_instructions') ? trim($this->input('special_instructions')) : null,
        ]);
    }

    /**
     * Additional conditional validation that depends on DB state:
     * - If a faculty id is selected and not '0' → ensure it exists.
     * - If faculty_id == '0' (Other) → ensure provided faculty_email DOES NOT exist in faculties table (unique).
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $facultyId = (string) $this->input('faculty_id');

            // If a real faculty was selected (not '0' and not empty), confirm it exists
            if ($facultyId !== '' && $facultyId !== '0') {
                $exists = DB::table('faculties')->where('id', $facultyId)->exists();
                if (! $exists) {
                    $validator->errors()->add('faculty_id', 'The selected faculty does not exist.');
                }
            }

            // If Other is selected, ensure the provided email is NOT already present in faculties
            if ($facultyId === '0') {
                $email = $this->input('faculty_email');

                if (! $email) {
                    // required_if will add message; keep this as guard
                    $validator->errors()->add('faculty_email', 'Faculty email is required when Other is selected.');
                } else {
                    $existsEmail = DB::table('faculties')->where('email', $email)->exists();
                    if ($existsEmail) {
                        $validator->errors()->add('faculty_email', 'This email already exists in our faculty records. Please choose the faculty from the list instead.');
                    }
                }

                // Also ensure faculty_name is present (required_if handles it but extra guard)
                if (! $this->input('faculty_name')) {
                    $validator->errors()->add('faculty_name', 'Faculty name is required when "Other" is selected.');
                }
            }
        });
    }
}
