export function initPasswordStrength(root, policy) {
    const password = root.querySelector('#password');
    const confirmation = root.querySelector('#password_confirmation');
    const meter = root.querySelector('[data-password-meter]');
    const bar = root.querySelector('[data-password-bar]');
    const label = root.querySelector('[data-password-label]');
    const checks = root.querySelector('[data-password-checks]');

    if (!password || !meter || !bar || !label) {
        return;
    }

    const rules = [
        {
            key: 'length',
            label: `At least ${policy.minLength} characters`,
            test: (value) => value.length >= policy.minLength,
        },
        {
            key: 'lower',
            label: 'Lowercase letter',
            test: (value) => !policy.requireLowercase || /[a-z]/.test(value),
            optional: !policy.requireLowercase,
        },
        {
            key: 'upper',
            label: 'Uppercase letter',
            test: (value) => !policy.requireUppercase || /[A-Z]/.test(value),
            optional: !policy.requireUppercase,
        },
        {
            key: 'number',
            label: 'Number',
            test: (value) => !policy.requireNumber || /[0-9]/.test(value),
            optional: !policy.requireNumber,
        },
        {
            key: 'symbol',
            label: 'Symbol',
            test: (value) => !policy.requireSymbol || /[^a-zA-Z0-9]/.test(value),
            optional: !policy.requireSymbol,
        },
    ];

    const classCount = (value) => {
        let count = 0;

        if (/[a-z]/.test(value)) count++;
        if (/[A-Z]/.test(value)) count++;
        if (/[0-9]/.test(value)) count++;
        if (/[^a-zA-Z0-9]/.test(value)) count++;

        return count;
    };

    const score = (value) => {
        if (!value) return 0;

        let total = Math.min(40, (value.length / policy.minLength) * 40);
        total += classCount(value) * 12;

        const requiredPass = rules
            .filter((rule) => !rule.optional)
            .every((rule) => rule.test(value));

        const classesPass =
            policy.minCharacterClasses <= 0 ||
            classCount(value) >= policy.minCharacterClasses;

        if (requiredPass && classesPass) {
            total += 12;
        }

        return Math.min(100, Math.round(total));
    };

    const strengthLabel = (value) => {
        const points = score(value);

        if (!value) return 'Enter a password';
        if (points < 35) return 'Weak';
        if (points < 65) return 'Fair';
        if (points < 85) return 'Good';

        return 'Strong';
    };

    const renderChecks = (value) => {
        if (!checks) return;

        const items = [...rules];

        if (policy.minCharacterClasses > 0) {
            items.push({
                key: 'classes',
                label: `${policy.minCharacterClasses} character types`,
                test: (current) => classCount(current) >= policy.minCharacterClasses,
            });
        }

        checks.innerHTML = items
            .map((rule) => {
                const passed = value !== '' && rule.test(value);

                return `<li class="${passed ? 'text-emerald-400' : 'text-zinc-500'}">${rule.label}</li>`;
            })
            .join('');
    };

    const paint = () => {
        const value = password.value;
        const points = score(value);

        bar.style.width = `${points}%`;
        bar.className = 'h-full rounded-full transition-all duration-200';

        if (points < 35) {
            bar.classList.add('bg-red-500');
        } else if (points < 65) {
            bar.classList.add('bg-amber-500');
        } else if (points < 85) {
            bar.classList.add('bg-emerald-500');
        } else {
            bar.classList.add('bg-emerald-400');
        }

        label.textContent = strengthLabel(value);
        renderChecks(value);

        if (confirmation) {
            const mismatch = confirmation.value !== '' && confirmation.value !== value;
            confirmation.classList.toggle('border-red-500/70', mismatch);
        }
    };

    password.addEventListener('input', paint);
    confirmation?.addEventListener('input', paint);
    paint();
}
