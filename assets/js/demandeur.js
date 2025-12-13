// assets/js/demandeur.js - Avec fonction d'édition (Edit)

let isEditing = false; // Variable pour savoir si on est en mode édition
let editId = null;     // ID de la demande en cours d'édition

document.addEventListener("DOMContentLoaded", () => {
  checkAuthentication();
  loadDashboardStats();
  loadRequestsTable();

  // Reset du formulaire quand on ferme la modale (IMPORTANT)
  const modalEl = document.getElementById('newRequestModal');
  if (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', resetModalForm);
  }
});

// Charger les statistiques
function loadDashboardStats() {
  fetch('../../process/get_stats.php')
    .then(r => r.json()).then(d => {
      if (d.success) {
        document.getElementById("totalRequests").textContent = d.stats.total || 0;
        document.getElementById("pendingRequests").textContent = d.stats.en_attente || 0;
        // Note: selon votre get_stats.php, vérifiez si les clés sont "en_attente" ou autre
        document.getElementById("approvedRequests").textContent = d.stats.validee || 0;
        document.getElementById("rejectedRequests").textContent = d.stats.rejetee || 0;
      }
    })
    .catch(error => console.error('Erreur:', error));
}

// Charger le tableau des demandes (uniquement les demandes "nouvelles" / en_attente)
function loadRequestsTable() {
  fetch('../../process/get_demandes.php')
    .then(r => r.json())
    .then(data => {
      const tbody = document.getElementById("requestsTable");
      if (!tbody) return;
      tbody.innerHTML = "";

      if (!data.success || !Array.isArray(data.demandes) || data.demandes.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="7" class="text-center text-muted py-4">Aucune demande en attente</td></tr>';
        return;
      }

      // On ne garde que les demandes "nouvelles" (non traitées) : statut en_attente
      const enAttente = data.demandes.filter(req => req.statut === 'en_attente');

      if (enAttente.length === 0) {
        tbody.innerHTML =
          '<tr><td colspan="7" class="text-center text-muted py-4">Aucune demande en attente</td></tr>';
        return;
      }

      enAttente.forEach(req => {
        const canEdit = req.statut === 'en_attente';

        const editBtn = canEdit
          ? `<button class="btn btn-sm btn-outline-warning me-1"
                     onclick="editRequest(${req.id})" title="Modifier">
               <i class="bi bi-pencil"></i>
             </button>`
          : '';

        const delBtn = canEdit
          ? `<button class="btn btn-sm btn-outline-danger"
                     onclick="deleteRequest(${req.id})" title="Supprimer">
               <i class="bi bi-trash"></i>
             </button>`
          : '';

        const row = document.createElement('tr');
        row.innerHTML = `
          <td><strong>#${req.id}</strong></td>
          <td>${req.type_nom || ''}</td>
          <td>${truncateText(req.description || '')}</td>
          <td><span class="badge ${getUrgencyBadgeClass(req.urgence)}">
                ${capitalizeFirst(req.urgence)}
              </span></td>
          <td><span class="badge ${getStatusBadgeClass(req.statut)}">
                ${formatStatus(req.statut)}
              </span></td>
          <td>${formatDate(req.created_at)}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary me-1"
                    onclick="viewDetails(${req.id})" title="Détails">
              <i class="bi bi-eye"></i>
            </button>
            ${editBtn}
            ${delBtn}
          </td>
        `;
        tbody.appendChild(row);
      });
    })
    .catch(error => console.error('Erreur:', error));
}


// === NOUVELLE FONCTION : Préparer la modale pour l'édition ===
function editRequest(id) {
  // 1. Récupérer les infos actuelles de la demande
  fetch(`../../process/get_demande_details.php?id=${id}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const d = data.demande;
        
        // 2. Remplir le formulaire avec les données existantes
        document.getElementById("requestType").value = d.type_id;
        document.getElementById("requestDescription").value = d.description;
        document.getElementById("requestUrgency").value = d.urgence;
        
        // 3. Passer en "Mode Édition"
        isEditing = true;
        editId = id;
        
        // 4. Adapter l'interface de la modale (Titre et Bouton)
        document.querySelector("#newRequestModal .modal-title").innerHTML = `<i class="bi bi-pencil-square"></i> Modifier la demande #${id}`;
        // On change le texte du bouton de soumission
        const submitBtn = document.querySelector("#newRequestModal button[type='submit']");
        submitBtn.innerHTML = `<i class="bi bi-save me-1"></i> Enregistrer`;
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-warning');

        // 5. Ouvrir la modale
        new bootstrap.Modal(document.getElementById("newRequestModal")).show();
      } else {
        showToast('danger', 'Impossible de charger les détails de la demande');
      }
    })
    .catch(error => console.error('Erreur:', error));
}

// === GESTION UNIFIÉE : CRÉATION ET MODIFICATION ===
function handleNewRequest(event) {
  event.preventDefault();

  const formData = new FormData();
  formData.append('type_id', document.getElementById("requestType").value);
  formData.append('description', document.getElementById("requestDescription").value);
  formData.append('urgence', document.getElementById("requestUrgency").value);

  // Gestion des fichiers (Optionnel : si on permet l'ajout de nouveaux fichiers en édition)
  const fileInput = document.getElementById("requestFiles");
  if(fileInput.files.length > 0) {
      for (let i = 0; i < fileInput.files.length; i++) {
        formData.append('files[]', fileInput.files[i]);
      }
  }

  // DÉCISION IMPORTANTE : Création ou Mise à jour ?
  let url = '../../process/create_demande.php';
  
  if (isEditing && editId) {
    url = '../../process/update_demande.php'; // On appelle le script de mise à jour
    formData.append('id', editId);
  }

  fetch(url, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('success', data.message || 'Opération réussie');
      
      // Fermer la modale proprement
      const modalEl = document.getElementById("newRequestModal");
      const modalInstance = bootstrap.Modal.getInstance(modalEl);
      modalInstance.hide();
      
      // Recharger les données
      loadDashboardStats();
      loadRequestsTable();
    } else {
      showToast('danger', data.message || 'Erreur lors de l\'opération');
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    showToast('danger', 'Une erreur réseau est survenue');
  });
}

// Réinitialiser la modale quand on la ferme (pour revenir au mode "Nouvelle demande")
function resetModalForm() {
    document.getElementById("newRequestForm").reset();
    isEditing = false;
    editId = null;
    
    // Remettre les textes et styles d'origine
    document.querySelector("#newRequestModal .modal-title").innerHTML = `<i class="bi bi-pencil-square"></i> Nouvelle demande`;
    const submitBtn = document.querySelector("#newRequestModal button[type='submit']");
    submitBtn.innerHTML = `<i class="bi bi-send me-1"></i> Soumettre`;
    submitBtn.classList.add('btn-primary');
    submitBtn.classList.remove('btn-warning');
}

// Afficher les détails (Lecture seule)
function viewDetails(id) {
    fetch(`../../process/get_demande_details.php?id=${id}`).then(r=>r.json()).then(d=>{
        if(d.success){
            const r=d.demande;
            document.getElementById("detailsId").innerText=r.id;
            document.getElementById("detailsType").innerText=r.type_nom;
            document.getElementById("detailsDescription").innerText=r.description;
            document.getElementById("detailsUrgency").innerText=capitalizeFirst(r.urgence);
            document.getElementById("detailsStatus").innerText=formatStatus(r.statut);
            document.getElementById("detailsDate").innerText=formatDate(r.created_at);
            
            // Badge
            const badge = document.getElementById("detailsBadge");
            badge.className = `badge ${getStatusBadgeClass(r.statut)}`;
            badge.textContent = formatStatus(r.statut);
            
            // Commentaires / Validations
             const commentsDiv = document.getElementById("commentsText");
            if (d.validations && d.validations.length > 0) {
              let commentsHtml = '';
              d.validations.forEach(val => {
                commentsHtml += `
                  <div class="mb-2 border-bottom pb-2">
                    <strong>${val.validateur_prenom} ${val.validateur_nom}</strong> 
                    <small class="text-muted">(${formatDate(val.created_at)})</small><br>
                    Statut: <strong>${val.action}</strong>
                    <p class="mb-0 fst-italic">"${val.commentaire || 'Aucun commentaire'}"</p>
                  </div>
                `;
              });
              commentsDiv.innerHTML = commentsHtml;
            } else {
              commentsDiv.textContent = "Aucun commentaire pour le moment.";
            }

            // Timeline
            const timeline = document.getElementById("detailsTimeline");
            timeline.innerHTML = `
              <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div>Créée le ${formatDate(r.created_at)}</div>
              </div>
              ${r.statut !== 'en_attente' ? `
                <div class="timeline-item">
                  <div class="timeline-marker bg-${r.statut === 'rejetee' ? 'danger' : 'success'}"></div>
                  <div>${formatStatus(r.statut)} le ${formatDate(r.updated_at)}</div>
                </div>
              ` : ''}
            `;

            new bootstrap.Modal(document.getElementById("detailsModal")).show();
        }
    });
}

function deleteRequest(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cette demande ?')) {
        fetch('../../process/delete_demande.php', {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id})
        }).then(r=>r.json()).then(d=>{
            if(d.success) { showToast('success','Demande supprimée'); loadDashboardStats(); loadRequestsTable(); }
            else showToast('danger', d.message);
        });
    }
}

// Helpers
function truncateText(t,l=50){return t && t.length>l?t.substring(0,l)+"...":t;}
function getUrgencyBadgeClass(u){return u==='urgente'?'bg-danger':(u==='moyenne'?'bg-warning text-dark':'bg-secondary');}
function getStatusBadgeClass(s){
    switch(s) {
        case 'validee': return 'bg-success';
        case 'rejetee': return 'bg-danger';
        case 'traitee': return 'bg-success';
        case 'en_cours_de_traitement': return 'bg-info text-dark';
        case 'en_attente': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}
function formatStatus(s){ 
    const m={'en_attente':'En attente','validee':'Validée','rejetee':'Rejetée','traitee':'Traitée','en_cours_de_traitement':'En cours'}; 
    return m[s]||s;
}
function formatDate(d){return new Date(d).toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'});}
function capitalizeFirst(s){return s ? s.charAt(0).toUpperCase()+s.slice(1) : '';}
function showToast(type, message) {
  const d = document.createElement('div');
  d.className = `position-fixed top-0 end-0 p-3`; d.style.zIndex = '9999';
  d.innerHTML = `<div class="toast show align-items-center text-white bg-${type} border-0"><div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
  document.body.appendChild(d); setTimeout(()=>d.remove(), 3000);
}