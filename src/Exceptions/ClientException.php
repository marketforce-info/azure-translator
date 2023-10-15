<?php

namespace MarketforceInfo\AzureTranslator\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientException extends RuntimeException implements RequestExceptionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function create(RequestInterface $request, ResponseInterface $response): self
    {
        $error = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        return new self($request, $error['error']['message'] ?? 'unknown', $error['error']['code'] ?? 0);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
