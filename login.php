<?php 
session_start();
// Si déjà connecté, rediriger
if(isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
    switch($_SESSION['user_role']) {
        case 'administrateur':
            header('Location: pages/admin/dashboard.php');
            break;
        case 'validateur':
            header('Location: pages/validateur/dashboard.php');
            break;
        case 'demandeur':
            header('Location: pages/demandeur/dashboard.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description" content="Système de gestion des demandes d'expression de besoins - Authentification">
    <meta name="theme-color" content="#0d6efd">
    <title>Gestion des Demandes - Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-page">

    <div class="login-wrapper">
        <div class="login-card shadow-lg border-0">

            <!-- PANEL GAUCHE : "Hello, welcome back" -->
            <div class="login-hero d-none d-md-flex">
                <div class="login-hero-content">
                    <h1 class="login-hero-title">Hello, welcome back 👋</h1>
                    <p class="login-hero-subtitle">
                        Centralisez la création, la validation et le traitement des demandes
                        pour simplifier le travail de toute l’équipe.
                    </p>
                </div>
            </div>

            <!-- PANEL DROIT : LOGO + FORMULAIRE, DANS login-form-panel -->
            <div class="login-form-panel">

                <!-- Header logo + titre -->
                <div class="text-center mb-4">
                    <div class="login-logo">
                        <img src="assets/img/logo-hub.svg" alt="Gestion Demandes" class="login-logo-img">
                    </div>
                    <h2 class="fw-bold mt-3 mb-1">Gestion Demandes</h2>
                    <p class="text-muted mb-0">Expression de Besoins</p>
                </div>

                <!-- Alertes PHP -->
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php 
                            switch($_GET['error']) {
                                case 'invalid':
                                    echo 'Email ou mot de passe incorrect';
                                    break;
                                case 'empty':
                                    echo 'Veuillez remplir tous les champs';
                                    break;
                                default:
                                    echo 'Une erreur est survenue';
                            }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulaire de connexion -->
                <form id="loginForm" onsubmit="handleLogin(event)">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>Email professionnel
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" required 
                                   placeholder="vous@entreprise.com" aria-label="Adresse email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock me-1"></i>Mot de passe
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" required 
                                   placeholder="••••••••" aria-label="Mot de passe">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                        </button>
                    </div>
                </form>

            </div> <!-- /login-form-panel -->

        </div> <!-- /login-card -->
    </div> <!-- /login-wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/auth.js"></script>
    <script>
        
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if(password.type === 'password') {
                password.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });
    </script>
</body>

</html>
