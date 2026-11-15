<?php /** @var array $old */ ?>
<div class="auth-wrapper">
    <div class="auth-card">
        <a href="<?= url('/') ?>" class="back-home">← Back to Home</a>
        
        <h2 class="auth-title">Log In</h2>

        <form method="post" action="<?= url('login') ?>" novalidate>
            <?= \App\Core\CSRF::field() ?>

            <div style="margin-bottom: 1.25rem;">
                <label for="email" class="auth-form-label">Email</label>
                <input type="email" class="auth-input" id="email" name="email"
                       value="<?= e($old['email'] ?? '') ?>" required autofocus>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="password" class="auth-form-label">Password</label>
                <input type="password" class="auth-input" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100" style="padding: 12px;">Log In</button>
        </form>

        <div class="auth-links">
            New here? <a href="<?= url('register') ?>">Create an account</a>
        </div>
    </div>
</div>