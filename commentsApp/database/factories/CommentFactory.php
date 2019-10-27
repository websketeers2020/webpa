<?php

use Faker\Generator as Faker;

$factory->define(App\Comment::class, function (Faker $faker) {
    return [
    'name' => $faker -> firstName . ' ' . $faker -> lastName,
    'comment' => $faker -> sentence (12, true),
    'likes' => $faker -> numberBetween (-20, 150),
    ];
});
