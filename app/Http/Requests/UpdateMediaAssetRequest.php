<?php

namespace App\Http\Requests;

use App\Models\MediaAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mediaAsset = $this->route('media_asset');

        return $mediaAsset instanceof MediaAsset && ($this->user()?->can('update', $mediaAsset) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visibility' => ['required', Rule::in(['public', 'private'])],
        ];
    }
}
