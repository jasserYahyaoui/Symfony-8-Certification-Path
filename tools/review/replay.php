<?php

declare(strict_types=1);

/*
 * Replays outcome sequences through the PHP ReviewScheduler, the reference
 * implementation, and prints every intermediate state as JSON. Read by
 * website/tools/verify-review.mjs, which runs the browser port over the same
 * sequences and fails on the first difference.
 *
 *   echo '[["GOOD","AGAIN"]]' | php tools/review/replay.php
 */

use CertPath\Review\ReviewOutcome;
use CertPath\Review\ReviewScheduler;
use CertPath\Review\ReviewState;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$sequences = json_decode((string) stream_get_contents(\STDIN), true, 512, \JSON_THROW_ON_ERROR);
$scheduler = new ReviewScheduler();
$out = [];
foreach ($sequences as $sequence) {
    $state = ReviewState::fresh();
    $states = [];
    foreach ($sequence as $outcome) {
        $state = $scheduler->next($state, ReviewOutcome::from($outcome));
        $states[] = [$state->intervalDays, $state->ease, $state->repetitions, $state->lapses, $state->dueInSession];
    }
    $out[] = $states;
}

echo json_encode($out, \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR);
