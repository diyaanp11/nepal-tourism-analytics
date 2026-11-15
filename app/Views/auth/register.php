<?php /** @var array $old */ ?>
<div class="auth-wrapper">
    <div class="auth-card">
        <a href="<?= url('/') ?>" class="back-home">← Back to Home</a>

        <h2 class="auth-title">Create Account</h2>

        <form method="post" action="<?= url('register') ?>" novalidate>
            <?= \App\Core\CSRF::field() ?>

            <!-- NEW ROLE TOGGLE -->
            <div style="margin-bottom: 0.5rem;">
                <label class="auth-form-label">I am a...</label>
            </div>
            <div class="role-toggle-group">
                <input type="radio" id="role_tourist" name="role" value="tourist" 
                    <?= (($old['role'] ?? 'tourist') === 'tourist') ? 'checked' : '' ?>>
                <label for="role_tourist">Tourist</label>

                <input type="radio" id="role_researcher" name="role" value="researcher"
                    <?= (($old['role'] ?? '') === 'researcher') ? 'checked' : '' ?>>
                <label for="role_researcher">Researcher</label>
            </div>
            <!-- END ROLE TOGGLE -->

            <div style="margin-bottom: 1rem;">
                <label for="name" class="auth-form-label">Full Name</label>
                <input type="text" class="auth-input" id="name" name="name"
                       value="<?= e($old['name'] ?? '') ?>" required autofocus>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="email" class="auth-form-label">Email</label>
                <input type="email" class="auth-input" id="email" name="email"
                       value="<?= e($old['email'] ?? '') ?>" required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label for="password" class="auth-form-label">Password</label>
                <input type="password" class="auth-input" id="password" name="password" required minlength="8">
                <div style="color: #a0b0a8; font-size: 12px; margin-top: 4px;">At least 8 characters.</div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="password_confirmation" class="auth-form-label">Confirm Password</label>
                <input type="password" class="auth-input" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary w-100" style="padding: 12px;">Sign Up</button>
        </form>

        <div class="auth-links">
            Already have an account? <a href="<?= url('login') ?>">Log in</a>
        </div>
    </div>
</div>