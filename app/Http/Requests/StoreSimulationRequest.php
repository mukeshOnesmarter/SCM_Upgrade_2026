<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSimulationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|max:70',
            'organization' => 'required|max:70',
            'start_date' => 'required|date_format:m-d-Y|after_or_equal:today',
            'end_date' => 'required|date_format:m-d-Y|after:start_date',
            'quarters' => 'required|numeric|between:1,10',
            'firms' => 'required|numeric|between:1,50',
            'quarter_duration' => 'required|numeric|between:1,10',
            'timezone' => ['', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title field is required.',
            'title.max' => 'The title cannot be more than 70 characters.',

            'organization.required' => 'The organization field is required.',
            'organization.max' => 'The organization name cannot be more than 70 characters.',

            'start_date.required' => 'The start date is required.',
            'start_date.date' => 'Please provide a valid start date.',
            'start_date.date_format' => 'The start date must be in MM/DD/YYYY format.',
            'start_date.after' => 'The start date must be a future date.',

            'end_date.required' => 'The end date is required.',
            'end_date.date' => 'Please provide a valid end date.',
            'end_date.date_format' => 'The end date must be in MM/DD/YYYY format.',
            'end_date.after' => 'The end date must be after the start date.',

            'quarters.required' => 'The number of quarters is required.',
            'quarters.numeric' => 'The number of quarters must be a numeric value.',
            'quarters.between' => 'The number of quarters must be between 1 and 10.',

            'firms.required' => 'The number of firms is required.',
            'firms.numeric' => 'The number of firms must be a numeric value.',
            'firms.between' => 'The number of firms must be between 1 and 10.',
        ];
    }
}
