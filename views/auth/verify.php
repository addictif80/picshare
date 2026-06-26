<?php ob_start(); ?>
<div class="auth-body-content">
  <h2>Vérification</h2>
  <p class="auth-sub">Un code à 6 chiffres a été envoyé à<br><strong><?= e($email) ?></strong></p>

  <form method="POST" action="<?= url('login/verify') ?>" id="otp-form">
    <?= csrfField() ?>
    <div class="otp-inputs">
      <?php for ($i = 0; $i < 6; $i++): ?>
        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
      <?php endfor; ?>
      <input type="hidden" name="otp" id="otp-hidden">
    </div>
    <button type="submit" class="btn btn-primary btn-full" id="otp-submit" disabled>Vérifier</button>
  </form>

  <div class="auth-divider"><span>Vous n'avez pas reçu le code ?</span></div>
  <a href="<?= url('login') ?>" class="btn btn-ghost btn-full">Renvoyer un code</a>
</div>

<script>
(function() {
  const inputs = document.querySelectorAll('.otp-digit');
  const hidden = document.getElementById('otp-hidden');
  const submit = document.getElementById('otp-submit');

  inputs.forEach((input, i) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(/\D/g, '').slice(-1);
      if (input.value && i < 5) inputs[i + 1].focus();
      const code = [...inputs].map(el => el.value).join('');
      hidden.value = code;
      submit.disabled = code.length < 6;
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !input.value && i > 0) inputs[i - 1].focus();
    });
    input.addEventListener('paste', e => {
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      if (text.length === 6) {
        [...inputs].forEach((el, j) => el.value = text[j] || '');
        hidden.value = text;
        submit.disabled = false;
        inputs[5].focus();
      }
      e.preventDefault();
    });
  });

  inputs[0].focus();
})();
</script>
<?php
$content = ob_get_clean();
require VIEWS_PATH . '/layouts/auth.php';
?>
