<?php

namespace App;

use Valitron\Validator as ValitronValidator;

class UrlValidator
{
    public function validate(array $urlData): array
    {
        $errors = [];

        $validator = new ValitronValidator($urlData);
        $validator->rule('required', 'name')->message('URL не должен быть пустым');
        $validator->rule('url', 'name')->message('Некорректный URL');
        $validator->rule('lengthMax', 'name', 255)->message('URL должен быть не более 255 знаков');

        if (!$validator->validate()) {
            $errors['name'] = $validator->errors('name');
        }

        return $errors;
    }
}
