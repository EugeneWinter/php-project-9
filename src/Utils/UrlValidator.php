<?php

declare(strict_types=1);

namespace App\Utils\Validators;

class UrlValidator
{
    public static function validate(string $url): array
    {
        $errors = [];

        if (empty(trim($url))) {
            $errors['name'] = 'URL не должен быть пустым';
        } elseif (strlen($url) > 255) {
            $errors['name'] = 'URL слишком длинный';
        } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors['name'] = 'Некорректный URL';
        }

        return $errors;
    }
}
