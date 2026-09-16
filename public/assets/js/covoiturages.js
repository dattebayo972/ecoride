/* EcoRide — Filtrage dynamique des covoiturages (US4)
 * Interroge api/search.php en fetch() et remplace la liste de résultats
 * dans le DOM, sans rechargement complet de la page. */

(function () {
    const filterForm  = document.getElementById('filterForm');
    const resultsWrap = document.getElementById('resultsWrap');
    if (!filterForm || !resultsWrap) return;

    const apiUrl = filterForm.dataset.apiUrl;
    const detailUrl = filterForm.dataset.detailUrl;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function ecoLabel(energie) {
        return energie === 'electrique'
            ? '<span class="badge bg-success"><i class="bi bi-lightning-charge-fill"></i> Écologique</span>'
            : '<span class="badge bg-secondary">Non écologique</span>';
    }

    function starRating(note) {
        const full = Math.round(note || 0);
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += i <= full
                ? '<i class="bi bi-star-fill text-warning"></i>'
                : '<i class="bi bi-star text-warning"></i>';
        }
        return html;
    }

    function cardHtml(c) {
        const note = c.note_moy !== null ? c.note_moy.toFixed(1) : 'N/A';
        return `
        <div class="card card-cov mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="${escapeHtml(c.avatar_url)}" alt="Photo" class="avatar me-3">
                    <div>
                        <strong>${escapeHtml(c.pseudo)}</strong>
                        <div class="stars small">
                            ${starRating(c.note_moy)}
                            <span class="text-muted ms-1">${note}</span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    ${ecoLabel(c.energie)}
                    <div class="mt-1">
                        <span class="fw-bold text-eco fs-5">${c.prix_personne}</span>
                        <small class="text-muted"> crédits</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="d-flex align-items-center">
                            <div class="text-center me-3">
                                <div class="fw-bold">${escapeHtml(c.heure_depart)}</div>
                                <small class="text-muted">${escapeHtml(c.lieu_depart)}</small>
                            </div>
                            <div class="flex-grow-1 text-center">
                                <hr class="my-0 border-2 border-eco">
                                <small class="text-muted">${escapeHtml(c.duree)}</small>
                            </div>
                            <div class="text-center ms-3">
                                <div class="fw-bold">${escapeHtml(c.heure_arrivee)}</div>
                                <small class="text-muted">${escapeHtml(c.lieu_arrivee)}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <span class="badge bg-eco-light text-eco fs-6">
                            <i class="bi bi-people-fill me-1"></i>
                            ${c.nb_place} place(s) disponible(s)
                        </span>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="${detailUrl}?id=${c.covoiturage_id}" class="btn btn-eco px-4">
                            Détail <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function emptyHtml(data, params) {
        let suggestionHtml = '';
        if (data.suggestion) {
            const p = new URLSearchParams(params);
            p.set('date', data.suggestion);
            const d = new Date(data.suggestion);
            const formatted = d.toLocaleDateString('fr-FR');
            suggestionHtml = `Prochain trajet disponible le
                <a href="?${p.toString()}"><strong>${formatted}</strong></a> — cliquez pour afficher.`;
        }
        return `
        <div class="alert alert-warning d-flex align-items-center">
            <i class="bi bi-info-circle-fill me-3 fs-4"></i>
            <div>Aucun covoiturage disponible pour cette date. ${suggestionHtml}</div>
        </div>`;
    }

    function render(data, params) {
        const badge = document.getElementById('resultsCount');
        if (badge) badge.textContent = data.count + ' trajet(s)';

        resultsWrap.innerHTML = data.count === 0
            ? emptyHtml(data, params)
            : data.results.map(cardHtml).join('');
    }

    function applyFilters() {
        const params = new URLSearchParams(new FormData(filterForm));
        resultsWrap.setAttribute('aria-busy', 'true');

        fetch(apiUrl + '?' + params.toString())
            .then(r => r.json())
            .then(data => render(data, params))
            .catch(() => {
                resultsWrap.innerHTML = '<div class="alert alert-danger">Impossible de charger les résultats. Réessayez.</div>';
            })
            .finally(() => resultsWrap.removeAttribute('aria-busy'));
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        applyFilters();
    });

    filterForm.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('change', () => applyFilters());
    });
})();
