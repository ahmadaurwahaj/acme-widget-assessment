# Acme Widget Co Basket

A sales basket for Acme Widget Co. The backend is PHP and the frontend is React with TypeScript.

![Basket page](docs/screenshot.jpg)

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

GitHub Actions runs the same checks on every push and pull request, on PHP 8.4 and 8.5.

### Without Docker

You need PHP 8.4 or newer, Composer and Node 24. Run these from the project folder in two separate terminals, one for the API and one for the website.

```bash
cd backend && composer install && php -S localhost:8000 -t public
```

```bash
cd frontend && npm ci && API_URL=http://localhost:8000 npm run dev
```

## How the basket works

The basket is created with the product catalogue, the delivery rules and the offers. It has an `add` method that takes a product code and a `total` method that returns the total.

```php
$basket = new Basket($productRepository, $deliveryRules, ...$offers);
$basket->add('R01');
$basket->add('R01');
$basket->total(); // 5437 cents, which is $54.37
```

The total is worked out in this order:

1. Add up the price of every item.
2. Take away the offer discount.
3. Add delivery based on what is left: under $50 costs $4.95, under $90 costs $2.95, $90 or more is free.

Products and prices are listed in `backend/config/products.php`. To add a product, add one line there. Delivery rules and offers are set in `backend/config/store.php`. Money is stored in cents as whole numbers so there are no rounding problems.

## Design decisions

* **The API is stateless.** There are no sessions and no saved baskets. The page keeps the list of product codes and sends the whole list every time it changes. The server builds a fresh basket from that list and prices it. This keeps the server simple, makes every request easy to test on its own, and means any number of servers could answer. The price is always worked out on the server, never trusted from the client.
* **No framework.** The task is one small domain with three endpoints. A small router, a few controllers and plain PHP classes show the design more clearly than a framework would. The domain classes do not depend on the HTTP layer, so they could be moved into Laravel or Symfony without changes.
* **The price is worked out once.** `Basket::priceBreakdown()` returns the subtotal, discount, delivery and total together, and the API uses that one result. `PriceBreakdown` checks that the numbers add up and are never negative.
* **API responses are checked in the browser.** TypeScript types disappear when the code runs, so each service checks the shape of the JSON before the page uses it. A wrong response shows a clear error instead of breaking the page somewhere else. The checks are small hand written functions because there are only three responses. With more endpoints or outside clients I would use a schema library such as Zod.

## Assumptions

* **Rounding.** Half of $32.95 is $16.475. The example "R01, R01 = $54.37" only works if the half price is rounded down to $16.47.
* **Delivery uses the price after the discount.** Two red widgets are $65.90 before the offer and $49.42 after it. The expected $54.37 only works with $4.95 delivery.
* **The offer works for every pair.** Four red widgets means two are half price.
* **An empty basket costs $0.** No delivery is charged.
* **Unknown product codes are rejected** instead of being skipped.
* **Product codes are case sensitive.** `R01` is a red widget, `r01` is an unknown product.
* **Offers stack.** If two offers apply to the same basket, both discounts are added up. The total discount can never be more than the subtotal, so the total never goes below the delivery charge.
* The task only asks for `add`. The page also has plus and minus buttons, which just send a shorter or longer list of codes.

## API

Base URL: `http://localhost:8000/api/v1`

| Method | Endpoint | Payload | Response |
| --- | --- | --- | --- |
| GET | `/products` | None | Array of products with `code`, `name` and `priceInCents` |
| GET | `/offers` | None | Array of active offers with a `code` and a `description` |
| POST | `/basket/total` | `{"productCodes": ["R01", "R01"]}` | Basket `lines`, `subtotalInCents`, `discountInCents`, `deliveryInCents` and `totalInCents` |

Errors are returned as `{"error": "..."}` with one of these status codes: 400 invalid JSON, 404 unknown endpoint, 405 method not allowed, 415 body is not JSON, 422 invalid payload, 429 too many requests, 500 server error.

## Security

* The frontend only sends product codes. The backend looks up every price and does all the maths, so a fake price sent from Postman is ignored.
* Each product code must be 1 to 32 letters or numbers and a basket can have at most 100 items. The page keeps its own copy of the 100 item limit so it can disable the add buttons in time. The two numbers are kept in sync by hand.
* Each client can send 25 basket requests per minute. After that the API returns 429 with a `Retry-After` header, and the page tells the user how long to wait. Only requests to a real POST route count, so a wrong URL gets a 404 without using up the limit. Reading products and offers is not limited. The limit can be changed with `RATE_LIMIT_PER_MINUTE`. Docker Compose sets it to 300 because every browser request goes through the Vite dev server and so shares one limit.
* A POST body must be sent as `application/json`.
* Every response has `X-Content-Type-Options: nosniff` and `Cache-Control: no-store`.
* Errors and blocked requests are logged as JSON with Monolog. See them with `docker compose logs api`. Client IP addresses are logged as a short SHA256 hash, not in plain text. This is pseudonymised, not anonymous: every IPv4 address can be hashed in minutes, so the hash can be reversed. In production I would use an HMAC with a secret key.
* The font is served from the app itself, not from Google Fonts.

## Project layout

* `backend/src/Domain`: basket, products, delivery rules and offers
* `backend/src/Repository`: loads products (kept in memory for now)
* `backend/src/Service`: building a basket from a list of product codes
* `backend/src/Http`: routes, controllers, request and response classes, rate limiter
* `backend/src/Application.php`: turns the PHP server variables into a request, adds the standard headers and returns a safe 500 error if anything fails
* `frontend/src`: API calls, hooks for products and basket, components with SCSS

## Limitations

* Products are kept in memory. A database would need a new `ProductRepository` class.
* The rate limiter stores counters in files, so it works on one server only. More servers would need Redis.
* The rate limiter uses a fixed window, so a client can send up to twice the limit around the end of a minute. Counter files are never deleted, so many different IP addresses slowly fill the temp folder. Redis with a sliding window and expiring keys would fix both.
* The limit is per IP address. People behind one shared office or school IP share the same 25 requests a minute, and the plus and minus buttons make it easy to click that fast. A real shop would limit by session or user instead.
* There are no user accounts and orders are not saved.

## Production

This setup is for development. It runs `php -S`, which handles one request at a time, and mounts the code from your disk. In production I would:

* Run the API with PHP FPM behind Nginx, or with FrankenPHP, from an image that has the code and `composer install --no-dev` built in.
* Build the frontend with `npm run build` and serve the files from the same domain as the API, so no CORS setup is needed.
* Move the rate limit to Redis and add a health check endpoint for the load balancer.
