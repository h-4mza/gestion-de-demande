// assets/js/admin.js - VERSION FINALE

document.addEventListener("DOMContentLoaded", () => {
  checkAuthentication();
  
  // On lance les graphiques TOUT DE SUITE pour qu'ils s'affichent
  initActivityChart();
  initDistributionChart();
  
  // Ensuite les données
  loadStatistics();
  loadAdminDemandes();
  loadAdminUsers();
  loadTypes();
});

// ============ GRAPHIQUES ============

function initActivityChart() {
    const ctx = document.getElementById('activityChart');
    if(!ctx) return;

    fetch('../../process/admin/get_all_demandes.php')
    .then(r => r.json())
    .then(data => {
        // Nettoyage
        const existingChart = Chart.getChart("activityChart");
        if (existingChart) existingChart.destroy();

        const count = (data.demandes || []).length;
        // Données visuelles pour la démo (Vague)
        const dataPoints = [Math.max(0, count-2), count+3, count-1, count+4, count, count+2, count];

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Demandes',
                    data: dataPoints,
                    borderColor: '#4338ca',
                    backgroundColor: (context) => {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, "rgba(67, 56, 202, 0.2)");
                        gradient.addColorStop(1, "rgba(67, 56, 202, 0.0)");
                        return gradient;
                    },
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { display: false }, y: { display: false } },
                layout: { padding: 0 }
            }
        });
    });
}

function initDistributionChart() {
    const ctx = document.getElementById('distributionChart');
    if(!ctx) return;

    fetch('../../process/admin/get_type_distribution.php')
    .then(r => r.json())
    .then(data => {
        const existingChart = Chart.getChart("distributionChart");
        if (existingChart) existingChart.destroy();

        let labels = ['Vide'];
        let counts = [1];
        let colors = ['#e5e7eb'];

        if (data.distribution && data.distribution.length > 0) {
            labels = data.distribution.map(item => item.type_nom);
            counts = data.distribution.map(item => item.total);
            colors = ['#4338ca', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'];
        }

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, boxWidth: 8 } }
                },
                cutout: '75%'
            }
        });
    });
}

// ============ TABLEAUX & ACTIONS ============

function loadAdminDemandes() {
  fetch('../../process/admin/get_all_demandes.php?t=' + Date.now())
    .then(r => r.json())
    .then(data => {
      const tbody = document.getElementById("adminDemandesTable");
      if(!tbody) return;
      tbody.innerHTML = "";

      if(!data.demandes || data.demandes.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Aucune demande</td></tr>';
          return;
      }

      data.demandes.forEach(req => {
          const isTreated = req.statut === 'traitee';
          const hasService = req.service_affecte && req.service_affecte !== 'Non affecté';
          
          let btn = '';
          if(isTreated) {
              btn = `<span class="badge bg-light text-success border border-success"><i class="bi bi-check-all"></i> Clôturée</span>`;
          } else {
              const disabled = hasService ? '' : 'disabled';
              const cls = hasService ? 'btn-primary' : 'btn-secondary';
              btn = `<button class="btn btn-sm ${cls} rounded-pill px-3" onclick="markTreated(${req.id})" ${disabled}>Traiter</button>`;
          }

          tbody.innerHTML += `
            <tr>
                <td><span class="fw-bold text-primary">#${req.id}</span></td>
                <td><div class="fw-bold text-dark small">${req.demandeur_nom} ${req.demandeur_prenom}</div></td>
                <td><span class="badge bg-light text-dark border fw-normal">${req.type_nom || 'Autre'}</span></td>
                <td><span class="badge ${getUrgencyClass(req.urgence)}">${req.urgence}</span></td>
                <td>${getStatusBadge(req.statut)}</td>
                <td>
                    <select class="form-select form-select-sm border bg-light" onchange="updateService(${req.id}, this.value)" ${isTreated?'disabled':''}>
                        <option value="" ${!req.service_affecte?'selected':''}>Service...</option>
                        <option value="Informatique" ${req.service_affecte==='Informatique'?'selected':''}>IT</option>
                        <option value="RH" ${req.service_affecte==='RH'?'selected':''}>RH</option>
                        <option value="Achat" ${req.service_affecte==='Achat'?'selected':''}>Achat</option>
                        <option value="Logistique" ${req.service_affecte==='Logistique'?'selected':''}>Logistique</option>
                        <option value="Direction" ${req.service_affecte==='Direction'?'selected':''}>Direction</option>
                    </select>
                </td>
                <td class="text-end">${btn}</td>
            </tr>`;
      });
    });
}

// Helpers
function loadStatistics() {
    fetch('../../process/get_stats.php').then(r=>r.json()).then(d=>{
        if(d.success && d.stats) {
            updateNum('statTotal', d.stats.total);
            updateNum('statToProcess', d.stats.validee);
            updateNum('statPending', d.stats.pending_total); // Utilise la valeur calculée par PHP
            loadTopRequesters();
        }
    });
}
function updateNum(id, val){ const e=document.getElementById(id); if(e) e.textContent=val||0; }

function loadTopRequesters() {
    fetch('../../process/admin/get_top_requesters.php').then(r=>r.json()).then(data=>{
        const div = document.getElementById("topRequesters");
        if(!div) return;
        if(!data.requesters || data.requesters.length===0) { div.innerHTML='<div class="text-muted small">Aucune donnée</div>'; return;}
        
        let h = '<div class="d-flex flex-column gap-3">';
        const max = Math.max(...data.requesters.map(r=>r.total))||1;
        data.requesters.forEach(r=>{
            const pct = (r.total/max)*100;
            h+=`<div class="d-flex align-items-center"><div class="fw-bold text-secondary small" style="width:20px">${r.total}</div><div class="flex-grow-1"><div class="d-flex justify-content-between mb-1"><span class="small fw-bold text-dark">${r.nom} ${r.prenom}</span></div><div class="progress" style="height:6px;border-radius:10px;background:#f3f4f6"><div class="progress-bar" style="width:${pct}%;background:#4338ca"></div></div></div></div>`;
        });
        div.innerHTML = h+'</div>';
    });
}

function markTreated(id) {
    if(confirm("Traiter la demande ?")) {
        fetch('../../process/admin/update_demande_status.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id:id, statut:'traitee'})
        }).then(r=>r.json()).then(()=>{ loadAdminDemandes(); loadStatistics(); });
    }
}
function updateService(id, srv) {
    fetch('../../process/admin/update_demande_service.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id:id, service:srv})
    }).then(r=>r.json()).then(()=>{ showToast('success','Service affecté'); loadAdminDemandes(); });
}

function getStatusBadge(s) { 
    if(s==='validee') return '<span class="badge bg-warning text-dark">À traiter</span>';
    if(s==='traitee') return '<span class="badge bg-success">Traitée</span>';
    return `<span class="badge bg-secondary">${s}</span>`;
}
function getUrgencyClass(u) { return u==='urgente'?'bg-danger':(u==='moyenne'?'bg-warning text-dark':'bg-secondary'); }
function showToast(t,m) { const d=document.createElement('div'); d.className='position-fixed top-0 end-0 p-3'; d.style.zIndex=9999; d.innerHTML=`<div class="toast show align-items-center text-white bg-${t} border-0"><div class="d-flex"><div class="toast-body">${m}</div></div></div>`; document.body.appendChild(d); setTimeout(()=>d.remove(),3000); }

// Users & Types (Version courte)
function loadAdminUsers() { fetch('../../process/admin/get_all_users.php').then(r=>r.json()).then(d=>{ const b=document.getElementById("adminUsersTable"); if(b&&d.users){ b.innerHTML=""; d.users.forEach(u=>{ b.innerHTML+=`<tr><td>${u.id}</td><td>${u.nom} ${u.prenom}</td><td>${u.email}</td><td><span class="badge bg-info">${u.role}</span></td><td>${u.service||'-'}</td><td>${u.statut}</td><td><button class="btn btn-sm btn-outline-primary" onclick="editUser(${u.id})"><i class="bi bi-pencil"></i></button></td></tr>`; }); } }); }
function loadTypes() { fetch('../../process/admin/get_all_types.php').then(r=>r.json()).then(d=>{ const b=document.getElementById("typesTable"); if(b&&d.types){ b.innerHTML=""; d.types.forEach(t=>{ b.innerHTML+=`<tr><td><b>${t.nom}</b></td><td>${t.description}</td><td><button class="btn btn-sm btn-outline-danger" onclick="deleteType(${t.id})"><i class="bi bi-trash"></i></button></td></tr>`; }); } }); }
function newUser(){new bootstrap.Modal(document.getElementById('userModal')).show();}
function handleSaveUser(e){e.preventDefault();location.reload();}
function handleAddType(e){e.preventDefault();location.reload();}
function deleteType(id){if(confirm('Supprimer?'))fetch('../../process/admin/delete_type.php',{method:'POST',body:JSON.stringify({id})}).then(()=>loadTypes());}
function exportCSV(){window.location.href='../../process/admin/export_demandes_csv.php';}
function editUser(id) { fetch(`../../process/admin/get_user.php?id=${id}`).then(r=>r.json()).then(d=>{ if(d.success){ document.getElementById("userNom").value=d.user.nom; document.getElementById("userId").value=id; new bootstrap.Modal(document.getElementById('userModal')).show(); }}); }