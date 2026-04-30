<!-- SECTION 1.5: RESTAURANT TYPE -->
    <section class="sf-step-section" id="sec-restaurant-type">
      <div class="sf-section-head">
        <div class="sf-section-title">Restaurant Type</div>
        <div class="sf-section-sub">Choose the dining style so we can suggest better seating and layouts.</div>
      </div>

      <div class="row g-3">
        <?php foreach($restaurantTypes as $key => $label): ?>
          <div class="col-md-4">
            <label class="sf-module-pill <?= $restaurantType === $key ? "is-checked" : "" ?>" for="rtype_<?= h($key) ?>">
              <div class="sf-module-left">
                <input
                  id="rtype_<?= h($key) ?>"
                  type="radio"
                  name="restaurant_type"
                  value="<?= h($key) ?>"
                  <?= $restaurantType === $key ? "checked" : "" ?>
                  required
                >
                <span class="sf-module-name"><?= h($label) ?></span>
              </div>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </section>