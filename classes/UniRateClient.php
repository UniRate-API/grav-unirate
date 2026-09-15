<?php

namespace Grav\Plugin\UniRate;

/**
 * Minimal, dependency-free client for the UniRate API (https://unirateapi.com).
 *
 * HTTP is performed with PHP's bundled cURL extension. A transport callable can be
 * injected (signature: fn(string $url): array{0:int,1:string}) so the client can be
 * unit-tested with zero network access.
 */
class UniRateClient
{
    /** @var string */
    private $apiKey;

    /** @var string */
    private $baseUrl;

    /** @var int */
    private $timeout;

    /** @var callable|null */
    private $transport;

    /**
     * @param callable|null $transport Optional fn(string $url): array{0:int,1:string} for testing.
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.unirateapi.com',
        int $timeout = 15,
        ?callable $transport = null
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->transport = $transport;
    }

    public function getRate(string $from, string $to): float
    {
        $data = $this->request('/api/rates', ['from' => strtoupper($from), 'to' => strtoupper($to)]);

        return (float) $data['rate'];
    }

    public function convert(float $amount, string $from, string $to): float
    {
        $data = $this->request('/api/convert', [
            'amount' => $amount,
            'from' => strtoupper($from),
            'to' => strtoupper($to),
        ]);

        return (float) $data['result'];
    }

    /**
     * @return string[]
     */
    public function getCurrencies(): array
    {
        $data = $this->request('/api/currencies', []);

        return $data['currencies'];
    }

    /**
     * @return array<string,mixed>
     */
    public function getVatRates(?string $country = null): array
    {
        $params = [];
        if ($country !== null && $country !== '') {
            $params['country'] = strtoupper($country);
        }

        return $this->request('/api/vat/rates', $params);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    private function request(string $endpoint, array $params): array
    {
        if ($this->apiKey === '') {
            throw new UniRateException('UniRate API key is not configured.');
        }

        $params['api_key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        [$status, $body] = $this->send($url);

        if ($status === 401) {
            throw new UniRateException('Missing or invalid UniRate API key.', 401);
        }
        if ($status === 404) {
            throw new UniRateException('Currency not found or no data available.', 404);
        }
        if ($status === 429) {
            throw new UniRateException('UniRate rate limit exceeded.', 429);
        }
        if ($status >= 400) {
            throw new UniRateException('UniRate request failed (HTTP ' . $status . ').', $status);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new UniRateException('Invalid JSON response from UniRate.');
        }

        return $decoded;
    }

    /**
     * @return array{0:int,1:string} [status, body]
     */
    private function send(string $url): array
    {
        if ($this->transport !== null) {
            return ($this->transport)($url);
        }

        // @codeCoverageIgnoreStart — real network path, exercised only outside unit tests.
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new UniRateException('UniRate HTTP error: ' . $error);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, (string) $body];
        // @codeCoverageIgnoreEnd
    }
}
