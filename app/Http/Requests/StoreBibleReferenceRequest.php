<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBibleReferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof Document && ($this->user()?->can('update', $document) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'translation' => ['required', 'string', 'max:32'],
            'book_slug' => ['required', 'string', 'max:255'],
            'start_chapter' => ['required', 'integer', 'min:1', 'max:200'],
            'start_verse' => ['nullable', 'integer', 'min:1', 'max:200'],
            'end_chapter' => ['nullable', 'integer', 'min:1', 'max:200'],
            'end_verse' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
