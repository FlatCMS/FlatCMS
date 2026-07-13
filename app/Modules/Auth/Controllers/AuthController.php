<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\FlatFile;
use App\Core\ModuleManager;
use App\Core\Mail\Mailer;
use App\Core\Security\Turnstile;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\LicenseCatalogService;
use App\Modules\Auth\Services\LicenseRevealService;
use App\Modules\Auth\Services\LicenseVaultService;
use App\Modules\Auth\Services\RoleService;
use App\Modules\Auth\Services\TokenRepository;
use App\Modules\Users\Support\UserName;
use App\Services\Licensing\ExtensionLicenseService;

class AuthController extends BaseController
{
    private AuthService $authService;
    private TokenRepository $tokenRepo;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        $this->tokenRepo = new TokenRepository();
        I18n::load('Auth');
    }

    private function normalizeIntendedRedirect(string $intended): string
    {
        $intended = trim($intended);
        if ($intended === '' || str_starts_with($intended, '//')) {
            return url('/admin');
        }

        $parts = parse_url($intended);
        if ($parts === false) {
            return url('/admin');
        }

        $isAbsolute = isset($parts['scheme']) || isset($parts['host']);
        if ($isAbsolute && !$this->isSameOriginRedirect($parts)) {
            return url('/admin');
        }

        $path = (string) ($parts['path'] ?? '');
        $query = (string) ($parts['query'] ?? '');

        if ($path === '' && $query === '') {
            return url('/admin');
        }

        $isIndexFallback = str_ends_with($path, '/index.php') || $path === 'index.php';
        if ($isIndexFallback && $query !== '') {
            parse_str($query, $params);
            $route = '';
            if (isset($params['path']) && is_scalar($params['path'])) {
                $route = trim((string) $params['path']);
                unset($params['path']);
            } elseif (isset($params['route']) && is_scalar($params['route'])) {
                $route = trim((string) $params['route']);
                unset($params['route']);
            }

            if ($route !== '') {
                $normalizedPath = '/' . ltrim($route, '/');
                $remainingQuery = http_build_query($params);
                return url($normalizedPath . ($remainingQuery !== '' ? '?' . $remainingQuery : ''));
            }

            return $intended;
        }

        if ($path !== '' && str_starts_with($path, '/admin')) {
            return url($path . ($query !== '' ? '?' . $query : ''));
        }

        if (!$isAbsolute && str_starts_with($intended, '/')) {
            return $intended;
        }

        return $intended;
    }

    /**
     * @param array<string, mixed> $parts
     */
    private function isSameOriginRedirect(array $parts): bool
    {
        $targetHost = strtolower((string) ($parts['host'] ?? ''));
        if ($targetHost === '') {
            return false;
        }

        [$currentHost, $currentPort] = $this->parseHostAndPort((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $currentHost = strtolower($currentHost);
        if ($currentHost === '' || $targetHost !== $currentHost) {
            return false;
        }

        $targetScheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $targetPort = isset($parts['port']) && is_int($parts['port']) ? $parts['port'] : null;
        $targetEffectivePort = $targetPort ?? ($targetScheme === 'https' ? 443 : 80);
        $currentEffectivePort = $currentPort ?? (((string) ($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) ($_SERVER['HTTPS'] ?? '')) !== 'off') ? 443 : 80);

        return $targetEffectivePort === $currentEffectivePort;
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function parseHostAndPort(string $hostHeader): array
    {
        $hostHeader = trim($hostHeader);
        if ($hostHeader === '') {
            return ['', null];
        }

        if (str_starts_with($hostHeader, '[')) {
            $end = strpos($hostHeader, ']');
            if ($end !== false) {
                $host = substr($hostHeader, 1, $end - 1);
                $rest = substr($hostHeader, $end + 1);
                if (str_starts_with($rest, ':')) {
                    $portRaw = substr($rest, 1);
                    if ($portRaw !== '' && ctype_digit($portRaw)) {
                        return [$host, (int) $portRaw];
                    }
                }
                return [$host, null];
            }
        }

        $firstColon = strpos($hostHeader, ':');
        $lastColon = strrpos($hostHeader, ':');
        if ($firstColon !== false && $lastColon !== false && $firstColon === $lastColon) {
            $host = substr($hostHeader, 0, $lastColon);
            $portRaw = substr($hostHeader, $lastColon + 1);
            if ($portRaw !== '' && ctype_digit($portRaw)) {
                return [$host, (int) $portRaw];
            }
        }

        return [$hostHeader, null];
    }

    private function frontendRegistrationEnabled(): bool
    {
        return false;
    }

    // --- Login ---

    public function showLogin(): void
    {
        if (is_auth()) {
            $role = auth()['role'] ?? RoleService::ROLE_MEMBER;
            $this->redirect(url(RoleService::getLoginRedirect($role)));
            return;
        }

        $rememberedUser = $this->authService->loginWithRememberToken($this->request->ip());
        if ($rememberedUser) {
            $this->authService->login($rememberedUser);
            $intended = $this->session->get('intended_url');
            $this->session->remove('intended_url');

            if ($intended) {
                $this->redirect($this->normalizeIntendedRedirect((string) $intended));
                return;
            }

            $role = $rememberedUser['role'] ?? RoleService::ROLE_MEMBER;
            $this->redirect(url(RoleService::getLoginRedirect($role)));
            return;
        }

        $this->render('Auth/Views/login', [
            'pageTitle' => __('login_title', 'Auth'),
            'registrationEnabled' => $this->frontendRegistrationEnabled(),
            ...$this->getTurnstileViewData(),
        ]);
    }

    public function login(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $email = $this->request->input('email', '');
        $password = $this->request->input('password', '');
        $remember = (bool) $this->request->input('remember', false);

        if (empty($email) || empty($password)) {
            $this->session->flash('error', __('login_required_fields', 'Auth'));
            $this->session->flash('old', ['email' => $email]);
            $this->redirect(url('/login'));
            return;
        }

        if (!$this->verifyTurnstileForAuth()) {
            $this->session->flash('old', ['email' => $email]);
            $this->redirect(url('/login'));
            return;
        }

        // Brute force check
        $ip = $this->request->ip();
        if ($this->tokenRepo->isBlocked($ip)) {
            $remaining = $this->tokenRepo->getRemainingBlockTime($ip);
            $minutes = (int) ceil($remaining / 60);
            $this->session->flash('error', __('account_locked', 'Auth', ['minutes' => $minutes]));
            $this->session->flash('old', ['email' => $email]);
            $this->redirect(url('/login'));
            return;
        }

        $user = $this->authService->attempt($email, $password);

        if ($user) {
            $this->tokenRepo->clearAttempts($ip);
            $this->tokenRepo->recordLoginAttempt($ip, $email, true);

            if ($this->shouldRequireEmail2fa($user)) {
                $disableRemember = $this->authService->shouldDisableRememberWhenEmail2faActive();
                $rememberForChallenge = $disableRemember ? false : $remember;

                if (!$this->startEmail2faChallenge($user, $rememberForChallenge, $ip)) {
                    $this->session->flash('error', __('two_factor_send_failed', 'Auth'));
                    $this->session->flash('old', ['email' => $email]);
                    $this->redirect(url('/login'));
                    return;
                }

                $this->session->flash('info', __('two_factor_sent', 'Auth', [
                    'email' => $this->maskEmail((string) ($user['email'] ?? '')),
                ]));
                $this->redirect(url('/two-factor'));
                return;
            }

            $this->authService->touchLastLogin((string) $user['id'], $ip);
            $this->authService->login($user, $remember);
            hook_run('auth.login', $user);

            $this->session->flash('success', __('login_success', 'Auth', ['name' => UserName::display($user)]));

            // Redirect to intended URL or role-based redirect
            $intended = $this->session->get('intended_url');
            $this->session->remove('intended_url');

            if ($intended) {
                $this->redirect($this->normalizeIntendedRedirect((string) $intended));
            } else {
                $role = $user['role'] ?? RoleService::ROLE_MEMBER;
                $this->redirect(url(RoleService::getLoginRedirect($role)));
            }
        } else {
            $this->tokenRepo->recordLoginAttempt($ip, $email, false);
            $failedCount = $this->tokenRepo->countFailedAttempts($ip);
            $remaining = 5 - $failedCount;

            if ($remaining > 0 && $remaining <= 3) {
                $this->session->flash('error', __('login_failed_attempts', 'Auth', ['remaining' => $remaining]));
            } else {
                $this->session->flash('error', __('login_failed', 'Auth'));
            }

            $this->session->flash('old', ['email' => $email]);
            $this->redirect(url('/login'));
        }
    }

    public function logout(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $user = auth();
        $this->authService->logout();
        hook_run('auth.logout', $user);
        $this->session->flash('success', __('logout_success', 'Auth'));
        $this->redirect(url('/login'));
    }

    // --- Two-factor (Email OTP) ---

    public function showTwoFactor(): void
    {
        if (is_auth()) {
            $role = auth()['role'] ?? RoleService::ROLE_MEMBER;
            $this->redirect(url(RoleService::getLoginRedirect($role)));
            return;
        }

        $pending = $this->session->get('two_factor_email');
        if (!is_array($pending) || empty($pending['user_id'])) {
            $this->redirect(url('/login'));
            return;
        }

        $this->render('Auth/Views/two-factor', [
            'pageTitle' => __('two_factor_title', 'Auth'),
            'maskedEmail' => $this->maskEmail((string) ($pending['sent_to'] ?? '')),
        ]);
    }

    public function verifyTwoFactor(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $pending = $this->session->get('two_factor_email');
        if (!is_array($pending) || empty($pending['user_id']) || empty($pending['code_hash'])) {
            $this->session->flash('error', __('two_factor_session_missing', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $expiresAt = (int) ($pending['expires_at'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < time()) {
            $this->session->remove('two_factor_email');
            $this->session->flash('error', __('two_factor_expired', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $attempts = (int) ($pending['attempts'] ?? 0);
        $maxAttempts = (int) ($pending['max_attempts'] ?? 5);
        if ($attempts >= $maxAttempts) {
            $this->session->remove('two_factor_email');
            $this->session->flash('error', __('two_factor_too_many_attempts', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $code = (string) $this->request->input('code', '');
        $code = preg_replace('/\\s+/', '', trim($code));

        if ($code === '' || !preg_match('/^\\d{6}$/', $code)) {
            $pending['attempts'] = $attempts + 1;
            $this->session->set('two_factor_email', $pending);
            $this->session->flash('error', __('two_factor_invalid_code', 'Auth'));
            $this->redirect(url('/two-factor'));
            return;
        }

        if (!password_verify($code, (string) $pending['code_hash'])) {
            $pending['attempts'] = $attempts + 1;
            $this->session->set('two_factor_email', $pending);
            $this->session->flash('error', __('two_factor_invalid_code', 'Auth'));
            $this->redirect(url('/two-factor'));
            return;
        }

        $users = FlatFile::for('users');
        $user = $users->find((string) $pending['user_id']);
        if (!$user) {
            $this->session->remove('two_factor_email');
            $this->session->flash('error', __('login_failed', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $status = $user['status'] ?? 'active';
        if ($status !== 'active' || (isset($user['active']) && !$user['active'])) {
            $this->session->remove('two_factor_email');
            $this->session->flash('error', __('login_failed', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        unset($user['password']);
        $remember = (bool) ($pending['remember'] ?? false);
        $ip = $this->request->ip();

        $this->authService->touchLastLogin((string) $user['id'], $ip);
        $this->authService->login($user, $remember);
        hook_run('auth.login', $user);

        $this->session->remove('two_factor_email');
        $this->session->flash('success', __('login_success', 'Auth', ['name' => UserName::display($user)]));

        $intended = $this->session->get('intended_url');
        $this->session->remove('intended_url');

        if ($intended) {
            $this->redirect($this->normalizeIntendedRedirect((string) $intended));
            return;
        }

        $role = $user['role'] ?? RoleService::ROLE_MEMBER;
        $this->redirect(url(RoleService::getLoginRedirect($role)));
    }

    public function resendTwoFactor(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $pending = $this->session->get('two_factor_email');
        if (!is_array($pending) || empty($pending['user_id']) || empty($pending['sent_to'])) {
            $this->session->flash('error', __('two_factor_session_missing', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $resendAt = (int) ($pending['resend_available_at'] ?? 0);
        if ($resendAt > time()) {
            $this->session->flash('error', __('two_factor_resend_wait', 'Auth', [
                'seconds' => max(1, $resendAt - time()),
            ]));
            $this->redirect(url('/two-factor'));
            return;
        }

        $users = FlatFile::for('users');
        $user = $users->find((string) $pending['user_id']);
        if (!$user) {
            $this->session->remove('two_factor_email');
            $this->session->flash('error', __('login_failed', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        unset($user['password']);
        $remember = (bool) ($pending['remember'] ?? false);
        $ip = $this->request->ip();

        if (!$this->startEmail2faChallenge($user, $remember, $ip, true)) {
            $this->session->flash('error', __('two_factor_send_failed', 'Auth'));
            $this->redirect(url('/two-factor'));
            return;
        }

        $this->session->flash('info', __('two_factor_resent', 'Auth'));
        $this->redirect(url('/two-factor'));
    }

    // --- Registration ---

    public function showRegister(?string $type = null): void
    {
        if (is_auth()) {
            $role = (string) (auth()['role'] ?? RoleService::ROLE_MEMBER);
            $this->redirect(url(RoleService::getLoginRedirect($role)));
            return;
        }

        if (!$this->frontendRegistrationEnabled()) {
            $this->session->flash('error', __('registration_disabled', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $roles = RoleService::getRegistrationRoles();
        $selectedRole = RoleService::normalizeRole((string) ($type ?? ''));
        if (!isset($roles[$selectedRole])) {
            $selectedRole = RoleService::ROLE_MEMBER;
        }

        $this->render('Auth/Views/register', [
            'pageTitle' => __('register_title', 'Auth'),
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            ...$this->getTurnstileViewData(),
        ]);
    }

    public function register(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        if (!$this->frontendRegistrationEnabled()) {
            $this->session->flash('error', __('registration_disabled', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $registerRedirect = url('/register');

        if (!$this->verifyTurnstileForAuth()) {
            $this->session->flash('old', [
                'first_name' => $this->request->input('first_name', ''),
                'name' => $this->request->input('name', ''),
                'email' => $this->request->input('email', ''),
                'phone' => $this->request->input('phone', ''),
                'role' => $this->request->input('role', ''),
            ]);
            $this->redirect($registerRedirect);
            return;
        }

        $data = $this->request->only(['first_name', 'name', 'email', 'phone', 'password', 'password_confirmation', 'role', 'terms']);
        $data = UserName::forStorage($data);

        // Validate required fields
        if (empty($data['first_name']) || empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            $this->session->flash('error', __('register_required_fields', 'Auth'));
            $this->session->flash('old', $data);
            $this->redirect($registerRedirect);
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', __('invalid_email', 'Auth'));
            $this->session->flash('old', $data);
            $this->redirect($registerRedirect);
            return;
        }

        // Check email unique
        $users = FlatFile::for('users');
        if ($users->findBy('email', $data['email'])) {
            $this->session->flash('error', __('email_exists', 'Auth'));
            $this->session->flash('old', $data);
            $this->redirect($registerRedirect);
            return;
        }

        // Validate password confirmation
        if ($data['password'] !== ($data['password_confirmation'] ?? '')) {
            $this->session->flash('error', __('password_mismatch', 'Auth'));
            $this->session->flash('old', $data);
            $this->redirect($registerRedirect);
            return;
        }

        // Validate password strength
        $pwErrors = $this->authService->validatePassword($data['password']);
        if (!empty($pwErrors)) {
            $this->session->flash('error', __('password_too_weak', 'Auth'));
            $this->session->flash('old', $data);
            $this->redirect($registerRedirect);
            return;
        }

        // Validate role
        $registrationRoles = RoleService::getRegistrationRoles();
        $role = $data['role'] ?? RoleService::ROLE_MEMBER;
        if (!isset($registrationRoles[$role])) {
            $role = RoleService::ROLE_MEMBER;
        }

        // Validate terms
        if (empty($data['terms'])) {
            $this->session->flash('error', __('terms_required', 'Auth'));
            $this->session->flash('old', $data);
            $this->redirect($registerRedirect);
            return;
        }

        // Build user data
        $userData = [
            'first_name' => $data['first_name'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => trim((string) ($data['phone'] ?? '')),
            'password' => $data['password'],
            'role' => $role,
        ];

        $newUser = $this->authService->register($userData);

        // Auto-login (optional if 2FA enabled for this role)
        unset($newUser['password']);
        hook_run('auth.register', $newUser);

        if ($this->shouldRequireEmail2fa($newUser)) {
            $this->session->flash('success', __('register_success', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $this->authService->login($newUser);
        $this->session->flash('success', __('register_success', 'Auth'));
        $this->redirect(url(RoleService::getLoginRedirect($role)));
    }

    // --- Forgot Password ---

    public function showForgotPassword(): void
    {
        if (is_auth()) {
            $role = (string) (auth()['role'] ?? RoleService::ROLE_MEMBER);
            $this->redirect(url(RoleService::getLoginRedirect($role)));
            return;
        }

        $this->render('Auth/Views/forgot-password', [
            'pageTitle' => __('forgot_password_title', 'Auth'),
            ...$this->getTurnstileViewData(),
        ]);
    }

    public function sendResetLink(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        if (!$this->verifyTurnstileForAuth()) {
            $this->session->flash('old', ['email' => $this->request->input('email', '')]);
            $this->redirect(url('/forgot-password'));
            return;
        }

        $email = $this->request->input('email', '');

        if (empty($email)) {
            $this->session->flash('error', __('email_required', 'Auth'));
            $this->redirect(url('/forgot-password'));
            return;
        }

        $users = FlatFile::for('users');
        $user = $users->findBy('email', $email);

        // Always show success to prevent email enumeration
        if ($user) {
            $token = $this->tokenRepo->createResetToken($email);
            $resetUrl = url('/reset-password/' . $token);

            $mailer = new Mailer();
            $subject = __('reset_password_title', 'Auth') . ' - ' . config('app.name', 'FlatCMS');
            $body = __('reset_link_email_intro', 'Auth') . "\n\n" . $resetUrl . "\n\n" . __('reset_link_email_ignore', 'Auth');
            $sent = $mailer->send($email, $subject, $body);

            // Dev fallback (do not leak tokens in production)
            if (!$sent && (bool) env('APP_DEBUG', false)) {
                $this->session->flash('reset_url', $resetUrl);
            }
        }

        $this->session->flash('success', __('reset_link_sent', 'Auth'));
        $this->redirect(url('/forgot-password'));
    }

    public function showResetPassword(string $token): void
    {
        $tokenData = $this->tokenRepo->verifyResetToken($token);

        if (!$tokenData) {
            $this->session->flash('error', __('invalid_reset_token', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $this->render('Auth/Views/reset-password', [
            'pageTitle' => __('reset_password_title', 'Auth'),
            'token' => $token,
            'email' => $tokenData['email'],
        ]);
    }

    public function resetPassword(string $token): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $tokenData = $this->tokenRepo->verifyResetToken($token);

        if (!$tokenData) {
            $this->session->flash('error', __('invalid_reset_token', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $password = $this->request->input('password', '');
        $confirmation = $this->request->input('password_confirmation', '');

        if ($password !== $confirmation) {
            $this->session->flash('error', __('password_mismatch', 'Auth'));
            $this->redirect(url('/reset-password/' . $token));
            return;
        }

        $pwErrors = $this->authService->validatePassword($password);
        if (!empty($pwErrors)) {
            $this->session->flash('error', __('password_too_weak', 'Auth'));
            $this->redirect(url('/reset-password/' . $token));
            return;
        }

        $users = FlatFile::for('users');
        $user = $users->findBy('email', $tokenData['email']);

        if (!$user) {
            $this->session->flash('error', __('user_not_found', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }

        $this->authService->updatePassword((string) $user['id'], $password);
        $this->tokenRepo->deleteToken($token);

        hook_run('auth.password_reset', $user);

        $this->session->flash('success', __('password_reset_success', 'Auth'));
        $this->redirect(url('/login'));
    }

    // --- Profile ---

    public function showProfile(): void
    {
        if (!$this->authorize('profile.view')) {
            return;
        }

        I18n::load('Users');
        $users = FlatFile::for('users');
        
        // Get authenticated user
        $authUser = auth();
        if (!$authUser || !isset($authUser['id'])) {
            $this->session->flash('error', __('not_authenticated', 'Auth'));
            $this->redirect(url('/login'));
            return;
        }
        
        // ALWAYS fetch fresh user data from database (not from session)
        $user = $users->find((string) $authUser['id']);
        
        // Si l'utilisateur n'existe plus en base, déconnecter
        if (!$user) {
            $this->session->flash('error', __('user_not_found', 'Auth'));
            $this->authService->logout();
            $this->redirect(url('/login'));
            return;
        }
        
        // Update session with fresh data to keep it in sync
        unset($user['password']);
        $this->session->set('user', UserName::forSession($user));
        $user = UserName::forForm($user);

        $normalizedRole = RoleService::normalizeRole((string) ($user['role'] ?? RoleService::ROLE_MEMBER));
        $canManageLicenses = RoleService::hasPermission($normalizedRole, 'licenses.manage');

        $this->render('Auth/Views/profile', [
            'pageTitle' => __('my_profile', 'Users'),
            'user' => $user,
            'languages' => $this->getLanguageOptions(),
            'adminLanguage' => $user['admin_language'] ?? '',
            'canManageLicenses' => $canManageLicenses,
            'profileLicenses' => $canManageLicenses ? $this->buildProfileLicenses() : [],
            'licenseProfileConfig' => $canManageLicenses ? [
                'csrfToken' => csrf_token(),
                'copySuccess' => __('license_profile_copy_success', 'Auth'),
                'copyError' => __('license_profile_copy_error', 'Auth'),
            ] : [],
        ], 'admin.main');
    }

    public function updateProfile(): void
    {
        if (!$this->authorize('profile.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        I18n::load('Users');
        $userId = (string) auth()['id'];
        $users = FlatFile::for('users');
        $user = $users->find($userId);
        if (!$user) {
            $this->session->flash('error', __('user_not_found', 'Users'));
            $this->redirect(url('/admin/profile'));
            return;
        }
        $data = $this->request->only(['first_name', 'name', 'email', 'bio', 'phone', 'company', 'admin_language']);
        $data = UserName::forStorage($data);
        $availableLocales = \App\Core\I18n::getSupportedLocales();
        $selectedLocale = trim((string) ($data['admin_language'] ?? ''));
        if ($selectedLocale !== '' && !in_array($selectedLocale, $availableLocales, true)) {
            $selectedLocale = '';
        }
        $data['admin_language'] = $selectedLocale;

        if (empty($data['first_name']) || empty($data['name']) || empty($data['email'])) {
            $this->session->flash('error', __('validation.required_fields', 'Users'));
            $this->redirect(url('/admin/profile'));
            return;
        }

        // Check email unique
        $existing = $users->findBy('email', $data['email']);
        if ($existing && $existing['id'] !== $userId) {
            $this->session->flash('error', __('email_exists', 'Users'));
            $this->redirect(url('/admin/profile'));
            return;
        }

        $removeAvatar = $this->request->input('avatar_remove') === '1';
        $oldAvatar = $user['avatar'] ?? '';

        // Handle avatar upload
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $avatar = $this->handleAvatarUpload($_FILES['avatar']);
            if ($avatar) {
                $data['avatar'] = $avatar;
                if (!empty($oldAvatar)) {
                    $this->deleteAvatarFile($user, $users);
                }
                $removeAvatar = false;
            }
        }

        if ($removeAvatar) {
            $data['avatar'] = '';
            if (!empty($oldAvatar)) {
                $this->deleteAvatarFile($user, $users);
            }
        }

        $users->update($userId, $data);

        // Update session
        $updatedUser = $users->find($userId);
        unset($updatedUser['password']);
        $this->session->set('user', UserName::forSession($updatedUser));

        $this->session->flash('success', __('profile_updated', 'Users'));
        $this->redirect(url('/admin/profile'));
    }

    public function requestLicenseReveal(string $module): void
    {
        if (!$this->authorizeLicenseApi('licenses.manage')) {
            return;
        }

        if (!$this->verifyProfileApiCsrf()) {
            return;
        }

        $catalog = new LicenseCatalogService();
        if (!$catalog->has($module)) {
            $this->json([
                'success' => false,
                'message' => __('license_profile_reveal_unavailable', 'Auth'),
            ], 404);
            return;
        }

        $user = $this->loadAuthenticatedUserRecord();
        if ($user === null) {
            return;
        }

        $service = new LicenseRevealService();
        $result = $service->beginChallenge($user, $module, $this->request->ip(), $this->request->userAgent());
        $status = (int) ($result['status'] ?? ($result['success'] ? 200 : 422));
        unset($result['status']);

        $this->json($result, $status);
    }

    public function verifyLicenseReveal(string $module): void
    {
        if (!$this->authorizeLicenseApi('licenses.reveal')) {
            return;
        }

        if (!$this->verifyProfileApiCsrf()) {
            return;
        }

        $catalog = new LicenseCatalogService();
        if (!$catalog->has($module)) {
            $this->json([
                'success' => false,
                'message' => __('license_profile_reveal_unavailable', 'Auth'),
            ], 404);
            return;
        }

        $user = $this->loadAuthenticatedUserRecord();
        if ($user === null) {
            return;
        }

        $payload = $this->request->isJson() ? $this->request->json() : $this->request->all();
        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            $this->json([
                'success' => false,
                'message' => __('license_profile_reveal_invalid_code', 'Auth'),
            ], 422);
            return;
        }

        $service = new LicenseRevealService();
        $result = $service->verifyChallenge($user, $module, $code, $this->request->ip(), $this->request->userAgent());
        $status = (int) ($result['status'] ?? ($result['success'] ? 200 : 422));
        unset($result['status']);

        $this->json($result, $status);
    }

    public function serveAvatar(string $id): void
    {
        $auth = auth();
        if (!$auth || (string) ($auth['id'] ?? '') !== (string) $id) {
            http_response_code(403);
            exit;
        }

        $users = FlatFile::for('users');
        $user = $users->find((string) $id);

        if (!$user || empty($user['avatar'])) {
            http_response_code(404);
            exit;
        }

        $filePath = $this->resolveAvatarPath($user, $users);
        if (!$filePath || !is_file($filePath)) {
            http_response_code(404);
            exit;
        }

        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    private function buildProfileLicenses(): array
    {
        $catalog = new LicenseCatalogService();
        $vault = new LicenseVaultService();
        $moduleManager = new ModuleManager();
        $licenseService = new ExtensionLicenseService($moduleManager, $vault);
        $licenses = [];

        foreach ($catalog->all() as $code => $definition) {
            $moduleName = (string) ($definition['module_name'] ?? '');
            if ($moduleName === '' || $moduleManager->get($moduleName) === null) {
                continue;
            }

            $profile = $licenseService->describe($moduleName);
            if (!is_array($profile)) {
                continue;
            }

            $license = is_array($profile['license_summary'] ?? null) ? $profile['license_summary'] : $vault->getModuleLicense($code);
            $hasLicense = trim((string) ($license['license_id'] ?? '')) !== ''
                || trim((string) ($license['masked_key'] ?? '')) !== '';
            $moduleEnabled = (bool) ($profile['enabled'] ?? $moduleManager->isEnabled($moduleName));
            [$statusBadgeClass, $statusBadgeLabel] = $this->resolveProfileLicenseStatusBadge((string) ($profile['status'] ?? 'missing'));
            $description = trim((string) ($definition['description'] ?? ''));
            $title = trim((string) ($definition['title'] ?? ''));

            $licenses[] = [
                'code' => $code,
                'module_name' => $moduleName,
                'title' => $title !== '' ? $title : $moduleName,
                'description' => $description,
                'icon' => (string) ($definition['icon'] ?? 'fas fa-key'),
                'has_license' => $hasLicense,
                'license' => $license,
                'license_profile' => $profile,
                'module_enabled' => $moduleEnabled,
                'module_badge_class' => $moduleEnabled ? 'badge-info' : 'badge-secondary',
                'module_badge_label' => $moduleEnabled
                    ? __('license_profile_module_enabled', 'Auth')
                    : __('license_profile_module_disabled', 'Auth'),
                'status_badge_class' => $statusBadgeClass,
                'status_badge_label' => $statusBadgeLabel,
            ];
        }

        return $licenses;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveProfileLicenseStatusBadge(string $status): array
    {
        return match ($status) {
            'active' => ['badge-success', __('license_profile_status_active', 'Auth')],
            'local_bypass' => ['badge-info', __('license_profile_status_local', 'Auth')],
            'inactive', 'invalid_domain' => ['badge-warning', __('license_profile_status_invalid', 'Auth')],
            default => ['badge-secondary', __('license_profile_status_missing', 'Auth')],
        };
    }

    private function getLanguageOptions(): array
    {
        $languages = [];
        $langPath = BASE_PATH . '/data/languages';

        if (is_dir($langPath)) {
            foreach (glob($langPath . '/*.json') as $file) {
                $code = basename($file, '.json');
                $config = json_read($file);
                if (is_array($config)) {
                    $languages[$code] = $config;
                } else {
                    $languages[$code] = [
                        'name' => strtoupper($code),
                        'native' => strtoupper($code),
                        'active' => true,
                    ];
                }
            }
        }

        if (empty($languages)) {
            foreach (\App\Core\I18n::getSupportedLocales() as $code) {
                $languages[$code] = [
                    'name' => strtoupper($code),
                    'native' => strtoupper($code),
                    'active' => true,
                ];
            }
        }

        $languages = \App\Core\I18n::localizeLanguageCatalog($languages, \App\Core\I18n::getLocale());
        ksort($languages);
        return $languages;
    }

    private function authorizeLicenseApi(string $permission): bool
    {
        $authUser = auth();
        if (!$authUser || !isset($authUser['id'])) {
            $this->json([
                'success' => false,
                'message' => __('not_authenticated', 'Auth'),
            ], 401);
            return false;
        }

        $role = RoleService::normalizeRole((string) ($authUser['role'] ?? RoleService::ROLE_MEMBER));
        if (!RoleService::hasPermission($role, $permission)) {
            $this->json([
                'success' => false,
                'message' => __('error.unauthorized', 'Core'),
            ], 403);
            return false;
        }

        return true;
    }

    private function verifyProfileApiCsrf(): bool
    {
        $token = (string) ($this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN'));
        if ($token === '' || !$this->session->verifyToken($token)) {
            $this->json([
                'success' => false,
                'message' => __('error.csrf', 'Core'),
            ], 419);
            return false;
        }

        return true;
    }

    private function loadAuthenticatedUserRecord(): ?array
    {
        $authUser = auth();
        if (!$authUser || !isset($authUser['id'])) {
            $this->json([
                'success' => false,
                'message' => __('not_authenticated', 'Auth'),
            ], 401);
            return null;
        }

        $users = FlatFile::for('users');
        $user = $users->find((string) $authUser['id']);
        if (!$user) {
            $this->json([
                'success' => false,
                'message' => __('user_not_found', 'Auth'),
            ], 404);
            return null;
        }

        return $user;
    }

    // --- Change Password ---

    public function showChangePassword(): void
    {
        if (!$this->authorize('profile.edit')) {
            return;
        }

        $this->render('Auth/Views/change-password', [
            'pageTitle' => __('change_password_title', 'Auth'),
        ], 'admin.main');
    }

    private function getTurnstileViewData(): array
    {
        $turnstile = new Turnstile();
        $enabled = $turnstile->isEnabled();
        $siteKey = $turnstile->siteKey();

        if (!$enabled || $siteKey === '') {
            return [
                'turnstileEnabled' => false,
                'turnstileSiteKey' => '',
            ];
        }

        return [
            'turnstileEnabled' => true,
            'turnstileSiteKey' => $siteKey,
        ];
    }

    private function verifyTurnstileForAuth(): bool
    {
        $turnstile = new Turnstile();
        if (!$turnstile->isEnabled()) {
            return true;
        }

        if ($turnstile->siteKey() === '' || $turnstile->secretKey() === '') {
            $this->session->flash('error', __('captcha_misconfigured', 'Auth'));
            return false;
        }

        $token = (string) $this->request->input('cf-turnstile-response', '');
        $start = microtime(true);
        $result = $turnstile->verify($token, $this->request->ip());
        $elapsedMs = (int) round((microtime(true) - $start) * 1000);
        $slowThresholdMs = (int) env('TURNSTILE_SLOW_LOG_THRESHOLD_MS', 1500);
        if ($slowThresholdMs < 200) {
            $slowThresholdMs = 200;
        }
        if ($elapsedMs >= $slowThresholdMs) {
            error_log(sprintf('[FlatCMS] Slow Turnstile verify (%d ms)', $elapsedMs));
        }

        if (!($result['success'] ?? false)) {
            $this->session->flash('error', __('captcha_failed', 'Auth'));
            return false;
        }

        return true;
    }

    private function shouldRequireEmail2fa(array $user): bool
    {
        return $this->authService->shouldRequireEmail2fa($user);
    }

    private function startEmail2faChallenge(array $user, bool $remember, string $ipAddress, bool $isResend = false): bool
    {
        $challengeStart = microtime(true);
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $ttl = (int) env('AUTH_2FA_EMAIL_TTL', 300);
        if ($ttl < 60) {
            $ttl = 60;
        }
        $cooldown = (int) env('AUTH_2FA_EMAIL_RESEND_COOLDOWN', 60);
        if ($cooldown < 10) {
            $cooldown = 10;
        }
        $maxAttempts = (int) env('AUTH_2FA_EMAIL_MAX_ATTEMPTS', 5);
        if ($maxAttempts < 3) {
            $maxAttempts = 3;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);

        // Regenerate session ID once we have a verified password (prevents fixation).
        if (!$isResend) {
            $this->session->regenerate();
        }

        $this->session->set('two_factor_email', [
            'user_id' => (string) ($user['id'] ?? ''),
            'sent_to' => $email,
            'remember' => $remember,
            'code_hash' => $codeHash,
            'expires_at' => time() + $ttl,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'resend_available_at' => time() + $cooldown,
            'ip' => $ipAddress,
        ]);

        $appName = (string) config('app.name', 'FlatCMS');
        $subject = $appName . ' - ' . __('two_factor_email_subject', 'Auth');
        $body = __('two_factor_email_intro', 'Auth') . "\n\n" .
            $code . "\n\n" .
            __('two_factor_email_expires', 'Auth', ['minutes' => (int) ceil($ttl / 60)]) . "\n\n" .
            __('two_factor_email_security', 'Auth');

        $mailer = new Mailer();
        $sent = $mailer->send($email, $subject, $body);
        $elapsedMs = (int) round((microtime(true) - $challengeStart) * 1000);
        $slowThresholdMs = (int) env('AUTH_2FA_SLOW_LOG_THRESHOLD_MS', 2000);
        if ($slowThresholdMs < 250) {
            $slowThresholdMs = 250;
        }
        if ($elapsedMs >= $slowThresholdMs) {
            error_log(sprintf(
                '[FlatCMS] Slow 2FA challenge (%d ms) role=%s resend=%s',
                $elapsedMs,
                (string) ($user['role'] ?? ''),
                $isResend ? '1' : '0'
            ));
        }

        if (!$sent) {
            $this->session->remove('two_factor_email');
            return false;
        }

        // Dev helper: show OTP only in debug mode (never in production)
        if ((bool) env('APP_DEBUG', false)) {
            $this->session->flash('two_factor_code_dev', $code);
        }

        return true;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = (string) $local;
        $domain = (string) $domain;

        $prefix = substr($local, 0, 2);
        if ($prefix === false) {
            $prefix = '';
        }
        $maskedLocal = $prefix . str_repeat('*', max(3, strlen($local) - strlen($prefix)));

        return $maskedLocal . '@' . $domain;
    }

    public function changePassword(): void
    {
        if (!$this->authorize('profile.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $userId = (string) auth()['id'];
        $current = $this->request->input('current_password', '');
        $newPassword = $this->request->input('password', '');
        $confirmation = $this->request->input('password_confirmation', '');

        // Verify current password
        if (!$this->authService->verifyPassword($userId, $current)) {
            $this->session->flash('error', __('current_password_incorrect', 'Auth'));
            $this->redirect(url('/admin/change-password'));
            return;
        }

        // Validate new password
        if ($newPassword !== $confirmation) {
            $this->session->flash('error', __('password_mismatch', 'Auth'));
            $this->redirect(url('/admin/change-password'));
            return;
        }

        $pwErrors = $this->authService->validatePassword($newPassword);
        if (!empty($pwErrors)) {
            $this->session->flash('error', __('password_too_weak', 'Auth'));
            $this->redirect(url('/admin/change-password'));
            return;
        }

        $this->authService->updatePassword($userId, $newPassword);

        $this->session->flash('success', __('password_changed_success', 'Auth'));
        $this->redirect(url('/admin/profile'));
    }

    // --- Helpers ---

    private function handleAvatarUpload(array $file): ?string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file['type'], $allowed, true)) {
            return null;
        }

        if ($file['size'] > 2 * 1024 * 1024) { // 2MB
            return null;
        }

        $ext = match ($file['type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = 'avatar_' . auth()['id'] . '_' . time() . '.' . $ext;
        $uploadDir = BASE_PATH . '/storage/uploads/avatars';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'avatars/' . $filename;
        }

        return null;
    }

    private function resolveAvatarPath(array $user, FlatFile $users): ?string
    {
        $avatar = $user['avatar'] ?? '';
        if ($avatar === '') {
            return null;
        }

        $normalized = ltrim($avatar, '/');

        // Legacy public path: /uploads/avatars/...
        if (str_starts_with($normalized, 'uploads/avatars/')) {
            $legacyPath = BASE_PATH . '/public/' . $normalized;
            if (!is_file($legacyPath)) {
                return null;
            }

            $filename = basename($legacyPath);
            $storageDir = BASE_PATH . '/storage/uploads/avatars';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $newPath = $storageDir . '/' . $filename;
            if (!is_file($newPath)) {
                @rename($legacyPath, $newPath);
            }

            if (is_file($newPath)) {
                $users->update($user['id'], ['avatar' => 'avatars/' . $filename]);
                if ((string) auth()['id'] === (string) $user['id']) {
                    $updatedUser = $users->find($user['id']);
                    if ($updatedUser) {
                        unset($updatedUser['password']);
                        $this->session->set('user', UserName::forSession($updatedUser));
                    }
                }
                return $newPath;
            }

            return $legacyPath;
        }

        if (str_starts_with($normalized, 'avatars/')) {
            return BASE_PATH . '/storage/uploads/' . $normalized;
        }

        if (str_starts_with($normalized, 'storage/uploads/avatars/')) {
            return BASE_PATH . '/' . $normalized;
        }

        return null;
    }

    private function deleteAvatarFile(array $user, FlatFile $users): void
    {
        $path = $this->resolveAvatarPath($user, $users);
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    private function redirectCommerceAccountIfNeeded(string $targetPath): bool
    {
        return false;
    }
}
