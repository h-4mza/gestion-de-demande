// assets/js/notifications.js

// BASE_URL doit être défini dans chaque page PHP avant ce script.
// Exemple : const BASE_URL = '../..'; ou '.'.
const NOTIF_BASE = (typeof BASE_URL !== "undefined") ? BASE_URL : ".";

let notifData = [];

// Petit helper toast si pas déjà défini ailleurs
if (typeof showToast === "undefined") {
  window.showToast = function (type, message) {
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
    setTimeout(() => container.remove(), 3500);
  };
}

document.addEventListener("DOMContentLoaded", () => {
  loadNotifications();

  const markAllBtn = document.getElementById("notifMarkAllBtn");
  if (markAllBtn) {
    markAllBtn.addEventListener("click", markAllNotificationsAsRead);
  }
});

// Charger les notifications depuis le backend
function loadNotifications() {
  fetch(`${NOTIF_BASE}/process/get_notifications.php`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Erreur notifs:", data.message);
        return;
      }
      notifData = data.notifications || [];
      renderNotifications();
    })
    .catch(err => {
      console.error("Erreur réseau notifs:", err);
    });
}

// Afficher dans le dropdown + badge
function renderNotifications() {
  const container = document.getElementById("notifListContainer");
  const badge = document.getElementById("notifBadge");

  if (!container) return;

  if (!notifData.length) {
    container.innerHTML = `
      <div class="text-center text-muted small py-3">
        Aucune notification
      </div>`;
    if (badge) {
      badge.classList.add("d-none");
      badge.textContent = "0";
    }
    return;
  }

  let unreadCount = 0;
  const itemsHtml = notifData.map(n => {
    const isRead = n.lu == 1;
    if (!isRead) unreadCount++;

    const badgeNew = isRead ? "" :
      `<span class="badge bg-primary rounded-pill ms-2">Nouveau</span>`;

    const dateStr = formatDate(n.created_at);

    return `
      <button type="button"
              class="dropdown-item small d-flex justify-content-between align-items-start notif-item
                     ${isRead ? 'text-muted' : ''}"
              data-id="${n.id}"
              data-demande="${n.demande_id || ''}">
        <div class="me-2 text-start">
          ${n.demande_id ? `<div class="fw-semibold">Demande #${n.demande_id}</div>` : ""}
          <div class="text-wrap">${escapeHtml(n.message || '')}</div>
          <div class="text-muted fst-italic small">${dateStr}</div>
        </div>
        ${badgeNew}
      </button>
    `;
  }).join("");

  container.innerHTML = itemsHtml;

  // Mise à jour du badge
  if (badge) {
    if (unreadCount > 0) {
      badge.classList.remove("d-none");
      badge.textContent = unreadCount > 9 ? "9+" : unreadCount;
    } else {
      badge.classList.add("d-none");
      badge.textContent = "0";
    }
  }

  // Écouteurs sur chaque notification
  document.querySelectorAll(".notif-item").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-id");
      const demandeId = btn.getAttribute("data-demande");
      markNotificationAsRead(id);

      // TODO : Si tu veux ouvrir le détail de la demande,
      // tu peux ici appeler ta fonction existante showDemandeDetails(demandeId)
      // selon le rôle (demandeur/validateur/admin).
    });
  });

  // Popup "vous avez X nouvelles notifications" (appel uniquement au chargement)
  if (unreadCount > 0) {
    showToast('info', `Vous avez ${unreadCount} notification(s) non lue(s).`);
  }
}

// Marquer une notification comme lue
function markNotificationAsRead(id) {
  fetch(`${NOTIF_BASE}/process/mark_notification_read.php`, {
    method: "POST",
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id })
  })
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Erreur mark read:", data.message);
        return;
      }
      // Mettre à jour localement
      notifData = notifData.map(n => {
        if (n.id == id) n.lu = 1;
        return n;
      });
      renderNotifications();
    })
    .catch(err => console.error("Erreur réseau mark read:", err));
}

// Tout marquer comme lu
function markAllNotificationsAsRead() {
  const unreadIds = notifData.filter(n => n.lu == 0).map(n => n.id);
  if (!unreadIds.length) return;

  // On enchaîne les appels, simple mais suffisant
  Promise.all(unreadIds.map(id =>
    fetch(`${NOTIF_BASE}/process/mark_notification_read.php`, {
      method: "POST",
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id })
    }).then(r => r.json())
  )).then(() => {
    notifData = notifData.map(n => ({ ...n, lu: 1 }));
    renderNotifications();
  }).catch(err => console.error("Erreur mark all read:", err));
}

// Helpers
function formatDate(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  if (isNaN(d)) return dateStr;
  return d.toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;', '<': '&lt;', '>': '&gt;',
    '"': '&quot;', "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}
