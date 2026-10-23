<?php

namespace App\Support;

/**
 * The simulation's random choices, drawn from PHP's Mersenne Twister so that
 * mt_srand() makes a run repeatable (tests and the recorded figures).
 *
 * Laravel 8's Arr::random used array_rand, which draws from that generator.
 * Laravel 13's Arr::random uses Random\Randomizer, which mt_srand() can't
 * seed, so the engine uses this instead. pick() draws exactly what Laravel 8's
 * Arr::random did for a list.
 */
final class SimRandom
{
    /**
     * @template T
     *
     * @param  list<T>  $options
     * @return T
     */
    public static function pick(array $options): mixed
    {
        $options = array_values($options);

        return $options[mt_rand(0, count($options) - 1)];
    }
}
