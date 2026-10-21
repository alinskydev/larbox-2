<?php

use App\Helpers\NumberHelper;
use Modules\Product\Models\Product;

/** @var Product $product */

$locale = app()->getLocale();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product->name ?></title>
</head>

<body>
    <h1><?= $product->name ?></h1>
    <ul>
        <li>ID: <?= $product->id ?></li>
        <li>Name: <?= $product->name ?></li>
        <li>Category: <?= $product->category->name[$locale] ?? '' ?></li>
        <li>Brand: <?= $product->brand->name ?></li>
        <li>Price: <?= $product->price ? NumberHelper::formatAmountWithCurrency($product->price->price) : '' ?></li>
    </ul>
</body>

</html>
