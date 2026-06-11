<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Confirm Email - Compassion Pharmacie</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href={{ asset("css/login.css") }}>
  <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('Imgs/Logos/logo_1.png') }}">
  <style>
     html,
        body {
        height: auto;
        overflow-y: auto;
        }
  </style>
</head>
<body class="login-page">
  <script>
        window.addEventListener("load", () => {

            const navigationEntries =
                performance.getEntriesByType("navigation");

            if (
                navigationEntries.length > 0 &&
                navigationEntries[0].type === "reload"
            )
            {
                window.location.href = "/inscription";
            }

        });
  </script>

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
    <p class="login-subtitle">Confirmer votre adresse mail</p>

    <!-- Card -->

    <div class="login-card">

        <form id="confirmForm" action="/validation-inscription" method="POST">
            @csrf

            <input type="text" value={{ $nom }} name="nom" id="nom" hidden>
            <input type="text" value={{ $email }} name="email" id="email" hidden>
            <input type="text" value={{ $password }} name="password" id="password" hidden>

            <h4 class="text-center mb-3">Vérification de votre compte</h4>

            <p class="text-center text-muted">
                Entrez le code de vérification envoyé à votre adresse {{ $emailMasque }}.
            </p>

            <div class="text-center mb-3">
                <span id="timer" style="font-size:20px;font-weight:bold;color:#dc3545;">
                    02:00
                </span>
            </div>

            <div class="login-field">
                <label for="verificationCode">Code de vérification</label>

                <div class="input-group">
                    <i class="bi bi-shield-lock input-icon"></i>

                    <input
                        type="text"
                        id="verificationCode"
                        class="form-input"
                        maxlength="5"
                        placeholder="12345"
                        required
                    >
                </div>

                <small
                    id="errorMessage"
                    style="display:none;color:red;"
                ></small>
            </div>

            <button type="submit" class="btn-login">
                Vérifier
            </button>

        </form>

    </div>

    <!-- Footer -->

  </div>

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

    const form = document.getElementById("confirmForm");
    const errorMessage = document.getElementById("errorMessage");

    // Code venant de Laravel
    const correctCode = "{{ $code }}";

    // 1 minutes = 60 secondes
    let timeLeft = 120;

    const timerElement = document.getElementById("timer");

    const countdown = setInterval(() => {

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;

        timerElement.textContent =
            String(minutes).padStart(2, "0") +
            ":" +
            String(seconds).padStart(2, "0");

        if (timeLeft <= 0) {

            clearInterval(countdown);

            window.location.href = "/";

        }

        timeLeft--;

    }, 1000);


    form.addEventListener("submit", function(e) {

        const enteredCode =
            document.getElementById("verificationCode").value.trim();

        if (enteredCode !== correctCode) {

            e.preventDefault();

            errorMessage.style.display = "block";
            errorMessage.textContent =
                "Le code de vérification est incorrect.";

            return false;
        }

        // Si correct, le formulaire est envoyé normalement
    });

  </script>

</body>
</html>
