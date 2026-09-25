<?php
/**
 * PESO - Real email validation
 * Beyond format checking: confirms the domain can actually receive mail
 * and blocks common disposable/throwaway email providers.
 */

const PESO_DISPOSABLE_EMAIL_DOMAINS = [
    'mailinator.com', 'guerrillamail.com', 'guerrillamail.info', 'sharklasers.com',
    '10minutemail.com', '10minutemail.net', 'tempmail.com', 'temp-mail.org',
    'yopmail.com', 'trashmail.com', 'getnada.com', 'dispostable.com',
    'throwawaymail.com', 'fakeinbox.com', 'maildrop.cc', 'mailnesia.com',
    'mintemail.com', 'moakt.com', 'tempinbox.com', 'emailondeck.com',
];

/**
 * Returns null if the email looks real, or a user-facing error message if not.
 */
function peso_validate_real_email(string $email): ?string
{
    $email = trim($email);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Please enter a valid email address.';
    }

    $domain = strtolower(substr(strrchr($email, '@'), 1));

    if (in_array($domain, PESO_DISPOSABLE_EMAIL_DOMAINS, true)) {
        return 'Temporary or disposable email addresses are not allowed. Please use a real email address.';
    }

    // Confirm the domain can actually receive mail (catches typos and
    // made-up domains). Skip on environments without DNS access.
    if (function_exists('checkdnsrr') && !checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        return "We couldn't verify that email domain. Please check for typos and try again.";
    }

    return null;
}
