<?php

require __DIR__.'/../core/db.php';

$args = getopt('', ['keywords:']);
$numKeywords = $args['keywords'] ?? 10;

$pdo = conn();

// Insert default keywords if none exist
$stmt = $pdo->prepare('SELECT COUNT(*) FROM keywords');
$stmt->execute();
$count = $stmt->fetch()['COUNT(*)'];
if ($count === 0) {
    $defaultKeywords = [
        'php development',
        'sqlite database',
        'php programming',
        'web development',
        'object oriented programming',
        'api design',
        'unit testing',
        'performance optimization',
        'code review',
        'software architecture',
    ];
    foreach ($defaultKeywords as $phrase) {
        $pdo->prepare('INSERT INTO keywords (phrase) VALUES (?)')->execute([$phrase]);
    }
}

// Get all keywords
$keywords = $pdo->query('SELECT id, phrase FROM keywords')->fetchAll();

// Generate 30 days of position data for each keyword
$today = new DateTime();
$date = clone $today;
$date->modify('-29 days');

foreach ($keywords as $kw) {
    $keywordId = $kw['id'];
    for ($i = 0; $i < 30; $i++) {
        $date->modify('+1 day');
        $dateStr = $date->format('Y-m-d');
        $position = random_int(1, 100);
        $pdo->prepare(
            'INSERT INTO positions (keyword_id, date, position) VALUES (?, ?, ?) ON CONFLICT(keyword_id, date) DO NOTHING'
        )->execute([$keywordId, $dateStr, $position]);
    }
}

echo "Seed completed: $numKeywords keywords, 30 days positions each.\n";