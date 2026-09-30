# Acme Widget Co Basket

This is a small sales basket for Acme Widget Co. The backend is written in PHP and the frontend is written in React with TypeScript.

## How to run it

You only need Docker.

```bash
docker compose up
```

Then open these in your browser:

* Website: http://localhost:5173
* API: http://localhost:8000/api/v1/products

## How to run the checks

Backend (code style, PHPStan and PHPUnit tests):

```bash
docker compose run --rm composer check
```

Frontend (Prettier, lint, TypeScript and build):

```bash
docker compose run --rm web sh -c "npm ci && npm run check"
```

GitHub Actions runs the same checks on every push to `main` and on every pull request. The workflow is in `.github/workflows/ci.yml`.

## How the basket works

The basket is created with three things: the product catalogue, the delivery rules and the offers. It has an `add` method that takes a product code and a `total` method that returns the total.

```php
$basket = new Basket($productRepository, $deliveryRules, $offers);
$basket->add('R01');
$basket->add('R01');
$basket->total(); // 5437 cents, which is $54.37
```

The total is worked out in this order:

1. Add up the price of every item. This is the subtotal.
2. Take away any offer discount.
3. Work out the delivery charge from what is left. Under $50 costs $4.95, under $90 costs $2.95, and $90 or more is free.
4. Total is subtotal minus discount plus delivery.

All prices, delivery rules and offers are set in one file: `backend/config/store.php`. To change a price or an offer you only edit that file.

All money is stored in cents as whole numbers. This avoids rounding problems you get with decimal numbers.

## Project layout

Backend (`backend/src`):

| Folder            | What it does                                                           |
| ----------------- | ---------------------------------------------------------------------- |
| `Domain`          | The business rules: basket, products, delivery rules and offers        |
| `Repository`      | Where products are loaded from. Right now they are kept in memory      |
| `Service`         | The actions the API can do, like listing products or building a basket |
| `Http`            | Routes, controllers, request and response classes, and the rate limiter |
| `Application.php` | Creates all the classes and connects them together                     |

Frontend (`frontend/src`):

| Folder       | What it does                                                        |
| ------------ | ------------------------------------------------------------------- |
| `api`        | Sends requests to the backend                                       |
| `services`   | One function for each API call                                      |
| `types`      | The shape of the data the API sends back                            |
| `hooks`      | Loads products and keeps the basket state                           |
| `components` | The product list and the basket panel, each with its own SCSS file |
| `styles`     | Shared colours, spacing and SCSS mixins                             |

## API

| Method | URL                    | What you send                      | What you get back                                        |
| ------ | ---------------------- | ---------------------------------- | -------------------------------------------------------- |
| GET    | `/api/v1/products`     | nothing                            | the list of products                                     |
| POST   | `/api/v1/basket/total` | `{"productCodes": ["R01", "R01"]}` | the basket lines, subtotal, discount, delivery and total |

Example answer for two red widgets:

```json
{
  "lines": [
    {
      "code": "R01",
      "name": "Red Widget",
      "quantity": 2,
      "unitPriceInCents": 3295,
      "lineTotalInCents": 6590
    }
  ],
  "subtotalInCents": 6590,
  "discountInCents": 1648,
  "deliveryInCents": 495,
  "totalInCents": 5437
}
```

When something goes wrong the API sends back `{"error": "..."}` with one of these status codes:

| Code | Meaning                                                                    |
| ---- | -------------------------------------------------------------------------- |
| 400  | The request is not valid JSON                                              |
| 404  | The URL does not exist                                                     |
| 405  | Wrong method for that URL                                                  |
| 422  | Bad input, for example an unknown product code                             |
| 429  | Too many requests. Wait and try again                                      |
| 500  | Something broke on the server. The details go to the log, not to the user |

## Security

The frontend only sends product codes. It never sends prices or totals. The backend looks up every price itself and does all the maths. If someone adds a fake price or total to the request, for example with Postman, the backend ignores it. There is a test for this.

The backend also checks every request:

* `productCodes` must be a list of text values.
* Each code must be 1 to 32 letters or numbers and must exist in the catalogue.
* A basket can have at most 100 items.
* Each client can make 10 requests per minute. After that the API returns 429. The limit is low on purpose so it is easy to test.

## Logging

The backend writes logs as JSON lines using Monolog. You can see them with:

```bash
docker compose logs api
```

Server errors, blocked requests and bad input are all logged. Request bodies are never logged.

## Assumptions

* **Rounding.** Half of $32.95 is $16.475. The example "R01, R01 = $54.37" only works if the half price is rounded down to $16.47. The last example ($98.27) agrees with this.
* **Delivery uses the price after the discount.** Two red widgets cost $65.90 before the offer and $49.42 after it. The expected total of $54.37 only works if delivery is $4.95, so delivery must be based on the lower amount.
* **The offer works for every pair.** Four red widgets means two of them are half price.
* **An empty basket costs $0.** There is no delivery charge when there is nothing to deliver.
* **Unknown product codes are rejected** instead of being skipped.
* The task only asks for an `add` method, so the page only has Add and Clear buttons. You cannot remove one item.

## Limitations

* Products are kept in memory. To use a database you would write a new class that follows the `ProductRepository` interface.
* The rate limiter saves its counters in files on one server. With more than one server you would use something like Redis instead.
* When running locally, all browser requests go through the Vite dev server, so the rate limit is shared by everyone using the site.
* There are no user accounts and orders are not saved. The task only asks for a basket and its total.
