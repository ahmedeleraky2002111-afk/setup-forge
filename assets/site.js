// assets/site.js
document.addEventListener("DOMContentLoaded", () => {
  // =========================
  // Wizard option cards
  // =========================

  // RADIO groups
  document.querySelectorAll('.sf-card-option input[type="radio"]').forEach((inp) => {
    const syncGroup = () => {
      const name = inp.name;
      document
        .querySelectorAll(`.sf-card-option input[type="radio"][name="${name}"]`)
        .forEach((r) => {
          const card = r.closest(".sf-card-option");
          if (card) card.classList.toggle("selected", r.checked);
        });
    };
    inp.addEventListener("change", syncGroup);
    syncGroup();
  });

  // CHECKBOX
  document.querySelectorAll('.sf-card-option input[type="checkbox"]').forEach((inp) => {
    const syncCard = () => {
      const card = inp.closest(".sf-card-option");
      if (card) card.classList.toggle("selected", inp.checked);
    };
    inp.addEventListener("change", syncCard);
    syncCard();
  });

  // Package cards RADIO groups
  document.querySelectorAll(".sf-pkg-card input[type='radio']").forEach((inp) => {
    const syncGroup = () => {
      const name = inp.name;
      document
        .querySelectorAll(`.sf-pkg-card input[type="radio"][name="${name}"]`)
        .forEach((r) => {
          const card = r.closest(".sf-pkg-card");
          if (card) card.classList.toggle("selected", r.checked);
        });
    };
    inp.addEventListener("change", syncGroup);
    syncGroup();
  });

  // =========================
  // REPLACE + SELLERS PANELS (SAFE / SCOPED)
  // =========================
  // Expected HTML:
  // - Replace button:  data-replace-open="1"
  // - Replace panel:   <div class="sf-replace-panel ..."> right after .sf-cart-row
  //
  // - Sellers button:  data-sellers-open="1"  data-group="some_group_key"
  // - Sellers panel:   <div class="sf-sellers-panel ..."> right after .sf-cart-row
  //
  // If your sellers panel is not immediate sibling, we also look for:
  // .sf-sellers-panel[data-group="..."] inside the same parent container.

  const closePanelsInScope = (scopeEl) => {
    if (!scopeEl) return;
    scopeEl.querySelectorAll(".sf-replace-panel").forEach((p) => p.classList.add("d-none"));
    scopeEl.querySelectorAll(".sf-sellers-panel").forEach((p) => p.classList.add("d-none"));
  };

  document.addEventListener("click", (e) => {
    const replaceBtn = e.target.closest("[data-replace-open='1']");
    const sellersBtn = e.target.closest("[data-sellers-open='1']");

    // Not our buttons
    if (!replaceBtn && !sellersBtn) return;

    e.preventDefault();

    const btn = replaceBtn || sellersBtn;

    // Find the cart row that contains the button
    const row = btn.closest(".sf-cart-row");
    if (!row) return;

    // Scope = the cart container (prevents touching other modules/pages)
    const scope = row.closest(".sf-cart") || row.parentElement;

    // Close all panels in same scope, then open the right one
    closePanelsInScope(scope);

    // Try immediate next sibling first
    const next = row.nextElementSibling;

    // --- Replace ---
    if (replaceBtn) {
      if (next && next.classList.contains("sf-replace-panel")) {
        next.classList.remove("d-none");
        return;
      }

      // Fallback: find replace panel by type (your existing markup uses data-replace-panel="type")
      const type = replaceBtn.getAttribute("data-type");
      if (type && scope) {
        const alt = scope.querySelector(`.sf-replace-panel[data-replace-panel="${type}"]`);
        if (alt) alt.classList.remove("d-none");
      }
      return;
    }

    // --- Sellers ---
    if (sellersBtn) {
      // Prefer data-group (product_group_key)
      const group = sellersBtn.getAttribute("data-group");

      // If your sellers panel is right after row
      if (next && next.classList.contains("sf-sellers-panel")) {
        next.classList.remove("d-none");
        return;
      }

      // Fallback: find by group key
      if (group && scope) {
        const alt = scope.querySelector(`.sf-sellers-panel[data-group="${CSS.escape(group)}"]`);
        if (alt) alt.classList.remove("d-none");
      }
      return;
    }
  });

  // ===============================
  // Tier-per-module (safe + scoped)
  // ===============================
  document.querySelectorAll('input[name="modules[]"]').forEach((cb) => {
    const key = cb.value;

    const wrap = document.querySelector(`[data-tier-wrap="${key}"]`);
    const hidden = document.querySelector(`[data-tier-input="${key}"]`);

    // If page doesn't have tier UI, skip safely
    if (!wrap || !hidden) return;

    const setVisible = (isOn) => {
      wrap.classList.toggle("d-none", !isOn);
    };

    const setTier = (tier) => {
      hidden.value = tier;

      // UI state (active chip)
      wrap.querySelectorAll("[data-tier]").forEach((b) => {
        b.classList.toggle("active", b.getAttribute("data-tier") === tier);
        b.setAttribute("aria-pressed", b.classList.contains("active") ? "true" : "false");
      });
    };

    // Default tier if empty
    if (!hidden.value) setTier("Balanced");

    const sync = () => {
      setVisible(cb.checked);
    };

    cb.addEventListener("change", sync);
    sync();

    wrap.querySelectorAll("[data-tier]").forEach((btn) => {
      btn.addEventListener("click", (ev) => {
        ev.preventDefault();
        const tier = btn.getAttribute("data-tier");
        if (!tier) return;
        setTier(tier);
      });
    });
  });
});