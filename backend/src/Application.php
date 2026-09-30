<?php

declare(strict_types=1);

namespace Acme;

use Acme\Http\Controller\BasketController;
use Acme\Http\Controller\OfferController;
use Acme\Http\Controller\ProductController;
use Acme\Http\RateLimit\RateLimiter;
use Acme\Http\Router;
use Acme\Service\BasketService;
use Acme\Service\OfferService;
use Acme\Service\ProductService;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

final class Application
{
    public static function createRouter(StoreConfig $storeConfig, RateLimiter $rateLimiter, LoggerInterface $logger): Router
    {
        $productService = new ProductService($storeConfig->productRepository);
        $basketService = new BasketService(
            $storeConfig->productRepository,
            $storeConfig->deliveryRules,
            ...$storeConfig->offers,
        );
        $offerService = new OfferService(...$storeConfig->offers);

        $router = new Router(
            new ProductController($productService),
            new BasketController($basketService),
            new OfferController($offerService),
            $rateLimiter,
            $logger,
        );

        return $router;
    }

    public static function createLogger(): LoggerInterface
    {
        $formatter = new JsonFormatter();
        $formatter->includeStacktraces();

        $stderrHandler = new StreamHandler('php://stderr', Level::Info);
        $stderrHandler->setFormatter($formatter);

        $logger = new Logger('acme-api');
        $logger->pushHandler($stderrHandler);
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }
}
