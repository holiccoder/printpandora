<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\ShippingCountryCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null, ?string $shippingCountry = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            ...$this->shippingAddressRules($shippingCountry),
        ];
    }

    /**
     * Get the optional saved shipping address rules.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function shippingAddressRules(?string $shippingCountry = null): array
    {
        $stateCodes = ShippingCountryCatalog::stateCodesFor($shippingCountry);
        $stateRules = ['nullable', 'string', 'max:255'];

        if ($stateCodes !== []) {
            $stateRules[] = Rule::in($stateCodes);
        }

        return [
            'shipping_address' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:255'],
            'shipping_state' => $stateRules,
            'shipping_zip' => ['nullable', 'string', 'max:20'],
            'shipping_country' => [
                'nullable',
                'string',
                'size:2',
                Rule::in(ShippingCountryCatalog::codes()),
            ],
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
