<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->user_id, 'user_id'),
            ],
            'gender' => ['required', 'boolean'],
            'role' => $user->role === 'Balsuojantysis' 
                ? ['required', 'string', 'in:Balsuojantysis'] // Balsuojantysis can't change their role
                : ['required', 'string', 'max:32'],
            'pedagogical_name' => ['nullable', 'string', 'max:32'],
        ];
    }
}
