import 'altcha';
import { initPasswordStrength } from './password-strength';

document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.querySelector('[data-register-form]');

    if (!registerForm) {
        return;
    }

    const policy = JSON.parse(registerForm.dataset.passwordPolicy || '{}');
    initPasswordStrength(registerForm, policy);
});
