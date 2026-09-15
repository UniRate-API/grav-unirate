<?php

namespace Grav\Plugin\UniRate;

/**
 * Raised when a UniRate API request fails (auth, rate limit, bad currency, transport, or decode).
 */
class UniRateException extends \RuntimeException
{
}
