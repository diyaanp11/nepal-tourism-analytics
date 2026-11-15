/* ============================================================
   Nepal Tourism Plus — Vanilla JS
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  // 1. Flash Alert Close
  document
    .querySelectorAll('.btn-close[data-dismiss="alert"]')
    .forEach(function (btn) {
      btn.addEventListener("click", function () {
        var alertBox = btn.closest(".alert");
        if (alertBox) {
          alertBox.style.opacity = "0";
          setTimeout(function () {
            alertBox.remove();
          }, 200);
        }
      });
    });

  // 2. Logout Modal
  var modal = document.getElementById("logoutModal");
  var trigger = document.getElementById("logoutBtn");
  var cancelBtn = document.getElementById("logoutCancel");

  if (modal && trigger) {
    // Open Modal
    trigger.addEventListener("click", function (e) {
      e.preventDefault();
      modal.classList.add("show");
    });

    // Close via No button
    if (cancelBtn) {
      cancelBtn.addEventListener("click", function () {
        modal.classList.remove("show");
      });
    }

    // Close via click outside
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        modal.classList.remove("show");
      }
    });

    // Close via Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        modal.classList.remove("show");
      }
    });
  }
});
