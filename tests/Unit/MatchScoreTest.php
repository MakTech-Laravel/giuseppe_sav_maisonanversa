<?php

use App\Enums\SessionMatchFormat;
use App\Support\MatchScore;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @return array{a: int, b: int, tiebreak_a: int|null, tiebreak_b: int|null}
 */
function matchSet(int $a, int $b, ?int $tiebreakA = null, ?int $tiebreakB = null): array
{
    return ['a' => $a, 'b' => $b, 'tiebreak_a' => $tiebreakA, 'tiebreak_b' => $tiebreakB];
}

test('valid set scores are accepted', function (SessionMatchFormat $format, int $index, array $set) {
    expect(MatchScore::setError($format, $index, $set))->toBeNull();
})->with([
    '6-0' => [SessionMatchFormat::BestOfThree, 0, matchSet(6, 0)],
    '6-4' => [SessionMatchFormat::BestOfThree, 0, matchSet(6, 4)],
    '4-6' => [SessionMatchFormat::BestOfThree, 0, matchSet(4, 6)],
    '7-5' => [SessionMatchFormat::BestOfThree, 0, matchSet(7, 5)],
    '7-6 without tie-break points' => [SessionMatchFormat::BestOfThree, 0, matchSet(7, 6)],
    '7-6(5)' => [SessionMatchFormat::BestOfThree, 0, matchSet(7, 6, 7, 5)],
    '6-7(10)' => [SessionMatchFormat::BestOfThree, 0, matchSet(6, 7, 10, 12)],
    'short set 4-2' => [SessionMatchFormat::ShortSets, 0, matchSet(4, 2)],
    'short set 5-4(3)' => [SessionMatchFormat::ShortSets, 0, matchSet(5, 4, 7, 3)],
    'super tie-break 10-8' => [SessionMatchFormat::SuperTiebreak, 2, matchSet(10, 8)],
    'super tie-break 12-10' => [SessionMatchFormat::SuperTiebreak, 2, matchSet(12, 10)],
    'super tie-break 7-10' => [SessionMatchFormat::SuperTiebreak, 2, matchSet(7, 10)],
]);

test('impossible set scores are rejected', function (SessionMatchFormat $format, int $index, array $set, string $message) {
    expect(MatchScore::setError($format, $index, $set))->toBe($message);
})->with([
    '6-5' => [SessionMatchFormat::BestOfThree, 0, matchSet(6, 5), 'Not a valid set score.'],
    '6-6' => [SessionMatchFormat::BestOfThree, 0, matchSet(6, 6), 'Not a valid set score.'],
    '8-6' => [SessionMatchFormat::BestOfThree, 0, matchSet(8, 6), 'Not a valid set score.'],
    '5-3' => [SessionMatchFormat::BestOfThree, 0, matchSet(5, 3), 'Not a valid set score.'],
    '0-0' => [SessionMatchFormat::BestOfThree, 0, matchSet(0, 0), 'Not a valid set score.'],
    'short set 6-4' => [SessionMatchFormat::ShortSets, 0, matchSet(6, 4), 'Not a valid set score.'],
    'tie-break on 6-4' => [SessionMatchFormat::BestOfThree, 0, matchSet(6, 4, 7, 5), 'Tie-break points only belong to a set decided by a tie-break.'],
    'tie-break 7-6' => [SessionMatchFormat::BestOfThree, 0, matchSet(7, 6, 7, 6), 'Not a valid tie-break.'],
    'tie-break 9-6' => [SessionMatchFormat::BestOfThree, 0, matchSet(7, 6, 9, 6), 'Not a valid tie-break.'],
    'tie-break won by the set loser' => [SessionMatchFormat::BestOfThree, 0, matchSet(7, 6, 5, 7), 'Not a valid tie-break.'],
    'one tie-break side only' => [SessionMatchFormat::BestOfThree, 0, matchSet(7, 6, 7), 'Not a valid tie-break.'],
    'super tie-break 10-9' => [SessionMatchFormat::SuperTiebreak, 2, matchSet(10, 9), 'Not a valid super tie-break (to 10, won by 2 points).'],
    'super tie-break 9-7' => [SessionMatchFormat::SuperTiebreak, 2, matchSet(9, 7), 'Not a valid super tie-break (to 10, won by 2 points).'],
    'super tie-break 13-10' => [SessionMatchFormat::SuperTiebreak, 2, matchSet(13, 10), 'Not a valid super tie-break (to 10, won by 2 points).'],
]);

test('only the third entry of the super tie-break format counts points', function () {
    expect(MatchScore::setError(SessionMatchFormat::SuperTiebreak, 0, matchSet(10, 8)))->toBe('Not a valid set score.')
        ->and(MatchScore::setError(SessionMatchFormat::BestOfThree, 2, matchSet(10, 8)))->toBe('Not a valid set score.');
});

test('a match must be decided and nothing may follow the deciding set', function (SessionMatchFormat $format, array $sets, ?string $message) {
    expect(MatchScore::matchError($format, $sets))->toBe($message);
})->with([
    'straight sets' => [SessionMatchFormat::BestOfThree, [matchSet(6, 4), matchSet(6, 3)], null],
    'three sets' => [SessionMatchFormat::BestOfThree, [matchSet(6, 4), matchSet(3, 6), matchSet(7, 5)], null],
    'super tie-break decides' => [SessionMatchFormat::SuperTiebreak, [matchSet(6, 4), matchSet(3, 6), matchSet(10, 7)], null],
    'one set' => [SessionMatchFormat::OneSet, [matchSet(7, 6, 7, 2)], null],
    'no sets' => [SessionMatchFormat::BestOfThree, [], 'Enter at least one set.'],
    'one set of three' => [SessionMatchFormat::BestOfThree, [matchSet(6, 4)], 'The match has not been decided yet.'],
    'one set all' => [SessionMatchFormat::BestOfThree, [matchSet(6, 4), matchSet(3, 6)], 'The match has not been decided yet.'],
    'set after the decider' => [SessionMatchFormat::BestOfThree, [matchSet(6, 4), matchSet(6, 3), matchSet(6, 2)], 'No set can follow the deciding set.'],
    'second set in one set format' => [SessionMatchFormat::OneSet, [matchSet(6, 4), matchSet(6, 3)], 'No set can follow the deciding set.'],
]);

test('errors are keyed per set before the match as a whole is checked', function () {
    expect(MatchScore::errors(SessionMatchFormat::BestOfThree, [matchSet(6, 5), matchSet(6, 3)]))->toBe(['sets.0' => 'Not a valid set score.'])
        ->and(MatchScore::errors(SessionMatchFormat::BestOfThree, [matchSet(6, 4)]))->toBe(['sets' => 'The match has not been decided yet.'])
        ->and(MatchScore::errors(SessionMatchFormat::BestOfThree, [matchSet(6, 4), matchSet(6, 3)]))->toBe([]);
});

test('the winner takes the most sets', function () {
    expect(MatchScore::winner(SessionMatchFormat::BestOfThree, [matchSet(6, 4), matchSet(3, 6), matchSet(7, 5)]))->toBe('a')
        ->and(MatchScore::winner(SessionMatchFormat::SuperTiebreak, [matchSet(6, 4), matchSet(3, 6), matchSet(8, 10)]))->toBe('b')
        ->and(MatchScore::winner(SessionMatchFormat::BestOfThree, [matchSet(6, 4), matchSet(3, 6)]))->toBeNull();
});

test('the score line uses tennis notation', function () {
    expect(MatchScore::line(SessionMatchFormat::SuperTiebreak, [matchSet(6, 4), matchSet(6, 7, 5, 7), matchSet(10, 7)]))->toBe('6-4, 6-7(5), [10-7]')
        ->and(MatchScore::line(SessionMatchFormat::BestOfThree, [matchSet(7, 6, 7, 3), matchSet(6, 2)]))->toBe('7-6(3), 6-2')
        ->and(MatchScore::line(SessionMatchFormat::ShortSets, [matchSet(4, 1), matchSet(5, 4)]))->toBe('4-1, 5-4');
});
