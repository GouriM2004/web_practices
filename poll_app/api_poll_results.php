<?php
// API endpoint for live poll results
header('Content-Type: application/json');
require_once __DIR__ . '/includes/bootstrap.php';

$poll_id = (int)($_GET['poll_id'] ?? 0);
if (!$poll_id) {
    echo json_encode(['error' => 'Invalid poll ID']);
    exit;
}

$pollModel = new Poll();
$poll = $pollModel->getPollById($poll_id);
if (!$poll) {
    echo json_encode(['error' => 'Poll not found']);
    exit;
}

$options = $pollModel->getOptions($poll_id);
$publicVoters = $pollModel->getPublicVoters($poll_id);
$geoBreakdown = $pollModel->getGeographicalBreakdown($poll_id);
$confidenceStats = $pollModel->getConfidenceStats($poll_id);
$layeredResults = $pollModel->getVoterTypeLayeredResults($poll_id);

$totalVotes = 0;
foreach ($options as $opt) {
    $totalVotes += $opt['votes'];
}

$data = [
    'poll' => [
        'id' => $poll['id'],
        'question' => $poll['question'],
        'allow_multiple' => (bool)$poll['allow_multiple'],
        'total_votes' => $totalVotes
    ],
    'options' => [],
    'public_voters' => [],
    'geographical' => [],
    'confidence' => [
        'overall' => [],
        'by_option' => []
    ],
    'segments' => [
        'weights' => $layeredResults['weights'] ?? [],
        'totals' => $layeredResults['totals'] ?? [],
        'options' => $layeredResults['options'] ?? []
    ]
];

foreach ($options as $opt) {
    $percent = $totalVotes ? round(($opt['votes'] / $totalVotes) * 100, 1) : 0;
    $data['options'][] = [
        'id' => $opt['id'],
        'text' => $opt['option_text'],
        'votes' => (int)$opt['votes'],
        'percentage' => $percent
    ];
}

foreach ($publicVoters as $v) {
    $data['public_voters'][] = $v['voter_name'];
}

// Group geographical data by location
$geoMap = [];
foreach ($geoBreakdown as $row) {
    $loc = $row['location'];
    if (!isset($geoMap[$loc])) {
        $geoMap[$loc] = [];
    }
    $geoMap[$loc][] = [
        'option' => $row['option_text'],
        'votes' => (int)$row['vote_count']
    ];
}

foreach ($geoMap as $location => $votes) {
    $data['geographical'][] = [
        'location' => $location,
        'votes' => $votes
    ];
}

// Add confidence statistics
foreach ($confidenceStats['overall'] as $stat) {
    $data['confidence']['overall'][] = [
        'level' => $stat['confidence_level'],
        'count' => (int)$stat['count'],
        'percentage' => (float)$stat['percentage']
    ];
}

$data['confidence']['by_option'] = $confidenceStats['by_option'];

echo json_encode($data, JSON_PRETTY_PRINT);
