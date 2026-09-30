<?php

declare(strict_types=1);

use Acme\Domain\Product;

$products = [
    new Product('R01', 'Red Widget', 3295),
    new Product('G01', 'Green Widget', 2495),
    new Product('B01', 'Blue Widget', 795),
];

return $products;
