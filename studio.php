<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="UX Pacific Studio — Premium design resources, UI kits, and digital products crafted for real-world product design." />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
  <title>Studio — UX Pacific Shop</title>
  <link rel="icon" type="image/png" href="img/fav.png" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,200;0,9..40,300;0,9..40,600;1,9..40,200&family=Gabarito:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
  <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('assets/css/studio.css')); ?>" />
  <?php include 'includes/auth-preload.php'; ?>
</head>
<body class="studio-page">
<div class="page">
  <?php include 'includes/header.php'; ?>

  <main class="studio-main">
    <!-- Studio Showcase -->
    <section class="studio-section studio-section-dark">
      <div class="studio-container">
        <div class="studio-reveal" style="text-align:center;">
          <h2 class="studio-heading">Studio <em>Showcase</em></h2>
          <p class="studio-sub">
            Every resource is crafted with the same care we bring to client work.
            Here&rsquo;s what we create.
          </p>
        </div>

        <div style="text-align:center;">
          <div class="studio-status-pill studio-reveal" id="studio-stats-pill">
            <div class="studio-status-item">
              <span class="studio-status-num" data-count="500" data-suffix="+">0+</span>
              <span class="studio-status-label">Projects Delivered</span>
            </div>
            <div class="studio-status-divider"></div>
            <div class="studio-status-item">
              <span class="studio-status-num" data-count="98" data-suffix="%">0%</span>
              <span class="studio-status-label">Client Satisfaction</span>
            </div>
            <div class="studio-status-divider"></div>
            <div class="studio-status-item">
              <span class="studio-status-num" data-count="6" data-suffix="+">0+</span>
              <span class="studio-status-label">Years of Experience</span>
            </div>
          </div>
        </div>

        <div class="studio-showcase-grid studio-stagger studio-reveal">
          <div class="studio-showcase-card">
            <div class="studio-showcase-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <h3>Design Systems</h3>
            <p>Comprehensive, scalable design systems with component libraries, tokens, and documentation for consistent product experiences.</p>
          </div>

          <div class="studio-showcase-card">
            <div class="studio-showcase-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <h3>UI Libraries</h3>
            <p>Production-ready interface libraries built for Figma, Sketch, and Adobe XD with pixel-perfect components and variants.</p>
          </div>

          <div class="studio-showcase-card">
            <div class="studio-showcase-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <h3>Research Frameworks</h3>
            <p>Structured research frameworks, interview guides, and testing templates to help teams validate design decisions.</p>
          </div>

          <div class="studio-showcase-card">
            <div class="studio-showcase-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            </div>
            <h3>UX Templates</h3>
            <p>Ready-to-use UX templates including user flows, wireframes, sitemaps, and journey maps for faster project kickoffs.</p>
          </div>

          <div class="studio-showcase-card">
            <div class="studio-showcase-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5"/><line x1="12" y1="22" x2="12" y2="15.5"/><polyline points="22 8.5 12 15.5 2 8.5"/></svg>
            </div>
            <h3>Figma Workspaces</h3>
            <p>Pre-configured Figma workspaces with organised pages, components, styles, and collaboration settings for teams.</p>
          </div>

          <div class="studio-showcase-card">
            <div class="studio-showcase-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
            <h3>Component Collections</h3>
            <p>Curated collections of UI components, patterns, and interaction specs ready to be dropped into any design system.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How We Build -->
    <section class="studio-section studio-section-darker">
      <div class="studio-container">
        <div class="studio-reveal" style="text-align:center;">
          <h2 class="studio-heading">How We <em>Build</em></h2>
          <p class="studio-sub">
            Every resource follows a rigorous four-stage process to ensure
            quality, consistency, and real-world usability.
          </p>
        </div>

        <div class="studio-process studio-stagger studio-reveal">
          <div class="studio-process-step">
            <div class="studio-process-num">01</div>
            <h3>Research</h3>
            <p>Understanding user problems, identifying pain points, and gathering insights to inform every design decision.</p>
          </div>

          <div class="studio-process-step">
            <div class="studio-process-num">02</div>
            <h3>Structure</h3>
            <p>Building scalable systems with clean architecture, consistent naming, and thoughtful component hierarchies.</p>
          </div>

          <div class="studio-process-step">
            <div class="studio-process-num">03</div>
            <h3>Design</h3>
            <p>Creating polished interfaces with meticulous attention to typography, spacing, colour, and interaction details.</p>
          </div>

          <div class="studio-process-step">
            <div class="studio-process-num">04</div>
            <h3>Refine</h3>
            <p>Improving details through rigorous iteration, testing, and feedback loops until every element earns its place.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Studio Work -->
    <section class="studio-section studio-section-dark studio-portfolio-section">
      <div class="studio-container">
        <div class="studio-portfolio-header">
          <h2 class="studio-heading">Studio <em>Portfolio</em></h2>
          <p class="studio-sub">
            A selection of our finest work&mdash;design systems, component libraries,
            and tools built for real-world impact.
          </p>
        </div>

        <div class="studio-bento">
          <article class="studio-bento-item studio-bento-item--featured">
            <div class="studio-bento-bg" style="background-image:url('img/poster3.webp')"></div>
            <div class="studio-bento-overlay"></div>
            <div class="studio-bento-content">
              <span class="studio-bento-tag">Design System</span>
              <h3>Pacific Design System</h3>
              <p>A comprehensive design system featuring 600+ components, dark/light modes, and full documentation.</p>
            </div>
          </article>

          <article class="studio-bento-item">
            <div class="studio-bento-bg" style="background-image:url('img/poster1.webp')"></div>
            <div class="studio-bento-overlay"></div>
            <div class="studio-bento-content">
              <span class="studio-bento-tag">UI Library</span>
              <h3>Figma Component Collection</h3>
              <p>400+ production-ready UI components with auto-layout, variants, and interactive prototypes.</p>
            </div>
          </article>

          <article class="studio-bento-item">
            <div class="studio-bento-bg" style="background-image:url('img/poster2.webp')"></div>
            <div class="studio-bento-overlay"></div>
            <div class="studio-bento-content">
              <span class="studio-bento-tag">Documentation</span>
              <h3>Component Docs</h3>
              <p>Detailed usage guidelines with code snippets, best practices, and accessibility notes.</p>
            </div>
          </article>

          <article class="studio-bento-item">
            <div class="studio-bento-bg" style="background-image:url('img/poster.webp')"></div>
            <div class="studio-bento-overlay"></div>
            <div class="studio-bento-content">
              <span class="studio-bento-tag">Wireframes</span>
              <h3>UX Wireframe Kit</h3>
              <p>200+ wireframe components for rapid prototyping and user flow mapping.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Design Principles -->
    <section class="studio-section studio-section-darker">
      <div class="studio-container">
        <div class="studio-reveal" style="text-align:center;">
          <h2 class="studio-heading">Design <em>Principles</em></h2>
          <p class="studio-sub">
            Four core principles guide every resource we create, from concept to final polish.
          </p>
        </div>

        <div class="studio-principles studio-stagger studio-reveal">
          <div class="studio-principle-card">
            <div class="studio-principle-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <h3>Clarity</h3>
            <p>Every element serves a purpose. We strip away the unnecessary until only the essential remains.</p>
          </div>

          <div class="studio-principle-card">
            <div class="studio-principle-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            </div>
            <h3>Consistency</h3>
            <p>Scalable systems create better products. We build with patterns that endure and adapt.</p>
          </div>

          <div class="studio-principle-card">
            <div class="studio-principle-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            </div>
            <h3>Accessibility</h3>
            <p>Design should work for everyone. We ensure every resource meets WCAG standards.</p>
          </div>

          <div class="studio-principle-card">
            <div class="studio-principle-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5C7 4 7 7 7 7"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5C17 4 17 7 17 7"/><path d="M4 22h16"/><path d="M10 22V6h4v16"/><path d="M2 6V2h20v4"/><path d="M6 6V2"/><path d="M18 6V2"/></svg>
            </div>
            <h3>Simplicity</h3>
            <p>Complexity hidden behind elegant experiences. Powerful tools that feel effortless to use.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Behind The Process -->
    <section class="studio-section studio-section-dark">
      <div class="studio-container">
        <div class="studio-reveal" style="text-align:center;">
          <h2 class="studio-heading">Behind The <em>Process</em></h2>
          <p class="studio-sub">
            Step inside our studio workflow. Every resource passes through
            rigorous stages before reaching your hands.
          </p>
        </div>

        <div class="studio-behind studio-stagger studio-reveal">
          <div class="studio-behind-card">
            <div class="studio-behind-visual">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </div>
            <h3>Wireframes &amp; Explorations</h3>
            <p>We begin every resource with low-fidelity explorations, mapping structure before polishing pixels.</p>
          </div>

          <div class="studio-behind-card">
            <div class="studio-behind-visual">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
            <h3>UI Iterations &amp; Refinement</h3>
            <p>Multiple rounds of iteration refine every component until it meets our quality bar.</p>
          </div>

          <div class="studio-behind-card">
            <div class="studio-behind-visual">
              <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#b198ff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <h3>Quality Assurance &amp; Testing</h3>
            <p>Every resource is tested across tools, platforms, and use cases to guarantee reliability.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="studio-section studio-section-darker">
      <div class="studio-container">
        <div class="studio-reveal" style="text-align:center;">
          <h2 class="studio-heading">Frequently Asked <em>Questions</em></h2>
          <p class="studio-sub">
            Everything you need to know about our design resources, workflow, and how we can help.
          </p>
        </div>

        <div class="studio-faq studio-reveal">
          <div class="studio-faq-item">
            <button class="studio-faq-toggle" type="button" aria-expanded="false">
              <span>What types of design resources do you offer?</span>
              <svg class="studio-faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="studio-faq-answer">
              <p>We offer a comprehensive range of resources including design systems, UI component libraries, UX research frameworks, Figma workspaces, wireframe kits, and documentation templates. Every resource is production-ready and built from real project experience.</p>
            </div>
          </div>

          <div class="studio-faq-item">
            <button class="studio-faq-toggle" type="button" aria-expanded="false">
              <span>Are your resources compatible with multiple design tools?</span>
              <svg class="studio-faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="studio-faq-answer">
              <p>Yes. Our resources are primarily built for Figma but we also provide versions for Sketch, Adobe XD, and web-based component libraries. Each resource includes a compatibility guide to help you get started in your tool of choice.</p>
            </div>
          </div>

          <div class="studio-faq-item">
            <button class="studio-faq-toggle" type="button" aria-expanded="false">
              <span>How often do you update your design resources?</span>
              <svg class="studio-faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="studio-faq-answer">
              <p>We update our resources regularly to keep pace with evolving design tooling, platform guidelines, and industry standards. Subscribers receive update notifications and access to the latest versions at no additional cost.</p>
            </div>
          </div>

          <div class="studio-faq-item">
            <button class="studio-faq-toggle" type="button" aria-expanded="false">
              <span>Can I use these resources for commercial projects?</span>
              <svg class="studio-faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="studio-faq-answer">
              <p>Absolutely. All our resources come with a commercial license that allows you to use them in client projects, internal products, and commercial applications. You can modify, adapt, and distribute as needed within your projects.</p>
            </div>
          </div>

          <div class="studio-faq-item">
            <button class="studio-faq-toggle" type="button" aria-expanded="false">
              <span>What is your design process for creating resources?</span>
              <svg class="studio-faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="studio-faq-answer">
              <p>Every resource follows a four-stage process: Research (understanding user needs), Structure (building scalable architecture), Design (crafting polished interfaces), and Refine (iterating through testing and feedback). This ensures consistent quality across everything we release.</p>
            </div>
          </div>

          <div class="studio-faq-item">
            <button class="studio-faq-toggle" type="button" aria-expanded="false">
              <span>Do you offer custom design system services?</span>
              <svg class="studio-faq-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="studio-faq-answer">
              <p>Yes. We work with teams to build custom design systems, component libraries, and design operations workflows tailored to their specific needs. Reach out through our <a href="contact.php">contact page</a> to discuss your project requirements.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include 'includes/footer.php'; ?>
</div>

<script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
<script src="<?php echo htmlspecialchars(asset_url('assets/js/studio.js')); ?>"></script>
</body>
</html>
