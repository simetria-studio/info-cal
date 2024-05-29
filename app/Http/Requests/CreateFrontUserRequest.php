<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFrontUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|regex:/(^([a-zA-z]+)(\d+)?$)/u',
            'last_name' => 'required|string|regex:/(^([a-zA-z]+)(\d+)?$)/u',
            'email' => 'required|email:filter|unique:users,email',
            'password' => 'required|same:password_confirmation|min:6',
        ];
    }
}
