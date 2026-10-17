<?php

namespace App\Http\Requests;

use App\DocumentStatus;
use App\Models\Document;
use App\UserRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('documents', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'audience_id' => ['nullable', 'integer', Rule::exists('audiences', 'id')],
            'cover_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'summary' => ['nullable', 'string'],
            'label' => ['nullable', 'string', 'max:255'],
            'external_url' => ['nullable', 'url', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'copyright_notice' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', new Enum(DocumentStatus::class)],
            'is_locked' => [Rule::prohibitedIf($this->user()?->role !== UserRole::Admin), 'boolean'],
            'published_at' => ['nullable', 'date'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists('tags', 'id')],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['parent_id', 'slug'])) {
                return;
            }

            $query = Document::query()->where('slug', $this->string('slug')->toString());

            if ($this->input('parent_id') === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $this->integer('parent_id'));
            }

            if ($query->exists()) {
                $validator->errors()->add('slug', 'Der Slug ist innerhalb derselben Ebene bereits vergeben.');
            }

            $status = DocumentStatus::tryFrom($this->input('status', DocumentStatus::Draft->value));

            if ($status === null) {
                return;
            }

            if ($status === DocumentStatus::Published && $this->input('published_at') === null) {
                $validator->errors()->add('published_at', 'Ein Veröffentlichungszeitpunkt ist erforderlich.');
            }

            if ($this->user()?->role === UserRole::Editor && ! $status->isEditableByEditor()) {
                $validator->errors()->add('status', 'Editoren dürfen Dokumente nur als Entwurf oder zur Prüfung speichern.');
            }
        }];
    }
}
