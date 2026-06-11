<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Inscription - Compassion Pharmacie</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href={{ asset("css/login.css") }}>
  <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('Imgs/Logos/logo_1.png') }}">
  <style>
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

        html,
        body {
        height: auto;
        overflow-y: auto;
        }
  </style>
  <style>
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

  <!-- Decorative crosses in background -->
  <div class="login-bg-cross bl"><i class="bi bi-plus-lg"></i></div>
  <div class="login-bg-cross tr"><i class="bi bi-plus-lg"></i></div>

  <div style="position:fixed;bottom:60px;right:60px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.35);pointer-events:none;z-index:1;"></div>
  <div style="position:fixed;bottom:90px;right:120px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.2);pointer-events:none;z-index:1;"></div>

  <div class="login-wrapper">
    <!-- Logo -->
    <div class="login-logo">
       <img src="{{ asset('Imgs/Logos/logo_full.png') }}" alt="" srcset="" width="200" height="90">
    </div>

    <!-- Subtitle -->
    <p class="login-subtitle">Bienvenue dans l'application de gestion</p>

    <!-- Card -->
  <div class="login-card">
    <form id="registerForm" novalidate action="/confirmEmail" method="POST">
        @csrf
        <input type="number" value="" name="code" id="code" hidden>
        <!-- Prenom -->
        <div class="login-field">
        <label for="prenom">Prénom</label>
        <div class="input-group">
            <i class="bi bi-person input-icon"></i>
            <input type="text" id="prenom" name="nom" class="form-input" placeholder="Votre prénom" required>
        </div>
        </div>

        <!-- Email -->
        <div class="login-field">
        <label for="email">Adresse Email</label>
        <div class="input-group">
            <i class="bi bi-envelope input-icon"></i>
            <input type="email" id="email" name="email" class="form-input" placeholder="exemple@domaine.com" required>
        </div>
        </div>

        <!-- Password -->
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
            required
            >

            <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('password', this)"></i>
        </div>

        <small id="passwordError" class="error-message"></small>
        </div>

        <!-- Confirm Password -->
        <div class="login-field">
        <label for="confirmPassword">Confirmer Mot de Passe</label>

        <div class="input-group">
            <i class="bi bi-lock input-icon"></i>

            <input
            type="password"
            id="confirmPassword"
            name="confirmPassword"
            class="form-input"
            placeholder="••••••••••"
            required
            >

            <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('confirmPassword', this)"></i>
        </div>

        <small id="confirmError" class="error-message"></small>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-login" id="submitBtn">
        S’inscrire
        </button>
        <!-- Loader -->
        <div id="loading" class="loading-box">
            <div class="spinner"></div>
            <span>Chargement...</span>
        </div>

        <!-- Login -->
        <div class="login-register">
        <span>Vous avez déjà un compte ?</span>
        <a href="/">Se connecter</a>
        </div>

    </form>
  </div>

    <!-- Footer -->

  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function handleLogin(e) {
      e.preventDefault();
      const btn = e.target.querySelector('.btn-login');
      btn.textContent = 'Connexion en cours...';
      btn.style.opacity = '0.8';
      setTimeout(() => { window.location.href = 'dashboard.html'; }, 800);
    }
  </script>
  <script>
    const form = document.getElementById("registerForm");

    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirmPassword");

    const passwordError = document.getElementById("passwordError");
    const confirmError = document.getElementById("confirmError");


    // Afficher / masquer mot de passe
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


    // Vérification mot de passe
    function validatePassword() {

    const value = password.value;

    const regex =
        /^(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

    if (!regex.test(value)) {

        passwordError.textContent =
        "Minimum 8 caractères, 1 majuscule et 1 caractère spécial.";

        return false;

    } else {

        passwordError.textContent = "";
        return true;
    }
    }


    // Vérification confirmation
    function validateConfirmPassword() {

    if (password.value !== confirmPassword.value) {

        confirmError.textContent =
        "Les mots de passe ne correspondent pas.";

        return false;

    } else {

        confirmError.textContent = "";
        return true;
    }
    }


    // Vérification en temps réel
    password.addEventListener("input", () => {
    validatePassword();
    validateConfirmPassword();
    });

    confirmPassword.addEventListener("input", validateConfirmPassword);


    // Soumission formulaire
    form.addEventListener("submit", function(event) {

    event.preventDefault();

    const isPasswordValid = validatePassword();
    const isConfirmValid = validateConfirmPassword();


    });
  </script>
  <script>
    const form1 = document.getElementById("registerForm");

    form1.addEventListener("submit", async function (event) {

        event.preventDefault();


        const loading = document.getElementById("loading");
        const submitBtn = document.getElementById("submitBtn");

        // Afficher loader
        loading.style.display = "block";

        // Désactiver bouton
        submitBtn.disabled = true;
        submitBtn.innerText = "Patientez...";

        // Champs
        const prenom = document.getElementById("prenom").value;
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;

        // Vérification mot de passe
        const regex = /^(?=.*[A-Z])(?=.*[\W_]).{8,}$/;

        if (!regex.test(password)) {

            alert("Le mot de passe est invalide.");
            return;
        }

        // Vérification confirmation
        if (password !== confirmPassword) {

            alert("Les mots de passe ne correspondent pas.");
            return;
        }

        try {

            const response = await fetch("/register-user", {

                method: "POST",

                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },

                body: JSON.stringify({
                    prenom: prenom,
                    email: email,
                    password: password
                }),
            });

            const data = await response.json();

            // Message retour
            if (data.status === false) {
                Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: false,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                    }).fire({
                    icon: "error",
                    title: data.message
                });

            } else {

                document.getElementById('code').value = data.code
                form1.submit();
            }

        } catch (error) {

            console.error(error);

            alert("Une erreur est survenue.");
        }

        // Cacher loader
    loading.style.display = "none";

    // Réactiver bouton
    submitBtn.disabled = false;
    submitBtn.innerText = "S’inscrire";
    });
  </script>
</body>
</html>
