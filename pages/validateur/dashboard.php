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

        /* SIDEBAR NEXUS */

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

        .nav-pills .nav-link i {
            font-size: 1.1rem;
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

        /* MAIN AREA */

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
            height: 100%;
            transition: transform 0.2s;
        }

        .card-stat:hover {
            transform: translateY(-4px);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .bg-blue-soft   { background: #e0e7ff; color: #4338ca; }
        .bg-yellow-soft { background: #fef3c7; color: #92400e; }
        .bg-green-soft  { background: #dcfce7; color: #15803d; }
        .bg-red-soft    { background: #fee2e2; color: #b91c1c; }

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
    <!-- SIDEBAR NEXUS -->
    <nav class="sidebar">
        <div class="logo-area">
            <img src="../../assets/img/logo-hub.svg" alt="Logo">
            <span>NEXUS</span>
        </div>

        <div class="nav flex-column nav-pills me-3">
            <a class="nav-link active" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        
        </div>

        <div class="mt-auto">
            <button onclick="logout()" class="nav-link text-white w-100 bg-transparent border-0 text-start ps-3">
                <i class="bi bi-box-arrow-left me-2"></i> Déconnexion
            </button>
        </div>
    </nav>

    <<main class="main-content py-4">
        <div class="top-bar">
            <div>
                <h4 class="fw-bold m-0">Vue d'ensemble</h4>
                <small class="text-muted">
                    Bienvenue, <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>
                </small>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border px-3 py-2 d-none d-md-inline">
                    <i class="bi bi-calendar3 me-2"></i><?php echo date('d M Y'); ?>
                </span>

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

                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow"
                         style="width: 32px; height: 32px;">
                        <?php echo strtoupper(substr($_SESSION['user_prenom'], 0, 1)); ?>
                    </div>
                    <div class="d-none d-sm-block text-start">
                        <div class="fw-semibold small">
                            <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
                        </div>
                        <div class="text-muted extra-small">Validateur</div>
                    </div>
                </div>
            </div>
        </div>
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
    <div class="modal fade" id="attachmentsModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pièces jointes</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="attachmentsList">
        Chargement...
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
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
    <script>
document.addEventListener("DOMContentLoaded", () => {
    const dropdown = document.getElementById("notifDropdown");

    if (dropdown && typeof bootstrap !== 'undefined') {
        dropdown.addEventListener("show.bs.dropdown", () => {
            if (typeof markAllNotificationsAsRead === "function") {
                markAllNotificationsAsRead();
            }
        });
    }
});
</script>
    <script src="../../assets/js/validateur.js"></script>
</body>
</html>
