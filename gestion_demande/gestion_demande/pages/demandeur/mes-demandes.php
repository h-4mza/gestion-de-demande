<?php 
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'demandeur') {
    header('Location: ../../login.php');
    exit();
}

require_once '../../classes/TypeBesoin.php';
require_once '../../config/Database.php';

$database = new Database();
$db = $database->getConnection();
$typeBesoin = new TypeBesoin($db);

$types_stmt = $typeBesoin->readAll();
$types = $types_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Demandes</title>
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="mes-demandes.php">
                            <i class="bi bi-list-check"></i> Mes demandes
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text"><strong>Demandeur</strong></span></li>
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
            <!-- Filtres -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="filterStatus" class="form-label">Statut</label>
                            <select class="form-select" id="filterStatus" onchange="applyFilters()">
                                <option value="">Tous</option>
                                <option value="en_attente">En attente</option>
                                <option value="en_validation">En validation</option>
                                <option value="validee">Validée</option>
                                <option value="rejetee">Rejetée</option>
                                <option value="traitee">Traitée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterType" class="form-label">Type</label>
                            <select class="form-select" id="filterType" onchange="applyFilters()">
                                <option value="">Tous</option>
                                <?php foreach($types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo htmlspecialchars($type['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterUrgency" class="form-label">Urgence</label>
                            <select class="form-select" id="filterUrgency" onchange="applyFilters()">
                                <option value="">Tous</option>
                                <option value="faible">Faible</option>
                                <option value="moyenne">Moyenne</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterSearch" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="filterSearch" 
                                   placeholder="Rechercher..." onkeyup="applyFilters()">
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-auto">
                            <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                                <i class="bi bi-funnel"></i> Filtrer
                            </button>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                                <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div class="card shadow-sm border-0">
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
                            <tbody id="filteredTable">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <nav aria-label="Pagination" class="mt-4">
                <ul class="pagination justify-content-center" id="pagination">
                    <!-- Populated by JavaScript -->
                </ul>
            </nav>
        </div>
    </main>

    <!-- Modal Détails (même que dashboard) -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">
                        Demande #<span id="detailsId"></span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/auth.js"></script>
    <script src="../../assets/js/mes-demandes.js"></script>
</body>
</html>