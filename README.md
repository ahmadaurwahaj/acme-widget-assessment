# Acme Widget Co Basket

A sales basket for Acme Widget Co. The backend is PHP and the frontend is React with TypeScript.

## How to run it

You only need Docker.

```bash
docker compose up
```

Website: http://localhost:5173
API: http://localhost:8000/api/v1/products

To run the tests and code checks:

```bash
docker compose run --rm composer check
docker compose run --rm web sh -c "npm ci && npm run check"
```

GitHub Actions runs the same checks on every push and pull request.

## How the basket works

The basket is created with the product catalogue, the delivery rules and the offers. It has an `add` method that takes a product code and a `total` method that returns the total.

```php
$basket = new Basket($productRepository, $deliveryRules, $offers);
$basket->add('R01');
$basket->add('R01');
$basket->total(); // 5437 cents, which is $54.37
```

The total is worked out in this order:

1. Add up the price of every item.
2. Take away the offer discount.
3. Add delivery based on what is left: under $50 costs $4.95, under $90 costs $2.95, $90 or more is free.

Prices, delivery rules and offers are all set in `backend/config/store.php`. Money is stored in cents as whole numbers so there are no rounding problems.

## Assumptions

* **Rounding.** Half of $32.95 is $16.475. The example "R01, R01 = $54.37" only works if the half price is rounded down to $16.47.
* **Delivery uses the price after the discount.** Two red widgets are $65.90 before the offer and $49.42 after it. The expected $54.37 only works with $4.95 delivery.
* **The offer works for every pair.** Four red widgets means two are half price.
* **An empty basket costs $0.** No delivery is charged.
* **Unknown product codes are rejected** instead of being skipped.
* The task only asks for `add`, so the page has Add and Clear buttons but you cannot remove one item.

## API

Base URL: `http://localhost:8000/api/v1`

| Method | Endpoint | Payload | Response |
| --- | --- | --- | --- |
| GET | `/products` | None | Array of products with `code`, `name` and `priceInCents` |
| GET | `/offers` | None | Array of active offers with a `description` |
| POST | `/basket/total` | `{"productCodes": ["R01", "R01"]}` | Basket `lines`, `subtotalInCents`, `discountInCents`, `deliveryInCents` and `totalInCents` |

Errors are returned as `{"error": "..."}` with one of these status codes: 400 invalid JSON, 404 unknown endpoint, 405 method not allowed, 422 invalid payload, 429 too many requests, 500 server error.

## Security

* The frontend only sends product codes. The backend looks up every price and does all the maths, so a fake price sent from Postman is ignored.
* Each product code must be 1 to 32 letters or numbers and a basket can have at most 100 items.
* Each client can make 25 requests per minute. After that the API returns 429.
* Errors and blocked requests are logged as JSON with Monolog. See them with `docker compose logs api`.

## Project layout

* `backend/src/Domain`: basket, products, delivery rules and offers
* `backend/src/Repository`: loads products (kept in memory for now)
* `backend/src/Service`: listing products and offers, and building a basket
* `backend/src/Http`: routes, controllers, request and response classes, rate limiter
* `frontend/src`: API calls, hooks for products and basket, components with SCSS

## Limitations

* Products are kept in memory. A database would need a new `ProductRepository` class.
* The rate limiter stores counters in files, so it works on one server only. More servers would need Redis.
* Locally, all browser requests go through the Vite dev server, so they share one rate limit.
* There are no user accounts and orders are not saved.
