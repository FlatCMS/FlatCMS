<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('form_open')) {
    function form_open(string $action, string $method = 'POST', array $attributes = []): string
    {
        $realMethod = strtoupper($method);
        $formMethod = in_array($realMethod, ['GET', 'POST']) ? $realMethod : 'POST';
        
        $attrs = [];
        $attrs['action'] = $action;
        $attrs['method'] = $formMethod;
        
        foreach ($attributes as $key => $value) {
            $attrs[$key] = $value;
        }

        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= ' ' . $key . '="' . e($value) . '"';
        }

        $html = '<form' . $attrString . '>';
        $html .= csrf_field();
        
        // Add method spoofing for PUT, DELETE, PATCH
        if (!in_array($realMethod, ['GET', 'POST'])) {
            $html .= method_field($realMethod);
        }

        return $html;
    }
}

if (!function_exists('form_close')) {
    function form_close(): string
    {
        return '</form>';
    }
}

if (!function_exists('form_input')) {
    function form_input(string $name, ?string $value = null, array $attributes = []): string
    {
        $type = strtolower((string) ($attributes['type'] ?? 'text'));
        unset($attributes['type']);

        $nonAutocompletableTypes = ['hidden', 'checkbox', 'radio', 'file', 'submit', 'button', 'reset', 'image'];
        if (!array_key_exists('autocomplete', $attributes) && !in_array($type, $nonAutocompletableTypes, true)) {
            $attributes['autocomplete'] = 'on';
        }
        
        $attrs = [
            'type' => $type,
            'name' => $name,
            'id' => $attributes['id'] ?? $name,
            'value' => $value ?? old($name, ''),
            'class' => 'form-input ' . ($attributes['class'] ?? '') . ' ' . error_class($name),
        ];
        
        unset($attributes['id'], $attributes['class']);
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($val === true) {
                $attrString .= ' ' . $key;
            } elseif ($val !== false && $val !== null) {
                $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
            }
        }

        return '<input' . $attrString . '>';
    }
}

if (!function_exists('form_textarea')) {
    function form_textarea(string $name, ?string $value = null, array $attributes = []): string
    {
        if (!array_key_exists('autocomplete', $attributes)) {
            $attributes['autocomplete'] = 'on';
        }

        $attrs = [
            'name' => $name,
            'id' => $attributes['id'] ?? $name,
            'class' => 'form-input ' . ($attributes['class'] ?? '') . ' ' . error_class($name),
            'rows' => $attributes['rows'] ?? 4,
        ];
        
        unset($attributes['id'], $attributes['class'], $attributes['rows']);
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($val === true) {
                $attrString .= ' ' . $key;
            } elseif ($val !== false && $val !== null) {
                $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
            }
        }

        $content = e($value ?? old($name, ''));
        
        return '<textarea' . $attrString . '>' . $content . '</textarea>';
    }
}

if (!function_exists('form_select')) {
    function form_select(string $name, array $options, mixed $selected = null, array $attributes = []): string
    {
        $attrs = [
            'name' => $name,
            'id' => $attributes['id'] ?? $name,
            'class' => 'form-input ' . ($attributes['class'] ?? '') . ' ' . error_class($name),
        ];
        
        unset($attributes['id'], $attributes['class']);
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($val === true) {
                $attrString .= ' ' . $key;
            } elseif ($val !== false && $val !== null) {
                $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
            }
        }

        $selected = $selected ?? old($name);
        
        $html = '<select' . $attrString . '>';
        
        foreach ($options as $value => $label) {
            if (is_array($label)) {
                // Option group
                $html .= '<optgroup label="' . e($value) . '">';
                foreach ($label as $optValue => $optLabel) {
                    $isSelected = selected($optValue, $selected);
                    $html .= '<option value="' . e((string) $optValue) . '" ' . $isSelected . '>' . e($optLabel) . '</option>';
                }
                $html .= '</optgroup>';
            } else {
                $isSelected = selected($value, $selected);
                $html .= '<option value="' . e((string) $value) . '" ' . $isSelected . '>' . e($label) . '</option>';
            }
        }
        
        $html .= '</select>';
        
        return $html;
    }
}

if (!function_exists('form_checkbox')) {
    function form_checkbox(string $name, mixed $value = '1', bool $isChecked = false, array $attributes = []): string
    {
        $checked = $isChecked || old($name) == $value ? 'checked' : '';
        
        $attrs = [
            'type' => 'checkbox',
            'name' => $name,
            'id' => $attributes['id'] ?? $name,
            'value' => $value,
            'class' => $attributes['class'] ?? 'form-checkbox',
        ];
        
        unset($attributes['id'], $attributes['class']);
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $val) {
            $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
        }

        return '<input' . $attrString . ' ' . $checked . '>';
    }
}

if (!function_exists('form_radio')) {
    function form_radio(string $name, mixed $value, bool $isChecked = false, array $attributes = []): string
    {
        $checked = $isChecked || old($name) == $value ? 'checked' : '';
        
        $attrs = [
            'type' => 'radio',
            'name' => $name,
            'id' => $attributes['id'] ?? $name . '_' . $value,
            'value' => $value,
            'class' => $attributes['class'] ?? 'form-radio',
        ];
        
        unset($attributes['id'], $attributes['class']);
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $val) {
            $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
        }

        return '<input' . $attrString . ' ' . $checked . '>';
    }
}

if (!function_exists('form_label')) {
    function form_label(string $text, string $for, array $attributes = []): string
    {
        $class = $attributes['class'] ?? 'form-label';
        unset($attributes['class']);
        
        $attrString = ' class="' . e($class) . '"';
        foreach ($attributes as $key => $val) {
            $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
        }

        return '<label for="' . e($for) . '"' . $attrString . '>' . e($text) . '</label>';
    }
}

if (!function_exists('form_button')) {
    function form_button(string $text, string $type = 'submit', array $attributes = []): string
    {
        $class = $attributes['class'] ?? 'btn btn-primary';
        unset($attributes['class']);
        
        $attrString = ' type="' . e($type) . '" class="' . e($class) . '"';
        foreach ($attributes as $key => $val) {
            $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
        }

        return '<button' . $attrString . '>' . e($text) . '</button>';
    }
}

if (!function_exists('form_file')) {
    function form_file(string $name, array $attributes = []): string
    {
        $attrs = [
            'type' => 'file',
            'name' => $name,
            'id' => $attributes['id'] ?? $name,
            'class' => 'form-file ' . ($attributes['class'] ?? ''),
        ];
        
        unset($attributes['id'], $attributes['class']);
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($val === true) {
                $attrString .= ' ' . $key;
            } elseif ($val !== false && $val !== null) {
                $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
            }
        }

        return '<input' . $attrString . '>';
    }
}

if (!function_exists('form_hidden')) {
    function form_hidden(string $name, mixed $value): string
    {
        return '<input type="hidden" name="' . e($name) . '" value="' . e((string) $value) . '">';
    }
}

if (!function_exists('form_password')) {
    /**
     * Generate a password input with toggle visibility button
     * 
     * @param string $name Input name attribute
     * @param array $attributes Additional HTML attributes
     * @param bool $withToggle Enable password visibility toggle (default: true)
     * @return string HTML markup
     */
    function form_password(string $name, array $attributes = [], bool $withToggle = true): string
    {
        $attrs = [
            'type' => 'password',
            'name' => $name,
            'id' => $attributes['id'] ?? $name,
            'class' => 'form-input ' . ($attributes['class'] ?? '') . ' ' . error_class($name),
            'autocomplete' => $attributes['autocomplete'] ?? 'current-password',
        ];
        
        unset($attributes['id'], $attributes['class'], $attributes['autocomplete']);
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($val === true) {
                $attrString .= ' ' . $key;
            } elseif ($val !== false && $val !== null) {
                $attrString .= ' ' . $key . '="' . e((string) $val) . '"';
            }
        }

        if (!$withToggle) {
            return '<input' . $attrString . '>';
        }

        // With toggle button, use module i18n keys only.
        $textVisible = __('show_password', 'Users');
        if ($textVisible === 'show_password') {
            $textVisible = __('show_password', 'Auth');
        }
        $textHidden = __('hide_password', 'Users');
        if ($textHidden === 'hide_password') {
            $textHidden = __('hide_password', 'Auth');
        }
        $ariaLabel = $textVisible;

        $html = '<div class="password-field-wrapper" data-component="password-toggle">';
        $html .= '<input' . $attrString . '>';
        $html .= '<button ';
        $html .= 'type="button" ';
        $html .= 'class="password-toggle-btn" ';
        $html .= 'data-action="toggle-password" ';
        $html .= 'data-text-visible="' . e($textVisible) . '" ';
        $html .= 'data-text-hidden="' . e($textHidden) . '" ';
        $html .= 'aria-label="' . e($ariaLabel) . '" ';
        $html .= 'tabindex="0">';
        
        // Eye icon (visible state)
        $html .= '<svg class="password-toggle-icon icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
        $html .= '</svg>';
        
        // Eye-off icon (hidden state)
        $html .= '<svg class="password-toggle-icon icon-eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">';
        $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
        $html .= '</svg>';
        
        $html .= '</button>';
        $html .= '</div>';

        return $html;
    }
}
