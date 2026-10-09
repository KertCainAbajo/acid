<?php

namespace App\Services;

use App\Models\FoodLog;
use App\Models\SleepLog;
use App\Models\SymptomLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Explainable pattern analysis over a patient's food, symptom and sleep logs.
 *
 * Every finding carries the evidence behind it, so the patient (and their clinician)
 * can see why a food is flagged or why the risk score is what it is.
 */
class PatternAnalysisService
{
    /** Symptoms within this many hours after a meal are linked to that meal. */
    public const MEAL_WINDOW_HOURS = 6;

    /** How far back trigger, timing and sleep analysis looks. */
    public const ANALYSIS_DAYS = 30;

    /** Meals at or after this local hour (or before 4 AM) count as late meals. */
    private const LATE_MEAL_HOUR = 20;

    private const CONFIDENCE_RANK = ['strong' => 3, 'likely' => 2, 'possible' => 1, 'clear' => 0];

    /**
     * @return array{
     *     generatedAt: CarbonImmutable,
     *     risk: array{score: int, level: string, factors: list<array{key: string, label: string, points: int, detail: string}>},
     *     week: array{symptoms: int, averageIntensity: float, foods: int, sleepEntries: int, averageSleep: ?float, averageStress: ?float},
     *     trend: array{direction: string, symptomChange: int, intensityChange: float, previousSymptoms: int, previousAverageIntensity: float},
     *     foods: list<array{name: string, category: string, eaten: int, followed: int, rate: float, averageIntensity: ?float, confidence: string, eatenThisWeek: bool}>,
     *     baselineRate: ?float,
     *     categories: list<array{category: string, eaten: int, followed: int, rate: float, lift: ?float}>,
     *     timeOfDay: array{buckets: array<string, int>, peak: ?string},
     *     lateMeals: array{late: int, lateFollowed: int, lateRate: ?float, earlier: int, earlierRate: ?float, isPattern: bool},
     *     sleepLink: ?array{shortNights: int, otherNights: int, shortAverage: float, otherAverage: float},
     *     stressLink: ?array{highDays: int, otherDays: int, highAverage: float, otherAverage: float},
     *     dataQuality: array{activeDays: int, level: string, tips: list<string>},
     *     recommendations: list<array{key: string, title: string, detail: string, tone: string}>,
     *     headline: string,
     *     summary: string,
     * }
     */
    public function analyze(User $user, ?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::instance($now ?? now());
        $timezone = (string) config('app.patient_timezone', config('app.timezone'));
        $analysisStart = $now->subDays(self::ANALYSIS_DAYS);
        $weekStart = $now->subDays(7);
        $previousWeekStart = $now->subDays(14);

        $foods = $user->foodLogs()->whereBetween('time', [$analysisStart, $now])->orderBy('time')->get()->toBase();
        $symptoms = $user->symptomLogs()->whereBetween('time', [$analysisStart, $now])->orderBy('time')->get()->toBase();
        $sleepLogs = $user->sleepLogs()->whereDate('logged_on', '>=', $analysisStart->toDateString())->get()->toBase();

        $weekSymptoms = $symptoms->filter(fn (SymptomLog $log): bool => $log->time->greaterThan($weekStart));
        $previousSymptoms = $symptoms->filter(fn (SymptomLog $log): bool => $log->time->greaterThan($previousWeekStart) && $log->time->lessThanOrEqualTo($weekStart));
        $weekFoods = $foods->filter(fn (FoodLog $log): bool => $log->time->greaterThan($weekStart));
        $weekSleep = $sleepLogs->filter(fn (SleepLog $log): bool => $log->logged_on->greaterThanOrEqualTo($weekStart->startOfDay()));
        $analysisSymptoms = $symptoms->filter(fn (SymptomLog $log): bool => $log->time->greaterThanOrEqualTo($analysisStart));

        $week = [
            'symptoms' => $weekSymptoms->count(),
            'averageIntensity' => $this->average($weekSymptoms, 'intensity') ?? 0.0,
            'foods' => $weekFoods->count(),
            'sleepEntries' => $weekSleep->count(),
            'averageSleep' => $this->average($weekSleep, 'sleep_hours'),
            'averageStress' => $this->average($weekSleep, 'stress_level'),
        ];

        $mealLinks = $foods->map(fn (FoodLog $meal): array => [
            'meal' => $meal,
            'symptoms' => $analysisSymptoms->filter(fn (SymptomLog $symptom): bool => $symptom->time->betweenIncluded($meal->time, $meal->time->copy()->addHours(self::MEAL_WINDOW_HOURS))),
        ]);

        $baselineRate = $mealLinks->isEmpty() ? null : round($mealLinks->filter(fn (array $link): bool => $link['symptoms']->isNotEmpty())->count() / $mealLinks->count(), 2);
        $foodStats = $this->foodStats($mealLinks, $weekStart);
        $trend = $this->trend($weekSymptoms, $previousSymptoms);
        $timeOfDay = $this->timeOfDay($analysisSymptoms, $timezone);
        $lateMeals = $this->lateMeals($mealLinks, $timezone);
        $sleepLink = $this->dailyLink($sleepLogs, $analysisSymptoms, $timezone, fn (SleepLog $log): bool => $log->sleep_hours < 6);
        $stressLink = $this->dailyLink($sleepLogs, $analysisSymptoms, $timezone, fn (SleepLog $log): bool => $log->stress_level >= 7);
        $dataQuality = $this->dataQuality($weekFoods, $weekSymptoms, $weekSleep, $timezone);
        $risk = $this->risk($week, $trend, $foodStats, $lateMeals, $weekFoods, $timezone);

        $analysis = [
            'generatedAt' => $now,
            'risk' => $risk,
            'week' => $week,
            'trend' => $trend,
            'foods' => $foodStats,
            'baselineRate' => $baselineRate,
            'categories' => $this->categoryStats($mealLinks, $baselineRate),
            'timeOfDay' => $timeOfDay,
            'lateMeals' => $lateMeals,
            'sleepLink' => $sleepLink !== null ? ['shortNights' => $sleepLink['matching'], 'otherNights' => $sleepLink['other'], 'shortAverage' => $sleepLink['matchingAverage'], 'otherAverage' => $sleepLink['otherAverage']] : null,
            'stressLink' => $stressLink !== null ? ['highDays' => $stressLink['matching'], 'otherDays' => $stressLink['other'], 'highAverage' => $stressLink['matchingAverage'], 'otherAverage' => $stressLink['otherAverage']] : null,
            'dataQuality' => $dataQuality,
        ];

        $analysis['recommendations'] = $this->recommendations($analysis);
        [$analysis['headline'], $analysis['summary']] = $this->narrative($analysis);

        return $analysis;
    }

    /**
     * @param  Collection<int, array{meal: FoodLog, symptoms: Collection<int, SymptomLog>}>  $mealLinks
     * @return list<array{name: string, category: string, eaten: int, followed: int, rate: float, averageIntensity: ?float, confidence: string, eatenThisWeek: bool}>
     */
    private function foodStats(Collection $mealLinks, CarbonImmutable $weekStart): array
    {
        return $mealLinks
            ->groupBy(fn (array $link): string => mb_strtolower(trim($link['meal']->food_name)))
            ->map(function (Collection $links) use ($weekStart): array {
                $followedLinks = $links->filter(fn (array $link): bool => $link['symptoms']->isNotEmpty());
                $eaten = $links->count();
                $followed = $followedLinks->count();
                $linkedSymptoms = $followedLinks->flatMap(fn (array $link): Collection => $link['symptoms'])->unique('id');
                $rate = round($followed / $eaten, 2);

                return [
                    'name' => $links->last()['meal']->food_name,
                    'category' => $links->countBy(fn (array $link): string => $link['meal']->category)->sortDesc()->keys()->first(),
                    'eaten' => $eaten,
                    'followed' => $followed,
                    'rate' => $rate,
                    'averageIntensity' => $this->average($linkedSymptoms, 'intensity'),
                    'confidence' => match (true) {
                        $followed >= 3 && $rate >= 0.6 => 'strong',
                        $followed >= 2 && $rate >= 0.5 => 'likely',
                        $followed >= 1 => 'possible',
                        default => 'clear',
                    },
                    'eatenThisWeek' => $links->contains(fn (array $link): bool => $link['meal']->time->greaterThan($weekStart)),
                ];
            })
            ->sort(fn (array $a, array $b): int => [self::CONFIDENCE_RANK[$b['confidence']], $b['rate'], $b['followed'], $b['eaten']]
                <=> [self::CONFIDENCE_RANK[$a['confidence']], $a['rate'], $a['followed'], $a['eaten']])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array{meal: FoodLog, symptoms: Collection<int, SymptomLog>}>  $mealLinks
     * @return list<array{category: string, eaten: int, followed: int, rate: float, lift: ?float}>
     */
    private function categoryStats(Collection $mealLinks, ?float $baselineRate): array
    {
        return $mealLinks
            ->groupBy(fn (array $link): string => $link['meal']->category)
            ->map(function (Collection $links, string $category) use ($baselineRate): array {
                $followed = $links->filter(fn (array $link): bool => $link['symptoms']->isNotEmpty())->count();
                $rate = round($followed / $links->count(), 2);

                return [
                    'category' => $category,
                    'eaten' => $links->count(),
                    'followed' => $followed,
                    'rate' => $rate,
                    'lift' => $baselineRate ? round($rate / $baselineRate, 2) : null,
                ];
            })
            ->sortByDesc(fn (array $stat): array => [$stat['rate'], $stat['eaten']])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, SymptomLog>  $current
     * @param  Collection<int, SymptomLog>  $previous
     * @return array{direction: string, symptomChange: int, intensityChange: float, previousSymptoms: int, previousAverageIntensity: float}
     */
    private function trend(Collection $current, Collection $previous): array
    {
        $symptomChange = $current->count() - $previous->count();
        $currentIntensity = $this->average($current, 'intensity') ?? 0.0;
        $previousIntensity = $this->average($previous, 'intensity') ?? 0.0;
        $intensityChange = $current->isNotEmpty() && $previous->isNotEmpty() ? round($currentIntensity - $previousIntensity, 1) : 0.0;

        $direction = match (true) {
            $current->isEmpty() && $previous->isEmpty() => 'quiet',
            $symptomChange >= 2 || ($symptomChange >= 1 && $intensityChange >= 1) || ($current->isNotEmpty() && $intensityChange >= 2) => 'worsening',
            $symptomChange <= -2 || ($symptomChange <= -1 && $intensityChange <= 0) || ($current->isNotEmpty() && $intensityChange <= -2) => 'improving',
            default => 'stable',
        };

        return [
            'direction' => $direction,
            'symptomChange' => $symptomChange,
            'intensityChange' => $intensityChange,
            'previousSymptoms' => $previous->count(),
            'previousAverageIntensity' => $previousIntensity,
        ];
    }

    /**
     * @param  Collection<int, SymptomLog>  $symptoms
     * @return array{buckets: array<string, int>, peak: ?string}
     */
    private function timeOfDay(Collection $symptoms, string $timezone): array
    {
        $buckets = ['morning' => 0, 'afternoon' => 0, 'evening' => 0, 'night' => 0];
        foreach ($symptoms as $symptom) {
            $buckets[$this->bucket((int) $symptom->time->copy()->setTimezone($timezone)->format('G'))]++;
        }

        $total = array_sum($buckets);
        $peak = $total >= 3 ? array_search(max($buckets), $buckets, true) : null;

        return ['buckets' => $buckets, 'peak' => $peak !== null && $buckets[$peak] / $total >= 0.4 ? $peak : null];
    }

    private function bucket(int $hour): string
    {
        return match (true) {
            $hour >= 5 && $hour < 11 => 'morning',
            $hour >= 11 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 22 => 'evening',
            default => 'night',
        };
    }

    /**
     * @param  Collection<int, array{meal: FoodLog, symptoms: Collection<int, SymptomLog>}>  $mealLinks
     * @return array{late: int, lateFollowed: int, lateRate: ?float, earlier: int, earlierRate: ?float, isPattern: bool}
     */
    private function lateMeals(Collection $mealLinks, string $timezone): array
    {
        [$late, $earlier] = $mealLinks->partition(fn (array $link): bool => $this->isLateMeal($link['meal']->time, $timezone));
        $lateFollowed = $late->filter(fn (array $link): bool => $link['symptoms']->isNotEmpty())->count();
        $lateRate = $late->isEmpty() ? null : round($lateFollowed / $late->count(), 2);
        $earlierRate = $earlier->isEmpty() ? null : round($earlier->filter(fn (array $link): bool => $link['symptoms']->isNotEmpty())->count() / $earlier->count(), 2);

        return [
            'late' => $late->count(),
            'lateFollowed' => $lateFollowed,
            'lateRate' => $lateRate,
            'earlier' => $earlier->count(),
            'earlierRate' => $earlierRate,
            'isPattern' => $late->count() >= 2 && $lateFollowed >= 2 && $lateRate >= ($earlierRate ?? 0) + 0.2,
        ];
    }

    private function isLateMeal(CarbonInterface $time, string $timezone): bool
    {
        $hour = (int) $time->copy()->setTimezone($timezone)->format('G');

        return $hour >= self::LATE_MEAL_HOUR || $hour < 4;
    }

    /**
     * Compare symptoms per day on days matching a sleep condition against the other logged days.
     *
     * @param  Collection<int, SleepLog>  $sleepLogs
     * @param  Collection<int, SymptomLog>  $symptoms
     * @param  callable(SleepLog): bool  $condition
     * @return ?array{matching: int, other: int, matchingAverage: float, otherAverage: float}
     */
    private function dailyLink(Collection $sleepLogs, Collection $symptoms, string $timezone, callable $condition): ?array
    {
        $symptomsPerDay = $symptoms->countBy(fn (SymptomLog $log): string => $log->time->copy()->setTimezone($timezone)->toDateString());
        [$matching, $other] = $sleepLogs->partition($condition);

        if ($matching->count() < 2 || $other->count() < 2) {
            return null;
        }

        $averageFor = fn (Collection $days): float => round($days->avg(fn (SleepLog $log): int => $symptomsPerDay->get($log->logged_on->toDateString(), 0)), 1);
        $matchingAverage = $averageFor($matching);
        $otherAverage = $averageFor($other);

        if ($matchingAverage - $otherAverage < 0.5 || $matchingAverage < $otherAverage * 1.5) {
            return null;
        }

        return ['matching' => $matching->count(), 'other' => $other->count(), 'matchingAverage' => $matchingAverage, 'otherAverage' => $otherAverage];
    }

    /**
     * @return array{activeDays: int, level: string, tips: list<string>}
     */
    private function dataQuality(Collection $weekFoods, Collection $weekSymptoms, Collection $weekSleep, string $timezone): array
    {
        $activeDays = $weekFoods->map(fn (FoodLog $log): string => $log->time->copy()->setTimezone($timezone)->toDateString())
            ->concat($weekSymptoms->map(fn (SymptomLog $log): string => $log->time->copy()->setTimezone($timezone)->toDateString()))
            ->concat($weekSleep->map(fn (SleepLog $log): string => $log->logged_on->toDateString()))
            ->unique()
            ->count();

        $tips = [];
        if ($weekFoods->count() < 7) {
            $tips[] = 'Log at least one meal a day so symptoms can be matched to food.';
        }
        if ($weekSleep->count() < 3) {
            $tips[] = 'Add sleep and stress check-ins to unlock the sleep and stress analysis.';
        }
        if ($activeDays < 5) {
            $tips[] = 'Logging on more days of the week makes every pattern more reliable.';
        }

        return [
            'activeDays' => min(7, $activeDays),
            'level' => $activeDays >= 5 ? 'high' : ($activeDays >= 3 ? 'medium' : 'low'),
            'tips' => $tips,
        ];
    }

    /**
     * Build an explainable 0-100 risk score from the last 7 days.
     *
     * @param  array{symptoms: int, averageIntensity: float, foods: int, sleepEntries: int, averageSleep: ?float, averageStress: ?float}  $week
     * @param  array{direction: string}  $trend
     * @param  list<array{name: string, confidence: string, eatenThisWeek: bool}>  $foodStats
     * @param  array{isPattern: bool}  $lateMeals
     * @param  Collection<int, FoodLog>  $weekFoods
     * @return array{score: int, level: string, factors: list<array{key: string, label: string, points: int, detail: string}>}
     */
    private function risk(array $week, array $trend, array $foodStats, array $lateMeals, Collection $weekFoods, string $timezone): array
    {
        $factors = [];
        $add = function (string $key, string $label, int $points, string $detail) use (&$factors): void {
            if ($points > 0) {
                $factors[] = ['key' => $key, 'label' => $label, 'points' => $points, 'detail' => $detail];
            }
        };

        $add('frequency', 'Symptom frequency', min(30, $week['symptoms'] * 5), sprintf('%d symptom%s in the last 7 days', $week['symptoms'], $week['symptoms'] === 1 ? '' : 's'));
        $add('severity', 'Symptom severity', match (true) {
            $week['averageIntensity'] >= 7 => 15,
            $week['averageIntensity'] >= 5 => 8,
            $week['averageIntensity'] >= 3 => 3,
            default => 0,
        }, sprintf('Average intensity %s/10', $week['averageIntensity']));

        $triggerPoints = ['strong' => 10, 'likely' => 7, 'possible' => 5];
        $triggersThisWeek = collect($foodStats)->filter(fn (array $food): bool => $food['eatenThisWeek'] && isset($triggerPoints[$food['confidence']]));
        $add('triggers', 'Suspected trigger foods eaten', min(20, $triggersThisWeek->sum(fn (array $food): int => $triggerPoints[$food['confidence']])), $triggersThisWeek->pluck('name')->take(3)->implode(', '));

        $lateThisWeek = $weekFoods->filter(fn (FoodLog $meal): bool => $this->isLateMeal($meal->time, $timezone))->count();
        $add('late-meals', 'Late meals', $lateMeals['isPattern'] && $lateThisWeek > 0 ? 5 : 0, sprintf('%d late meal%s this week, and late meals tend to be followed by symptoms', $lateThisWeek, $lateThisWeek === 1 ? '' : 's'));

        $sleep = $week['averageSleep'];
        $add('sleep', 'Short sleep', $sleep === null ? 0 : ($sleep < 6 ? 15 : ($sleep < 7 ? 7 : 0)), sprintf('Averaging %s hours a night', $sleep));
        $stress = $week['averageStress'];
        $add('stress', 'High stress', $stress === null ? 0 : ($stress >= 8 ? 10 : ($stress >= 6 ? 5 : 0)), sprintf('Average stress %s/10', $stress));
        $add('trend', 'Worsening trend', $trend['direction'] === 'worsening' ? 5 : 0, 'More or stronger symptoms than the week before');

        $score = min(100, array_sum(array_column($factors, 'points')));
        usort($factors, fn (array $a, array $b): int => $b['points'] <=> $a['points']);

        return ['score' => $score, 'level' => $score >= 60 ? 'high' : ($score >= 25 ? 'medium' : 'low'), 'factors' => $factors];
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return list<array{key: string, title: string, detail: string, tone: string}>
     */
    private function recommendations(array $analysis): array
    {
        $recommendations = [];
        $percent = fn (?float $rate): string => (int) round(($rate ?? 0) * 100).'%';

        foreach (array_slice(array_filter($analysis['foods'], fn (array $food): bool => in_array($food['confidence'], ['strong', 'likely'], true)), 0, 3) as $food) {
            $recommendations[] = [
                'key' => 'trigger',
                'title' => "Try a 2-week break from {$food['name']}",
                'detail' => sprintf('%d of the %d times you had it, symptoms followed within %d hours%s.', $food['followed'], $food['eaten'], self::MEAL_WINDOW_HOURS, $food['averageIntensity'] ? sprintf(' (average %s/10)', $food['averageIntensity']) : ''),
                'tone' => 'red',
            ];
        }

        foreach ($analysis['categories'] as $category) {
            if ($category['eaten'] >= 3 && $category['lift'] !== null && $category['lift'] >= 1.5 && $category['category'] !== 'other') {
                $recommendations[] = [
                    'key' => 'category',
                    'title' => 'Go easy on '.$category['category'].' foods',
                    'detail' => sprintf('Symptoms followed %s of your %s meals, compared with %s of meals overall.', $percent($category['rate']), $category['category'], $percent($analysis['baselineRate'])),
                    'tone' => 'orange',
                ];
                break;
            }
        }

        if ($analysis['lateMeals']['isPattern']) {
            $recommendations[] = [
                'key' => 'late-meals',
                'title' => 'Finish dinner at least 3 hours before bed',
                'detail' => sprintf('Symptoms followed %s of your late meals (after %d PM) but only %s of earlier meals.', $percent($analysis['lateMeals']['lateRate']), self::LATE_MEAL_HOUR - 12, $percent($analysis['lateMeals']['earlierRate'])),
                'tone' => 'violet',
            ];
        }

        if (in_array($analysis['timeOfDay']['peak'], ['evening', 'night'], true)) {
            $recommendations[] = [
                'key' => 'timing',
                'title' => 'Your symptoms cluster in the '.$analysis['timeOfDay']['peak'],
                'detail' => 'Try a lighter evening meal, avoid lying down soon after eating, and consider raising the head of your bed.',
                'tone' => 'violet',
            ];
        }

        if ($analysis['sleepLink'] !== null) {
            $recommendations[] = [
                'key' => 'sleep',
                'title' => 'Protect your sleep',
                'detail' => sprintf('After nights under 6 hours you logged %s symptoms a day, compared with %s on other days.', $analysis['sleepLink']['shortAverage'], $analysis['sleepLink']['otherAverage']),
                'tone' => 'sky',
            ];
        }

        if ($analysis['stressLink'] !== null) {
            $recommendations[] = [
                'key' => 'stress',
                'title' => 'Plan ahead for high-stress days',
                'detail' => sprintf('On days with stress 7/10 or higher you logged %s symptoms, compared with %s on calmer days. Smaller meals and short breaks may help.', $analysis['stressLink']['highAverage'], $analysis['stressLink']['otherAverage']),
                'tone' => 'amber',
            ];
        }

        $wellTolerated = array_values(array_filter($analysis['foods'], fn (array $food): bool => $food['confidence'] === 'clear' && $food['eaten'] >= 2));
        if ($wellTolerated !== []) {
            $recommendations[] = [
                'key' => 'safe-foods',
                'title' => 'Lean on foods that suit you',
                'detail' => collect($wellTolerated)->take(4)->pluck('name')->join(', ', ' and ').(count($wellTolerated) === 1 ? ' was' : ' were').' eaten more than once with no symptoms afterwards.',
                'tone' => 'green',
            ];
        }

        if ($analysis['dataQuality']['level'] !== 'high') {
            $recommendations[] = [
                'key' => 'data',
                'title' => 'Log a little every day',
                'detail' => $analysis['dataQuality']['tips'][0] ?? 'More complete logs make your patterns more reliable.',
                'tone' => 'green',
            ];
        }

        if ($recommendations === [] || ($analysis['week']['symptoms'] === 0 && $analysis['dataQuality']['level'] === 'high')) {
            $recommendations[] = [
                'key' => 'keep-going',
                'title' => 'Keep doing what you are doing',
                'detail' => 'No clear trigger stands out right now. Keep logging so any change is caught early.',
                'tone' => 'green',
            ];
        }

        return $recommendations;
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return array{0: string, 1: string}
     */
    private function narrative(array $analysis): array
    {
        $week = $analysis['week'];
        $trend = $analysis['trend'];
        $level = $analysis['risk']['level'];

        $headline = match (true) {
            $trend['direction'] === 'worsening' => 'Your symptoms are picking up this week.',
            $level === 'high' => 'Your recent pattern deserves attention.',
            $level === 'medium' => 'Some patterns need a closer look.',
            $trend['direction'] === 'improving' => 'Your symptoms are easing. Nice work.',
            default => 'Your recent pattern looks stable.',
        };

        $sentences = [sprintf(
            'In the last 7 days you logged %d symptom%s%s and %d meal%s.',
            $week['symptoms'],
            $week['symptoms'] === 1 ? '' : 's',
            $week['symptoms'] > 0 ? sprintf(' (average intensity %s/10)', $week['averageIntensity']) : '',
            $week['foods'],
            $week['foods'] === 1 ? '' : 's',
        )];

        $sentences[] = match ($trend['direction']) {
            'worsening' => sprintf('That is %s than the week before (%d symptom%s).', $trend['symptomChange'] > 0 ? 'more' : 'stronger', $trend['previousSymptoms'], $trend['previousSymptoms'] === 1 ? '' : 's'),
            'improving' => sprintf('That is an improvement on the week before (%d symptom%s).', $trend['previousSymptoms'], $trend['previousSymptoms'] === 1 ? '' : 's'),
            'quiet' => 'No symptoms were logged in the last two weeks.',
            default => 'That is about the same as the week before.',
        };

        $topTrigger = collect($analysis['foods'])->first(fn (array $food): bool => $food['confidence'] !== 'clear');
        $sentences[] = $topTrigger
            ? sprintf('Your strongest food link is %s: symptoms followed %d of %d times you had it.', $topTrigger['name'], $topTrigger['followed'], $topTrigger['eaten'])
            : 'No food has been linked to your symptoms in the last 30 days.';

        if ($analysis['lateMeals']['isPattern']) {
            $sentences[] = 'Late meals are followed by symptoms more often than earlier ones.';
        } elseif ($analysis['timeOfDay']['peak'] !== null) {
            $sentences[] = 'Most of your symptoms happen in the '.$analysis['timeOfDay']['peak'].'.';
        }

        if ($analysis['sleepLink'] !== null) {
            $sentences[] = 'Short nights are followed by more symptoms.';
        } elseif ($week['averageSleep'] === null) {
            $sentences[] = 'Add sleep and stress entries to improve this estimate.';
        }

        $sentences[] = sprintf('Risk is %s (%d/100). This is a tracking aid, not a medical diagnosis.', $level, $analysis['risk']['score']);

        return [$headline, implode(' ', $sentences)];
    }

    /**
     * @param  Collection<int, mixed>  $items
     */
    private function average(Collection $items, string $column): ?float
    {
        return $items->isEmpty() ? null : round((float) $items->avg($column), 1);
    }
}
