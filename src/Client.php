<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi;

use Exception;
use JsonException;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Terminal42\WeblingApi\Exception\ApiErrorException;
use Terminal42\WeblingApi\Exception\HttpStatusException;
use Terminal42\WeblingApi\Exception\NotFoundException;
use Terminal42\WeblingApi\Exception\ParseException;

class Client implements ClientInterface
{
    /**
     * @var PsrClientInterface
     */
    private PsrClientInterface $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * @var string
     */
    private string $baseUri;

    /**
     * @var array
     */
    private array $defaultQuery;

    public function __construct(
        string $subdomain,
        string $apiKey,
        int $apiVersion,
        ?PsrClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null
    )
    {
        $this->httpClient = $httpClient ?: Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();

        $this->baseUri = sprintf('https://%s.webling.ch/api/%s/', $subdomain, $apiVersion);
        $this->defaultQuery = ['apikey' => $apiKey];
    }

    /**
     * @throws HttpStatusException
     */
    public function get(string $url, array $query = []): mixed
    {
        try {
            $response = $this->httpClient->sendRequest(
                $this->requestFactory->createRequest('GET', $this->buildUri($url, $query))
            );

            if (200 !== $response->getStatusCode()) {
                throw $this->convertResponseToException($response);
            }

            $json = @json_decode((string) $response->getBody(), true);

            if (false === $json) {
                throw new ParseException(json_last_error_msg(), json_last_error());
            }

            return $json;
        } catch (Exception $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        } catch (ClientExceptionInterface $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HttpStatusException
     */
    public function post(string $url, string $json): mixed
    {
        try {
            $response = $this->httpClient->sendRequest(
                $this->requestFactory->createRequest('POST', $this->buildUri($url), [], $json)
            );

            if (201 !== $response->getStatusCode()) {
                throw $this->convertResponseToException($response);
            }

            return $response->getBody()->getContents();
        } catch (Exception $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        } catch (ClientExceptionInterface $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HttpStatusException
     */
    public function put(string $url, string $json): mixed
    {
        try {
            $response = $this->httpClient->sendRequest(
                $this->requestFactory->createRequest('PUT', $this->buildUri($url), [], $json)
            );

            if (204 !== $response->getStatusCode()) {
                throw $this->convertResponseToException($response);
            }

            return $response->getBody()->getContents();
        } catch (Exception $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        } catch (ClientExceptionInterface $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HttpStatusException
     */
    public function delete(string $url): mixed
    {
        try {
            $response = $this->httpClient->sendRequest(
                $this->requestFactory->createRequest('DELETE', $this->buildUri($url))
            );

            if (204 !== $response->getStatusCode()) {
                throw $this->convertResponseToException($response);
            }

            return $response->getBody()->getContents();
        } catch (Exception $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        } catch (ClientExceptionInterface $e)
        {
            throw new HttpStatusException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Convert a Guzzle Response to an exception. Useful if status code is not as expected.
     *
     * @param ResponseInterface $response
     * @param Exception|null    $exception
     *
     * @return HttpStatusException|ApiErrorException|NotFoundException
     * @throws JsonException
     */
    protected function convertResponseToException(ResponseInterface $response, ?Exception $exception = null): HttpStatusException|ApiErrorException|NotFoundException
    {
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (!\is_array($body) || empty($body['error'])) {
            return new HttpStatusException($response->getBody()->getContents(), $response->getStatusCode(), $exception);
        }

        if (404 === $response->getStatusCode()) {
            return new NotFoundException($body['error'], $response->getStatusCode(), $exception);
        }

        return new ApiErrorException($body['error'], $response->getStatusCode(), $exception);
    }

    /**
     * Builds an API request URI including authentication credentials.
     *
     * @param string $path
     * @param array $query
     *
     * @return string
     */
    private function buildUri(string $path, array $query = []): string
    {
        $query = array_merge($this->defaultQuery, $query);

        return $this->baseUri.ltrim($path, '/').'?'.http_build_query($query);
    }
}
