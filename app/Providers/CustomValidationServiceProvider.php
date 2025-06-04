<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as BaseValidator;

class CustomValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Validator::resolver(function ($translator, $data, $rules, $messages, $customAttributes) {
            return new class($translator, $data, $rules, $messages, $customAttributes) extends BaseValidator {
                protected function getMessage($attribute, $rule)
                {
                    $message = parent::getMessage($attribute, $rule);

                    return translate($message);
                }

                public function makeReplacements($message, $attribute, $rule, $parameters)
                {
                    $message = parent::makeReplacements($message, $attribute, $rule, $parameters);

                    return translate($message);
                }
            };
        });
    }
}
