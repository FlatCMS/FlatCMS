/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Comments/Assets/js/comments-admin.js
 * Version: 2.0.0-dev
 */

(function () {
  "use strict";

  var modal = document.getElementById("commentsReadModal");
  if (!modal) {
    return;
  }

  var authorEl = modal.querySelector("[data-comment-modal-author]");
  var dateEl = modal.querySelector("[data-comment-modal-date]");
  var postEl = modal.querySelector("[data-comment-modal-post]");
  var contentEl = modal.querySelector("[data-comment-modal-content]");
  var closeControls = modal.querySelectorAll("[data-comment-modal-close]");
  var modalController = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.modal
    ? window.FlatCMS.AdminUI.modal.attach(modal)
    : null;

  function toText(value) {
    return (value || "").toString();
  }

  function buildPostLabel(dataset) {
    var label = toText(modal.dataset.labelPost);
    var type = toText(dataset.commentPostType || "post");
    var id = toText(dataset.commentPostId);
    return label + ": " + type + (id ? " #" + id : "");
  }

  function openModal(trigger) {
    var dataset = trigger.dataset;

    if (authorEl) {
      authorEl.textContent = toText(modal.dataset.labelAuthor) + ": " + toText(dataset.commentAuthor) + " (" + toText(dataset.commentEmail) + ")";
    }
    if (dateEl) {
      dateEl.textContent = toText(modal.dataset.labelDate) + ": " + toText(dataset.commentDate);
    }
    if (postEl) {
      postEl.textContent = buildPostLabel(dataset);
    }
    if (contentEl) {
      contentEl.textContent = toText(dataset.commentContent);
    }

    if (modalController) {
      modalController.open(trigger, { initialFocus: ".comments-read-modal__close" });
    }
  }

  function closeModal() {
    if (modalController) {
      modalController.close();
    }
  }

  document.addEventListener("click", function (event) {
    var trigger = event.target.closest("[data-comment-open]");
    if (trigger) {
      event.preventDefault();
      openModal(trigger);
    }
  });

  closeControls.forEach(function (control) {
    control.addEventListener("click", function () {
      closeModal();
    });
  });
})();
