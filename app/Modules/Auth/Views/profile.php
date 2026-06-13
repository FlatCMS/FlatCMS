<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Auth', 'css/auth-module.css') ?>">

<div class="page-header">
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
</div>

<div class="card">
    <form method="POST" action="<?= url('/admin/profile') ?>" enctype="multipart/form-data" id="profileForm">
        <?= csrf_field() ?>

        <div class="form-layout-columns">
            <!-- Left column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="name" class="form-label"><?= __('name', 'Users') ?> *</label>
                    <input type="text" id="name" name="name" class="form-input"
                        value="<?= e(old('name', $user['name'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label"><?= __('email', 'Users') ?> *</label>
                    <input type="email" id="email" name="email" class="form-input"
                        value="<?= e(old('email', $user['email'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label"><?= __('phone', 'Users') ?></label>
                    <input type="tel" id="phone" name="phone" class="form-input"
                        value="<?= htmlspecialchars(old('phone') ?? ($user['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group">
                    <label for="company" class="form-label"><?= __('company', 'Users') ?></label>
                    <input type="text" id="company" name="company" class="form-input"
                        value="<?= htmlspecialchars(old('company') ?? ($user['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group">
                    <label for="bio" class="form-label"><?= __('bio', 'Users') ?></label>
                    <textarea id="bio" name="bio" class="form-input" rows="4" data-no-editor><?= e(old('bio') ?? ($user['bio'] ?? '')) ?></textarea>
                </div>
            </div>

            <!-- Right column -->
            <div class="form-column">
                <div class="form-group">
                    <label class="form-label"><?= __('role', 'Users') ?></label>
                    <div class="profile-role-box">
                        <?php
                            $normalizedRole = \App\Modules\Auth\Services\RoleService::normalizeRole((string) ($user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER));
                            $roleMeta = \App\Modules\Auth\Services\RoleService::ROLES[$normalizedRole] ?? [];
                            $badgeClass = $roleMeta['badge_class'] ?? 'badge-secondary';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= __('role_' . $normalizedRole, 'Users') ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="admin_language" class="form-label"><?= __('backend_language', 'Users') ?></label>
                    <select id="admin_language" name="admin_language" class="form-select">
                        <option value=""><?= __('backend_language_default', 'Users') ?></option>
                        <?php foreach (($languages ?? []) as $code => $lang): ?>
                            <?php
                                $label = $lang['native'] ?? $lang['name'] ?? $code;
                                if (!empty($lang['name']) && !empty($lang['native']) && $lang['name'] !== $lang['native']) {
                                    $label = $lang['native'] . ' (' . $lang['name'] . ')';
                                }
                            ?>
                            <option value="<?= e($code) ?>" <?= ($adminLanguage ?? '') === $code ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint"><?= __('backend_language_hint', 'Users') ?></div>
                    <div class="form-hint"><?= __('backend_language_scope_hint', 'Users') ?></div>
                </div>

                <!-- AVATAR SECTION - MODERN DESIGN -->
                <div class="form-group">
                    <label class="form-label"><?= __('avatar', 'Users') ?></label>
                    
                    <?php $avatarUrl = avatar_url($user); ?>
                    <div class="avatar-upload-container"
                        data-msg-invalid-type="<?= __('invalid_file_type', 'Auth') ?>"
                        data-msg-file-too-large="<?= __('file_too_large', 'Auth') ?>"
                        data-msg-confirm-remove="<?= __('confirm_remove_avatar', 'Auth') ?>">
                        <!-- Preview Circle -->
                        <div class="avatar-preview-wrapper">
                            <div class="avatar-preview" id="avatarPreview">
                                <?php if (!empty($avatarUrl)): ?>
                                    <img src="<?= $avatarUrl ?>" alt="Avatar" class="avatar-image" id="avatarImage">
                                <?php else: ?>
                                    <div class="avatar-placeholder" id="avatarPlaceholder">
                                        <svg class="avatar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Overlay on hover -->
                                <div class="avatar-overlay" id="avatarOverlay">
                                    <svg class="avatar-upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="avatar-overlay-text"><?= __('change_avatar', 'Users') ?></span>
                                </div>
                            </div>

                            <!-- File input hidden -->
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" class="hidden">
                            <input type="hidden" id="avatarRemove" name="avatar_remove" value="0">
                        </div>

                        <!-- Upload buttons and info -->
                        <div class="avatar-actions">
                            <button type="button" class="btn-avatar-upload" id="btnSelectAvatar">
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?= __('select_photo', 'Users') ?></span>
                            </button>

                            <?php if (!empty($avatarUrl)): ?>
                            <button type="button" class="btn-avatar-remove" id="btnRemoveAvatar">
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>

                        <!-- Hints -->
                        <div class="avatar-hints">
                            <p class="avatar-hint">
                                <svg class="hint-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <?= __('avatar_hint', 'Users') ?>
                            </p>
                            <p class="avatar-filename" id="avatarFilename"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions form-actions-divider">
            <button type="submit" class="btn btn-primary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <?= __('save', 'Core') ?>
            </button>
            <a href="<?= url('/admin/change-password') ?>" class="btn btn-secondary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                <?= __('change_password', 'Auth') ?>
            </a>
        </div>
    </form>
</div>

<?php
$canManageLicenses = !empty($canManageLicenses);
$profileLicenses = is_array($profileLicenses ?? null) ? $profileLicenses : [];
$licenseProfileConfig = is_array($licenseProfileConfig ?? null) ? $licenseProfileConfig : [];
$profileLicenseLocale = locale();
$profileLicenseDateFormat = $profileLicenseLocale === 'en-US' ? 'm/d/Y' : 'd/m/Y';
$profileLicenseTimeFormat = 'H:i:s';
?>

<?php if ($canManageLicenses): ?>
<div class="card profile-license-card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><?= __('license_profile_title', 'Auth') ?></h2>
            <p class="card-subtitle"><?= __('license_profile_subtitle', 'Auth') ?></p>
        </div>
    </div>
    <div class="card-body">
        <div class="profile-license-grid">
            <?php foreach ($profileLicenses as $licenseCard): ?>
                <?php
                    $license = is_array($licenseCard['license'] ?? null) ? $licenseCard['license'] : [];
                    $moduleCode = (string) ($licenseCard['code'] ?? '');
                    $maskedValue = trim((string) ($license['masked_key'] ?? ''));
                    if ($maskedValue === '') {
                        $maskedValue = __('license_profile_hidden_value', 'Auth');
                    }
                    $domainValue = trim((string) ($license['domain'] ?? ''));
                    if ($domainValue === '') {
                        $domainValue = __('license_profile_not_configured', 'Auth');
                    }
                    $updatedAt = trim((string) ($license['updated_at'] ?? ''));
                    if ($updatedAt === '') {
                        $updatedAt = __('license_profile_not_configured', 'Auth');
                    } else {
                        $updatedAt = __('license_profile_datetime', 'Auth', [
                            'date' => human_date($updatedAt, $profileLicenseDateFormat, $profileLicenseLocale),
                            'time' => human_date($updatedAt, $profileLicenseTimeFormat, $profileLicenseLocale),
                        ]);
                    }
                ?>
                <article class="profile-license-item">
                    <div class="profile-license-item-head">
                        <div class="profile-license-item-title-wrap">
                            <span class="profile-license-item-icon">
                                <i class="<?= e((string) ($licenseCard['icon'] ?? 'fas fa-key')) ?>" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3 class="profile-license-item-title"><?= e((string) ($licenseCard['title'] ?? '')) ?></h3>
                                <p class="profile-license-item-description"><?= e((string) ($licenseCard['description'] ?? '')) ?></p>
                            </div>
                        </div>
                        <div class="profile-license-item-badges">
                            <span class="badge <?= e((string) ($licenseCard['module_badge_class'] ?? 'badge-secondary')) ?>">
                                <?= e((string) ($licenseCard['module_badge_label'] ?? '')) ?>
                            </span>
                            <span class="badge <?= e((string) ($licenseCard['status_badge_class'] ?? 'badge-secondary')) ?>">
                                <?= e((string) ($licenseCard['status_badge_label'] ?? '')) ?>
                            </span>
                        </div>
                    </div>

                    <div class="profile-license-meta">
                        <div class="profile-license-meta-row">
                            <span class="profile-license-meta-label"><?= __('license_profile_domain_label', 'Auth') ?></span>
                            <span class="profile-license-meta-value"><?= e($domainValue) ?></span>
                        </div>
                        <div class="profile-license-meta-row">
                            <span class="profile-license-meta-label"><?= __('license_profile_updated_label', 'Auth') ?></span>
                            <span class="profile-license-meta-value"><?= e($updatedAt) ?></span>
                        </div>
                    </div>

                    <div class="form-group form-group-sm profile-license-field">
                        <label class="form-label"><?= __('license_profile_key_label', 'Auth') ?></label>
                        <div class="profile-license-key-row">
                            <input type="text" class="form-input profile-license-key-input" value="<?= e($maskedValue) ?>" readonly>
                            <button
                                type="button"
                                class="btn btn-secondary profile-license-reveal-btn"
                                data-license-reveal="<?= e($moduleCode) ?>"
                                data-license-request-url="<?= e(url('/admin/profile/licenses/' . $moduleCode . '/request')) ?>"
                                data-license-verify-url="<?= e(url('/admin/profile/licenses/' . $moduleCode . '/verify')) ?>"
                                data-license-title="<?= e((string) ($licenseCard['title'] ?? '')) ?>"
                                <?= !empty($licenseCard['has_license']) ? '' : 'disabled' ?>
                            >
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span><?= __('license_profile_reveal_button', 'Auth') ?></span>
                            </button>
                        </div>
                        <span class="form-hint">
                            <?= !empty($licenseCard['has_license'])
                                ? __('license_profile_reveal_hint', 'Auth')
                                : __('license_profile_no_key', 'Auth') ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div
    id="authProfileLicensesConfig"
    data-auth-profile-licenses='<?= e(json_encode($licenseProfileConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
></div>

<div class="modal-overlay is-initially-hidden" id="licenseRevealModal" aria-hidden="true">
    <div class="modal-container modal-md">
        <div class="modal-header">
            <h3 class="modal-title" id="licenseRevealModalTitle">
                <i class="fas fa-key modal-icon-info"></i>
                <?= __('license_profile_modal_title', 'Auth') ?>
            </h3>
            <button type="button" class="modal-close" data-modal-close="licenseRevealModal">&times;</button>
        </div>
        <div class="modal-body">
            <p class="auth-license-modal-intro" id="licenseRevealModalIntro"><?= __('license_profile_modal_intro', 'Auth') ?></p>

            <div class="auth-license-modal-dev hidden" id="licenseRevealDevBlock">
                <span class="auth-license-modal-dev-label"><?= __('license_profile_dev_code_label', 'Auth') ?></span>
                <code class="auth-license-modal-dev-code" id="licenseRevealDevCode"></code>
            </div>

            <div class="form-group">
                <label for="licenseRevealCodeInput" class="form-label"><?= __('license_profile_code_label', 'Auth') ?></label>
                <input
                    type="text"
                    id="licenseRevealCodeInput"
                    class="form-input"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="<?= __('license_profile_code_placeholder', 'Auth') ?>"
                >
                <span class="form-hint" id="licenseRevealCodeHint"><?= __('license_profile_code_hint', 'Auth') ?></span>
            </div>

            <div class="form-group hidden" id="licenseRevealKeyGroup">
                <label for="licenseRevealKeyInput" class="form-label"><?= __('license_profile_revealed_key_label', 'Auth') ?></label>
                <div class="profile-license-key-row">
                    <input type="text" id="licenseRevealKeyInput" class="form-input profile-license-key-input" readonly>
                    <button type="button" class="btn btn-secondary profile-license-copy-btn" id="licenseRevealCopyBtn">
                        <i class="fas fa-copy" aria-hidden="true"></i>
                        <span><?= __('license_profile_copy_button', 'Auth') ?></span>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="licenseRevealResendBtn"><?= __('license_profile_resend_button', 'Auth') ?></button>
            <button type="button" class="btn btn-primary" id="licenseRevealVerifyBtn"><?= __('license_profile_verify_button', 'Auth') ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?= module_asset('Auth', 'js/auth-module.js') ?>" defer></script>
<script src="<?= module_asset('Auth', 'js/avatar-upload.js') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Auth/Assets/js/avatar-upload.js') ?>"></script>
