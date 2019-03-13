<?php

namespace Cerpus\Gdpr\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeletionRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $theRules = [
            'deletionRequestId' => 'required|string|max:36|unique:gdpr_deletion_requests,id',
            'userId' => 'required|string|max:36',
        ];

        return $theRules;
    }

    public function messages()
    {
        return [
            'deletionRequestId.unique' => 'Another deletion request with the same Id already exist.'
        ];
    }
}
