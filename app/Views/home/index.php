<style>
    /* 
       LANDING PAGE SPECIFIC STYLES 
       (Internal CSS because it's only used here)
    */
    .hero {
        min-height: 590px; position: relative; display: flex; align-items: center; overflow: hidden;
        background: linear-gradient(90deg, rgba(17,36,29,0.94) 0%, rgba(17,36,29,0.78) 50%, rgba(17,36,29,0.18) 100%),
                    url('<?= asset("img/hero-bg.avif") ?>'); 
        background-size: cover; background-position: center; background-color: #192b25;
    }
    .hero::before {
        content: ""; position: absolute; inset: 0;
        background-image: repeating-radial-gradient(ellipse at 85% 55%, transparent 0px, transparent 35px, rgba(255,255,255,0.18) 36px, transparent 37px, transparent 65px);
        opacity: 0.35; pointer-events: none;
    }
    .hero-content { position: relative; z-index: 2; width: 100%; padding: 80px 10%; max-width: 1050px; }
    .eyebrow {
        display: inline-block; color: #e4bd80; font-size: 11px; font-weight: 700; letter-spacing: 3px;
        padding: 10px 15px; border: 1px solid rgba(228,189,128,0.5); margin-bottom: 28px;
    }
    .hero h1 {
        font-family: 'Playfair Display', Georgia, serif; font-size: clamp(43px, 5vw, 65px);
        line-height: 1.17; color: #fff; font-weight: 600; letter-spacing: -1.5px; margin-bottom: 22px;
    }
    .hero h1 span { color: #e5bd80; }
    .subtitle { color: #f0eee7; font-size: clamp(17px, 2vw, 21px); line-height: 1.6; max-width: 750px; font-weight: 500; margin: 0; }
    .description { margin-top: 19px; color: #d0d8d1; font-size: 14px; line-height: 1.8; }
    .hero-buttons { display: flex; gap: 14px; margin-top: 34px; }
    .hero-buttons .btn { padding: 14px 30px; font-size: 15px; }
    .hero-coordinate { margin-top: 65px; color: #e5bd80; font-size: 10px; letter-spacing: 2.5px; display: flex; align-items: center; gap: 12px; }
    .hero-coordinate::before { content: ""; width: 25px; height: 1px; background: #d5a45e; }
    .map-marker {
        position: absolute; z-index: 2; right: 15%; top: 34%; color: #f3d39d; font-size: 11px;
        letter-spacing: 2px; border-left: 1px solid #d5a45e; padding: 10px 14px; background: rgba(25,43,37,0.35);
    }
    .map-marker::before { content: "●"; color: #e5bd80; margin-right: 9px; }
    
    @media (max-width: 768px) {
        .hero { background-position: 60% center; }
        .hero-content { padding: 65px 7%; }
        .map-marker { display: none; }
    }
</style>

<section class="hero">
    <div class="hero-content">
        <div class="eyebrow">DISCOVER THE BEAUTY OF NEPAL</div>
        <h1>Nepal Tourism<br><span>Analytics.</span></h1>
        <p class="subtitle">
            Recommendations tailored to your interests, budget, and travel month.
        </p>
        <p class="description">
            Real government data, analysed. Personalised top-5 destinations, scored for you.
        </p>
        <div class="hero-buttons">
            <?php if (!\App\Core\Auth::check()): ?>
                <a href="<?= url('register') ?>" class="btn btn-signup">Sign Up</a>
                <a href="<?= url('login') ?>" class="btn btn-login">Log In</a>
            <?php else: ?>
                <a href="<?= url('dashboard') ?>" class="btn btn-signup">Go to Dashboard</a>
            <?php endif; ?>
        </div>
        <div class="hero-coordinate">NEPAL · THE HIMALAYAN REGION</div>
    </div>
    <div class="map-marker">EXPLORE NEPAL</div>
</section>