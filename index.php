<?php
/**
 * MedCare System - Login (entry page)
 * Signed-in users are sent straight to the dashboard for their role
 * (doctor, staff or patient) stored in the users table.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    redirectBasedOnRole();
}

$error = '';
$info = '';

if (isset($_GET['registered'])) {
    $info = 'Account created successfully! You can now log in with your credentials.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if (loginUser($username, $password)) {
            redirectBasedOnRole();
        } else {
            $error = 'Invalid username or password. Please verify your credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - MedCare System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Login page styles live here so index.php works even if style.css is missing or out of date */
        :root {
            --primary: #2563eb; --primary-hover: #1d4ed8; --primary-light: #eff6ff; --primary-border: #bfdbfe;
            --success: #16a34a; --success-light: #f0fdf4; --success-border: #86efac;
            --warning: #d97706; --danger: #dc2626; --danger-light: #fef2f2; --danger-border: #fca5a5;
            --slate-900: #0f172a; --slate-800: #1e293b; --slate-700: #334155; --slate-600: #475569;
            --slate-500: #64748b; --slate-400: #94a3b8; --slate-300: #cbd5e1; --slate-200: #e2e8f0; --slate-50: #f8fafc;
            --radius-sm: 6px; --radius-md: 10px; --radius-xl: 20px;
            --transition-fast: 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; }
        body.signin-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: var(--slate-800);
            -webkit-font-smoothing: antialiased;
        }
        .signin-page svg { display: block; }
        [hidden] { display: none !important; }

        /* ==========================================================================
           20. Sign-in Page (index.php)
           ========================================================================== */
        .signin-page {
            background:
                radial-gradient(ellipse at 15% 0%, rgba(219, 234, 254, 0.85), transparent 45%),
                linear-gradient(160deg, #f8fbff 0%, #eef3fa 100%);
        }

        .signin-shell {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }

        .signin-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
            width: 100%;
            max-width: 920px;
            min-height: 560px;
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-xl);
            box-shadow: 0 24px 60px rgba(30, 64, 110, 0.12), 0 4px 12px rgba(30, 64, 110, 0.05);
            overflow: hidden;
        }

        /* ---- Brand panel ---- */
        .signin-brand {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            padding: 2.25rem;
            color: #e0ecff;
            background:
                radial-gradient(circle at 85% 10%, rgba(255, 255, 255, 0.14), transparent 40%),
                linear-gradient(160deg, #1d4ed8 0%, #1e3a8a 100%);
        }

        .signin-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .signin-logo-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            flex-shrink: 0;
        }

        .signin-logo-mark svg {
            width: 22px;
            height: 22px;
        }

        .signin-logo-text {
            display: flex;
            flex-direction: column;
            color: #ffffff;
            font-weight: 800;
            font-size: 1.15rem;
            line-height: 1.2;
        }

        .signin-logo-text small {
            color: #bfd4ff;
            font-weight: 500;
            font-size: 0.78rem;
        }

        .signin-brand-body {
            margin-top: auto;
        }

        .signin-brand-body h1 {
            color: #ffffff;
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.02em;
            margin: 0 0 1.5rem;
            text-wrap: balance;
        }

        .signin-features {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.9rem;
        }

        .signin-features li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.45;
        }

        .signin-features svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            margin-top: 1px;
            color: #93c5fd;
        }

        .signin-brand-foot {
            margin: 0;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.16);
            color: #a9c3f5;
            font-size: 0.8rem;
        }

        /* ---- Form panel ---- */
        .signin-form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 2.75rem;
        }

        .signin-heading {
            margin-bottom: 1.5rem;
        }

        .signin-heading h2 {
            color: var(--slate-900);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 0.35rem;
        }

        .signin-heading p {
            color: var(--slate-500);
            font-size: 0.92rem;
            margin: 0;
        }

        .signin-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            padding: 0.75rem 0.9rem;
            margin-bottom: 1.1rem;
            border-radius: var(--radius-md);
            border: 1px solid;
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .signin-alert svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .signin-alert-error {
            background: var(--danger-light);
            border-color: var(--danger-border);
            color: #991b1b;
        }

        .signin-alert-success {
            background: var(--success-light);
            border-color: var(--success-border);
            color: #166534;
        }

        .signin-field {
            margin-bottom: 1rem;
        }

        .signin-field label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--slate-700);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .signin-input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .signin-input > svg {
            position: absolute;
            left: 0.9rem;
            width: 18px;
            height: 18px;
            color: var(--slate-400);
            pointer-events: none;
        }

        .signin-input input {
            width: 100%;
            min-height: 48px;
            padding: 0.7rem 0.9rem 0.7rem 2.7rem;
            border: 1px solid var(--slate-300);
            border-radius: var(--radius-md);
            background: #ffffff;
            color: var(--slate-900);
            font: inherit;
            font-size: 0.95rem;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
        }

        .signin-input input[type="password"],
        .signin-input input#password {
            padding-right: 4.5rem;
        }

        .signin-input input::placeholder {
            color: var(--slate-400);
        }

        .signin-input input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .signin-input:focus-within > svg {
            color: var(--primary);
        }

        .signin-reveal {
            position: absolute;
            right: 0.4rem;
            padding: 0.4rem 0.7rem;
            border: 0;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--primary);
            font: inherit;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        .signin-reveal:hover {
            background: var(--primary-light);
        }

        .signin-reveal:focus-visible,
        .signin-submit:focus-visible,
        .signin-register a:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .signin-caps,
        .signin-inline-error {
            margin: 0.4rem 0 0;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .signin-caps {
            color: var(--warning);
        }

        .signin-inline-error {
            margin: -0.25rem 0 0.9rem;
            color: var(--danger);
        }

        .signin-submit {
            width: 100%;
            min-height: 48px;
            margin-top: 0.35rem;
            border: 0;
            border-radius: var(--radius-md);
            background: var(--primary);
            color: #ffffff;
            font: inherit;
            font-size: 0.98rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
            transition: background-color var(--transition-fast), transform var(--transition-fast);
        }

        .signin-submit:hover {
            background: var(--primary-hover);
        }

        .signin-submit:active {
            transform: translateY(1px);
        }

        .signin-submit.is-loading {
            opacity: 0.8;
            cursor: progress;
        }

        .signin-register {
            margin: 1.4rem 0 0;
            text-align: center;
            color: var(--slate-500);
            font-size: 0.9rem;
        }

        .signin-register a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            border-radius: 4px;
        }

        .signin-register a:hover {
            text-decoration: underline;
        }

        /* ---- Tablets & phones: stack, brand becomes a compact strip ---- */
        @media (max-width: 820px) {
            .signin-shell {
                align-items: flex-start;
                padding: 1rem;
            }

            .signin-card {
                grid-template-columns: 1fr;
                min-height: 0;
                max-width: 460px;
            }

            .signin-brand {
                padding: 1.1rem 1.4rem;
                gap: 0;
            }

            .signin-brand-body,
            .signin-brand-foot {
                display: none;
            }

            .signin-form-panel {
                padding: 1.6rem 1.4rem 1.75rem;
            }

            .signin-heading h2 {
                font-size: 1.5rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .signin-submit,
            .signin-input input {
                transition: none;
            }
        }
    </style>
</head>
<body class="auth-page signin-page">
    <main class="signin-shell">
        <div class="signin-card">

            <!-- Brand panel (left on desktop, compact strip on phones) -->
            <aside class="signin-brand">
                <div class="signin-logo">
                    <span class="signin-logo-mark">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                            <path d="M12 5v6"/><path d="M9 8h6"/>
                        </svg>
                    </span>
                    <span class="signin-logo-text">MedCare<small>Health Portal</small></span>
                </div>

                <div class="signin-brand-body">
                    <h1>Patient records and care coordination in one place.</h1>
                    <ul class="signin-features">
                        <li>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            <span>Consultations, diagnoses and treatment progress</span>
                        </li>
                        <li>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                            <span>Prescriptions linked to live medicine stock</span>
                        </li>
                        <li>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span>Appointments for doctors and patients</span>
                        </li>
                    </ul>
                </div>

                <p class="signin-brand-foot">Municipality of Tupi, South Cotabato</p>
            </aside>

            <!-- Sign-in form -->
            <section class="signin-form-panel">
                <div class="signin-heading">
                    <h2>Sign in</h2>
                    <p>You will be taken to your Doctor, Staff or Patient portal automatically.</p>
                </div>

                <div aria-live="polite">
                    <?php if ($info): ?>
                        <div class="signin-alert signin-alert-success" role="status">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span><?php echo htmlspecialchars($info); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="signin-alert signin-alert-error" role="alert">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" class="signin-form" id="signinForm" novalidate>
                    <div class="signin-field">
                        <label for="username">Username</label>
                        <div class="signin-input">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" id="username" name="username" placeholder="Enter your username" required
                                   autocomplete="username" autocapitalize="none" spellcheck="false"
                                   <?php echo $error ? '' : 'autofocus'; ?>
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="signin-field">
                        <label for="password">Password</label>
                        <div class="signin-input">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required
                                   autocomplete="current-password" <?php echo $error ? 'autofocus' : ''; ?>>
                            <button type="button" class="signin-reveal" id="togglePassword" aria-label="Show password" aria-pressed="false">Show</button>
                        </div>
                        <p class="signin-caps" id="capsWarning" hidden>Caps Lock is on</p>
                    </div>

                    <p class="signin-inline-error" id="formError" hidden>Please enter both username and password.</p>

                    <button type="submit" class="signin-submit" id="signinSubmit">
                        <span class="signin-submit-label">Sign in</span>
                    </button>
                </form>

                <p class="signin-register">
                    New patient?
                    <a href="<?php echo BASE_URL; ?>/pages/auth/register.php">Create an account</a>
                </p>
            </section>
        </div>
    </main>

    <script>
    (function () {
        var form = document.getElementById('signinForm');
        var user = document.getElementById('username');
        var pass = document.getElementById('password');
        var reveal = document.getElementById('togglePassword');
        var caps = document.getElementById('capsWarning');
        var formError = document.getElementById('formError');
        var submit = document.getElementById('signinSubmit');

        // Show / hide password
        reveal.addEventListener('click', function () {
            var show = pass.type === 'password';
            pass.type = show ? 'text' : 'password';
            reveal.textContent = show ? 'Hide' : 'Show';
            reveal.setAttribute('aria-pressed', show ? 'true' : 'false');
            reveal.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            pass.focus();
        });

        // Warn when Caps Lock is on (passwords are case-sensitive)
        function checkCaps(e) {
            if (e.getModifierState) caps.hidden = !e.getModifierState('CapsLock');
        }
        pass.addEventListener('keydown', checkCaps);
        pass.addEventListener('keyup', checkCaps);
        pass.addEventListener('blur', function () { caps.hidden = true; });

        // Simple check before sending, and prevent double submits
        form.addEventListener('submit', function (e) {
            if (!user.value.trim() || !pass.value) {
                e.preventDefault();
                formError.hidden = false;
                (user.value.trim() ? pass : user).focus();
                return;
            }
            submit.disabled = true;
            submit.classList.add('is-loading');
            submit.querySelector('.signin-submit-label').textContent = 'Signing in…';
        });
    })();
    </script>
</body>
</html>
