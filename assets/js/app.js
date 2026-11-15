/* ============================================================
   Nepal Tourism Plus — Vanilla JS
   ============================================================ */

document.addEventListener("DOMContentLoaded", function () {
  // ==========================================================
  // 1. Flash Alert Close
  // ==========================================================
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

  // ==========================================================
  // 2. Logout Modal
  // ==========================================================
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

  // ==========================================================
  // 3. AJAX Polling for Newly Added Destinations (Phase 6D)
  // ==========================================================
  const newRow = document.getElementById("new-places-row");
  if (!newRow) return; // Only runs on the tourist dashboard

  const newContainer = document.getElementById("new-places-container");
  const baseUrl = newRow.dataset.url;

  // Use server time provided in the HTML to prevent timezone mismatch
  let since = newRow.dataset.initialSince;
  const shownIds = new Set();

  // Reads the base URL dynamically from the layout
  const appBase = window.APP_BASE || "";

  function pollNewDestinations() {
    fetch(`${baseUrl}?since=${encodeURIComponent(since)}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.server_time) {
          since = data.server_time;
        }

        if (data.destinations && data.destinations.length > 0) {
          let added = false;

          data.destinations.forEach((d) => {
            if (shownIds.has(d.id)) return;
            shownIds.add(d.id);
            added = true;

            const card = document.createElement("div");
            card.className = "dest-card";
            card.style.minWidth = "220px";
            card.style.maxWidth = "220px";

            const imgWrap = document.createElement("div");
            imgWrap.className = "dest-img-wrap";

            const img = document.createElement("img");
            img.src = d.image;
            img.alt = d.name;
            img.onerror = function () {
              this.src = `${appBase}/assets/img/placeholder.svg`;
            };

            const a = document.createElement("a");
            a.href = `${appBase}/tourist/destination/${d.id}`;
            a.appendChild(img);
            imgWrap.appendChild(a);
            card.appendChild(imgWrap);

            const info = document.createElement("div");
            info.className = "dest-info";

            const h4 = document.createElement("h4");
            h4.textContent = d.name;
            info.appendChild(h4);

            const types = document.createElement("div");
            types.className = "dest-types";
            d.type_names.forEach((tn) => {
              const span = document.createElement("span");
              span.textContent = tn;
              types.appendChild(span);
            });
            info.appendChild(types);

            const btn = document.createElement("a");
            btn.href = `${appBase}/tourist/destination/${d.id}`;
            btn.className = "btn btn-outline btn-sm";
            btn.style.marginTop = "auto";
            btn.style.width = "100%";
            btn.textContent = "See more";
            info.appendChild(btn);

            card.appendChild(info);
            newContainer.appendChild(card);
          });

          if (added) {
            newRow.style.display = "block";
          }
        }
      })
      .catch(() => {
        // Fails quietly if the request fails
      });
  }

  // Poll every 30 seconds
  setInterval(pollNewDestinations, 30000);
});
