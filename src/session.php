<?php
declare(strict_types=1);

use Airship\Engine\{
    AutoPilot,
    State
};
use ParagonIE\Cookie\{
    Cookie,
    Session
};

/**
 * @global State $state
 */

// Start the session
if (!Session::id()) {
    if (!isset($state)) {
        $state = State::instance();
    }
    $session_config = [
        // Prevent uninitialized sessions from being accepted
        'use_strict_mode' => true,
        // The session ID cookie should be inaccessible to JavaScript
        'cookie_httponly' => true,
        // If we're over HTTPS, enforce secure=1
        'cookie_secure' => AutoPilot::isHTTPSConnection()
    ];
    if (isset($state->universal['session_config'])) {
        $session_config = $state->universal['session_config'] + $session_config;
        if (isset($session_config['cookie_domain'])) {
            if ($session_config['cookie_domain'] === '*' || \trim($session_config['cookie_domain']) === '') {
                unset($session_config['cookie_domain']);
            }
        }
    }

    Session::start(
        Cookie::SAME_SITE_RESTRICTION_STRICT,
        $session_config
    );
}

if (empty($_SESSION['created_canary'])) {
    // We haven't seen this session ID before
    $_SESSION = [];
    Session::regenerate(true);
    // Create the canary
    $_SESSION['created_canary'] = (new \DateTime())
        ->format(\AIRSHIP_DATE_FORMAT);
} else {
    $dt = (new \DateTime($_SESSION['created_canary']))->add(
        new \DateInterval('PT01H')
    );
    $now = new \DateTime();
    // Has an hour passed?
    if ($dt < $now) {
        // An hour has passed:
        Session::regenerate(true);
        // Recreate the canary
        $_SESSION['created_canary'] = $now->format(\AIRSHIP_DATE_FORMAT);
    }
}
