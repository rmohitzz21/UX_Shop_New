<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>UX Pacific – Shop All Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php include 'includes/auth-preload.php'; ?>
    <!-- Google Font -->
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
      <link rel="icon" type="image/png" href="img/fav.png" />
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('style.css')); ?>" />
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
  </head>
  <style></style>
  <body class="shopAll">
    <div class="page">
      <?php include 'includes/header.php'; ?>

      <!-- MAIN CONTENT – SHOP ALL PAGE -->
      <main class="main shop-all-main">
        <!-- Page Header -->
        <section class="shop-all-header">
          <h1 class="shop-all-title">Our <span>Policies</span></h1>
          <!-- <p class="shop-all-subtitle">
            Clear, transparent, and globally compliant policies for digital and
            physical products.
          </p> -->
        </section>

        <!-- ================= POLICIES LAYOUT ================= -->
        <section class="shop-all-layout">
          <!-- LEFT SIDEBAR -->
          <aside class="shop-all-filters">
            <button class="filter-pill active" data-policy="privacy">
              Privacy Policy
            </button>
            <button class="filter-pill" data-policy="terms">
              Terms & Conditions
            </button>
            <button class="filter-pill" data-policy="refund">
              Refund & Returns
            </button>
            <button class="filter-pill" data-policy="shipping">
              Shipping Policy
            </button>
            <button class="filter-pill" data-policy="cookie">
              Cookie Policy
            </button>
            <button class="filter-pill" data-policy="digital">
              Digital License
            </button>
            <button class="filter-pill" data-policy="disclaimer">
              Disclaimer
            </button>
          </aside>

          <!-- RIGHT CONTENT -->
          <div class="policy-content">
            <!-- PRIVACY -->
            <div class="policy-section active" id="privacy">
              <h1 class="policy-title">Privacy Policy</h1>

              <div class="policy-meta">
                <span>Effective Date: 1 January 2026</span>|
                <span>Last Updated: 1 January 2026</span>
              </div>

              <p class="policy-intro">
                UXPACIFIC Shop (“we”, “our”, “us”) values your privacy and is
                committed to protecting your personal data. This Privacy Policy
                explains how we collect, use, store, disclose, and safeguard
                your information when you access or use our Website.
              </p>

              <!-- SECTION 1 -->
              <div class="policy-block">
                <h2>1. Information We Collect</h2>

                <h3>1.1 Personal Information</h3>
                <ul>
                  <li>Full name</li>
                  <li>Email address</li>
                  <li>Phone number</li>
                  <li>Billing and shipping address</li>
                  <li>
                    Payment-related details (processed securely via third-party
                    gateways)
                  </li>
                </ul>
                <p class="policy-note">
                  Note: We do not store credit/debit card numbers or sensitive
                  payment data.
                </p>

                <h3>1.2 Automatically Collected Information</h3>
                <ul>
                  <li>IP address</li>
                  <li>Browser type and version</li>
                  <li>Device information</li>
                  <li>Pages visited and time spent</li>
                  <li>Cookies and tracking technologies</li>
                </ul>

                <h3>1.3 Digital Product Access Data</h3>
                <ul>
                  <li>Download history</li>
                  <li>Access timestamps</li>
                  <li>Order identifiers (fraud prevention)</li>
                </ul>
              </div>

              <!-- SECTION 2 -->
              <div class="policy-block">
                <h2>2. How We Use Your Information</h2>
                <ul>
                  <li>Process and fulfill orders</li>
                  <li>Deliver digital and physical products</li>
                  <li>Provide customer support</li>
                  <li>Improve website performance</li>
                  <li>Detect fraud and unauthorized access</li>
                  <li>Comply with legal obligations</li>
                </ul>
              </div>

              <!-- SECTION 3 -->
              <div class="policy-block">
                <h2>3. Legal Basis for Processing (GDPR)</h2>
                <ul>
                  <li>Consent</li>
                  <li>Contractual necessity</li>
                  <li>Legal obligation</li>
                  <li>Legitimate business interests</li>
                </ul>
              </div>

              <!-- SECTION 4 -->
              <div class="policy-block">
                <h2>4. Cookies & Tracking</h2>
                <p>
                  We use cookies to enable essential functionality, analyze
                  traffic, and improve user experience. You may control cookies
                  through your browser settings.
                </p>
              </div>

              <!-- SECTION 5 -->
              <div class="policy-block">
                <h2>5. Data Security</h2>
                <p>
                  We implement industry-standard security measures including
                  encryption, secure servers, and restricted access controls.
                  However, no online system is completely secure.
                </p>
              </div>

              <!-- SECTION 6 -->
              <div class="policy-block">
                <h2>6. Your Rights</h2>
                <ul>
                  <li>Access your personal data</li>
                  <li>Request correction or deletion</li>
                  <li>Restrict or object to processing</li>
                  <li>Withdraw consent</li>
                  <li>Request data portability</li>
                </ul>
              </div>

              <!-- SECTION 7 -->
              <div class="policy-block">
                <h2>7. Contact Information</h2>
                <p>
                  For privacy-related requests, contact us at:
                  <br />
                  <strong>support@uxpacific.com</strong>
                </p>
              </div>
            </div>

            <!-- TERMS -->
            <div class="policy-section" id="terms">
              <h1 class="policy-title">Terms & Conditions</h1>

              <div class="policy-meta">
                <span>Website: https://shop.uxpacific.com</span>|
                <span>Effective Date: 1 January 2026</span>|
                <span>Last Updated: 1 January 2026</span>
              </div>

              <p class="policy-intro">
                Welcome to UXPACIFIC Shop. These Terms & Conditions (“Terms”)
                govern your access to and use of our website, products, and
                services. By visiting, browsing, or purchasing from our Website,
                you agree to be legally bound by these Terms.
              </p>

              <p class="policy-intro">
                If you do not agree to any part of these Terms, please
                discontinue use of the Website.
              </p>

              <!-- 1 -->
              <div class="policy-block">
                <h2>1. About UXPACIFIC Shop</h2>
                <p>
                  UXPACIFIC Shop is a global marketplace offering both digital
                  and physical products, including but not limited to:
                </p>
                <ul>
                  <li>
                    <strong>Digital products:</strong> UI templates, mockups,
                    digital workbooks, downloadable design resources
                  </li>
                  <li>
                    <strong>Physical products:</strong> T-shirts, stickers,
                    badges, printed booklets, and merchandise
                  </li>
                </ul>
                <p>These Terms apply to all users worldwide.</p>
              </div>

              <!-- 2 -->
              <div class="policy-block">
                <h2>2. Eligibility</h2>
                <ul>
                  <li>You are at least 18 years of age, or</li>
                  <li>You have legal parental or guardian consent, and</li>
                  <li>
                    You are legally capable of entering into a binding agreement
                    under applicable laws
                  </li>
                </ul>
              </div>

              <!-- 3 -->
              <div class="policy-block">
                <h2>3. User Accounts & Responsibilities</h2>
                <ul>
                  <li>
                    You agree to provide accurate, current, and complete
                    information
                  </li>
                  <li>
                    You are responsible for all activities conducted under your
                    account or order credentials
                  </li>
                  <li>
                    You must immediately notify us of any unauthorized use
                  </li>
                </ul>
                <p>
                  UXPACIFIC Shop reserves the right to suspend or terminate
                  access if misuse, fraud, or violation of these Terms is
                  detected.
                </p>
              </div>

              <!-- 4 -->
              <div class="policy-block">
                <h2>4. Product Information & Availability</h2>
                <ul>
                  <li>
                    Product descriptions, images, and pricing are provided for
                    informational purposes
                  </li>
                  <li>Digital previews are indicative only</li>
                  <li>
                    Physical product colors and finishes may vary due to display
                    or manufacturing differences
                  </li>
                </ul>
                <p>
                  We reserve the right to modify or discontinue any product
                  without prior notice.
                </p>
              </div>

              <!-- 5 -->
              <div class="policy-block">
                <h2>5. Digital Products – License & Usage</h2>

                <h3>5.1 License Grant</h3>
                <p>All digital products are licensed, not sold.</p>
                <p>Unless explicitly stated otherwise, you are granted a:</p>
                <ul>
                  <li>Non-exclusive</li>
                  <li>Non-transferable</li>
                  <li>Non-sublicensable</li>
                </ul>
                <p>license for personal or internal business use only.</p>

                <h3>5.2 Usage Restrictions</h3>
                <p>You may not:</p>
                <ul>
                  <li>
                    Resell, share, distribute, or sublicense digital products
                  </li>
                  <li>Upload products to public or private repositories</li>
                  <li>Use products to create competing offerings</li>
                  <li>Claim ownership, authorship, or exclusive rights</li>
                </ul>
                <p>
                  Violation may result in license termination and legal action.
                </p>
              </div>

              <!-- 6 -->
              <div class="policy-block">
                <h2>6. Physical Products</h2>
                <ul>
                  <li>
                    Physical products are intended for lawful, personal use
                  </li>
                  <li>Care and usage instructions must be followed</li>
                  <li>
                    We are not responsible for damage caused by misuse, improper
                    handling, or negligence
                  </li>
                </ul>
              </div>

              <!-- 7 -->
              <div class="policy-block">
                <h2>7. Pricing & Payments</h2>
                <ul>
                  <li>All prices are displayed in the applicable currency</li>
                  <li>Prices may change at any time without notice</li>
                  <li>
                    Payments are processed securely through third-party payment
                    gateways
                  </li>
                </ul>
                <p>
                  UXPACIFIC Shop does not store sensitive payment information.
                </p>
              </div>

              <!-- 8 -->
              <div class="policy-block">
                <h2>8. Shipping & Delivery</h2>
                <ul>
                  <li>Delivery timelines are estimates only</li>
                  <li>
                    International orders may be subject to customs duties,
                    taxes, or import charges
                  </li>
                  <li>
                    Delays caused by logistics partners, customs, or
                    force-majeure events are beyond our control
                  </li>
                </ul>
                <p>Refer to our Shipping Policy for full details.</p>
              </div>

              <!-- 9 -->
              <div class="policy-block">
                <h2>9. Refunds, Returns & Cancellations</h2>
                <p>
                  Refunds, returns, and replacements are governed strictly by
                  our Refund & Returns Policy.
                </p>
                <ul>
                  <li><strong>Digital products:</strong> Non-refundable</li>
                  <li>
                    <strong>Physical products:</strong> Replacement only for
                    verified damage, defect, or incorrect delivery
                  </li>
                </ul>
              </div>

              <!-- 10 -->
              <div class="policy-block">
                <h2>10. Intellectual Property</h2>
                <p>
                  All content, designs, templates, graphics, logos, and
                  materials available on the Website are the exclusive
                  intellectual property of UXPACIFIC and are protected under
                  applicable copyright and trademark laws.
                </p>
                <p>Unauthorized use is strictly prohibited.</p>
              </div>

              <!-- 11 -->
              <div class="policy-block">
                <h2>11. Prohibited Activities</h2>
                <ul>
                  <li>Engage in unlawful or fraudulent activities</li>
                  <li>Attempt to compromise website security</li>
                  <li>Circumvent digital protection mechanisms</li>
                  <li>Misrepresent association with UXPACIFIC</li>
                </ul>
              </div>

              <!-- 12 -->
              <div class="policy-block">
                <h2>12. Disclaimer of Warranties</h2>
                <p>
                  All products and services are provided “as is” and “as
                  available.”
                </p>
                <p>
                  We disclaim all warranties, express or implied, including
                  fitness for a particular purpose and uninterrupted
                  availability.
                </p>
              </div>

              <!-- 13 -->
              <div class="policy-block">
                <h2>13. Limitation of Liability</h2>
                <ul>
                  <li>
                    UXPACIFIC Shop shall not be liable for indirect, incidental,
                    or consequential damages
                  </li>
                  <li>
                    Total liability shall not exceed the amount paid by the
                    customer for the relevant product
                  </li>
                </ul>
              </div>

              <!-- 14 -->
              <div class="policy-block">
                <h2>14. Indemnification</h2>
                <p>
                  You agree to indemnify and hold harmless UXPACIFIC Shop from
                  any claims, losses, or damages arising from:
                </p>
                <ul>
                  <li>Your violation of these Terms</li>
                  <li>Misuse of products or services</li>
                  <li>Infringement of third-party rights</li>
                </ul>
              </div>

              <!-- 15 -->
              <div class="policy-block">
                <h2>15. Termination</h2>
                <p>
                  We reserve the right to suspend or terminate access, without
                  prior notice, for any breach of these Terms or applicable
                  laws.
                </p>
              </div>

              <!-- 16 -->
              <div class="policy-block">
                <h2>16. Governing Law & Jurisdiction</h2>
                <p>
                  These Terms shall be governed by and interpreted in accordance
                  with the laws of India.
                </p>
                <p>
                  All disputes shall be subject to the exclusive jurisdiction of
                  courts located in India.
                </p>
              </div>

              <!-- 17 -->
              <div class="policy-block">
                <h2>17. Updates to These Terms</h2>
                <p>
                  We may update these Terms at any time. Revisions will be
                  posted on this page with an updated effective date.
                </p>
                <p>
                  Continued use of the Website after updates constitutes
                  acceptance of the revised Terms.
                </p>
              </div>

              <!-- 18 -->
              <div class="policy-block">
                <h2>18. Contact Information</h2>
                <p>
                  For any questions regarding these Terms & Conditions:
                  <br />
                  <strong>support@uxpacific.com</strong>
                  <br />
                  Serving customers worldwide
                </p>
              </div>
            </div>

            <!-- REFUND -->
            <div class="policy-section" id="refund">
              <h1 class="policy-title">Refund & Returns Policy</h1>

              <div class="policy-meta">
                <span>Website: https://shop.uxpacific.com</span>|
                <span>Effective Date: 1 January 2026</span>|
                <span>Last Updated: 1 January 2026</span>
              </div>

              <p class="policy-intro">
                This Refund & Returns Policy (“Policy”) governs all purchases
                made on UXPACIFIC Shop. This policy applies globally and is
                designed to protect both customers and UXPACIFIC Shop while
                maintaining transparency, fairness, and compliance with
                international e-commerce standards.
              </p>

              <p class="policy-intro">
                By completing a purchase on our Website, you acknowledge that
                you have read, understood, and agreed to this Policy.
              </p>

              <!-- 1 -->
              <div class="policy-block">
                <h2>1. Purpose of This Policy</h2>
                <ul>
                  <li>
                    Clearly define refund, return, and replacement eligibility
                  </li>
                  <li>
                    Prevent misuse, fraud, and unauthorized duplication of
                    digital products
                  </li>
                  <li>Set fair expectations for physical merchandise</li>
                  <li>
                    Comply with global consumer protection and payment gateway
                    requirements
                  </li>
                </ul>
              </div>

              <!-- 2 -->
              <div class="policy-block">
                <h2>2. Classification of Products</h2>

                <h3>2.1 Digital Products</h3>
                <ul>
                  <li>UI templates</li>
                  <li>Design mockups</li>
                  <li>Digital workbooks</li>
                  <li>Downloadable files</li>
                  <li>Licensed digital assets</li>
                </ul>

                <h3>2.2 Physical Products</h3>
                <ul>
                  <li>Apparel (T-shirts, wearable merchandise)</li>
                  <li>Stickers and badges</li>
                  <li>Printed booklets and workbooks</li>
                </ul>

                <p>Different refund and return rules apply to each category.</p>
              </div>

              <!-- 3 -->
              <div class="policy-block">
                <h2>3. General Refund Policy Principles</h2>
                <ul>
                  <li>
                    Refunds are not guaranteed and are governed strictly by this
                    Policy
                  </li>
                  <li>
                    Digital products are treated differently from physical
                    products
                  </li>
                  <li>
                    All decisions made by UXPACIFIC Shop under this Policy are
                    final
                  </li>
                </ul>
                <p>
                  Abuse of this Policy may result in account restriction or
                  permanent refusal of service.
                </p>
              </div>

              <!-- 4 -->
              <div class="policy-block">
                <h2>
                  4. Digital Products – Strict No Refund & No Return Policy
                </h2>

                <h3>4.1 Non-Refundable Nature</h3>
                <p>
                  All digital products are non-refundable, non-returnable, and
                  non-exchangeable once the purchase is completed.
                </p>
                <ul>
                  <li>Download status</li>
                  <li>Access duration</li>
                  <li>File usage</li>
                  <li>Customer satisfaction or preference</li>
                </ul>

                <h3>4.2 Legal & Practical Basis</h3>
                <p>Digital products:</p>
                <ul>
                  <li>Are delivered instantly</li>
                  <li>Cannot be physically returned</li>
                  <li>Can be duplicated once accessed</li>
                </ul>
                <p>
                  To protect intellectual property and prevent misuse, refunds
                  cannot be offered.
                </p>

                <h3>4.3 Compatibility & Due Diligence</h3>
                <p>Customers are responsible for verifying:</p>
                <ul>
                  <li>Software compatibility</li>
                  <li>File formats</li>
                  <li>Intended use cases</li>
                  <li>Technical requirements</li>
                </ul>

                <h3>4.4 Exceptions (Extremely Limited)</h3>
                <p>
                  Refunds for digital products may be considered only if the
                  file is technically corrupted and the issue cannot be resolved
                  through support. Such cases are reviewed individually and do
                  not establish precedent.
                </p>
              </div>

              <!-- 5 -->
              <div class="policy-block">
                <h2>5. Physical Products – Replacement-Only Policy</h2>

                <h3>5.1 No Refunds for Physical Products</h3>
                <p>
                  Physical products are not eligible for refunds once delivered.
                </p>

                <h3>5.2 Replacement Eligibility Criteria</h3>
                <ul>
                  <li>Product is received damaged</li>
                  <li>Product is defective due to manufacturing fault</li>
                  <li>Incorrect product is delivered</li>
                </ul>

                <h3>5.3 Reporting Window</h3>
                <p>
                  Replacement requests must be submitted within
                  <strong>7 calendar days</strong> of confirmed delivery.
                </p>

                <h3>5.4 Evidence & Verification</h3>
                <ul>
                  <li>Clear photos or videos showing the issue</li>
                  <li>Images of original packaging</li>
                  <li>Order ID and shipping details</li>
                </ul>
              </div>

              <!-- 6 -->
              <div class="policy-block">
                <h2>6. Cases Where Replacement Is Not Applicable</h2>
                <ul>
                  <li>Change of mind</li>
                  <li>Size or color preference issues</li>
                  <li>Minor variations in color or finish</li>
                  <li>Normal wear and tear</li>
                  <li>Damage caused after delivery</li>
                  <li>Improper washing, handling, or storage</li>
                  <li>Courier delays without product damage</li>
                </ul>
              </div>

              <!-- 7 -->
              <div class="policy-block">
                <h2>7. Replacement Fulfilment Process</h2>
                <ul>
                  <li>
                    Replacement will be dispatched without additional product
                    cost
                  </li>
                  <li>
                    Shipping timelines depend on availability and location
                  </li>
                  <li>Replacement does not reset policy timelines</li>
                  <li>
                    No cash refund, store credit, or partial refund will be
                    issued
                  </li>
                </ul>
              </div>

              <!-- 8 -->
              <div class="policy-block">
                <h2>8. Order Cancellation Policy</h2>

                <h3>8.1 Digital Products</h3>
                <p>Orders cannot be cancelled once placed.</p>

                <h3>8.2 Physical Products</h3>
                <p>
                  Orders may be cancelled only before shipment. Once shipped,
                  cancellation is not possible.
                </p>
              </div>

              <!-- 9 -->
              <div class="policy-block">
                <h2>9. International Orders & Customs</h2>
                <ul>
                  <li>Customs duties, taxes, and import fees may apply</li>
                  <li>Customers are responsible for all such charges</li>
                  <li>
                    Refusal to pay customs charges does not qualify for refund
                  </li>
                </ul>
              </div>

              <!-- 10 -->
              <div class="policy-block">
                <h2>10. Chargebacks & Payment Disputes</h2>
                <p>
                  Customers must contact UXPACIFIC Shop before initiating any
                  chargeback.
                </p>
                <ul>
                  <li>Immediate account suspension</li>
                  <li>Cancellation of future orders</li>
                  <li>Reporting to payment processors</li>
                </ul>
              </div>

              <!-- 11 -->
              <div class="policy-block">
                <h2>11. Fraud, Misuse & Abuse Prevention</h2>
                <ul>
                  <li>False claims</li>
                  <li>Repeated refund requests</li>
                  <li>Digital product misuse</li>
                  <li>Chargeback abuse</li>
                </ul>
                <p>
                  We reserve the right to deny service permanently in such
                  cases.
                </p>
              </div>

              <!-- 12 -->
              <div class="policy-block">
                <h2>12. Policy Amendments</h2>
                <p>
                  UXPACIFIC Shop reserves the right to amend or update this
                  Policy at any time. The version applicable at the time of
                  purchase shall govern the transaction.
                </p>
              </div>

              <!-- 13 -->
              <div class="policy-block">
                <h2>13. Contact & Support</h2>
                <p>
                  For refund, return, or replacement inquiries:
                  <br />
                  <strong>support@uxpacific.com</strong>
                  <br />
                  Serving customers worldwide
                </p>
              </div>
            </div>

            <!-- SHIPPING -->
            <div class="policy-section" id="shipping">
              <h1 class="policy-title">Shipping Policy</h1>

              <div class="policy-meta">
                <span>Website: https://shop.uxpacific.com</span>|
                <span>Effective Date: 1 January 2026</span>|
                <span>Last Updated: 1 January 2026</span>
              </div>

              <p class="policy-intro">
                This Shipping Policy outlines how orders placed on UXPACIFIC
                Shop are processed, shipped, delivered, and handled globally.
                This policy applies to all customers worldwide and should be
                read in conjunction with our Refund & Returns Policy and Terms &
                Conditions.
              </p>

              <p class="policy-intro">
                By placing an order on our Website, you agree to the terms
                stated below.
              </p>

              <!-- 1 -->
              <div class="policy-block">
                <h2>1. Scope of This Policy</h2>
                <ul>
                  <li>All physical products sold on UXPACIFIC Shop</li>
                  <li>Domestic and international shipments</li>
                  <li>
                    Standard and expedited shipping options (where available)
                  </li>
                </ul>
                <p>
                  <strong>Note:</strong> Digital products are delivered
                  electronically and are not subject to shipping.
                </p>
              </div>

              <!-- 2 -->
              <div class="policy-block">
                <h2>2. Digital Products – No Shipping Required</h2>
                <ul>
                  <li>Delivered via download link, email, or user dashboard</li>
                  <li>
                    Delivery is typically instant or within a short processing
                    window
                  </li>
                  <li>
                    No physical shipping, tracking, or courier services apply
                  </li>
                </ul>
                <p>
                  Digital delivery issues should be reported to
                  <strong>support@uxpacific.com</strong>.
                </p>
              </div>

              <!-- 3 -->
              <div class="policy-block">
                <h2>3. Order Processing Time (Physical Products)</h2>

                <h3>3.1 Processing Timeline</h3>
                <p>
                  Orders are typically processed within
                  <strong>2–5 business days</strong>
                  after payment confirmation.
                </p>
                <p>
                  Processing includes order verification, quality checks,
                  packaging, and dispatch preparation.
                </p>

                <h3>3.2 Business Days</h3>
                <ul>
                  <li>Saturdays and Sundays</li>
                  <li>Public holidays</li>
                  <li>Unforeseen operational delays</li>
                </ul>
              </div>

              <!-- 4 -->
              <div class="policy-block">
                <h2>4. Shipping Destinations</h2>
                <p>UXPACIFIC Shop ships to:</p>
                <ul>
                  <li><strong>India</strong> (Domestic Shipping)</li>
                  <li>
                    <strong>International destinations</strong> (Worldwide
                    Shipping)
                  </li>
                </ul>
                <p>
                  Availability may vary based on product type, destination
                  country, or local regulations.
                </p>
              </div>

              <!-- 5 -->
              <div class="policy-block">
                <h2>5. Shipping Methods & Carriers</h2>
                <ul>
                  <li>
                    Orders are shipped via trusted third-party courier partners
                  </li>
                  <li>
                    Carrier selection depends on destination and package size
                  </li>
                  <li>
                    Delivery by a specific courier is not guaranteed unless
                    stated
                  </li>
                </ul>
              </div>

              <!-- 6 -->
              <div class="policy-block">
                <h2>6. Estimated Delivery Timelines</h2>

                <h3>6.1 Domestic (India)</h3>
                <p>Estimated delivery: <strong>5–10 business days</strong></p>

                <h3>6.2 International</h3>
                <p>
                  Estimated delivery: <strong>10–25 business days</strong>,
                  depending on destination and customs clearance.
                </p>

                <p>Delays may occur due to:</p>
                <ul>
                  <li>Customs clearance</li>
                  <li>Weather conditions</li>
                  <li>Public holidays</li>
                  <li>Force majeure events</li>
                </ul>
              </div>

              <!-- 7 -->
              <div class="policy-block">
                <h2>7. Shipping Charges</h2>
                <ul>
                  <li>
                    Shipping charges (if applicable) are calculated at checkout
                  </li>
                  <li>
                    Charges depend on destination, weight, and shipping method
                  </li>
                  <li>Charges may change without prior notice</li>
                </ul>
              </div>

              <!-- 8 -->
              <div class="policy-block">
                <h2>8. Customs, Duties & Taxes (International Orders)</h2>
                <ul>
                  <li>Customs duties, VAT, or import fees may apply</li>
                  <li>
                    Such charges are not included in product or shipping price
                  </li>
                  <li>Customers are solely responsible for these charges</li>
                </ul>
                <p>
                  Failure to pay customs duties does not qualify for refund or
                  replacement.
                </p>
              </div>

              <!-- 9 -->
              <div class="policy-block">
                <h2>9. Tracking Information</h2>
                <ul>
                  <li>
                    Tracking details are shared once the order is dispatched
                  </li>
                  <li>
                    Tracking availability depends on courier and destination
                  </li>
                  <li>
                    Customers are responsible for monitoring shipment progress
                  </li>
                </ul>
              </div>

              <!-- 10 -->
              <div class="policy-block">
                <h2>10. Delivery Attempts & Address Accuracy</h2>

                <h3>10.1 Accurate Address Requirement</h3>
                <p>
                  Customers must ensure complete and accurate shipping details.
                </p>

                <h3>10.2 Failed Delivery Attempts</h3>
                <p>
                  If delivery fails due to incorrect address, unavailability, or
                  refusal, the order may be returned or disposed of and is not
                  eligible for refund.
                </p>
              </div>

              <!-- 11 -->
              <div class="policy-block">
                <h2>11. Lost, Delayed, or Damaged Shipments</h2>

                <h3>11.1 Delayed Shipments</h3>
                <p>Delays beyond our control do not qualify for refunds.</p>

                <h3>11.2 Lost Shipments</h3>
                <p>
                  If confirmed lost, we will assist with investigation and may
                  offer a replacement at our discretion.
                </p>

                <h3>11.3 Damaged Shipments</h3>
                <p>
                  Damage must be reported within <strong>7 days</strong> of
                  delivery with photo/video proof.
                </p>
              </div>

              <!-- 12 -->
              <div class="policy-block">
                <h2>12. Partial Shipments</h2>
                <p>
                  Orders with multiple items may be shipped separately. Tracking
                  details will be shared for each shipment where applicable.
                </p>
              </div>

              <!-- 13 -->
              <div class="policy-block">
                <h2>13. Force Majeure</h2>
                <p>
                  We are not liable for delays or failures caused by events
                  beyond our control, including natural disasters, pandemics, or
                  government actions.
                </p>
              </div>

              <!-- 14 -->
              <div class="policy-block">
                <h2>14. Policy Updates</h2>
                <p>
                  We reserve the right to update this Shipping Policy at any
                  time. The version applicable at the time of purchase will
                  govern the shipment.
                </p>
              </div>

              <!-- 15 -->
              <div class="policy-block">
                <h2>15. Contact Information</h2>
                <p>
                  For shipping-related inquiries:
                  <br />
                  <strong>support@uxpacific.com</strong>
                  <br />
                  Serving customers worldwide
                </p>
              </div>
            </div>

            <!-- COOKIE -->
            <div class="policy-section" id="cookie">
              <h1 class="policy-title">Cookie Policy</h1>

              <div class="policy-meta">
                <span>Website: https://shop.uxpacific.com</span>|
                <span>Effective Date: 1 January 2026</span>|
                <span>Last Updated: 1 January 2026</span>
              </div>

              <p class="policy-intro">
                This Cookie Policy explains how UXPACIFIC Shop (“we”, “our”,
                “us”) uses cookies and similar tracking technologies when you
                visit or interact with our Website. This policy applies globally
                and is designed to comply with GDPR, the ePrivacy Directive, and
                other applicable data protection laws.
              </p>

              <p class="policy-intro">
                By continuing to browse or use our Website, you consent to the
                use of cookies as described in this policy, subject to your
                cookie preferences.
              </p>

              <!-- 1 -->
              <div class="policy-block">
                <h2>1. What Are Cookies?</h2>
                <p>
                  Cookies are small text files placed on your device (computer,
                  mobile, or tablet) when you visit a website. They help
                  websites function efficiently, remember preferences, and
                  improve user experience.
                </p>
                <p>
                  Cookies do not give us access to your device or personal
                  files.
                </p>
              </div>

              <!-- 2 -->
              <div class="policy-block">
                <h2>2. Why We Use Cookies</h2>
                <ul>
                  <li>Ensure the Website functions correctly</li>
                  <li>Improve Website performance and usability</li>
                  <li>Remember user preferences and settings</li>
                  <li>Analyze traffic and usage patterns</li>
                  <li>Enhance security and prevent fraud</li>
                </ul>
                <p>
                  We do not use cookies to sell personal data or track users
                  across unrelated websites for advertising purposes.
                </p>
              </div>

              <!-- 3 -->
              <div class="policy-block">
                <h2>3. Types of Cookies We Use</h2>

                <h3>3.1 Strictly Necessary Cookies</h3>
                <p>
                  These cookies are essential for Website operation and cannot
                  be disabled.
                </p>
                <ul>
                  <li>Page navigation</li>
                  <li>Secure checkout</li>
                  <li>Payment processing</li>
                  <li>Session management</li>
                </ul>

                <h3>3.2 Performance & Analytics Cookies</h3>
                <ul>
                  <li>Pages visited</li>
                  <li>Time spent on pages</li>
                  <li>Traffic sources</li>
                  <li>Error messages</li>
                </ul>
                <p>
                  This data is anonymous and helps improve Website performance.
                </p>

                <h3>3.3 Functional Cookies</h3>
                <ul>
                  <li>Language preferences</li>
                  <li>Region or location settings</li>
                  <li>Previously viewed products</li>
                </ul>

                <h3>3.4 Security Cookies</h3>
                <ul>
                  <li>Detect suspicious activity</li>
                  <li>Prevent fraud and abuse</li>
                  <li>Protect user accounts and transactions</li>
                </ul>
              </div>

              <!-- 4 -->
              <div class="policy-block">
                <h2>4. Third-Party Cookies</h2>
                <p>
                  We may allow trusted third-party services to place cookies on
                  your device, including:
                </p>
                <ul>
                  <li>Analytics providers</li>
                  <li>Payment service providers</li>
                  <li>Website infrastructure or performance tools</li>
                </ul>
                <p>
                  These third parties are required to comply with applicable
                  data protection laws. UXPACIFIC Shop does not control
                  third-party cookie policies.
                </p>
              </div>

              <!-- 5 -->
              <div class="policy-block">
                <h2>5. Cookies & Personal Data</h2>
                <p>
                  Some cookies may process limited personal data such as IP
                  address, device identifiers, or browser information.
                </p>
                <p>
                  All such data is processed in accordance with our Privacy
                  Policy.
                </p>
              </div>

              <!-- 6 -->
              <div class="policy-block">
                <h2>6. Cookie Consent & User Choices</h2>
                <ul>
                  <li>You may see a cookie consent banner on first visit</li>
                  <li>
                    You can accept, reject, or customize non-essential cookies
                  </li>
                  <li>You may withdraw consent at any time</li>
                </ul>
                <p>
                  Strictly necessary cookies cannot be disabled as they are
                  essential for Website functionality.
                </p>
              </div>

              <!-- 7 -->
              <div class="policy-block">
                <h2>7. Managing Cookies Through Your Browser</h2>
                <ul>
                  <li>View stored cookies</li>
                  <li>Delete cookies</li>
                  <li>Block or restrict cookies</li>
                </ul>
                <p>
                  Disabling cookies may impact Website functionality and user
                  experience.
                </p>
              </div>

              <!-- 8 -->
              <div class="policy-block">
                <h2>8. Data Retention for Cookies</h2>
                <ul>
                  <li>Session cookies (deleted when browser closes)</li>
                  <li>Persistent cookies (stored until expiry or deletion)</li>
                </ul>
                <p>
                  Retention periods vary depending on cookie purpose and legal
                  requirements.
                </p>
              </div>

              <!-- 9 -->
              <div class="policy-block">
                <h2>9. International Users</h2>
                <p>
                  As a global platform, cookies may be processed outside your
                  country of residence. Appropriate safeguards are implemented
                  to ensure GDPR compliance.
                </p>
              </div>

              <!-- 10 -->
              <div class="policy-block">
                <h2>10. Updates to This Cookie Policy</h2>
                <p>
                  We reserve the right to update this Cookie Policy at any time.
                  Changes will be posted on this page with a revised effective
                  date.
                </p>
              </div>

              <!-- 11 -->
              <div class="policy-block">
                <h2>11. Contact Information</h2>
                <p>
                  For questions about cookies or data privacy:
                  <br />
                  <strong>support@uxpacific.com</strong>
                  <br />
                  Serving customers worldwide
                </p>
              </div>
            </div>

            <!-- DIGITAL LICENSE -->
            <div class="policy-section" id="digital">
              <h1 class="policy-title">Digital License Policy</h1>

              <div class="policy-meta">
                <span>Website: https://shop.uxpacific.com</span>|
                <span>Effective Date: 1 January 2026</span>|
                <span>Last Updated: 1 January 2026</span>
              </div>

              <p class="policy-intro">
                This Digital License Policy (“License Policy”) governs the use
                of all digital products sold or distributed by UXPACIFIC Shop.
                This policy applies globally and forms an integral part of our
                Terms & Conditions.
              </p>

              <p class="policy-intro">
                By purchasing, downloading, or accessing any digital product
                from UXPACIFIC Shop, you agree to be bound by this License
                Policy.
              </p>

              <!-- 1 -->
              <div class="policy-block">
                <h2>1. Scope of Digital Products</h2>
                <p>
                  This License Policy applies to all digital products,
                  including:
                </p>
                <ul>
                  <li>UI / UX templates</li>
                  <li>Design mockups</li>
                  <li>Digital workbooks</li>
                  <li>Downloadable files</li>
                  <li>Design resources and assets</li>
                </ul>
                <p>
                  All digital products are protected by copyright and
                  intellectual property laws.
                </p>
              </div>

              <!-- 2 -->
              <div class="policy-block">
                <h2>2. License Grant (Not a Sale)</h2>
                <p>
                  All digital products are licensed, not sold. Upon successful
                  purchase, UXPACIFIC Shop grants you a limited, non-exclusive,
                  non-transferable, non-sublicensable license to use the digital
                  product strictly in accordance with this policy.
                </p>
                <p>
                  Ownership of the digital product remains with UXPACIFIC Shop.
                </p>
              </div>

              <!-- 3 -->
              <div class="policy-block">
                <h2>3. Permitted Use</h2>
                <p>Unless otherwise stated on the product page, you may:</p>
                <ul>
                  <li>Use the digital product for personal use</li>
                  <li>
                    Use the digital product for internal business or client
                    projects
                  </li>
                  <li>Modify the product for your own use</li>
                  <li>
                    Use the product in commercial projects where the end result
                    is not resold as a competing product
                  </li>
                </ul>
              </div>

              <!-- 4 -->
              <div class="policy-block">
                <h2>4. Prohibited Use (Very Important)</h2>
                <p>You may not, under any circumstances:</p>
                <ul>
                  <li>Resell, redistribute, or share the digital product</li>
                  <li>
                    Upload the product to marketplaces, repositories, or
                    file-sharing platforms
                  </li>
                  <li>Include the product in free or paid downloads</li>
                  <li>
                    Sub-license, assign, or transfer the product to third
                    parties
                  </li>
                  <li>Claim authorship or ownership</li>
                  <li>
                    Use the product to create competing templates, tools, or
                    products
                  </li>
                  <li>
                    Use the product in a way that harms or misrepresents the
                    UXPACIFIC brand
                  </li>
                </ul>
                <p>
                  Violation of these terms constitutes a material breach of this
                  License Policy.
                </p>
              </div>

              <!-- 5 -->
              <div class="policy-block">
                <h2>5. Single User vs Team Use</h2>
                <p>
                  Unless explicitly stated otherwise, each license is valid for
                  one user or one organization. Multiple users or teams require
                  separate licenses.
                </p>
                <p>
                  Sharing files within an organization beyond the licensed scope
                  is not permitted.
                </p>
              </div>

              <!-- 6 -->
              <div class="policy-block">
                <h2>6. No Redistribution of Source Files</h2>
                <p>Even if modified, you may not:</p>
                <ul>
                  <li>Distribute source files</li>
                  <li>Share editable versions</li>
                  <li>Provide raw files to clients or third parties</li>
                </ul>
                <p>
                  End deliverables may be shared only if they do not expose the
                  original licensed files.
                </p>
              </div>

              <!-- 7 -->
              <div class="policy-block">
                <h2>7. Attribution</h2>
                <ul>
                  <li>
                    Attribution is not mandatory unless explicitly required
                  </li>
                  <li>
                    Removing or altering copyright notices embedded in files is
                    prohibited
                  </li>
                </ul>
              </div>

              <!-- 8 -->
              <div class="policy-block">
                <h2>8. License Validation & Monitoring</h2>
                <ul>
                  <li>Monitor download activity</li>
                  <li>Validate license usage</li>
                  <li>Investigate suspected misuse</li>
                </ul>
                <p>
                  Technical measures may be used to prevent unauthorized
                  distribution or access.
                </p>
              </div>

              <!-- 9 -->
              <div class="policy-block">
                <h2>9. Termination of License</h2>
                <p>This license will automatically terminate if you:</p>
                <ul>
                  <li>Violate any part of this License Policy</li>
                  <li>Engage in unauthorized distribution or misuse</li>
                </ul>
                <p>
                  Upon termination, you must immediately cease use of the
                  product and delete all copies. No refunds will be issued.
                </p>
              </div>

              <!-- 10 -->
              <div class="policy-block">
                <h2>10. No Warranty</h2>
                <p>
                  Digital products are provided “as is” and “as available.”
                  UXPACIFIC Shop does not guarantee compatibility, error-free
                  performance, or suitability for a specific purpose.
                </p>
              </div>

              <!-- 11 -->
              <div class="policy-block">
                <h2>11. Limitation of Liability</h2>
                <p>
                  To the maximum extent permitted by law, UXPACIFIC Shop shall
                  not be liable for damages arising from use or inability to use
                  digital products.
                </p>
                <p>
                  Liability, if any, shall be limited to the amount paid for the
                  digital product.
                </p>
              </div>

              <!-- 12 -->
              <div class="policy-block">
                <h2>12. Enforcement & Legal Action</h2>
                <p>
                  Unauthorized use or infringement may result in license
                  termination, legal action, and claims for damages or
                  injunctive relief.
                </p>
              </div>

              <!-- 13 -->
              <div class="policy-block">
                <h2>13. Policy Updates</h2>
                <p>
                  We reserve the right to update this Digital License Policy at
                  any time. The version applicable at the time of purchase shall
                  govern the license.
                </p>
              </div>

              <!-- 14 -->
              <div class="policy-block">
                <h2>14. Contact Information</h2>
                <p>
                  For licensing questions or permissions beyond this policy:
                  <br />
                  <strong>support@uxpacific.com</strong>
                  <br />
                  Serving customers worldwide
                </p>
              </div>
            </div>

            <!-- DISCLAIMER -->
            <div class="policy-section" id="disclaimer">
              <h1 class="policy-title">Disclaimer</h1>

              <div class="policy-meta">
                <span>Website: https://shop.uxpacific.com</span>|
                <span>Effective Date: 1 January 2026</span>|
                <span>Last Updated: 1 January 2026</span>
              </div>

              <p class="policy-intro">
                The information, products, and services provided by UXPACIFIC
                Shop are offered for general informational, creative, and
                commercial purposes only. By accessing, browsing, or purchasing
                from this Website, you acknowledge and agree to the terms
                outlined in this Disclaimer.
              </p>

              <p class="policy-intro">
                This Disclaimer applies globally and should be read in
                conjunction with our Privacy Policy, Terms & Conditions, Refund
                & Returns Policy, Shipping Policy, and Digital License Policy.
              </p>

              <!-- 1 -->
              <div class="policy-block">
                <h2>1. General Information Disclaimer</h2>
                <p>
                  All content available on UXPACIFIC Shop, including text,
                  graphics, images, templates, designs, and descriptions, is
                  provided “as is” and “as available.”
                </p>
                <p>We make no guarantees or warranties that:</p>
                <ul>
                  <li>Information is error-free</li>
                  <li>Content is complete or up to date</li>
                  <li>Products will meet every individual expectation</li>
                </ul>
                <p>
                  Use of the Website and its content is entirely at your own
                  risk.
                </p>
              </div>

              <!-- 2 -->
              <div class="policy-block">
                <h2>2. Digital Products Disclaimer</h2>
                <p>Digital products:</p>
                <ul>
                  <li>Are provided for creative and professional use</li>
                  <li>May require specific software or technical knowledge</li>
                  <li>Are not guaranteed to be compatible with all systems</li>
                </ul>
                <p>
                  UXPACIFIC Shop does not warrant that digital products will:
                </p>
                <ul>
                  <li>Be free from errors or bugs</li>
                  <li>Produce specific results</li>
                  <li>Suit a particular business or technical requirement</li>
                </ul>
              </div>

              <!-- 3 -->
              <div class="policy-block">
                <h2>3. Physical Products Disclaimer</h2>
                <ul>
                  <li>Designed for lawful, normal use</li>
                  <li>
                    May show minor variations in color, size, texture, or finish
                    due to manufacturing or display differences
                  </li>
                </ul>
                <p>We are not responsible for:</p>
                <ul>
                  <li>Minor aesthetic variations</li>
                  <li>Improper use or handling</li>
                  <li>Wear and tear over time</li>
                </ul>
              </div>

              <!-- 4 -->
              <div class="policy-block">
                <h2>4. No Professional Advice</h2>
                <p>
                  Nothing on this Website constitutes legal, business,
                  financial, or technical advice. Any reliance you place on
                  provided content or resources is at your own discretion and
                  risk.
                </p>
              </div>

              <!-- 5 -->
              <div class="policy-block">
                <h2>5. Third-Party Services & Links</h2>
                <p>
                  Our Website may contain links to third-party services or
                  tools. UXPACIFIC Shop:
                </p>
                <ul>
                  <li>Does not control third-party content</li>
                  <li>Does not endorse third-party services</li>
                  <li>Is not responsible for third-party privacy practices</li>
                </ul>
              </div>

              <!-- 6 -->
              <div class="policy-block">
                <h2>6. Limitation of Liability</h2>
                <p>
                  To the maximum extent permitted by law, UXPACIFIC Shop shall
                  not be liable for any direct, indirect, incidental, or
                  consequential damages, including loss of data, profit, or
                  business interruption.
                </p>
                <p>
                  Total liability, if any, shall be limited to the amount paid
                  for the relevant product or service.
                </p>
              </div>

              <!-- 7 -->
              <div class="policy-block">
                <h2>7. Use at Your Own Risk</h2>
                <p>
                  Your use of this Website, products, and services is entirely
                  at your own risk. We are not responsible for technical issues,
                  incompatibility, misuse, or unauthorized use of digital files.
                </p>
              </div>

              <!-- 8 -->
              <div class="policy-block">
                <h2>8. Intellectual Property Disclaimer</h2>
                <p>
                  All intellectual property rights related to products, designs,
                  templates, content, and branding remain the exclusive property
                  of UXPACIFIC.
                </p>
              </div>

              <!-- 9 -->
              <div class="policy-block">
                <h2>9. Jurisdiction & Legal Compliance</h2>
                <p>
                  This Disclaimer shall be governed by the laws of India. Users
                  are responsible for compliance with local laws applicable in
                  their jurisdiction.
                </p>
              </div>

              <!-- 10 -->
              <div class="policy-block">
                <h2>10. Changes to This Disclaimer</h2>
                <p>
                  We reserve the right to update this Disclaimer at any time.
                  The version published on the Website at the time of access
                  shall apply.
                </p>
              </div>

              <!-- 11 -->
              <div class="policy-block">
                <h2>11. Contact Information</h2>
                <p>
                  For any questions regarding this Disclaimer:
                  <br />
                  <strong>support@uxpacific.com</strong>
                  <br />
                  Serving customers worldwide
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>
      <!-- FOOTER (exact copy from index) -->
      <footer id="" class="site-footer">
        <div class="footer-main">
          <div class="footer-top">
            <div class="footer-brand">
              <img src="img/logo1.webp" alt="UX Pacific" />
              <p>
                Design resources and merchandise trusted by creators worldwide 
                built to be used, worn, and valued.
              </p>
              <div class="footer-socials">
                <a
                  href="https://dribbble.com/social-ux-pacific"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/bl.webp" alt="Dribbble" />
                </a>

                <a
                  href="https://www.instagram.com/official_uxpacific/"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/i.webp" alt="Instagram" />
                </a>

                <a
                  href="https://www.linkedin.com/company/uxpacific/"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/in1.png" alt="LinkedIn" />
                </a>

                <a
                  href="https://in.pinterest.com/uxpacific/"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/p.webp" alt="Pinterest" />
                </a>

                <a
                  href="https://www.behance.net/ux_pacific"
                  target="_blank"
                  rel="noopener"
                >
                  <img src="img/be.webp" alt="Behance" />
                </a>
              </div>
            </div>

            <div class="footer-contact">
              <p>Support : +91 9274061063&nbsp;&nbsp;&nbsp;&nbsp;|</p>
              <p>
                Email :
                <a
                  href="https://mail.google.com/mail/?view=cm&fs=1&to=support@uxpacific.com"
                  style="text-decoration: none; color: inherit"
                  target="_blank"
                  >support@uxpacific.com</a
                >
                &nbsp;&nbsp;&nbsp;&nbsp;
              </p>
              <!-- <p>UX Pacific, Ahmedabad.</p> -->
            </div>
          </div>
        </div>

        <div class="footer-bottom">
          <p>&copy; <?php echo date('Y'); ?> UX Pacific. All rights reserved.</p>
          <div class="footer-links">
            <a href="policies.php" target="">Our Policies </a>
            <span>•</span>
            <a href="contact.php" style="text-decoration: none;">Contact Us</a>
          </div>
        </div>
        </div>
      </footer>
    </div>
    <!-- ================= JS (Policy Switch) ================= -->
    <script>
      const policyButtons = document.querySelectorAll(".filter-pill");
      const policySections = document.querySelectorAll(".policy-section");

      policyButtons.forEach((button) => {
        button.addEventListener("click", () => {
          // Remove active from all buttons
          policyButtons.forEach((btn) => btn.classList.remove("active"));

          // Hide all policy sections
          policySections.forEach((section) =>
            section.classList.remove("active")
          );

          // Activate clicked button
          button.classList.add("active");

          // Show only selected policy
          const policyId = button.getAttribute("data-policy");
          document.getElementById(policyId).classList.add("active");
        });
      });
    </script>

    <script src="<?php echo htmlspecialchars(asset_url('script.js')); ?>"></script>
  </body>
</html>

