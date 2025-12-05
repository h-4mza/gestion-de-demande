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
    <div class="container-fluid">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <div class="login-logo">
                                <i class="bi bi-building"></i>
                            </div>
                            <h2 class="fw-bold text-primary mt-2">Gestion Demandes</h2>
                            <p class="text-muted">Expression de Besoins</p>
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

                        <!-- Form -->
                        <form id="loginForm" onsubmit="handleLogin(event)">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope me-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="email" required 
                                       placeholder="vous@exemple.com" aria-label="Adresse email">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock me-1"></i>Mot de passe
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" required 
                                           placeholder="••••••••" aria-label="Mot de passe">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Se souvenir de moi
                                </label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                                </button>
                            </div>
                        </form>

                        <!-- Demo Users Info -->
                        <hr class="my-4">
                        <div class="alert alert-info alert-sm" role="alert">
                            <small><strong>Comptes de test:</strong></small><br>
                            <small>Admin: admin@entreprise.com / Admin123!</small><br>
                            <small>Validateur: h.alami@entreprise.com / Admin123!</small><br>
                            <small>Demandeur: f.benali@entreprise.com / Admin123!</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/auth.js"></script>
    <script>
        // Toggle password visibility
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