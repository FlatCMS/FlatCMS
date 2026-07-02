/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function (window) {
    'use strict';

    var namespace = window.FlatCMSStudioFlatCMS = window.FlatCMSStudioFlatCMS || {};

    function showToast(message, type) {
        var text = String(message || '').trim();
        if (text === '') {
            return;
        }

        if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
            window.FlatCMS.toast.show(text, type || 'success');
            return;
        }

        if (typeof namespace.pushToast === 'function') {
            namespace.pushToast(text, type || 'success');
        }
    }

    function requestJson(url, options) {
        return window.fetch(url, options).then(function (response) {
                return response.json().catch(function () {
                    return {};
                }).then(function (payload) {
                    if (!response.ok) {
                    var error = new Error(String((payload && payload.message) || response.statusText || ''));
                    error.payload = payload;
                    throw error;
                }

                return payload;
            });
        });
    }

    namespace.api = {
        showToast: showToast,
        loadDocument: function (boot) {
            return requestJson(String(boot.routes.data || ''), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
        },
        saveDocument: function (boot, documentData) {
            return requestJson(String(boot.routes.save || ''), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': String(((boot.config || {}).token) || '')
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    _token: String(((boot.config || {}).token) || ''),
                    document: documentData
                })
            });
        }
    };
}(window));
