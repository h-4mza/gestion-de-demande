// assets/js/validateur.js

document.addEventListener("DOMContentLoaded", () => {
  checkAuthentication();
  loadValidationRequests();
  refreshStats();
});

// Charger les demandes à valider (équipe du validateur)
function loadValidationRequests() {
  fetch('../../process/get_demandes_validateur.php')
    .then(response => response.json())
    .then(data => {
      const tbody = document.getElementById("validationTable");
      tbody.innerHTML = "";

      if (!data.success) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center text-danger py-4">
              ${data.message || 'Erreur de chargement'}
            </td>
          </tr>`;
        return;
      }

      if (!data.demandes || data.demandes.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              Aucune demande à valider pour votre équipe
            </td>
          </tr>`;
        return;
      }

      data.demandes.forEach(request => {
        const row = document.createElement("tr");

        const canDecide =
          request.statut === 'en_attente' ||
          request.statut === 'en_cours_de_validation';

        
        const disabledAttr = canDecide ? '' : 'disabled';
        const attachmentsBtn = request.has_attachments
          ? `<button class="btn btn-sm btn-outline-secondary me-1"
                onclick="openAttachments(${request.id})"
                title="Voir pièces jointes">
                <i class="bi bi-paperclip"></i>
            </button>`
          : "";

        row.innerHTML = `
          <td><strong>#${request.id}</strong></td>
          <td>${request.demandeur_prenom} ${request.demandeur_nom}</td>
          <td>${request.type_nom}</td>
          <td>${truncateText(request.description || '', 70)}</td>
          <td>
            <span class="badge ${getUrgencyBadgeClass(request.urgence)}">
              ${capitalizeFirst(request.urgence)}
            </span>
          </td>
          <td>${formatDate(request.created_at)}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary me-1"
                    onclick="openValidationModal(${request.id}, 'valider')"
                    ${disabledAttr}>
              <i class="bi bi-check-lg"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger"
                    onclick="openValidationModal(${request.id}, 'rejeter')"
                    ${disabledAttr}>
              <i class="bi bi-x-lg"></i>
    
            </button>
            ${attachmentsBtn}

          </td>
        `;

        tbody.appendChild(row);
      });
    })
    .catch(error => {
      console.error('Erreur:', error);
      const tbody = document.getElementById("validationTable");
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="text-center text-danger py-4">
            Erreur de chargement
          </td>
        </tr>`;
    });
}

// Ouvrir la modale de validation/rejet
function openValidationModal(id, type) {
  const title = document.getElementById("validationTitle");
  const commentHelp = document.getElementById("commentHelp");
  const submitBtn = document.getElementById("validationSubmitBtn");
  const summary = document.getElementById("requestSummary");

  document.getElementById("currentRequestId").value = id;
  document.getElementById("validationType").value = type;
  document.getElementById("validationComment").value = "";

  if (type === 'valider') {
    title.textContent = `Valider la demande #${id}`;
    commentHelp.textContent = "Le commentaire est optionnel.";
    submitBtn.classList.remove('btn-danger');
    submitBtn.classList.add('btn-primary');
    submitBtn.textContent = "Confirmer la validation";
    summary.textContent = `Vous êtes sur le point de VALIDER la demande #${id}.`;
  } else {
    title.textContent = `Rejeter la demande #${id}`;
    commentHelp.textContent = "Un commentaire est OBLIGATOIRE pour rejeter la demande.";
    submitBtn.classList.remove('btn-primary');
    submitBtn.classList.add('btn-danger');
    submitBtn.textContent = "Confirmer le rejet";
    summary.textContent = `Vous êtes sur le point de REJETER la demande #${id}.`;
  }

  const modal = new bootstrap.Modal(document.getElementById("validationModal"));
  modal.show();
}

// Soumission du formulaire de validation/rejet
function handleValidation(event) {
  event.preventDefault();

  const id = parseInt(document.getElementById("currentRequestId").value, 10);
  const type = document.getElementById("validationType").value; // 'valider' ou 'rejeter'
  const commentaire = document.getElementById("validationComment").value.trim();

  if (!id || !['valider', 'rejeter'].includes(type)) {
    showToast('danger', 'Paramètres invalides.');
    return;
  }

  if (type === 'rejeter' && !commentaire) {
    showToast('danger', 'Un commentaire est obligatoire pour rejeter la demande.');
    return;
  }

  fetch('../../process/validation_process.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, action: type, commentaire })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast('success', data.message || 'Décision enregistrée.');
        bootstrap.Modal.getInstance(document.getElementById("validationModal")).hide();
        loadValidationRequests();
        refreshStats();
      } else {
        showToast('danger', data.message || 'Erreur lors de la décision.');
      }
    })
    .catch(error => {
      console.error('Erreur:', error);
      showToast('danger', 'Erreur lors de la décision.');
    });
}

// Rafraîchir les statistiques du validateur
function refreshStats() {
  fetch('../../process/get_stats.php')
    .then(response => response.json())
    .then(data => {
      if (!data.success) return;
      const stats = data.stats;

      if (!stats) return;

      const total = parseInt(stats.total || 0, 10);
      const enAttente = parseInt(stats.en_attente || 0, 10);
      const enCoursVal = parseInt(stats.en_cours_de_validation || 0, 10);
      const validees = parseInt(stats.validee || 0, 10);
      const rejetees = parseInt(stats.rejetee || 0, 10);

      const pending = enAttente + enCoursVal;

      const totalEl = document.getElementById("totalRequests");
      const pendingEl = document.getElementById("pendingValidation");
      const approvedEl = document.getElementById("approvedCount");
      const rejectedEl = document.getElementById("rejectedCount");

      if (totalEl) totalEl.textContent = total;
      if (pendingEl) pendingEl.textContent = pending;
      if (approvedEl) approvedEl.textContent = validees;
      if (rejectedEl) rejectedEl.textContent = rejetees;
    })
    .catch(error => console.error('Erreur stats:', error));
}

// ==== Helpers visuels ====

function truncateText(text, maxLength) {
  if (!text) return '';
  return text.length > maxLength ? text.substring(0, maxLength) + '…' : text;
}

function getUrgencyBadgeClass(urgence) {
  switch (urgence) {
    case 'faible': return 'bg-secondary';
    case 'moyenne': return 'bg-warning text-dark';
    case 'urgente': return 'bg-danger';
    default: return 'bg-light text-dark';
  }
}

function capitalizeFirst(str) {
  if (!str) return '';
  str = str.toString();
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  if (isNaN(date)) return dateStr;
  return date.toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Petit toaster
function showToast(type, message) {
  const container = document.createElement('div');
  container.className = 'position-fixed top-0 end-0 p-3';
  container.style.zIndex = '9999';

  container.innerHTML = `
    <div class="toast show align-items-center text-white bg-${type} border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  `;

  document.body.appendChild(container);
  setTimeout(() => container.remove(), 3000);
}

// assets/js/validateur.js — replace openAttachments function with this
function openAttachments(demandeId) {
  const url = `../../process/get_attachments.php?demande_id=${demandeId}`;
  fetch(url)
    .then(r => {
      if (!r.ok) return r.text().then(txt => { throw new Error(txt || 'Erreur réseau'); });
      return r.json().catch(() => { throw new Error('Réponse JSON invalide'); });
    })
    .then(data => {
      const container = document.getElementById("attachmentsList");
      if (!data.success || !Array.isArray(data.attachments) || data.attachments.length === 0) {
        container.innerHTML = "<p class='text-muted'>Aucune pièce jointe.</p>";
      } else {
        container.innerHTML = "";
        data.attachments.forEach(att => {
          // att.filename already normalized by PHP (e.g. "assets/uploads/xyz.png")
          const stored = att.filename || null;
          const original = att.original_name || att.nom_fichier || 'Pièce jointe';
          if (!stored) {
            // skip or show a note (no valid file path)
            container.innerHTML += `<div class="text-muted mb-2">Fichier introuvable: ${escapeHtml(original)}</div>`;
            return;
          }
          // build href relative to current page: use the path returned by PHP
          // if your dashboard is at pages/validateur/dashboard.php, prefix with ../../
          const basePrefix = '../../'; // keep same prefix you used elsewhere
          const href = stored.startsWith('http') ? stored : (basePrefix + stored.replace(/^\/+/, ''));
          const sizeText = att.size ? ` <small class="text-muted">(${Math.round(att.size/1024)} KB)</small>` : '';
          container.innerHTML += `
            <a href="${href}" target="_blank" class="d-block mb-2 text-decoration-none">
              <i class="bi bi-paperclip"></i> ${escapeHtml(original)}${sizeText}
            </a>`;
        });
      }
      new bootstrap.Modal(document.getElementById("attachmentsModal")).show();
    })
    .catch(err => {
      console.error('Erreur attachments:', err);
      const container = document.getElementById("attachmentsList");
      container.innerHTML = "<p class='text-danger'>Impossible de charger les pièces jointes.</p>";
      new bootstrap.Modal(document.getElementById("attachmentsModal")).show();
    });
}


// small helper to avoid injecting raw HTML (escape)
function escapeHtml(unsafe) {
  if (!unsafe) return '';
  return unsafe.replace(/[&<>"'`=\/]/g, function (s) {
    return ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
      '/': '&#x2F;',
      '`': '&#x60;',
      '=': '&#x3D;'
    })[s];
  });
}

