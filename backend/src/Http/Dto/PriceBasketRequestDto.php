<?php

declare(strict_types=1);

namespace Acme\Http\Dto;

use Acme\Http\HttpException;
use JsonException;

final readonly class PriceBasketRequestDto
{
    private const int MAX_ITEMS = 100;

    private const string PRODUCT_CODE_PATTERN = '/^[A-Za-z0-9]{1,32}$/';

    /** @param list<string> $productCodes */
    private function __construct(public array $productCodes) {}

    public static function fromJson(string $requestBody): self
    {
        try {
            $decodedBody = json_decode($requestBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw HttpException::badRequest('Request body must be valid JSON.');
        }

        $rawCodes = is_array($decodedBody) ? ($decodedBody['productCodes'] ?? null) : null;
        if (!is_array($rawCodes) || !array_is_list($rawCodes)) {
            throw HttpException::unprocessable('"productCodes" must be an array of product codes.');
        }
        if (count($rawCodes) > self::MAX_ITEMS) {
            throw HttpException::unprocessable('A basket cannot hold more than ' . self::MAX_ITEMS . ' items.');
        }

        $productCodes = [];
        foreach ($rawCodes as $rawCode) {
            if (!is_string($rawCode)) {
                throw HttpException::unprocessable('Every product code must be a string.');
            }
            if (preg_match(self::PRODUCT_CODE_PATTERN, $rawCode) !== 1) {
                throw HttpException::unprocessable('Product codes must be 1-32 letters or digits.');
            }
            $productCodes[] = $rawCode;
        }

        return new self($productCodes);
    }
}
