/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
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
  var lastTrigger = null;

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
    lastTrigger = trigger;

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

    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("comments-modal-open");

    var closeBtn = modal.querySelector(".comments-read-modal__close");
    if (closeBtn) {
      closeBtn.focus();
    }
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("comments-modal-open");

    if (lastTrigger && typeof lastTrigger.focus === "function") {
      lastTrigger.focus();
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

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && !modal.hidden) {
      closeModal();
    }
  });
})();

