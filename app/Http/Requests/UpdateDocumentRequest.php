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

class UpdateDocumentRequest extends FormRequest
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
        $document = $this->document();

        return [
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('documents', 'id'), Rule::notIn([$document->getKey()])],
            'category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')],
            'audience_id' => ['sometimes', 'nullable', 'integer', Rule::exists('audiences', 'id')],
            'cover_media_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media_assets', 'id')],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash:ascii'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'array'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'external_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:255'],
            'copyright_notice' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', new Enum(DocumentStatus::class)],
            'is_locked' => ['sometimes', Rule::prohibitedIf($this->user()?->role !== UserRole::Admin), 'boolean'],
            'published_at' => ['sometimes', 'nullable', 'date'],
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

            $document = $this->document();
            $parentId = $this->has('parent_id') ? $this->input('parent_id') : $document->parent_id;
            $slug = $this->has('slug') ? $this->string('slug')->toString() : $document->slug;

            $query = Document::query()->where('slug', $slug)->whereKeyNot($document);

            if ($parentId === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }

            if ($query->exists()) {
                $validator->errors()->add('slug', 'Der Slug ist innerhalb derselben Ebene bereits vergeben.');
            }

            $parent = $parentId === null ? null : Document::query()->find($parentId);

            while ($parent !== null) {
                if ($parent->is($document)) {
                    $validator->errors()->add('parent_id', 'Ein Dokument kann nicht unter sich selbst oder einem Unterdokument liegen.');

                    return;
                }

                $parent = $parent->parent;
            }

            $status = $this->has('status')
                ? DocumentStatus::tryFrom($this->input('status'))
                : $document->status;
            $publishedAt = $this->has('published_at') ? $this->input('published_at') : $document->published_at;

            if ($status === null) {
                return;
            }

            if ($status === DocumentStatus::Published && $publishedAt === null) {
                $validator->errors()->add('published_at', 'Ein Veröffentlichungszeitpunkt ist erforderlich.');
            }

            if ($this->has('status') && $this->user()?->role === UserRole::Editor && ! $status->isEditableByEditor()) {
                $validator->errors()->add('status', 'Editoren dürfen Dokumente nur als Entwurf oder zur Prüfung speichern.');
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
