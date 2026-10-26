<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'ticker' => 'required|string|max:150',
            'comment' => 'nullable|string',
            'price' => 'required_without:total|nullable|numeric|min:0',
            'count' => 'required|numeric|min:1',
            'total' => 'required_without:price|nullable|numeric|min:0',
        ];

        if ($this->route('deal')) {
            $rules['ticker'] = 'nullable|string|max:150';
        }

        return $rules;
    }
}
