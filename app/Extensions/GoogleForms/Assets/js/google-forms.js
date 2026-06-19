(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var searchInput = document.getElementById("gfResponseSearch");
        var tableBody = document.getElementById("gfResponsesTableBody");
        if (searchInput && tableBody) {
            searchInput.addEventListener("input", function () {
                var query = (searchInput.value || "").toLowerCase().trim();
                var rows = tableBody.querySelectorAll("tr[data-gf-search]");
                var visibleCount = 0;

                rows.forEach(function (row) {
                    var haystack = (row.getAttribute("data-gf-search") || "").toLowerCase();
                    var visible = query === "" || haystack.indexOf(query) !== -1;

                    row.hidden = !visible;

                    if (visible) {
                        visibleCount += 1;
                    }
                });
            });
        }

        var modal = document.getElementById("gfResponseModal");
        var modalBody = document.getElementById("gfResponseModalBody");
        var lastActiveElement = null;

        function openModal(templateId) {
            if (!modal || !modalBody) {
                return;
            }

            var template = document.getElementById(templateId);

            if (!template) {
                return;
            }

            lastActiveElement = document.activeElement;
            modalBody.innerHTML = template.innerHTML;
            modal.classList.add("is-open");
            modal.setAttribute("aria-hidden", "false");
            document.body.classList.add("google-forms-modal-open");

            var closeButton = modal.querySelector("[data-modal-close]");
            if (closeButton) {
                closeButton.focus({ preventScroll: true });
            }
        }

        function closeModal() {
            if (!modal || !modalBody || !modal.classList.contains("is-open")) {
                return;
            }

            modal.classList.remove("is-open");
            modal.setAttribute("aria-hidden", "true");
            modalBody.innerHTML = "";
            document.body.classList.remove("google-forms-modal-open");

            if (lastActiveElement && typeof lastActiveElement.focus === "function") {
                lastActiveElement.focus({ preventScroll: true });
            }
        }

        document.querySelectorAll(".google-forms-detail-trigger").forEach(function (button) {
            button.addEventListener("click", function () {
                openModal(button.getAttribute("data-detail-target"));
            });
        });

        document.querySelectorAll("[data-modal-close]").forEach(function (button) {
            button.addEventListener("click", closeModal);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });

        document.querySelectorAll("[data-copy-target]").forEach(function (button) {
            button.addEventListener("click", function () {
                var selector = button.getAttribute("data-copy-target");
                var target = selector ? document.querySelector(selector) : null;
                var value = target ? (target.value || target.textContent || "") : "";
                var label = button.querySelector("span");
                var original = label ? label.textContent : button.textContent;
                var doneText = button.getAttribute("data-copy-done") || "Copié";
                var failedText = button.getAttribute("data-copy-failed") || "Erreur";

                function setTemporaryLabel(text) {
                    if (label) {
                        label.textContent = text;
                    } else {
                        button.textContent = text;
                    }

                    window.setTimeout(function () {
                        if (label) {
                            label.textContent = original;
                        } else {
                            button.textContent = original;
                        }
                    }, 1600);
                }

                function fallbackCopy(text) {
                    var textarea = document.createElement("textarea");
                    textarea.value = text;
                    textarea.setAttribute("readonly", "readonly");
                    textarea.style.position = "fixed";
                    textarea.style.opacity = "0";
                    document.body.appendChild(textarea);
                    textarea.select();

                    try {
                        document.execCommand("copy");
                        setTemporaryLabel(doneText);
                    } catch (error) {
                        setTemporaryLabel(failedText);
                    } finally {
                        document.body.removeChild(textarea);
                    }
                }

                if (!value) {
                    setTemporaryLabel(failedText);
                    return;
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(function () {
                        setTemporaryLabel(doneText);
                    }).catch(function () {
                        fallbackCopy(value);
                    });
                } else {
                    fallbackCopy(value);
                }
            });
        });

        document.querySelectorAll("[data-google-forms-confirm]").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                var message = String(form.getAttribute("data-google-forms-confirm") || "").trim();
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    });
})();
