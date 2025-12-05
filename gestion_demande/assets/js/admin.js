// assets/js/admin.js - Adapté pour PHP/MySQL

document.addEventListener("DOMContentLoaded", () => {
  checkAuthentication();
  loadAdminDemandes();
  loadAdminUsers();
  loadTypes();
  loadStatistics();
});

// ============ GESTION DES DEMANDES ============

function loadAdminDemandes() {
  fetch('../../process/admin/get_all_demandes.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      
      const tbody = document.getElementById("adminDemandesTable");
      tbody.innerHTML = "";

      if (data.demandes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Aucune demande</td></tr>';
        return;
      }

      data.demandes.forEach(request => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td><strong>#${request.id}</strong></td>
          <td>${request.demandeur_nom} ${request.demandeur_prenom}</td>
          <td>${request.type_nom}</td>
          <td>${truncateText(request.description)}</td>
          <td><span class="badge ${getUrgencyBadgeClass(request.urgence)}">${capitalizeFirst(request.urgence)}</span></td>
          <td>
           <select class="form-select form-select-sm" onchange="updateStatus(${request.id}, this.value)">
              <option value="en_cours_de_traitement" ${request.statut === "en_cours_de_traitement" ? "selected" : ""}>
                En cours de traitement
              </option>
              <option value="traitee" ${request.statut === "traitee" ? "selected" : ""}>
                Traitée
              </option>
           </select>

          </td>
          <td>
            <select class="form-select form-select-sm" onchange="updateService(${request.id}, this.value)">
              <option ${!request.service_affecte ? 'selected' : ''}>Non affecté</option>
              <option value="Informatique" ${request.service_affecte === "Informatique" ? "selected" : ""}>Informatique</option>
              <option value="RH" ${request.service_affecte === "RH" ? "selected" : ""}>RH</option>
              <option value="Achat" ${request.service_affecte === "Achat" ? "selected" : ""}>Achat</option>
              <option value="Logistique" ${request.service_affecte === "Logistique" ? "selected" : ""}>Logistique</option>
            </select>
          </td>
          <td>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${request.id})" title="Supprimer">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch(error => console.error('Erreur:', error));
}

function updateStatus(requestId, newStatus) {
  fetch('../../process/admin/update_demande_status.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: requestId, statut: newStatus })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Statut mis à jour');
      loadStatistics();
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

function updateService(requestId, newService) {
  fetch('../../process/admin/update_demande_service.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: requestId, service: newService })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Service affecté');
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

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
      showToast('success', 'Demande supprimée');
      loadAdminDemandes();
      loadStatistics();
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

function exportCSV() {
  window.location.href = '../../process/admin/export_demandes_csv.php';
}

// ============ GESTION DES UTILISATEURS ============

function loadAdminUsers() {
  fetch('../../process/admin/get_all_users.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      
      const tbody = document.getElementById("adminUsersTable");
      tbody.innerHTML = "";

      data.users.forEach(user => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${user.id}</td>
          <td>${user.nom} ${user.prenom}</td>
          <td>${user.email}</td>
          <td><span class="badge bg-info">${capitalizeFirst(user.role)}</span></td>
          <td>${user.service || 'N/A'}</td>
          <td><span class="badge ${user.statut === "actif" ? "bg-success" : "bg-secondary"}">${capitalizeFirst(user.statut)}</span></td>
          <td>
            <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(${user.id})" title="Éditer">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})" title="Supprimer">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch(error => console.error('Erreur:', error));
}

function newUser() {
  document.getElementById("userForm").reset();
  document.getElementById("userId").value = "";
  document.getElementById("userPassword").required = true;
}

function editUser(userId) {
  fetch(`../../process/admin/get_user.php?id=${userId}`)
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      
      const user = data.user;
      document.getElementById("userNom").value = user.nom;
      document.getElementById("userPrenom").value = user.prenom;
      document.getElementById("userEmail").value = user.email;
      document.getElementById("userRole").value = user.role;
      document.getElementById("userService").value = user.service;
      document.getElementById("userStatut").value = user.statut;
      document.getElementById("userId").value = userId;
      document.getElementById("userPassword").required = false;

      const modal = new bootstrap.Modal(document.getElementById("userModal"));
      modal.show();
    })
    .catch(error => console.error('Erreur:', error));
}

function handleSaveUser(event) {
  event.preventDefault();
  
  const userId = document.getElementById("userId").value;
  const formData = new FormData();
  
  formData.append('nom', document.getElementById("userNom").value);
  formData.append('prenom', document.getElementById("userPrenom").value);
  formData.append('email', document.getElementById("userEmail").value);
  formData.append('role', document.getElementById("userRole").value);
  formData.append('service', document.getElementById("userService").value);
  formData.append('statut', document.getElementById("userStatut").value);
  
  const password = document.getElementById("userPassword").value;
  if (password) {
    formData.append('password', password);
  }
  
  if (userId) {
    formData.append('id', userId);
  }

  const url = userId 
    ? '../../process/admin/update_user.php' 
    : '../../process/admin/create_user.php';

  fetch(url, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Utilisateur enregistré avec succès');
      const modal = bootstrap.Modal.getInstance(document.getElementById("userModal"));
      modal.hide();
      loadAdminUsers();
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

function deleteUser(userId) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) return;
  
  fetch('../../process/admin/delete_user.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: userId })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Utilisateur supprimé');
      loadAdminUsers();
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

// ============ GESTION DES TYPES ============

function loadTypes() {
  fetch('../../process/admin/get_all_types.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      
      const tbody = document.getElementById("typesTable");
      tbody.innerHTML = "";

      data.types.forEach(type => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td><strong>${type.nom}</strong></td>
          <td>${type.description || 'N/A'}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary me-1" onclick="editType(${type.id})" title="Éditer">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteType(${type.id})" title="Supprimer">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch(error => console.error('Erreur:', error));
}

function handleAddType(event) {
  event.preventDefault();
  
  const formData = new FormData();
  formData.append('nom', document.getElementById("typeName").value);
  formData.append('description', document.getElementById("typeDesc").value);

  fetch('../../process/admin/create_type.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Type ajouté avec succès');
      document.getElementById("typeForm").reset();
      loadTypes();
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

function editType(typeId) {
  showToast('info', 'Fonctionnalité à venir');
}

function deleteType(typeId) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce type ?')) return;
  
  fetch('../../process/admin/delete_type.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: typeId })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', 'Type supprimé');
      loadTypes();
    } else {
      showToast('danger', data.message || 'Erreur');
    }
  })
  .catch(error => console.error('Erreur:', error));
}

// ============ STATISTIQUES ============

function loadStatistics() {
  fetch('../../process/get_stats.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      
      const stats = data.stats;
      document.getElementById("statTotal").textContent = stats.total || 0;
      document.getElementById("statApproved").textContent = stats.validee || 0;
      document.getElementById("statPending").textContent = stats.en_attente || 0;
      document.getElementById("statRejected").textContent = stats.rejetee || 0;

      loadTopRequesters();
      loadTypeDistribution();
    })
    .catch(error => console.error('Erreur:', error));
}

function loadTopRequesters() {
  fetch('../../process/admin/get_top_requesters.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      
      let html = '<div class="list-group">';
      data.requesters.forEach(r => {
        html += `
          <div class="list-group-item d-flex justify-content-between align-items-center">
            ${r.nom} ${r.prenom}
            <span class="badge bg-primary rounded-pill">${r.total}</span>
          </div>
        `;
      });
      html += '</div>';
      
      document.getElementById("topRequesters").innerHTML = html;
    })
    .catch(error => console.error('Erreur:', error));
}

function loadTypeDistribution() {
  fetch('../../process/admin/get_type_distribution.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      
      const total = data.distribution.reduce((sum, item) => sum + parseInt(item.total), 0);
      
      let html = '<div class="list-group">';
      data.distribution.forEach(item => {
        const percentage = total > 0 ? ((item.total / total) * 100).toFixed(1) : 0;
        html += `
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
              <span>${item.type_nom}</span>
              <span class="badge bg-primary">${item.total}</span>
            </div>
            <div class="progress mt-2" style="height: 6px;">
              <div class="progress-bar" style="width: ${percentage}%"></div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      
      document.getElementById("typeDistribution").innerHTML = html;
    })
    .catch(error => console.error('Erreur:', error));
}

// ============ FONCTIONS UTILITAIRES ============

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