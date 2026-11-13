<?php

namespace App\Http\Requests;

use App\Models\Document;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MoveDocumentRequest extends FormRequest
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
            'parent_id' => ['present', 'nullable', 'integer', Rule::exists('documents', 'id')],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('parent_id')) {
                return;
            }

            $parentId = $this->input('parent_id');

            if ($parentId === null) {
                return;
            }

            $document = $this->document();
            $parent = Document::query()->find($parentId);

            while ($parent !== null) {
                if ($parent->is($document)) {
                    $validator->errors()->add('parent_id', 'Ein Dokument kann nicht unter sich selbst oder einem Unterdokument liegen.');

                    return;
                }

                $parent = $parent->parent;
            }
        }];
    }

    private function document(): Document
    {
        /** @var Document $document */
        $document = $this->route('document');

        return $document;
    }
}
