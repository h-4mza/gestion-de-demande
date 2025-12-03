<?php  
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'demandeur') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../config/Database.php';
require_once '../../classes/Demande.php';
require_once '../../classes/TypeBesoin.php';

$database = new Database();
$db = $database->getConnection();
$demande = new Demande($db);
$typeBesoin = new TypeBesoin($db);

// Récupérer les statistiques
$stats = $demande->getStatistics($_SESSION['user_id'], 'demandeur');

// Récupérer les types de besoins
$types_stmt = $typeBesoin->readAll();
$types = $types_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Demandeur</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="mes-demandes.php">
                            <i class="bi bi-list-check"></i> Mes demandes
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
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><span class="dropdown-item-text"><strong>Demandeur</strong></span></li>
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
                                    <h6 class="text-muted mb-0">En attente</h6>
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
                                    <h6 class="text-muted mb-0">Validées</h6>
                                    <h3 class="mb-0" id="approvedRequests"><?php echo $stats['validee']; ?></h3>
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
                                    <h3 class="mb-0" id="rejectedRequests"><?php echo $stats['rejetee']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des demandes -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Mes demandes récentes</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                            <i class="bi bi-plus-circle"></i> Nouvelle demande
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Urgence</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="requestsTable">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Nouvelle Demande -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Nouvelle demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newRequestForm" onsubmit="handleNewRequest(event)">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="requestType" class="form-label">Type de besoin</label>
                            <select class="form-select" id="requestType" required>
                                <option value="">Sélectionner un type</option>
                                <?php foreach($types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo htmlspecialchars($type['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="requestDescription" class="form-label">Description détaillée</label>
                            <textarea class="form-control" id="requestDescription" rows="4" required 
                                      placeholder="Décrivez votre besoin..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="requestUrgency" class="form-label">Urgence</label>
                            <select class="form-select" id="requestUrgency" required>
                                <option value="faible">Faible</option>
                                <option value="moyenne" selected>Moyenne</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="requestFiles" class="form-label">Pièces jointes (optionnel)</label>
                            <input type="file" class="form-control" id="requestFiles" multiple>
                            <small class="text-muted">Max 5MB par fichier</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Soumettre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Détails Demande -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">
                        <span>Demande #<span id="detailsId"></span></span>
                        <span class="badge ms-2" id="detailsBadge"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-muted">Informations</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Type:</strong> <span id="detailsType"></span></p>
                                <p><strong>Urgence:</strong> <span id="detailsUrgency"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date création:</strong> <span id="detailsDate"></span></p>
                                <p><strong>Statut:</strong> <span id="detailsStatus"></span></p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-muted">Description</h6>
                        <p id="detailsDescription"></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-muted">Historique</h6>
                        <div class="timeline" id="detailsTimeline">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>

                    <div id="detailsComments">
                        <h6 class="text-muted">Commentaires</h6>
                        <div class="alert alert-light" id="commentsText"></div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
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
    <script src="../../assets/js/demandeur.js"></script>
</body>
</html>
