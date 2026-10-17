<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('category')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('categories', 'slug')->ignore($this->route('category'))], 'name' => ['sometimes', 'required', 'string', 'max:255'], 'icon' => ['sometimes', 'nullable', 'string', 'max:255'], 'color' => ['sometimes', 'nullable', 'string', 'max:255'], 'synonyms' => ['sometimes', 'nullable', 'array'], 'synonyms.*' => ['string', 'max:255'],
        ];
    }
}
