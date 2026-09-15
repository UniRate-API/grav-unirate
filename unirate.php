<?php

namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Plugin\UniRate\UniRateClient;
use Grav\Plugin\UniRate\UniRateException;

require_once __DIR__ . '/classes/UniRateException.php';
require_once __DIR__ . '/classes/UniRateClient.php';

/**
 * UniRate plugin — live currency exchange rates, conversion, and VAT rates as Twig
 * functions, with server-side caching so the API is not hit on every page render.
 */
class UniratePlugin extends Plugin
{
    /**
     * @return array<string,mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        // Twig helpers are for front-end rendering only.
        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onTwigInitialized' => ['onTwigInitialized', 0],
        ]);
    }

    public function onTwigInitialized(): void
    {
        $twig = $this->grav['twig']->twig();
        $twig->addFunction(new \Twig\TwigFunction('unirate_rate', [$this, 'twigRate']));
        $twig->addFunction(new \Twig\TwigFunction('unirate_convert', [$this, 'twigConvert']));
        $twig->addFunction(new \Twig\TwigFunction('unirate_currencies', [$this, 'twigCurrencies']));
        $twig->addFunction(new \Twig\TwigFunction('unirate_vat', [$this, 'twigVat']));
    }

    /**
     * {{ unirate_rate('USD', 'EUR') }}
     *
     * @return float|null
     */
    public function twigRate(string $from, string $to)
    {
        return $this->cached("rate:$from:$to", function () use ($from, $to) {
            return $this->client()->getRate($from, $to);
        });
    }

    /**
     * {{ unirate_convert(100, 'USD', 'EUR') }}
     *
     * @return float|null
     */
    public function twigConvert(float $amount, string $from, string $to)
    {
        return $this->cached("convert:$amount:$from:$to", function () use ($amount, $from, $to) {
            return $this->client()->convert($amount, $from, $to);
        });
    }

    /**
     * {{ unirate_currencies() }}
     *
     * @return string[]|null
     */
    public function twigCurrencies()
    {
        return $this->cached('currencies', function () {
            return $this->client()->getCurrencies();
        });
    }

    /**
     * {{ unirate_vat('DE') }}
     *
     * @return array<string,mixed>|null
     */
    public function twigVat(?string $country = null)
    {
        return $this->cached('vat:' . ($country ?? 'all'), function () use ($country) {
            return $this->client()->getVatRates($country);
        });
    }

    private function client(): UniRateClient
    {
        return new UniRateClient(
            (string) $this->config->get('plugins.unirate.api_key', ''),
            (string) $this->config->get('plugins.unirate.base_url', 'https://api.unirateapi.com')
        );
    }

    /**
     * Fetch through Grav's cache; on any API error, log a warning and return null so
     * templates degrade gracefully instead of throwing.
     *
     * @return mixed
     */
    private function cached(string $key, callable $callback)
    {
        $cache = $this->grav['cache'];
        $id = 'unirate_' . md5($key);

        $result = $cache->fetch($id);
        if ($result !== false) {
            return $result;
        }

        try {
            $result = $callback();
        } catch (UniRateException $e) {
            $this->grav['log']->warning('UniRate: ' . $e->getMessage());

            return null;
        }

        $lifetime = (int) $this->config->get('plugins.unirate.cache_lifetime', 3600);
        $cache->save($id, $result, $lifetime);

        return $result;
    }
}
