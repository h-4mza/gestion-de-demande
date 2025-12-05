<?php  
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
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

// Récupérer les statistiques globales
$stats = $demande->getStatistics(null, 'administrateur');

// Récupérer les types de besoins
$types_stmt = $typeBesoin->readAll();
$types = $types_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Administrateur</title>
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

                    <!-- Profil -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><strong>Administrateur</strong></span></li>
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
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="demandes-tab" data-bs-toggle="tab" 
                            data-bs-target="#demandes-content" type="button" role="tab">
                        <i class="bi bi-list-check"></i> Demandes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="utilisateurs-tab" data-bs-toggle="tab" 
                            data-bs-target="#utilisateurs-content" type="button" role="tab">
                        <i class="bi bi-people"></i> Utilisateurs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="types-tab" data-bs-toggle="tab" 
                            data-bs-target="#types-content" type="button" role="tab">
                        <i class="bi bi-tags"></i> Types de besoins
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="stats-tab" data-bs-toggle="tab" 
                            data-bs-target="#stats-content" type="button" role="tab">
                        <i class="bi bi-graph-up"></i> Statistiques
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Demandes Tab -->
                <div class="tab-pane fade show active" id="demandes-content" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Toutes les demandes</h5>
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportCSV()">
                                <i class="bi bi-download"></i> Exporter CSV
                            </button>
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
                                            <th>Statut</th>
                                            <th>Service</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminDemandesTable">
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Utilisateurs Tab -->
                <div class="tab-pane fade" id="utilisateurs-content" role="tabpanel">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Gestion des utilisateurs</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                    data-bs-target="#userModal" onclick="newUser()">
                                <i class="bi bi-plus-circle"></i> Ajouter un utilisateur
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Rôle</th>
                                            <th>Service</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="adminUsersTable">
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Types de Besoins Tab -->
                <div class="tab-pane fade" id="types-content" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Ajouter un type</h5>
                                </div>
                                <div class="card-body">
                                    <form id="typeForm" onsubmit="handleAddType(event)">
                                        <div class="mb-3">
                                            <label for="typeName" class="form-label">Nom du type</label>
                                            <input type="text" class="form-control" id="typeName" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="typeDesc" class="form-label">Description</label>
                                            <textarea class="form-control" id="typeDesc" rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-plus-circle me-1"></i>Ajouter
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="mb-0"><i class="bi bi-list"></i> Types existants</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="typesTable">
                                                <!-- Populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques Tab -->
                <div class="tab-pane fade" id="stats-content" role="tabpanel">
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card stat-card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Demandes totales</h6>
                                    <h3 class="mb-0" id="statTotal"><?php echo $stats['total']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card stat-card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Approuvées</h6>
                                    <h3 class="mb-0 text-success" id="statApproved"><?php echo $stats['validee']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card stat-card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">En attente</h6>
                                    <h3 class="mb-0 text-warning" id="statPending"><?php echo $stats['en_attente']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card stat-card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Rejetées</h6>
                                    <h3 class="mb-0 text-danger" id="statRejected"><?php echo $stats['rejetee']; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="mb-0">Top 5 demandeurs</h5>
                                </div>
                                <div class="card-body">
                                    <div id="topRequesters"><!-- Populated by JavaScript --></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="mb-0">Demandes par type</h5>
                                </div>
                                <div class="card-body">
                                    <div id="typeDistribution"><!-- Populated by JavaScript --></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal User Management -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Gestion Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="userForm" onsubmit="handleSaveUser(event)">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="userNom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="userNom" required>
                        </div>
                        <div class="mb-3">
                            <label for="userPrenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="userPrenom" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="userEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="userPassword">
                            <small class="text-muted">Laisser vide pour ne pas modifier</small>
                        </div>
                        <div class="mb-3">
                            <label for="userRole" class="form-label">Rôle</label>
                            <select class="form-select" id="userRole" required>
                                <option value="demandeur">Demandeur</option>
                                <option value="validateur">Validateur</option>
                                <option value="administrateur">Administrateur</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="userService" class="form-label">Service</label>
                            <select class="form-select" id="userService" required>
                                <option value="Informatique">Informatique</option>
                                <option value="RH">RH</option>
                                <option value="Achat">Achat</option>
                                <option value="Logistique">Logistique</option>
                                <option value="Direction">Direction</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="userStatut" class="form-label">Statut</label>
                            <select class="form-select" id="userStatut" required>
                                <option value="actif">Actif</option>
                                <option value="inactif">Inactif</option>
                            </select>
                        </div>
                        <input type="hidden" id="userId">
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Enregistrer
                        </button>
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
    <script src="../../assets/js/admin.js"></script>
</body>
</html>
