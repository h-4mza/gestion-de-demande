<?php 
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'validateur') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../config/Database.php';
require_once '../../classes/Demande.php';

$database = new Database();
$db = $database->getConnection();
$demande = new Demande($db);

// Récupérer les statistiques
$stats = $demande->getStatistics($_SESSION['user_id'], 'validateur');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Validateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-building"></i> Gestion Demandes
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>

                    <!-- 🔔 Cloche de notifications -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative" href="#" id="notifDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle 
                                        badge rounded-pill bg-danger d-none" 
                                  id="notifBadge">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notifDropdown" style="min-width: 320px;">
                            <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                                <span>Notifications</span>
                                <button class="btn btn-sm btn-link p-0" id="notifMarkAllBtn">
                                    Tout marquer comme lu
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-0"></li>
                            <li id="notifListContainer">
                                <div class="text-center text-muted small py-3">
                                    Aucune notification
                                </div>
                            </li>
                        </ul>
                    </li>
                    <!-- fin cloche -->

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><strong>Validateur</strong></span></li>
                            <li><span class="dropdown-item-text text-muted small"><?php echo $_SESSION['user_email']; ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="logout()">
                                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container-fluid">
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon icon-blue">
                                    <i class="bi bi-folder"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">Total demandes</h6>
                                    <h3 class="mb-0" id="totalRequests"><?php echo $stats['total']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon icon-warning">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">À valider</h6>
                                    <h3 class="mb-0" id="pendingValidation">
                                        <?php echo $stats['en_attente'] + $stats['en_cours_de_validation']; ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon icon-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">Approuvées</h6>
                                    <h3 class="mb-0" id="approvedCount"><?php echo $stats['validee']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card stat-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon icon-danger">
                                    <i class="bi bi-x-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">Rejetées</h6>
                                    <h3 class="mb-0" id="rejectedCount"><?php echo $stats['rejetee']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des demandes à valider -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-check"></i> Demandes à valider
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Demandeur</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Urgence</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="validationTable">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Validation/Rejet -->
    <div class="modal fade" id="validationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="validationTitle">Valider la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="validationForm" onsubmit="handleValidation(event)">
                    <div class="modal-body">
                        <div class="alert alert-info" id="requestSummary"></div>
                        <div class="mb-3">
                            <label for="validationComment" class="form-label">Commentaire</label>
                            <textarea class="form-control" id="validationComment" rows="4" 
                                      placeholder="Ajouter un commentaire..."></textarea>
                            <small class="text-muted" id="commentHelp"></small>
                        </div>
                        <input type="hidden" id="currentRequestId">
                        <input type="hidden" id="validationType">
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="validationSubmitBtn">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/auth.js"></script>
    <script>
        const BASE_URL = '../..';
    </script>
    <script src="../../assets/js/notifications.js"></script>
    <script src="../../assets/js/validateur.js"></script>
</body>
</html>
