/* EcoRide — JavaScript global */

// Validation mot de passe en temps réel
const pwdInput = document.getElementById('password');
const pwdHint  = document.getElementById('passwordHint');
if (pwdInput && pwdHint) {
    pwdInput.addEventListener('input', function () {
        const v = pwdInput.value;
        const ok = v.length >= 8 && /[A-Z]/.test(v) && /[0-9]/.test(v) && /[\W_]/.test(v);
        pwdHint.className = ok ? 'form-text text-success' : 'form-text text-danger';
        pwdHint.textContent = ok
            ? '✓ Mot de passe sécurisé'
            : '⚠ 8 car. min., une majuscule, un chiffre, un caractère spécial';
    });
}

// Confirmation de participation (modal)
const participerBtn = document.getElementById('btnParticiper');
if (participerBtn) {
    participerBtn.addEventListener('click', function (e) {
        const prix = this.dataset.prix;
        if (!confirm(`Confirmer la participation ? ${prix} crédit(s) seront débités de votre compte.`)) {
            e.preventDefault();
        }
    });
}

// Fermeture automatique des alertes après 4s
document.querySelectorAll('.alert-dismissible').forEach(alert => {
    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
    }, 4000);
});

// Filtres covoiturages : soumission automatique au changement
const filterForm = document.getElementById('filterForm');
if (filterForm) {
    filterForm.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('change', () => filterForm.submit());
    });
}

// Aperçu photo de profil
const photoInput = document.getElementById('photoInput');
const photoPreview = document.getElementById('photoPreview');
if (photoInput && photoPreview) {
    photoInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => { photoPreview.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });
}
