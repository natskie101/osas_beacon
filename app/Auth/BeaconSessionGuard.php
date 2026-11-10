<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\RememberToken;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Str;

/**
 * The portal's session guard.
 *
 * Extends the native guard so session-fixation handling, auth events and
 * middleware behaviour come from the framework, while preserving the portal's
 * own remember-me scheme and legacy semantics:
 *
 *  - a stale session id (deleted / deactivated account) is dropped, and the
 *    beacon_remember cookie is consulted as a fallback exactly like
 *    App\Support\auth_user() did;
 *  - login rotates the CSRF token (_token) as the legacy login did;
 *  - logout clears the remember token, flushes the whole session and
 *    regenerates the session id (the native guard only forgets the auth key).
 */
final class BeaconSessionGuard extends SessionGuard
{
    /**
     * Whether the beacon_remember cookie has already been inspected this
     * request. RememberToken::validate() rotates the cookie when it matches,
     * so it must run at most once per request (legacy $resolved parity).
     */
    private bool $rememberChecked = false;

    /** @return AuthenticatableContract|null */
    public function user()
    {
        if ($this->loggedOut) {
            return null;
        }

        if ($this->user !== null) {
            return $this->user;
        }

        $user = parent::user();

        if ($user !== null) {
            return $user;
        }

        // Legacy parity: a session id that no longer resolves (deleted or
        // deactivated account) is dropped instead of retried forever.
        if ($this->session->has($this->getName())) {
            $this->session->forget($this->getName());
        }

        if ($this->rememberChecked) {
            return null;
        }

        $this->rememberChecked = true;

        $rememberedId = RememberToken::validate(
            $_COOKIE[(string) config('app.remember.cookie', 'beacon_remember')] ?? null
        );

        if ($rememberedId === null) {
            return null;
        }

        $user = $this->provider->retrieveById($rememberedId);

        if ($user === null) {
            return null;
        }

        // Legacy parity: regenerate() + put('auth_user_id', ...) — rotate the
        // session id and store the resolved user.
        $this->updateSession((int) $user->getAuthIdentifier());
        $this->user = $user;
        $this->fireAuthenticatedEvent($user);

        return $user;
    }

    public function login(AuthenticatableContract $user, $remember = false)
    {
        // Always false: the native recaller cookie (remember_web_*) is unused
        // by the portal — remember-me is handled below via beacon_remember.
        parent::login($user, false);

        // Legacy parity: rotate the CSRF token whenever a session becomes
        // authenticated (session-fixation defence).
        $this->session->put('_token', Str::random(32));

        if ($remember) {
            RememberToken::issue((int) $user->getAuthIdentifier());
        }
    }

    public function logout()
    {
        $cookie = $_COOKIE[(string) config('app.remember.cookie', 'beacon_remember')] ?? null;

        parent::logout();

        // Legacy parity: invalidate the remember token, wipe the whole session
        // (url.intended, flashes, ...) and regenerate the session id.
        RememberToken::clear($cookie);
        $this->session->flush();
        $this->session->migrate(true);
    }
}
