<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Connexion - Compassion Pharmacie</title>

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

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            margin-right: 8px;
        }

        #loginError {
            display: none;
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 500;
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
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #666;
            font-size: 18px;
        }

        .error-message {
            display: block;
            margin-top: 5px;
            color: red;
            font-size: 13px;
        }

        .login-register {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }

        .login-register a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 600;
        }

        .loading-box {
            display: none;
            margin-top: 15px;
            text-align: center;
            color: #555;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 4px solid #ddd;
            border-top: 4px solid #0d6efd;
            border-radius: 50%;
            margin: auto;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="login-page">

    <div class="login-bg-cross bl">
        <i class="bi bi-plus-lg"></i>
    </div>

    <div class="login-bg-cross tr">
        <i class="bi bi-plus-lg"></i>
    </div>

    <div style="position:fixed;bottom:60px;right:60px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.35);pointer-events:none;z-index:1;"></div>

    <div style="position:fixed;bottom:90px;right:120px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.2);pointer-events:none;z-index:1;"></div>

    <div class="login-wrapper">

        <div class="login-logo">
            <img
                src="{{ asset('Imgs/Logos/logo_full.png') }}"
                alt="Compassion Pharmacie"
                width="200"
                height="90">
        </div>

        <p class="login-subtitle">
            Bienvenue dans l'application de gestion
        </p>

        <div class="login-card">

            <form id="loginForm" action="/confirmEmail" method="POST" novalidate>
                @csrf

                <input type="hidden" name="code" id="code" value="">

                <div id="loginError"></div>

                <div class="login-field">
                    <label for="email">Adresse Email</label>

                    <div class="input-group">
                        <i class="bi bi-envelope input-icon"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="exemple@domaine.com"
                            autocomplete="off"
                            required>
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Mot de Passe</label>

                    <div class="input-group">
                        <i class="bi bi-lock input-icon"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="••••••••••"
                            autocomplete="current-password"
                            required>

                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>

                    <small id="passwordError" class="error-message"></small>
                </div>

                <button
                    type="submit"
                    class="btn-login"
                    id="loginBtn">
                    Se Connecter
                </button>

                <a href="/mot_de_passe_oublier" class="login-forgot">
                    Mot de passe oublié ?
                </a>

                <div class="login-register">
                    <span>Vous n’avez pas encore de compte ?</span>
                    <a href="/inscription">S’inscrire</a>
                </div>

            </form>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const form = document.getElementById("loginForm");
        const password1 = document.getElementById("password");
        const passwordError = document.getElementById("passwordError");
        const loginBtn = document.getElementById("loginBtn");
        const loginError = document.getElementById("loginError");

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);

            if (input.type === "password") {
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
            const value = password1.value;
            const regex = /^(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

            if (!regex.test(value)) {
                passwordError.textContent = "Minimum 8 caractères, 1 majuscule et 1 caractère spécial.";
                return false;
            }

            passwordError.textContent = "";
            return true;
        }

        password1.addEventListener("input", validatePassword);

        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const isPasswordValid = validatePassword();
            if (!isPasswordValid) {
                return;
            }

            const email = document.getElementById("email").value.trim();
            const password = password1.value;

            loginError.style.display = "none";
            loginBtn.disabled = true;
            loginBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Connexion...
            `;

            try {
                const response = await fetch("/connexion", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                let data = {};
                try {
                    data = await response.json();
                } catch (jsonError) {
                    throw new Error("Réponse invalide du serveur.");
                }

                if (!response.ok || data.status === false) {
                    loginError.style.display = "block";
                    loginError.innerText = data.message || "Identifiants incorrects.";

                    loginBtn.disabled = false;
                    loginBtn.innerHTML = "Se Connecter";
                    return;
                }

                document.getElementById("code").value = data.code || "";
                form.submit();
            } catch (error) {
                console.error(error);

                loginError.style.display = "block";
                loginError.innerText = "Une erreur est survenue.";

                loginBtn.disabled = false;
                loginBtn.innerHTML = "Se Connecter";
            }
        });
    </script>

</body>
</html>
