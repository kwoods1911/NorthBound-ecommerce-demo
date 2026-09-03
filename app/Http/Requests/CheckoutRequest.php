<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:32'],
            'country' => ['required', 'string', 'size:2'],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function shipping(): array
    {
        return [
            'email' => $this->validated('email'),
            'name' => $this->validated('name'),
            'line1' => $this->validated('line1'),
            'line2' => $this->validated('line2'),
            'city' => $this->validated('city'),
            'state' => $this->validated('state'),
            'postal_code' => $this->validated('postal_code'),
            'country' => strtoupper($this->validated('country')),
        ];
    }
}
