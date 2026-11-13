<?php

namespace App\Http\Requests;

use App\Models\User;
use App\UserRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateManagedUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userToUpdate())],
            'role' => ['sometimes', new Enum(UserRole::class)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('role') || ! $this->has('role')) {
                return;
            }

            $user = $this->userToUpdate();

            if ($user->role === UserRole::Admin && $this->input('role') !== UserRole::Admin->value && User::query()->where('role', UserRole::Admin)->count() === 1) {
                $validator->errors()->add('role', 'Der letzte Administrator kann nicht zur Redaktion herabgestuft werden.');
            }
        }];
    }

    private function userToUpdate(): User
    {
        /** @var User $user */
        $user = $this->route('user');

        return $user;
    }
}
