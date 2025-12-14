// assets/js/mes-demandes.js 

let currentPage = 1;
const itemsPerPage = 10;
let allDemandes = [];
let filteredDemandes = [];

document.addEventListener("DOMContentLoaded", () => {
  checkAuthentication();
  loadAllDemandes();

  // Brancher les filtres sur applyFilters()
  const status  = document.getElementById("filterStatus");
  const type    = document.getElementById("filterType");
  const urgency = document.getElementById("filterUrgency");
  const search  = document.getElementById("filterSearch");

  if (status)  status.addEventListener("change", applyFilters);
  if (type)    type.addEventListener("change", applyFilters);
  if (urgency) urgency.addEventListener("change", applyFilters);
  if (search)  search.addEventListener("input", applyFilters);
});


// Charger toutes les demandes
function loadAllDemandes() {
  fetch('../../process/get_demandes.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // On garde uniquement les demandes validées ou traitées
        allDemandes = (data.demandes || []).filter(d =>
          d.statut === 'validee' || d.statut === 'traitee'
        );
        filteredDemandes = [...allDemandes];
        currentPage = 1;
        loadFilteredRequests();
        loadPagination();
      }
    })
    .catch(error => console.error('Erreur:', error));
}



// Appliquer les filtres
function applyFilters() {
  const status = document.getElementById("filterStatus").value;
  const type = document.getElementById("filterType").value;
  const urgency = document.getElementById("filterUrgency").value;
  const search = document.getElementById("filterSearch").value.toLowerCase();

  filteredDemandes = allDemandes.filter(request => {
    const matchStatus = !status || request.statut === status;
    const matchType = !type || request.type_id == type;
    const matchUrgency = !urgency || request.urgence === urgency;
    const matchSearch = !search || 
      request.description.toLowerCase().includes(search) || 
      request.id.toString().includes(search);

    return matchStatus && matchType && matchUrgency && matchSearch;
  });

  currentPage = 1;
  loadFilteredRequests();
  loadPagination();
}

// Réinitialiser les filtres
function resetFilters() {
  document.getElementById("filterStatus").value = "";
  document.getElementById("filterType").value = "";
  document.getElementById("filterUrgency").value = "";
  document.getElementById("filterSearch").value = "";
  
  filteredDemandes = [...allDemandes];
  currentPage = 1;
  loadFilteredRequests();
  loadPagination();
}

// Charger le tableau avec pagination
function loadFilteredRequests() {
  const start = (currentPage - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  const pageRequests = filteredDemandes.slice(start, end);

  const tbody = document.getElementById("filteredTable");
  tbody.innerHTML = "";

  if (pageRequests.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Aucune demande trouvée</td></tr>';
    return;
  }

  pageRequests.forEach(request => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td><strong>#${request.id}</strong></td>
      <td>${request.type_nom}</td>
      <td>${truncateText(request.description)}</td>
      <td><span class="badge ${getUrgencyBadgeClass(request.urgence)}">${capitalizeFirst(request.urgence)}</span></td>
      <td><span class="badge ${getStatusBadgeClass(request.statut)}">${formatStatus(request.statut)}</span></td>
      <td>${formatDate(request.created_at)}</td>
      <td>
        <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${request.id})" title="Voir">
          <i class="bi bi-eye"></i>
        </button>
        ${request.statut === 'en_attente' ? `
          <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${request.id})" title="Supprimer">
            <i class="bi bi-trash"></i>
          </button>
        ` : ''}
      </td>
    `;
    tbody.appendChild(row);
  });
}

// Pagination
function loadPagination() {
  const totalPages = Math.ceil(filteredDemandes.length / itemsPerPage);
  const paginationDiv = document.getElementById("pagination");
  paginationDiv.innerHTML = "";

  if (totalPages === 0) return;

  // Bouton Précédent
  if (currentPage > 1) {
    const prevLi = document.createElement("li");
    prevLi.className = "page-item";
    prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Précédent</a>`;
    paginationDiv.appendChild(prevLi);
  }

  // Numéros de pages
  const startPage = Math.max(1, currentPage - 2);
  const endPage = Math.min(totalPages, currentPage + 2);

  if (startPage > 1) {
    const li = document.createElement("li");
    li.className = "page-item";
    li.innerHTML = `<a class="page-link" href="#" onclick="changePage(1); return false;">1</a>`;
    paginationDiv.appendChild(li);
    
    if (startPage > 2) {
      const dots = document.createElement("li");
      dots.className = "page-item disabled";
      dots.innerHTML = `<span class="page-link">...</span>`;
      paginationDiv.appendChild(dots);
    }
  }

  for (let i = startPage; i <= endPage; i++) {
    const li = document.createElement("li");
    li.className = `page-item ${i === currentPage ? "active" : ""}`;
    li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
    paginationDiv.appendChild(li);
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      const dots = document.createElement("li");
      dots.className = "page-item disabled";
      dots.innerHTML = `<span class="page-link">...</span>`;
      paginationDiv.appendChild(dots);
    }
    
    const li = document.createElement("li");
    li.className = "page-item";
    li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>`;
    paginationDiv.appendChild(li);
  }

  // Bouton Suivant
  if (currentPage < totalPages) {
    const nextLi = document.createElement("li");
    nextLi.className = "page-item";
    nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Suivant</a>`;
    paginationDiv.appendChild(nextLi);
  }
}

// Changer de page
function changePage(page) {
  currentPage = page;
  loadFilteredRequests();
  loadPagination();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Voir les détails
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

        // Commentaires
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

        const modal = new bootstrap.Modal(document.getElementById("detailsModal"));
        modal.show();
      }
    })
    .catch(error => console.error('Erreur:', error));
}

// Supprimer une demande
function deleteRequest(requestId) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette demande ?')) return;
  
  fetch('../../process/delete_demande.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: requestId })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Demande supprimée avec succès');
      loadAllDemandes();
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

// Fonctions utilitaires
function truncateText(text, maxLength = 50) {
  return text && text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
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
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function showToast(type, message) {
  const toastDiv = document.createElement('div');
  toastDiv.className = 'position-fixed top-0 end-0 p-3';
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