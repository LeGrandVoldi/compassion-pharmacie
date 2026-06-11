<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Activation du compte - Compassion Pharmacie</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('Imgs/Logos/logo_1.png') }}">

    <style>
        html,
        body {
            height: auto;
            overflow-y: auto;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-input {
            width: 100%;
            padding: 12px 45px;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: #666;
            z-index: 10;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #666;
            font-size: 18px;
            z-index: 10;
        }

        .error-message {
            display: block;
            margin-top: 5px;
            color: red;
            font-size: 13px;
        }

        .success-message {
            display: block;
            margin-top: 5px;
            color: green;
            font-size: 13px;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            margin-right: 8px;
        }

        #activationError {
            display: none;
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        #activationSuccess {
            display: none;
            color: #198754;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>

<body class="login-page">

    <!-- Décoration -->
    <div class="login-bg-cross bl">
        <i class="bi bi-plus-lg"></i>
    </div>

    <div class="login-bg-cross tr">
        <i class="bi bi-plus-lg"></i>
    </div>

    <div style="position:fixed;bottom:60px;right:60px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.35);pointer-events:none;z-index:1;"></div>

    <div style="position:fixed;bottom:90px;right:120px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.2);pointer-events:none;z-index:1;"></div>

    <div class="login-wrapper">

        <!-- Logo -->
        <div class="login-logo">
            <img
                src="{{ asset('Imgs/Logos/logo_full.png') }}"
                alt="Compassion Pharmacie"
                width="200"
                height="90">
        </div>

        <!-- Sous-titre -->
        <p class="login-subtitle">
            Activation de votre compte utilisateur
        </p>

        <!-- Carte -->
        <div class="login-card">

            <form id="activationForm" action="/activation-compte" method="POST">

                @csrf

                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="id" value="{{ $id }}">

                <!-- Messages -->
                <div id="activationError"></div>

                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Nouveau mot de passe -->
                <div class="login-field">

                    <label for="password">
                        Nouveau mot de passe
                    </label>

                    <div class="input-group">

                        <i class="bi bi-lock input-icon"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Nouveau mot de passe"
                            required>

                        <i class="bi bi-eye-slash toggle-password"
                           onclick="togglePassword('password', this)">
                        </i>

                    </div>

                    <small id="passwordError" class="error-message"></small>

                </div>

                <!-- Confirmation -->
                <div class="login-field">

                    <label for="confirmPassword">
                        Confirmer le mot de passe
                    </label>

                    <div class="input-group">

                        <i class="bi bi-shield-lock input-icon"></i>

                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirmPassword"
                            class="form-input"
                            placeholder="Confirmer le mot de passe"
                            required>

                        <i class="bi bi-eye-slash toggle-password"
                           onclick="togglePassword('confirmPassword', this)">
                        </i>

                    </div>

                    <small id="confirmError" class="error-message"></small>

                </div>

                <!-- Bouton -->
                <button
                    type="submit"
                    class="btn-login"
                    id="activationBtn">

                    Activer mon compte

                </button>

            </form>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        function togglePassword(inputId, icon) {

            const input = document.getElementById(inputId);

            if(input.type === "password") {

                input.type = "text";

                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");

            } else {

                input.type = "password";

                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");

            }
        }

        function validatePassword() {

            const password = document.getElementById('password').value;

            const regex =
                /^(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

            const passwordError =
                document.getElementById('passwordError');

            if(!regex.test(password)) {

                passwordError.textContent =
                    "Minimum 8 caractères, 1 majuscule et 1 caractère spécial.";

                return false;
            }

            passwordError.textContent = '';

            return true;
        }

        function validateConfirmPassword() {

            const password =
                document.getElementById('password').value;

            const confirmPassword =
                document.getElementById('confirmPassword').value;

            const confirmError =
                document.getElementById('confirmError');

            if(password !== confirmPassword) {

                confirmError.textContent =
                    "Les mots de passe ne correspondent pas.";

                return false;
            }

            confirmError.textContent = '';

            return true;
        }

        document
            .getElementById('password')
            .addEventListener('input', validatePassword);

        document
            .getElementById('confirmPassword')
            .addEventListener('input', validateConfirmPassword);

        document
            .getElementById('activationForm')
            .addEventListener('submit', function(e){

                const validPassword =
                    validatePassword();

                const validConfirm =
                    validateConfirmPassword();

                if(!validPassword || !validConfirm){

                    e.preventDefault();

                    return;
                }

                const btn =
                    document.getElementById('activationBtn');

                btn.disabled = true;

                btn.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    Activation...
                `;
            });

    </script>

</body>
</html>
