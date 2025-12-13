// assets/js/auth.js - Adapté pour PHP/MySQL

// Fonction de connexion - envoie au serveur PHP
function handleLogin(event) {
  event.preventDefault();
  
  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  // Validation côté client
  if (!email || !password) {
    showAlert('danger', 'Veuillez remplir tous les champs');
    return;
  }

  // Envoyer au serveur PHP
  const formData = new FormData();
  formData.append('email', email);
  formData.append('password', password);

  fetch('process/login_process.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Redirection selon le rôle
      switch(data.user.role) {
        case 'demandeur':
          window.location.href = 'pages/demandeur/dashboard.php';
          break;
        case 'validateur':
          window.location.href = 'pages/validateur/dashboard.php';
          break;
        case 'administrateur':
          window.location.href = 'pages/admin/dashboard.php';
          break;
      }
    } else {
      showAlert('danger', data.message || 'Email ou mot de passe incorrect');
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    showAlert('danger', 'Une erreur est survenue');
  });
}

// Fonction de déconnexion
function logout() {
  if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
    window.location.href = '../../process/logout_process.php';
  }
}

// Vérifier l'authentification (pour les pages protégées)
function checkAuthentication() {
  fetch('../../process/check_auth.php')
    .then(response => response.json())
    .then(data => {
      if (!data.authenticated) {
        window.location.href = '../../login.php';
      }
      return data.user;
    })
    .catch(error => {
      console.error('Erreur auth:', error);
      window.location.href = '../../login.php';
    });
}

// Fonction utilitaire pour afficher les alertes
function showAlert(type, message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  const form = document.getElementById('loginForm');
  form.parentNode.insertBefore(alertDiv, form);
  
  // Auto-dismiss après 5 secondes
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// Récupérer l'utilisateur courant
function getCurrentUser() {
  return fetch('../../process/get_current_user.php')
    .then(response => response.json())
    .then(data => data.user)
    .catch(error => {
      console.error('Erreur:', error);
      return null;
    });
}
