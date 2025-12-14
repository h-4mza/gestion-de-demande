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

// Récupérer les stats 
$raw_stats = $demande->getStatistics(null, 'administrateur');

// Sécurisation des données pour éviter "Undefined array key"
$stats = [
    'total' => $raw_stats['total'] ?? 0,
    'validee' => $raw_stats['validee'] ?? 0,
    'en_attente' => $raw_stats['en_attente'] ?? 0,
    'en_cours_de_validation' => $raw_stats['en_cours_de_validation'] ?? 0,
    'rejetee' => $raw_stats['rejetee'] ?? 0,
    // On calcule le cumul "En attente / En cours"
    'pending_total' => ($raw_stats['en_attente'] ?? 0) + ($raw_stats['en_cours_de_validation'] ?? 0) + ($raw_stats['en_cours_de_traitement'] ?? 0)
];

$types_stmt = $typeBesoin->readAll();
$types = $types_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXUS - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <style>
        :root { --sidebar-width: 280px; --primary-nexus: #4338ca; --primary-light: #6366f1; --bg-body: #f3f4f6; --text-dark: #1f2937; --card-radius: 16px; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-dark); overflow-x: hidden; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background: linear-gradient(180deg, var(--primary-nexus) 0%, var(--primary-light) 100%); color: white; padding: 1.5rem; z-index: 1000; display: flex; flex-direction: column; }
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
                    background: rgba(255, 255, 255, 0.15);
                    padding: 3px;
                    }

        .nav-pills .nav-link { color: rgba(255, 255, 255, 0.7); font-weight: 500; padding: 12px 20px; border-radius: 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; transition: all 0.3s; }
        .nav-pills .nav-link:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-pills .nav-link.active { background: white; color: var(--primary-nexus); font-weight: 700; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .nav-pills .nav-link.active i { color: var(--primary-nexus); }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: white; padding: 1rem 1.5rem; border-radius: var(--card-radius); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .card-stat { background: white; border: none; border-radius: var(--card-radius); padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); height: 100%; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .bg-blue-soft { background: #e0e7ff; color: #4338ca; }
        .bg-green-soft { background: #dcfce7; color: #15803d; }
        .bg-orange-soft { background: #ffedd5; color: #c2410c; }
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .table-custom thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; font-weight: 700; padding: 1rem; border: none; }
        .table-custom tbody tr { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s; }
        .table-custom tbody tr:hover { transform: scale(1.005); }
        .table-custom td { padding: 1rem; vertical-align: middle; border: none; }
        .table-custom td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        .table-custom td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }
        @media (max-width: 991px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="logo-area">
    <img src="../../assets/img/logo-hub.svg" alt="Nexus" />
    <span>NEXUS</span>
</div>

        <div class="nav flex-column nav-pills me-3" id="v-pills-tab" role="tablist">
            <button class="nav-link active" id="stats-tab" data-bs-toggle="pill" data-bs-target="#stats-content"><i class="bi bi-speedometer2"></i> Dashboard</button>
            <button class="nav-link" id="demandes-tab" data-bs-toggle="pill" data-bs-target="#demandes-content"><i class="bi bi-list-task"></i> Demandes</button>
            <button class="nav-link" id="utilisateurs-tab" data-bs-toggle="pill" data-bs-target="#utilisateurs-content"><i class="bi bi-people"></i> Utilisateurs</button>
            <button class="nav-link" id="types-tab" data-bs-toggle="pill" data-bs-target="#types-content"><i class="bi bi-gear"></i> Configuration</button>
        </div>
        <div class="mt-auto"><button onclick="logout()" class="nav-link text-white w-100 bg-transparent border-0 text-start ps-3"><i class="bi bi-box-arrow-left me-2"></i> Déconnexion</button></div>
    </nav>

    <main class="main-content">
        <div class="top-bar">
            <div><h4 class="fw-bold m-0">Vue d'ensemble</h4><small class="text-muted">Bienvenue, <?php echo $_SESSION['user_prenom']; ?></small></div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-calendar3 me-2"></i> <?php echo date('d M Y'); ?></span>
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold shadow" style="width: 40px; height: 40px;"><?php echo strtoupper(substr($_SESSION['user_prenom'], 0, 1)); ?></div>
            </div>
        </div>

        <div class="tab-content" id="v-pills-tabContent">
            <div class="tab-pane fade show active" id="stats-content">
                <div class="row g-4 mb-4">
                    <div class="col-md-4"><div class="card-stat d-flex justify-content-between align-items-center"><div><p class="text-muted text-uppercase small fw-bold mb-1">Total Demandes</p><h2 class="fw-bold m-0" id="statTotal"><?php echo $stats['total']; ?></h2></div><div class="icon-box bg-blue-soft"><i class="bi bi-folder"></i></div></div></div>
                    <div class="col-md-4"><div class="card-stat d-flex justify-content-between align-items-center"><div><p class="text-muted text-uppercase small fw-bold mb-1">Validées (À traiter)</p><h2 class="fw-bold m-0 text-success" id="statToProcess"><?php echo $stats['validee']; ?></h2></div><div class="icon-box bg-green-soft"><i class="bi bi-check-lg"></i></div></div></div>
                    <div class="col-md-4"><div class="card-stat d-flex justify-content-between align-items-center"><div><p class="text-muted text-uppercase small fw-bold mb-1">En cours / Attente</p><h2 class="fw-bold m-0 text-warning" id="statPending"><?php echo $stats['pending_total']; ?></h2></div><div class="icon-box bg-orange-soft"><i class="bi bi-hourglass-split"></i></div></div></div>
                </div>
                <div class="row g-4 mb-4">
                    <div class="col-lg-8"><div class="card-stat"><div class="d-flex justify-content-between align-items-center mb-4"><h6 class="fw-bold m-0">Activité des demandes</h6><span class="badge bg-light text-secondary">Temps réel</span></div><div style="height: 300px; width: 100%;"><canvas id="activityChart"></canvas></div></div></div>
                    <div class="col-lg-4"><div class="card-stat"><h6 class="fw-bold mb-4">Répartition par Type</h6><div style="height: 250px; position: relative;"><canvas id="distributionChart"></canvas></div></div></div>
                </div>
                <div class="card-stat"><h6 class="fw-bold mb-3">Top 5 Demandeurs</h6><div id="topRequesters"></div></div>
            </div>

            <div class="tab-pane fade" id="demandes-content">
                <div class="card-stat h-100"><div class="d-flex justify-content-between align-items-center mb-4"><h5 class="fw-bold m-0">Gestion des Demandes</h5><button class="btn btn-primary rounded-pill btn-sm" onclick="exportCSV()"><i class="bi bi-download me-2"></i> CSV</button></div><div class="table-responsive"><table class="table-custom"><thead><tr><th>#</th><th>Demandeur</th><th>Sujet</th><th>Urgence</th><th>Statut</th><th>Service</th><th>Action</th></tr></thead><tbody id="adminDemandesTable"></tbody></table></div></div>
            </div>

            <div class="tab-pane fade" id="utilisateurs-content">
                <div class="card-stat h-100"><div class="d-flex justify-content-between align-items-center mb-4"><h5 class="fw-bold m-0">Utilisateurs</h5><button class="btn btn-primary rounded-pill px-4" onclick="newUser()"><i class="bi bi-plus-lg me-2"></i> Nouveau</button></div><div class="table-responsive"><table class="table-custom"><thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Service</th><th>Statut</th><th>Actions</th></tr></thead><tbody id="adminUsersTable"></tbody></table></div></div>
            </div>

            <div class="tab-pane fade" id="types-content">
                <div class="row g-4"><div class="col-md-4"><div class="card-stat"><h6 class="fw-bold mb-3">Ajouter un Type</h6><form id="typeForm" onsubmit="handleAddType(event)"><div class="mb-3"><label class="form-label small fw-bold text-muted">NOM</label><input type="text" class="form-control bg-light border-0" id="typeName" required></div><div class="mb-3"><label class="form-label small fw-bold text-muted">DESCRIPTION</label><textarea class="form-control bg-light border-0" id="typeDesc" rows="3"></textarea></div><button type="submit" class="btn btn-primary w-100 rounded-pill">Ajouter</button></form></div></div><div class="col-md-8"><div class="card-stat"><h6 class="fw-bold mb-3">Types Existants</h6><table class="table-custom"><tbody id="typesTable"></tbody></table></div></div></div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4"><h5 class="modal-title fw-bold">Gestion Utilisateur</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <form id="userForm" onsubmit="handleSaveUser(event)">
                        <div class="row g-3 mb-3"><div class="col"><input type="text" class="form-control bg-light border-0" id="userNom" placeholder="Nom" required></div><div class="col"><input type="text" class="form-control bg-light border-0" id="userPrenom" placeholder="Prénom" required></div></div>
                        <input type="email" class="form-control bg-light border-0 mb-3" id="userEmail" placeholder="Email" required>
                        <input type="password" class="form-control bg-light border-0 mb-3" id="userPassword" placeholder="Mot de passe">
                        <div class="row g-3 mb-4"><div class="col"><select class="form-select bg-light border-0" id="userRole"><option value="demandeur">Demandeur</option><option value="validateur">Validateur</option><option value="administrateur">Administrateur</option></select></div><div class="col"><select class="form-select bg-light border-0" id="userService"><option value="Informatique">IT</option><option value="RH">RH</option><option value="Achat">Achat</option><option value="Direction">Direction</option></select></div></div>
                        <select class="form-select bg-light border-0 mb-4" id="userStatut"><option value="actif">Actif</option><option value="inactif">Inactif</option></select>
                        <input type="hidden" id="userId">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>const BASE_URL = '../..';</script>
    <script src="../../assets/js/auth.js"></script>
    <script src="../../assets/js/admin.js?v=<?php echo time(); ?>"></script>

</body>
</html>