<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'locale' => ['required', Rule::in(['de', 'en'])],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'current_password' => ['required_with:password', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['password', 'current_password']) || ! $this->filled('password')) {
                return;
            }

            if (! Hash::check($this->string('current_password')->toString(), $this->user()->password)) {
                $validator->errors()->add('current_password', 'Das aktuelle Passwort ist nicht korrekt.');
            }
        }];
    }
}
