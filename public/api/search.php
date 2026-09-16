<?php
// API AJAX — recherche/filtrage dynamique des covoiturages (US4)
// Appelée en fetch() par assets/js/covoiturages.js, sans rechargement de page.
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../src/helpers/functions.php';
require_once __DIR__ . '/../../src/models/CovoiturageModel.php';

header('Content-Type: application/json; charset=utf-8');

$depart  = trim($_GET['depart']  ?? '');
$arrivee = trim($_GET['arrivee'] ?? '');
$date    = $_GET['date'] ?? date('Y-m-d');

$filters = [
    'ecologique' => !empty($_GET['ecologique']),
    'prix_max'   => !empty($_GET['prix_max'])  ? (float) $_GET['prix_max']  : null,
    'duree_max'  => !empty($_GET['duree_max']) ? (int) $_GET['duree_max']   : null,
    'note_min'   => !empty($_GET['note_min'])  ? (float) $_GET['note_min']  : null,
];

if ($depart === '' || $arrivee === '') {
    echo json_encode(['searched' => false, 'count' => 0, 'results' => []]);
    exit;
}

$rows = CovoiturageModel::search($depart, $arrivee, $date, $filters);

$results = array_map(function (array $c): array {
    $debut = new DateTime($c['date_depart'] . ' ' . $c['heure_depart']);
    $fin   = new DateTime($c['date_arrivee'] . ' ' . $c['heure_arrivee']);
    $diff  = $debut->diff($fin);

    return [
        'covoiturage_id' => (int) $c['covoiturage_id'],
        'pseudo'         => $c['pseudo'],
        'avatar_url'     => avatarUrl($c['photo']),
        'note_moy'       => $c['note_moy'] !== null ? round((float) $c['note_moy'], 1) : null,
        'energie'        => $c['energie'],
        'prix_personne'  => $c['prix_personne'],
        'heure_depart'   => formatTime($c['heure_depart']),
        'heure_arrivee'  => formatTime($c['heure_arrivee']),
        'lieu_depart'    => $c['lieu_depart'],
        'lieu_arrivee'   => $c['lieu_arrivee'],
        'nb_place'       => (int) $c['nb_place'],
        'duree'          => $diff->h . 'h' . ($diff->i ? $diff->i . 'm' : ''),
    ];
}, $rows);

$suggestion = null;
if (empty($results)) {
    $next = CovoiturageModel::nextAvailable($depart, $arrivee, $date);
    if ($next) {
        $suggestion = $next['prochaine'];
    }
}

echo json_encode([
    'searched'   => true,
    'count'      => count($results),
    'results'    => $results,
    'suggestion' => $suggestion,
]);
