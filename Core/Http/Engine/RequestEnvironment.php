<?php

namespace Rbn\Framework\Core\Http\Engine;

/**
 * RequestEnvironment - Shared request/transport facts 🌍🔒
 *
 * [http #11] `Secure` cookie flag must be decided from ONE place so that every
 * cookie producer (session, response helper, alert cookie) agrees.
 *
 * The logic previously lived as a `private` method inside
 * `SessionSandboxStage` and was therefore unreachable from the response and
 * alert layers. It is extracted here verbatim (same signals, same order, same
 * conservative direction) so no existing behaviour changes.
 *
 * SECURITY NOTE: the fallback `X-Forwarded-Proto` check only ever turns
 * `Secure` ON. A client can forge that header, but a false positive only means
 * the cookie is marked Secure on a request we already believe is HTTPS; the
 * opposite direction (marking Secure on plain HTTP) would break local
 * development, so it is never inferred.
 */
final class RequestEnvironment
{
    /**
     * Is the current (or given) request served over HTTPS?
     *
     * @param array<string,mixed>|null $server Defaults to `$_SERVER`.
     */
    public static function isHttpsRequest(?array $server = null): bool
    {
        $server ??= $_SERVER;

        $https = (string) ($server['HTTPS'] ?? '');
        if ($https !== '' && strtolower($https) !== 'off') {
            return true;
        }
        if ((string) ($server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        // NOLINT: client header — only a plain `https` value is accepted.
        $proto = strtolower(trim(explode(',', (string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        if ($proto === 'https') {
            return true;
        }
        return false;
    }
}