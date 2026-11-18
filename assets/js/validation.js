/*
 * GUCAIR form validation (vanilla JavaScript only).
 *
 * These checks give quick feedback in the browser. PHP repeats every
 * important check on the server, so this file is not a security layer.
 */

(function () {
    'use strict';

    const registerForm = document.querySelector('[data-form="register"]');
    const loginForm = document.querySelector('[data-form="login"]');
    const assessmentForm = document.querySelector('[data-form="assessment"]');
    const profileForm = document.querySelector('[data-form="profile"]');

    /**
     * Find a named input inside one form.
     */
    function control(form, name) {
        return form.querySelector('[name="' + name + '"]');
    }

    /**
     * Find the matching inline error span.
     */
    function errorNode(form, name) {
        return form.querySelector('#' + name + '-error');
    }

    /**
     * Radio groups use a fieldset as the error target.
     */
    function errorTarget(form, name) {
        return form.querySelector('[data-field="' + name + '"]') || control(form, name);
    }

    function setError(form, name, message) {
        const target = errorTarget(form, name);
        const error = errorNode(form, name);

        if (target) {
            if (message) {
                target.setAttribute('aria-invalid', 'true');
            } else {
                target.removeAttribute('aria-invalid');
            }
        }

        if (error) {
            error.textContent = message;
        }
    }

    function valueOf(form, name) {
        const input = control(form, name);
        return input ? (input.value || '').trim() : '';
    }

    function rawValueOf(form, name) {
        const input = control(form, name);
        return input ? (input.value || '') : '';
    }

    function checkedValue(form, name) {
        const input = form.querySelector('[name="' + name + '"]:checked');
        return input ? input.value : '';
    }

    function required(value, label) {
        return value === '' ? label + ' is required.' : '';
    }

    function emailError(value) {
        if (value === '') {
            return 'Email is required.';
        }

        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(value) ? '' : 'Enter a valid email address.';
    }

    function urlError(value) {
        if (value === '') {
            return 'Website URL is required.';
        }

        try {
            const parsed = new URL(value);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:'
                ? ''
                : 'Website URL must use http or https.';
        } catch (error) {
            return 'Enter a valid website URL, including https://';
        }
    }

    function passwordError(value) {
        if (value === '') {
            return 'Password is required.';
        }

        return value.length >= 8 ? '' : 'Password must be at least 8 characters.';
    }

    /**
     * Show all errors and focus the first invalid field.
     */
    function applyErrors(form, errors, order) {
        let firstInvalid = null;

        order.forEach(function (name) {
            setError(form, name, errors[name] || '');

            if (errors[name] && !firstInvalid) {
                firstInvalid = control(form, name);
            }
        });

        if (firstInvalid) {
            firstInvalid.focus();
        }
    }

    function hasErrors(errors) {
        return Object.keys(errors).some(function (name) {
            return errors[name] !== '';
        });
    }

    /**
     * Clear a field error as soon as the user starts correcting it.
     */
    function watchFields(form, names) {
        names.forEach(function (name) {
            const input = control(form, name);

            if (input) {
                input.addEventListener('input', function () {
                    setError(form, name, '');
                });
            }
        });
    }

    /**
     * Radio groups fire "change", not "input", for each option.
     */
    function watchRatings(form, names) {
        names.forEach(function (name) {
            const inputs = form.querySelectorAll('[name="' + name + '"]');

            inputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    setError(form, name, '');
                });
            });
        });
    }

    if (registerForm) {
        const registerOrder = ['name', 'country', 'email', 'website', 'contact_person', 'password'];

        watchFields(registerForm, registerOrder);

        registerForm.addEventListener('submit', function (event) {
            const errors = {
                name: required(valueOf(registerForm, 'name'), 'University name'),
                country: required(valueOf(registerForm, 'country'), 'Country'),
                email: emailError(valueOf(registerForm, 'email')),
                website: urlError(valueOf(registerForm, 'website')),
                contact_person: required(valueOf(registerForm, 'contact_person'), 'Contact person'),
                password: passwordError(rawValueOf(registerForm, 'password'))
            };

            applyErrors(registerForm, errors, registerOrder);

            if (hasErrors(errors)) {
                // Do not clear the form or send invalid data to PHP.
                event.preventDefault();
            }
        });
    }

    if (loginForm) {
        const loginOrder = ['email', 'password'];

        watchFields(loginForm, loginOrder);

        loginForm.addEventListener('submit', function (event) {
            const errors = {
                email: emailError(valueOf(loginForm, 'email')),
                password: required(rawValueOf(loginForm, 'password'), 'Password')
            };

            applyErrors(loginForm, errors, loginOrder);

            if (hasErrors(errors)) {
                event.preventDefault();
            }
        });
    }

    if (assessmentForm) {
        const assessmentOrder = [
            'ai_education',
            'ai_research',
            'ai_infrastructure',
            'faculty_knowledge',
            'ai_policy'
        ];

        watchRatings(assessmentForm, assessmentOrder);

        assessmentForm.addEventListener('submit', function (event) {
            const errors = {};

            assessmentOrder.forEach(function (name) {
                const target = errorTarget(assessmentForm, name);
                const label = target && target.getAttribute('data-label')
                    ? target.getAttribute('data-label')
                    : 'This rating';

                errors[name] = checkedValue(assessmentForm, name) === ''
                    ? label + ' is required.'
                    : '';
            });

            applyErrors(assessmentForm, errors, assessmentOrder);

            if (hasErrors(errors)) {
                event.preventDefault();
            }
        });
    }

    if (profileForm) {
        const profileOrder = ['country', 'website', 'contact_person'];

        watchFields(profileForm, profileOrder);

        profileForm.addEventListener('submit', function (event) {
            const errors = {
                country: required(valueOf(profileForm, 'country'), 'Country'),
                website: urlError(valueOf(profileForm, 'website')),
                contact_person: required(valueOf(profileForm, 'contact_person'), 'Contact person')
            };

            applyErrors(profileForm, errors, profileOrder);

            if (hasErrors(errors)) {
                event.preventDefault();
            }
        });
    }

    // Expose small helpers in case later lab pages need the same checks.
    window.GUCAIRValidation = {
        setError: setError,
        checkedValue: checkedValue,
        emailError: emailError,
        urlError: urlError,
        passwordError: passwordError
    };
})();
