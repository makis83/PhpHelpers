<?php

namespace Makis83\Helpers;

use Pdp\Rules;
use Pdp\Domain;
use Pdp\CannotProcessHost;
use InvalidArgumentException;
use Safe\Exceptions\UrlException;
use Safe\Exceptions\PcreException;

/**
 * Provides HTTP related helper methods.
 * Created by PhpStorm.
 * User: max
 * Date: 2025-09-29
 * Time: 13:06
 */
class Http
{
    /**
     * Get user IP address.
     *
     * @param null|array<string, mixed> $server Server and execution environment information
     * @param null|non-empty-string $sapi Server API name
     * @return false|string User IP address or false on failure
     */
    public static function ip(?array $server = null, ?string $sapi = null): false|string
    {
        // Get dependencies
        $server = $server ?? $_SERVER;
        $sapi = $sapi ?? PHP_SAPI;

        // Detect if the method is called via CLI
        if ('cli' === $sapi) {
            return '127.0.0.1';
        }

        // Define HTTP headers that can include IP address
        $httpHeaders = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare's IP header
            'HTTP_X_FORWARDED_FOR', // Apache proxy's IP header
            'HTTP_X_FORWARDED', // Proxy's IP header
            'HTTP_X_REAL_IP', // Nginx proxy's IP header
            'HTTP_CLIENT_IP', // Non-proxy IP header
            'HTTP_X_CLUSTER_CLIENT_IP', // Rackspace's IP header
            'REMOTE_ADDR' // Default IP header
        ];

        // Get IP
        foreach ($httpHeaders as $header) {
            if (array_key_exists($header, $server)) {
                $ips = explode(',', $server[$header]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return $ip;
                    }
                }
            }
        }

        // Default value
        return false;
    }


    /**
     * Get registrable domain name from host.
     *
     * Note that this method uses the Public Suffix List to determine the registrable domain name.
     * Null will be returned for hosts with no registrable domain name (i.e. localhost, IP addresses etc.).
     *
     * @param non-empty-string $host Host name
     * @return null|non-empty-string Null if the registrable domain cannot be determined,
     * the registrable domain name otherwise
     * @throws CannotProcessHost if the host cannot be processed
     */
    public static function getDomainFromHost(string $host): null|string
    {
        // Validate
        if ('' === trim($host)) {
            return null;
        }

        // Parse the host to get domain
        $publicSuffixList = Rules::fromPath(__DIR__ . '/domains/public_suffix_list.dat');
        $domainObject = Domain::fromIDNA2008($host);
        $registrableDomain = $publicSuffixList->resolve($domainObject)->registrableDomain()->toString();

        // Result
        return ('' === trim($registrableDomain)) ? null : $registrableDomain;
    }


    /**
     * Check if the given URL is valid.
     *
     * @param non-empty-string $url Url to check
     * @return bool Whether the URL is valid
     * @see https://regex101.com/r/w8UowZ/2
     * @see https://gist.github.com/dperini/729294#gistcomment-2998255
     * @see https://mathiasbynens.be/demo/url-regex
     */
    public static function isValidUrl(string $url): bool
    {
        // Check length
        if ('' === trim($url)) {
            return false;
        }

        // Validate url
        $pattern = '/^(?:(?:https?|ftp):)?\/\/(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4])|(?:(?:[a-z0-9\x{00a1}-\x{ffff}][a-z0-9\x{00a1}-\x{ffff}_-]{0,62})?[a-z0-9\x{00a1}-\x{ffff}]\.)+(?:[a-z\x{00a1}-\x{ffff}]{2,}|xn--[a-z0-9]{2,})\.?)(?::\d{2,5})?(?:[\/?#]\S*)?$/iuS';
        try {
            // Check if the URL is valid
            $valid = (bool) \Safe\preg_match($pattern, $url);
            if (!$valid) {
                return false;
            }

            // Parse the URL to get host
            $parsedUrl = \Safe\parse_url($url);
            $host = $parsedUrl['host'] ?? null;
            if (null === $host) {
                return false;
            }

            // Check if there is a start punycode sequence in the host
            if (false !== stripos($host, 'xn--')) {
                // Get domain from host
                $domain = static::getDomainFromHost($host);
                if ('' === trim($domain)) {
                    return false;
                }

                // Validate it
                self::isPunycodeDomain($domain);
            }

            // Domain is valid
            return true;
        } catch (PcreException|UrlException|CannotProcessHost|InvalidArgumentException) {
            return false;
        }
    }


    /**
     * Get host from URL.
     *
     * @param non-empty-string $url URL
     * @return non-empty-string Host name
     * @throws InvalidArgumentException if the URL is invalid
     * @throws UrlException if the URL cannot be parsed
     */
    public static function getHostFromUrl(string $url): string
    {
        // Validate URL
        if (!static::isValidUrl($url)) {
            throw new InvalidArgumentException('Invalid URL: ' . $url);
        }

        // Parse the URL to get host
        $parsedUrl = \Safe\parse_url($url);
        $host = $parsedUrl['host'] ?? null;
        if ('' === trim($host)) {
            throw new UrlException('Failed to get host from URL: ' . $url);
        }

        // Result
        return $host;
    }


    /**
     * Get registrable domain name from URL.
     * Note that this method uses the Public Suffix List to determine the registrable domain name.
     * Null will be returned for URL with no registrable domain name (i.e. localhost, IP addresses etc.).
     *
     * @param non-empty-string $url URL
     * @return null|non-empty-string Null if the registrable domain cannot be determined,
     * the registrable domain name otherwise
     * @throws InvalidArgumentException if the URL is invalid
     * @throws UrlException if the URL cannot be parsed
     * @throws CannotProcessHost if the host cannot be processed
     */
    public static function getDomainFromUrl(string $url): null|string
    {
        return static::getDomainFromHost(static::getHostFromUrl($url));
    }


    /**
     * Check if the given registrable domain is valid.
     * Note that this method uses the Public Suffix List to determine the registrable domain name.
     *
     * @param string $domain Domain name
     * @param bool $checkDns Whether to check DNS records
     * @return bool Whether the domain is valid
     */
    public static function isValidDomain(string $domain, bool $checkDns = false): bool
    {
        // Check length
        if ('' === trim($domain)) {
            return false;
        }

        // Default check
        if (false === filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return false;
        }

        // Try to get the registrable domain
        try {
            $domainRegistrable = static::getDomainFromHost($domain);
            if ('' === trim($domainRegistrable)) {
                return false;
            }
        } catch (CannotProcessHost) {
            return false;
        }

        // Result
        if ($checkDns) {
            if (!checkdnsrr($domainRegistrable, 'A') || !checkdnsrr($domainRegistrable)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Check if the given domain is in punycode format.
     *
     * @param non-empty-string $domain Domain
     * @return bool Whether the domain is in punycode
     * @throws InvalidArgumentException if the domain is in punycode format but cannot be converted to Unicode
     * @see https://stackoverflow.com/a/53939317/9653787
     * @see https://developer.mozilla.org/en-US/docs/Mozilla/Internationalized_domain_names_support_in_Mozilla#ASCII-compatible_encoding_.28ACE.29
     */
    public static function isPunycodeDomain(string $domain): bool
    {
        // Validate domain
        if (!static::isValidDomain($domain)) {
            return false;
        }

        // Extract host into parts
        $parts = explode('.', $domain);

        // Host is in punycode if at least one part is in punycode
        foreach ($parts as $part) {
            if (str_starts_with($part, 'xn--')) {
                if (Text::isPunycode($part)) {
                    return true;
                }

                throw new InvalidArgumentException(
                    "Domain $domain is in punycode format, but cannot be converted to Unicode"
                );
            }
        }

        // Default case
        return false;
    }
}
