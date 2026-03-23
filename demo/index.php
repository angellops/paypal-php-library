<?php require_once('core/useful-functions.php'); ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PayPal DemoKits | PHP Class Library | Angell EYE</title>
  <link rel="stylesheet" href="assets/css/style.css" />

  <!--- Fav and touch icons --->
  <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/apple-touch-icon-144-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/images/apple-touch-icon-114-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/apple-touch-icon-72-precomposed.png">
  <link rel="apple-touch-icon-precomposed" href="assets/images/apple-touch-icon-57-precomposed.png">
  <link rel="shortcut icon" href="assets/images/favicon.png">

  <script type="text/javascript" src="assets/js/jquery.min.js"></script>
  <script type="text/javascript" src="assets/js/scripts.js"></script>
</head>

<body>
  <!--- HEADER --->
  <?php require_once('partials/header.php'); ?>

  <!--- HERO --->
  <section class="hero">
    <div class="hero-bg"></div>
    <div class="container hero-inner">
      <div class="hero-grid">
        <div>
          <div class="hero-badge">
            <?php echo inline_svg('assets/images/spark.svg'); ?>
            Interactive Developer Sandbox
          </div>
          <h1>PayPal API<br><span>Integration Demos</span></h1>
          <p class="hero-description">Explore our extensive library of working payment integrations. Test drive
            different checkout flows, understand the buyer experience, and see exactly how the APIs function in a
            real-world environment.</p>
          <div class="hero-cta">
            <a href="#demos" class="btn-primary">
              Browse Demos
              <?php echo inline_svg('assets/images/right-arrow.svg'); ?>
            </a>
            <a href="https://www.angelleye.com/product-category/php-class-libraries/demo-kits/" class="btn-outline">
              Additional DemoKits
              <?php echo inline_svg('assets/images/right-arrow.svg'); ?>
            </a>
          </div>
        </div>

        <!-- Right: info cards -->
        <div class="hero-cards">
          <div class="hero-card">
            <h3>What is This?</h3>
            <p>These DemoKits are fully functional implementations of PayPal's various checkout
              and payment processing APIs. They are connected to the PayPal Sandbox environment,
              allowing you to complete test transactions safely.</p>
          </div>
          <div class="hero-card">
            <h3>How Does This Work?</h3>
            <p>Select any demo below and click the action button. You'll be guided through the
              standard payment flow just like a real customer would experience on a live
              e-commerce site. Use sandbox buyer accounts to test.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!--- WARNING BANNER --->
  <div class="container">
    <div class="banner-wrap">
      <div class="banner" id="deprecation-banner">
        <div class="banner-icon-wrap">
          <?php echo inline_svg('assets/images/warning-icon.svg'); ?>
        </div>
        <div class="banner-body">
          <h3>Classic API Deprecation Notice</h3>
          <p>Some demos in this catalog utilize PayPal's <strong>Classic APIs</strong> (NVP/SOAP). These APIs are
            considered legacy and may have limited support for new integrations. For new projects, we strongly recommend
            evaluating the modern REST APIs first. However, these demos remain available for developers maintaining
            existing integrations.</p>
        </div>
      </div>
    </div>
  </div>

  <!--- CATALOG --->
  <main class="catalog" id="demos"
    data-link-icon='<?php echo htmlspecialchars(inline_svg("assets/images/redirect-icon.svg")); ?>'>
    <div class="container">
      <!--- Section 1: Classic API --->
      <div class="demo-section">
        <div class="section-header">
          <div class="section-title-row">
            <div class="section-icon section-icon--amber">
              <?php echo inline_svg('assets/images/spark.svg'); ?>
            </div>
            <h2 class="section-title">Classic API</h2>
            <span class="section-pill section-pill--amber">Legacy / NVP &amp; SOAP</span>
          </div>
          <p class="section-desc">
            The original PayPal integration suite. Still fully functional — ideal for
            developers maintaining existing integrations or exploring the foundational API.
          </p>
        </div>
        <div class="card-grid" id="classic-grid"></div>
      </div>
      <div class="section-divider"></div>

      <!--- Section 2: Express Checkout & Payments Pro --->
      <div class="demo-section">
        <div class="section-header">
          <div class="section-title-row">
            <div class="section-icon section-icon--blue">
              <?php echo inline_svg('assets/images/layout-icon.svg'); ?>
            </div>
            <h2 class="section-title">REST API</h2>
            <span class="section-pill section-pill--blue">Recommended</span>
          </div>
          <p class="section-desc">
            Modern REST-based PayPal integrations covering the full range of checkout experiences —
            from basic payments to advanced flows like subscriptions, vaulting, multi-party, and alternative payment
            methods.
          </p>
        </div>
        <div class="card-grid" id="rest-grid"></div>
      </div>
    </div>
  </main>

  <!--- FOOTER --->
  <?php require_once('partials/footer.php'); ?>
</body>

</html>