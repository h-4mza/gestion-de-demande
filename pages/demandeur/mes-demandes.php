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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="././assets/css/styles.css">

    <!-- même styles NEXUS que dashboard.php -->
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-nexus: #4338ca;
            --primary-light: #6366f1;
            --bg-body: #f3f4f6;
            --text-dark: #1f2937;
            --card-radius: 16px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, var(--primary-nexus) 0%, var(--primary-light) 100%);
            color: #fff;
            padding: 1.5rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .logo-area {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-area img {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: rgba(255,255,255,0.15);
            padding: 3px;
        }

        .nav-pills .nav-link {
            color: rgba(255, 255, 255, 0.75);
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
        }

        .nav-pills .nav-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .nav-pills .nav-link.active {
            background: #fff;
            color: var(--primary-nexus);
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .nav-pills .nav-link.active i {
            color: var(--primary-nexus);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: #fff;
            padding: 1rem 1.5rem;
            border-radius: var(--card-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .card-stat {
            background: #fff;
            border: none;
            border-radius: var(--card-radius);
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .card-stat:hover {
            transform: translateY(-4px);
        }

        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table-custom thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-weight: 700;
            padding: 1rem;
            border: none;
        }

        .table-custom tbody tr {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }

        .table-custom tbody tr:hover {
            transform: scale(1.005);
        }

        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            border: none;
        }

        .table-custom td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .table-custom td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .icon-button {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: var(--primary-nexus);
            border: none;
        }

        .icon-button:hover {
            background: #e0e7ff;
            color: var(--primary-nexus);
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 1.5rem 1rem;
            }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
<body>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="logo-area">
            <img src="../../assets/img/logo-hub.svg" alt="Logo">
            <span>NEXUS</span>
        </div>

        <div class="nav flex-column nav-pills me-3">
            <a class="nav-link" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link active" href="mes-demandes.php">
                <i class="bi bi-list-task"></i> Mes demandes
            </a>
        </div>

        <div class="mt-auto">
            <button onclick="logout()" class="nav-link text-white w-100 bg-transparent border-0 text-start ps-3">
                <i class="bi bi-box-arrow-left me-2"></i> Déconnexion
            </button>
        </div>
    </nav>

    <!-- MAIN -->
    <main class="main-content">

        <div class="top-bar">
            <div>
                <h4 class="fw-bold m-0">Mes demandes</h4>
                <small class="text-muted">
                    Historique de vos demandes
                </small>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border px-3 py-2 d-none d-md-inline">
                    <i class="bi bi-calendar3 me-2"></i><?php echo date('d M Y'); ?>
                </span>

                <!-- 🔔 Notifications -->
                <div class="dropdown">
                    <a class="icon-button position-relative" href="#" id="notifDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" 
                              id="notifBadge">0</span>
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
                </div>

                <!-- Profil -->
             
<div class="d-flex align-items-center gap-2">
    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow"
         style="width: 32px; height: 32px;">
        <?php echo strtoupper(substr($_SESSION['user_prenom'], 0, 1)); ?>
    </div>
    <div class="d-none d-sm-block text-start">
        <div class="fw-semibold small">
            <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
        </div>
        <div class="text-muted extra-small">Demandeur</div>
    </div>
</div>

            </div>
        </div>

        <div class="container-fluid px-0">

            <!-- Filtres -->
            <div class="card-stat mb-4">
                <h5 class="fw-bold mb-3">Filtres</h5>
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filterStatus" class="form-label">Statut</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Tous</option>
                            <option value="en_attente">En attente</option>
                            <option value="en_cours_de_validation">En cours de validation</option>
                            <option value="validee">Validées</option>
                            <option value="rejetee">Rejetées</option>
                            <option value="traitee">Traitées</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterType" class="form-label">Type</label>
                        <select id="filterType" class="form-select">
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
                        <select id="filterUrgency" class="form-select">
                            <option value="">Toutes</option>
                            <option value="faible">Faible</option>
                            <option value="moyenne">Moyenne</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterSearch" class="form-label">Recherche</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Sujet, description...">
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div class="card-stat">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0">Toutes mes demandes</h5>
                </div>

                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Urgence</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="filteredTable">
                            <!-- rempli par mes-demandes.js -->
                        </tbody>
                    </table>
                </div>

                <nav aria-label="Pagination" class="mt-4">
                    <ul class="pagination justify-content-center" id="pagination">
                        <!-- rempli par JS -->
                    </ul>
                </nav>
            </div>

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