<?php
/**
 * Shared PHP validation helpers for GUCAIR forms.
 *
 * JavaScript gives quick feedback in the browser, but these functions
 * repeat the checks on the server where users cannot skip them.
 */

if (!function_exists('e')) {
    /**
     * Escape a value before printing it inside HTML.
     */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('old_value')) {
    /**
     * Return a submitted POST value so a failed form does not lose input.
     * Callers should still use e() when printing this value.
     */
    function old_value(string $key, string $default = ''): string
    {
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
            return $default;
        }

        return $_POST[$key];
    }
}

if (!function_exists('field_error')) {
    /**
     * Return the error text for one field, or an empty string.
     */
    function field_error(array $errors, string $key): string
    {
        return isset($errors[$key]) ? (string) $errors[$key] : '';
    }
}

if (!function_exists('has_error')) {
    function has_error(array $errors, string $key): bool
    {
        return isset($errors[$key]);
    }
}

if (!function_exists('invalid_attr')) {
    /**
     * Add aria-invalid only when the field has a server-side error.
     */
    function invalid_attr(array $errors, string $key): string
    {
        return has_error($errors, $key) ? ' aria-invalid="true"' : '';
    }
}

if (!function_exists('rating_checked')) {
    /**
     * Keep a radio button selected after a failed form submission.
     */
    function rating_checked(string $key, int $value): string
    {
        return old_value($key) === (string) $value ? ' checked' : '';
    }
}

if (!function_exists('validate_required_text')) {
    function validate_required_text($value, string $label, int $maxLength = 150): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $label . ' is required.';
        }

        if (strlen($value) > $maxLength) {
            return $label . ' must be ' . $maxLength . ' characters or fewer.';
        }

        return '';
    }
}

if (!function_exists('validate_email')) {
    function validate_email($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Email is required.';
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }

        if (strlen($value) > 254) {
            return 'Email is too long.';
        }

        return '';
    }
}

if (!function_exists('validate_url')) {
    function validate_url($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Website URL is required.';
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return 'Enter a valid website URL, including https://';
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return 'Website URL must use http or https.';
        }

        return '';
    }
}

if (!function_exists('validate_password')) {
    function validate_password($value): string
    {
        // Do not trim passwords: spaces may be intentional.
        $value = (string) $value;

        if ($value === '') {
            return 'Password is required.';
        }

        if (strlen($value) < 8) {
            return 'Password must be at least 8 characters.';
        }

        return '';
    }
}

if (!function_exists('validate_registration')) {
    /**
     * Validate the registration form.
     *
     * Returns [$cleanValues, $errors].
     */
    function validate_registration(array $post): array
    {
        $values = [
            'name'           => trim((string) ($post['name'] ?? '')),
            'country'        => trim((string) ($post['country'] ?? '')),
            // Lowercase emails make duplicate checks case-insensitive.
            'email'          => strtolower(trim((string) ($post['email'] ?? ''))),
            'website'        => trim((string) ($post['website'] ?? '')),
            'contact_person' => trim((string) ($post['contact_person'] ?? '')),
            'password'       => (string) ($post['password'] ?? ''),
        ];

        $errors = [];

        $message = validate_required_text($values['name'], 'University name', 120);
        if ($message !== '') {
            $errors['name'] = $message;
        }

        $message = validate_required_text($values['country'], 'Country', 100);
        if ($message !== '') {
            $errors['country'] = $message;
        }

        $message = validate_email($values['email']);
        if ($message !== '') {
            $errors['email'] = $message;
        }

        $message = validate_url($values['website']);
        if ($message !== '') {
            $errors['website'] = $message;
        }

        $message = validate_required_text($values['contact_person'], 'Contact person', 120);
        if ($message !== '') {
            $errors['contact_person'] = $message;
        }

        $message = validate_password($values['password']);
        if ($message !== '') {
            $errors['password'] = $message;
        }

        return [$values, $errors];
    }
}

if (!function_exists('validate_login')) {
    /**
     * Validate the login form. Returns [$cleanValues, $errors].
     */
    function validate_login(array $post): array
    {
        $values = [
            'email'    => strtolower(trim((string) ($post['email'] ?? ''))),
            'password' => (string) ($post['password'] ?? ''),
        ];

        $errors = [];

        $message = validate_email($values['email']);
        if ($message !== '') {
            $errors['email'] = $message;
        }

        // For login, only require a password here. password_verify() checks it later.
        if ($values['password'] === '') {
            $errors['password'] = 'Password is required.';
        }

        return [$values, $errors];
    }
}

if (!function_exists('validate_profile')) {
    /**
     * Validate the editable profile fields. Returns [$cleanValues, $errors].
     */
    function validate_profile(array $post): array
    {
        $values = [
            'country'        => trim((string) ($post['country'] ?? '')),
            'website'        => trim((string) ($post['website'] ?? '')),
            'contact_person' => trim((string) ($post['contact_person'] ?? '')),
        ];

        $errors = [];

        $message = validate_required_text($values['country'], 'Country', 100);
        if ($message !== '') {
            $errors['country'] = $message;
        }

        $message = validate_url($values['website']);
        if ($message !== '') {
            $errors['website'] = $message;
        }

        $message = validate_required_text($values['contact_person'], 'Contact person', 120);
        if ($message !== '') {
            $errors['contact_person'] = $message;
        }

        return [$values, $errors];
    }
}
