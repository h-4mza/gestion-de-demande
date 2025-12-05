// assets/js/demandeur.js - Adapté pour PHP/MySQL

document.addEventListener("DOMContentLoaded", () => {
  checkAuthentication();
  loadDashboardStats();
  loadRequestsTable();
});

// Charger les statistiques depuis le serveur
function loadDashboardStats() {
  fetch('../../process/get_stats.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById("totalRequests").textContent = data.stats.total || 0;
        document.getElementById("pendingRequests").textContent = data.stats.en_attente || 0;
        document.getElementById("approvedRequests").textContent = data.stats.validee || 0;
        document.getElementById("rejectedRequests").textContent = data.stats.rejetee || 0;
      }
    })
    .catch(error => console.error('Erreur:', error));
}

// Charger le tableau des demandes
function loadRequestsTable() {
  fetch('../../process/get_demandes.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const tbody = document.getElementById("requestsTable");
        tbody.innerHTML = "";

        if (data.demandes.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Aucune demande</td></tr>';
          return;
        }

        data.demandes.forEach(request => {
          const row = document.createElement("tr");
          row.innerHTML = `
            <td><strong>#${request.id}</strong></td>
            <td>${request.type_nom}</td>
            <td>${truncateText(request.description)}</td>
            <td><span class="badge ${getUrgencyBadgeClass(request.urgence)}">${capitalizeFirst(request.urgence)}</span></td>
            <td><span class="badge ${getStatusBadgeClass(request.statut)}">${formatStatus(request.statut)}</span></td>
            <td>${formatDate(request.created_at)}</td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${request.id})">
                <i class="bi bi-eye"></i>
              </button>
              ${request.statut === 'en_attente' ? `
                <button class="btn btn-sm btn-outline-warning" onclick="editRequest(${request.id})">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${request.id})">
                  <i class="bi bi-trash"></i>
                </button>
              ` : ''}
            </td>
          `;
          tbody.appendChild(row);
        });
      }
    })
    .catch(error => console.error('Erreur:', error));
}

// Afficher les détails d'une demande
function viewDetails(requestId) {
  fetch(`../../process/get_demande_details.php?id=${requestId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const request = data.demande;
        
        document.getElementById("detailsId").textContent = request.id;
        document.getElementById("detailsType").textContent = request.type_nom;
        document.getElementById("detailsDescription").textContent = request.description;
        document.getElementById("detailsUrgency").textContent = capitalizeFirst(request.urgence);
        document.getElementById("detailsDate").textContent = formatDate(request.created_at);
        document.getElementById("detailsStatus").textContent = formatStatus(request.statut);
        
        const badge = document.getElementById("detailsBadge");
        badge.className = `badge ${getStatusBadgeClass(request.statut)}`;
        badge.textContent = formatStatus(request.statut);

        // Afficher les commentaires s'il y en a
        const commentsDiv = document.getElementById("commentsText");
        if (data.validations && data.validations.length > 0) {
          let commentsHtml = '';
          data.validations.forEach(val => {
            commentsHtml += `
              <div class="mb-2">
                <strong>${val.validateur_nom} ${val.validateur_prenom}</strong> 
                <small class="text-muted">(${formatDate(val.created_at)})</small>:
                <p class="mb-0">${val.commentaire || 'Aucun commentaire'}</p>
              </div>
            `;
          });
          commentsDiv.innerHTML = commentsHtml;
        } else {
          commentsDiv.textContent = "Aucun commentaire";
        }

        // Timeline
        const timeline = document.getElementById("detailsTimeline");
        timeline.innerHTML = `
          <div class="timeline-item">
            <div class="timeline-marker"></div>
            <div>Créée le ${formatDate(request.created_at)}</div>
          </div>
          ${request.statut !== 'en_attente' ? `
            <div class="timeline-item">
              <div class="timeline-marker"></div>
              <div>${formatStatus(request.statut)} le ${formatDate(request.updated_at)}</div>
            </div>
          ` : ''}
        `;

        const modal = new bootstrap.Modal(document.getElementById("detailsModal"));
        modal.show();
      }
    })
    .catch(error => console.error('Erreur:', error));
}

// Soumettre une nouvelle demande
function handleNewRequest(event) {
  event.preventDefault();

  const formData = new FormData();
  formData.append('type_id', document.getElementById("requestType").value);
  formData.append('description', document.getElementById("requestDescription").value);
  formData.append('urgence', document.getElementById("requestUrgency").value);

  // Gestion des fichiers
  const files = document.getElementById("requestFiles").files;
  for (let i = 0; i < files.length; i++) {
    formData.append('files[]', files[i]);
  }

  fetch('../../process/create_demande.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Demande soumise avec succès!');
      document.getElementById("newRequestForm").reset();
      const modal = bootstrap.Modal.getInstance(document.getElementById("newRequestModal"));
      modal.hide();
      loadDashboardStats();
      loadRequestsTable();
    } else {
      showToast('danger', data.message || 'Erreur lors de la soumission');
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    showToast('danger', 'Une erreur est survenue');
  });
}

// Supprimer une demande
function deleteRequest(requestId) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette demande ?')) {
    fetch('../../process/delete_demande.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: requestId })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast('success', 'Demande supprimée avec succès');
        loadDashboardStats();
        loadRequestsTable();
      } else {
        showToast('danger', data.message || 'Erreur lors de la suppression');
      }
    })
    .catch(error => console.error('Erreur:', error));
  }
}

// Fonctions utilitaires
function truncateText(text, maxLength = 50) {
  return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
}

function getUrgencyBadgeClass(urgence) {
  switch (urgence) {
    case "faible": return "bg-secondary";
    case "moyenne": return "bg-warning text-dark";
    case "urgente": return "bg-danger";
    default: return "bg-secondary";
  }
}

function getStatusBadgeClass(statut) {
  switch (statut) {
    case "en_attente": return "bg-warning text-dark";
    case "en_cours_de_validation": return "bg-info";
    case "validee": return "bg-success";
    case "rejetee": return "bg-danger";
    case "traitee": return "bg-secondary";
    default: return "bg-secondary";
  }
}

function formatStatus(statut) {
  const statuses = {
    'en_attente': 'En attente',
    'en_cours_de_validation': 'En validation',
    'validee': 'Validée',
    'rejetee': 'Rejetée',
    'traitee': 'Traitée'
  };
  return statuses[statut] || statut;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
}

function capitalizeFirst(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function showToast(type, message) {
  const toastDiv = document.createElement('div');
  toastDiv.className = `position-fixed top-0 end-0 p-3`;
  toastDiv.style.zIndex = '9999';
  toastDiv.innerHTML = `
    <div class="toast show align-items-center text-white bg-${type} border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  `;
  document.body.appendChild(toastDiv);
  
  setTimeout(() => toastDiv.remove(), 3000);
}