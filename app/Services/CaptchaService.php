<?php

namespace App\Services;

use ReCaptcha\ReCaptcha;

/**
 * CAPTCHA Verification Service
 *
 * Integrates Google reCAPTCHA v3 for bot protection
 *
 * Environment Variables Required:
 * - RECAPTCHA_ENABLED (bool): Enable/disable CAPTCHA
 * - RECAPTCHA_SITE_KEY (string): Public key for frontend
 * - RECAPTCHA_SECRET_KEY (string): Secret key for backend verification
 * - RECAPTCHA_SCORE_THRESHOLD (float): Minimum score (0.0-1.0, default: 0.5)
 */
class CaptchaService
{
    /**
     * @var bool Whether CAPTCHA is enabled
     */
    protected bool $enabled;

    /**
     * @var string|null CAPTCHA secret key
     */
    protected ?string $secretKey;

    /**
     * @var float Score threshold (0.0-1.0)
     */
    protected float $scoreThreshold;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->enabled = filter_var(env('RECAPTCHA_ENABLED', false), FILTER_VALIDATE_BOOL);
        $this->secretKey = env('RECAPTCHA_SECRET_KEY');
        $this->scoreThreshold = (float) env('RECAPTCHA_SCORE_THRESHOLD', 0.5);
    }

    /**
     * Check if CAPTCHA is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->secretKey);
    }

    /**
     * Verify CAPTCHA token
     *
     * @param string|null $token CAPTCHA token from frontend
     * @param string|null $ip User's IP address
     * @param string|null $action Expected action name (e.g., 'login', 'signup')
     * @return array Verification result
     */
    public function verify(?string $token, ?string $ip = null, ?string $action = null): array
    {
        // If CAPTCHA is disabled, always return success
        if (!$this->isEnabled()) {
            return [
                'success' => true,
                'score' => 1.0,
                'message' => 'CAPTCHA is disabled',
            ];
        }

        // Check if token is provided
        if (empty($token)) {
            return [
                'success' => false,
                'score' => 0.0,
                'message' => 'CAPTCHA token is required',
                'error_codes' => ['missing-input-response'],
            ];
        }

        try {
            // Initialize reCAPTCHA
            $recaptcha = new ReCaptcha($this->secretKey);

            // Set hostname verification (optional but recommended)
            if ($hostname = env('APP_DOMAIN')) {
                $recaptcha->setExpectedHostname($hostname);
            }

            // Set action verification (reCAPTCHA v3)
            if ($action) {
                $recaptcha->setExpectedAction($action);
            }

            // Set minimum score threshold
            $recaptcha->setScoreThreshold($this->scoreThreshold);

            // Verify the token
            $response = $recaptcha->verify($token, $ip);

            // Check if verification was successful
            if ($response->isSuccess()) {
                return [
                    'success' => true,
                    'score' => $response->getScore(),
                    'message' => 'CAPTCHA verification successful',
                ];
            }

            // Verification failed
            return [
                'success' => false,
                'score' => $response->getScore(),
                'message' => 'CAPTCHA verification failed',
                'error_codes' => $response->getErrorCodes(),
            ];
        } catch (\Exception $e) {
            // Log error for debugging
            logger()->error('CAPTCHA verification error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'score' => 0.0,
                'message' => 'CAPTCHA verification error: ' . $e->getMessage(),
                'error_codes' => ['verification-error'],
            ];
        }
    }

    /**
     * Get human-readable error message from error codes
     *
     * @param array $errorCodes Error codes from reCAPTCHA
     * @return string Error message
     */
    public function getErrorMessage(array $errorCodes): string
    {
        if (empty($errorCodes)) {
            return 'Unknown error';
        }

        $messages = [
            'missing-input-secret' => 'CAPTCHA secret key is missing',
            'invalid-input-secret' => 'CAPTCHA secret key is invalid',
            'missing-input-response' => 'CAPTCHA token is missing',
            'invalid-input-response' => 'CAPTCHA token is invalid or expired',
            'bad-request' => 'Bad request to CAPTCHA service',
            'timeout-or-duplicate' => 'CAPTCHA token has been used before or has expired',
            'score-threshold-not-met' => 'CAPTCHA score is too low (likely a bot)',
            'action-mismatch' => 'CAPTCHA action does not match expected action',
            'hostname-mismatch' => 'CAPTCHA hostname does not match expected hostname',
        ];

        $firstError = $errorCodes[0];
        return $messages[$firstError] ?? 'CAPTCHA verification failed';
    }

    /**
     * Get recommended action based on CAPTCHA score
     *
     * @param float $score CAPTCHA score (0.0-1.0)
     * @return string Recommended action
     */
    public function getRecommendedAction(float $score): string
    {
        if ($score >= 0.7) {
            return 'allow'; // Likely human
        } elseif ($score >= 0.5) {
            return 'challenge'; // Suspicious, may require additional verification
        } else {
            return 'block'; // Likely bot
        }
    }
}
