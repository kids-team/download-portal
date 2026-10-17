<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('categories', 'slug')], 'name' => ['required', 'string', 'max:255'], 'icon' => ['nullable', 'string', 'max:255'], 'color' => ['nullable', 'string', 'max:255'], 'synonyms' => ['nullable', 'array'], 'synonyms.*' => ['string', 'max:255'],
        ];
    }
}
