(function () {
    "use strict";

    var DEFAULT_DURATION = 3000;
    var containerId = "toast-container";

    function ensureContainer() {
        var container = document.getElementById(containerId);
        if (container) {
            return container;
        }
        container = document.createElement("div");
        container.id = containerId;
        container.className = "toast-container";
        document.body.appendChild(container);
        return container;
    }

    function normalizeType(type) {
        if (!type) {
            return "info";
        }
        var safe = String(type).toLowerCase();
        if (safe === "success" || safe === "error" || safe === "warn") {
            return safe;
        }
        return "info";
    }

    function createToast(message, type) {
        var toast = document.createElement("div");
        toast.className = "toast toast--" + type;
        toast.setAttribute("role", "status");
        toast.setAttribute("aria-live", "polite");

        var text = document.createElement("div");
        text.className = "toast__text";
        text.textContent = message;

        var closeBtn = document.createElement("button");
        closeBtn.className = "toast__close";
        closeBtn.type = "button";
        closeBtn.textContent = "Fechar";
        closeBtn.addEventListener("click", function () {
            hideToast(toast);
        });

        toast.appendChild(text);
        toast.appendChild(closeBtn);
        return toast;
    }

    function hideToast(toast) {
        if (!toast || toast.classList.contains("toast--leaving")) {
            return;
        }
        toast.classList.add("toast--leaving");
        toast.addEventListener("animationend", function () {
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        });
    }

    function showToast(message, type, options) {
        if (!message) {
            return;
        }
        var opts = options || {};
        var duration = typeof opts.duration === "number" ? opts.duration : DEFAULT_DURATION;
        var safeType = normalizeType(type);
        var container = ensureContainer();
        var toast = createToast(message, safeType);
        container.appendChild(toast);

        if (duration > 0) {
            window.setTimeout(function () {
                hideToast(toast);
            }, duration);
        }
    }

    window.showToast = showToast;
})();
