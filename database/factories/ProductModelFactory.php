<?php

use Faker\Generator as Faker;

$factory->define(App\Models\ProductModel::class, function (Faker $faker) {
    return [
        'name'        => $faker->name,
        'description' => $faker->sentence(6),
        'published'   => true,
        'brand_id'    => function () {
            return factory(App\Models\Brand::class)->create()->id;
        }
    ];
});
