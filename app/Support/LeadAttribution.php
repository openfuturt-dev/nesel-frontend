<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

/**
 * First-touch marketing attribution, kept in the visitor's existing session and
 * saved with a contact request when one is submitted.
 *
 * Only data the request actually carries is kept, after sanitising: recognised
 * UTM parameters, the landing page rebuilt from APP_URL (with no other query
 * parameters, so click IDs or anything personal are dropped) and an external
 * HTTPS referrer reduced to its origin and path.
 */
class LeadAttribution
{
    public const SESSION_KEY = 'lead_attribution';

    public const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public const FIELDS = ['landing_page_url', 'referrer_url', ...self::UTM_KEYS];

    public const MAX_URL_LENGTH = 2048;

    public const MAX_UTM_LENGTH = 150;

    /**
     * Record the request as the session's first touch. A later request replaces
     * it only when the first touch was direct (no referrer, no UTM) and the later
     * one is a real external touch; internal navigation never does.
     */
    public static function capture(Request $request): void
    {
        $current = $request->session()->get(self::SESSION_KEY);

        if (is_array($current) && ! self::isDirect($current)) {
            return;
        }

        $touch = self::fromRequest($request);

        if (is_array($current) && self::isDirect($touch)) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $touch);
    }

    /**
     * Attribution fields to save with a contact request (all null when none).
     *
     * @return array<string, string|null>
     */
    public static function fromSession(Session $session): array
    {
        $stored = $session->get(self::SESSION_KEY);

        return array_map(
            fn (string $field): ?string => is_array($stored) && is_string($stored[$field] ?? null) ? $stored[$field] : null,
            array_combine(self::FIELDS, self::FIELDS),
        );
    }

    /**
     * @return array<string, string|null>
     */
    public static function fromRequest(Request $request): array
    {
        $utm = [];

        foreach (self::UTM_KEYS as $key) {
            $utm[$key] = self::utmValue($request->query($key));
        }

        return [
            'landing_page_url' => self::landingPageUrl($request, $utm),
            'referrer_url' => self::referrerUrl($request),
            ...$utm,
        ];
    }

    /**
     * @param  array<string, string|null>  $attribution
     */
    private static function isDirect(array $attribution): bool
    {
        foreach (['referrer_url', ...self::UTM_KEYS] as $field) {
            if (filled($attribution[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string|null>  $utm
     */
    private static function landingPageUrl(Request $request, array $utm): ?string
    {
        $query = http_build_query(array_filter($utm));
        $url = Seo::url($request->path()).($query === '' ? '' : '?'.$query);

        return strlen($url) <= self::MAX_URL_LENGTH ? $url : null;
    }

    private static function referrerUrl(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');

        // Checked before parsing: parse_url() silently turns control characters into "_".
        if (! is_string($referrer) || $referrer === '' || strlen($referrer) > self::MAX_URL_LENGTH
            || preg_match('/[\x00-\x20\x7F]/', $referrer)) {
            return null;
        }

        $parts = parse_url($referrer);

        if ($parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || filter_var($parts['host'] ?? '', FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '/';

        if (self::isInternalHost($host, $request)) {
            return null;
        }

        // Query string and fragment are dropped: they can carry search terms or identifiers.
        return "https://{$host}{$path}";
    }

    private static function isInternalHost(string $host, Request $request): bool
    {
        return in_array($host, [
            strtolower($request->getHost()),
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
        ], true);
    }

    private static function utmValue(mixed $value): ?string
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
            return null;
        }

        $value = trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', $value));

        return $value === '' ? null : mb_substr($value, 0, self::MAX_UTM_LENGTH);
    }
}
