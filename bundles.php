<?php require_once 'includes/config.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Ready-made UI/UX career bundles from UX Pacific." />

    <title>Ready-Made Career Bundles - UX Pacific Shop</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,200;0,9..40,300;0,9..40,600;1,9..40,200&family=Gabarito:wght@400;500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="style.css" />
</head>

<body class="bundles-page figma-bundles">

<?php
$headerUserId = $_SESSION['user_id'] ?? null;

$headerFirstName = trim((string) ($_SESSION['first_name'] ?? ''));
$headerLastName = trim((string) ($_SESSION['last_name'] ?? ''));

$headerUserName = trim($headerFirstName . ' ' . $headerLastName);

if ($headerUserName === '') {
    $headerUserName = 'Profile';
}
?>

<?php include 'includes/header.php'; ?>

<main class="bundles-figma-main">

    <!-- HERO -->
    <section class="figma-hero">

        <h1>
            <span>Ready-Made Career</span>
            <em>Bundles</em>
            <span>for</span>
            <em>UI/UX Designers</em>
        </h1>

        <p>
            Explore curated UI/UX learning kits, templates, case studies,
            and career resources designed to help creators grow faster.
        </p>

    </section>

    <!-- FEATURED BUNDLE -->
    <?php

    $featuredSql = "
        SELECT *
        FROM bundles
        WHERE is_active = 1
        ORDER BY updated_at DESC, id DESC
        LIMIT 1
    ";

    $featuredResult = $conn->query($featuredSql);

    if ($featuredResult && $featuredResult->num_rows > 0):

        $featured = $featuredResult->fetch_assoc();

        $featuredItems = json_decode(
            $featured['included_items'] ?? '[]',
            true
        );

        if (!is_array($featuredItems)) {
            $featuredItems = [];
        }

    ?>

    <section class="figma-featured-card">

        <div class="figma-featured-image">

            <img
                src="<?php echo htmlspecialchars($featured['image'] ?: 'img/poster.webp'); ?>"
                alt="<?php echo htmlspecialchars($featured['name']); ?>"
                onerror="this.src='img/poster.webp'"
            />

            <span>
                <?php echo htmlspecialchars($featured['badge_text'] ?: 'Best Seller'); ?>
            </span>

        </div>

        <div class="figma-featured-content">

            <h2>
                <?php echo htmlspecialchars($featured['name']); ?>
            </h2>

            <div class="figma-rating-row">

                <span class="figma-stars">
                    *****
                </span>

                <span>
                    <?php echo htmlspecialchars($featured['rating'] ?: '4.9'); ?>
                    rating
                </span>

            </div>

            <p class="figma-featured-desc">

                <?php
                echo htmlspecialchars(
                    $featured['description']
                    ?: 'Premium UI/UX career bundle.'
                );
                ?>

            </p>

            <h3>What's Included:</h3>

            <ul class="figma-featured-list">

                <?php if (!empty($featuredItems)): ?>

                    <?php foreach($featuredItems as $item): ?>

                        <li>
                            <?php echo htmlspecialchars($item); ?>
                        </li>

                    <?php endforeach; ?>

                <?php else: ?>

                    <li>Portfolio Templates</li>
                    <li>UX Workbook</li>
                    <li>Resume Kit</li>
                    <li>Interview Guide</li>

                <?php endif; ?>

            </ul>

            <div class="figma-featured-price">

                <strong>
                    ₹<?php echo number_format((float)$featured['price']); ?>
                </strong>

                <?php if (!empty($featured['old_price'])): ?>

                    <del>
                        ₹<?php echo number_format((float)$featured['old_price']); ?>
                    </del>

                <?php endif; ?>

            </div>

            <div class="figma-featured-actions">

                <a
                    class="figma-btn figma-btn-primary"
                    href="bundle-details.php?id=<?php echo (int)$featured['id']; ?>">

                    View Details

                </a>

                <button
                    class="figma-btn figma-btn-outline"
                    type="button"

                    onclick='addToCart(
                        "bundle_<?php echo (int)$featured["id"]; ?>",
                        null,
                        1,
                        {
                            name: <?php echo json_encode($featured["name"]); ?>,
                            price: <?php echo (float)$featured["price"]; ?>,
                            image: <?php echo json_encode($featured["image"]); ?>,
                            category: "Bundles",
                            description: <?php echo json_encode($featured["description"]); ?>
                        },
                        "digital"
                    )'>

                    Buy Now

                </button>

            </div>

        </div>

    </section>

    <?php endif; ?>

    <!-- BUNDLE GRID -->
    <section class="figma-card-grid" aria-label="Career bundles">

        <?php

        $bundleSql = "
            SELECT 
                b.*,
                COUNT(bi.id) AS product_count
            FROM bundles b
            LEFT JOIN bundle_items bi
                ON bi.bundle_id = b.id
            WHERE b.is_active = 1
            GROUP BY b.id
            ORDER BY b.updated_at DESC, b.id DESC
        ";

        $bundleResult = $conn->query($bundleSql);

        if ($bundleResult && $bundleResult->num_rows > 0):

            while($bundle = $bundleResult->fetch_assoc()):

                $includedItems = json_decode(
                    $bundle['included_items'] ?? '[]',
                    true
                );

                if (!is_array($includedItems)) {
                    $includedItems = [];
                }

        ?>

        <article class="uxp-bundle-card">

            <div class="uxp-bundle-image">

                <img
                    src="<?php echo htmlspecialchars($bundle['image'] ?: 'img/poster1.webp'); ?>"
                    alt="<?php echo htmlspecialchars($bundle['name']); ?>"
                    loading="lazy"
                    onerror="this.src='img/poster1.webp'"
                />

                <span>

                    <?php
                    echo htmlspecialchars(
                        $bundle['badge_text']
                        ?: 'Most Popular'
                    );
                    ?>

                </span>

            </div>

            <div class="uxp-bundle-content">

                <div class="uxp-bundle-title-row">

                    <h3>
                        <?php echo htmlspecialchars($bundle['name']); ?>
                    </h3>

                    <span class="uxp-bundle-rating">

                        &#9733;

                        <b>
                            <?php
                            echo htmlspecialchars(
                                $bundle['rating']
                                ?: '4.5'
                            );
                            ?>
                        </b>

                    </span>

                </div>

                <p>

                    <?php
                    echo htmlspecialchars(
                        $bundle['description']
                        ?: 'Premium curated UI/UX bundle.'
                    );
                    ?>

                </p>

                <ul>

                    <?php if (!empty($includedItems)): ?>

                        <?php foreach($includedItems as $item): ?>

                            <li>
                                <?php echo htmlspecialchars($item); ?>
                            </li>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <li>Premium templates</li>
                        <li>Career-ready assets</li>
                        <li>Professional resources</li>
                        <li>Design systems included</li>

                    <?php endif; ?>

                </ul>

                <div class="uxp-bundle-footer">

                    <strong>

                        ₹<?php echo number_format((float)$bundle['price']); ?>

                        <?php if (!empty($bundle['old_price'])): ?>

                            <span>
                                ₹<?php echo number_format((float)$bundle['old_price']); ?>
                            </span>

                        <?php endif; ?>

                    </strong>

                    <div>

                        <a
                            href="bundle-details.php?id=<?php echo (int)$bundle['id']; ?>"
                            class="uxp-card-btn uxp-card-btn-primary">

                            View Bundle

                        </a>

                        <button
                            class="uxp-card-btn uxp-card-btn-secondary"
                            type="button"

                            onclick='addToCart(
                                "bundle_<?php echo (int)$bundle["id"]; ?>",
                                null,
                                1,
                                {
                                    name: <?php echo json_encode($bundle["name"]); ?>,
                                    price: <?php echo (float)$bundle["price"]; ?>,
                                    image: <?php echo json_encode($bundle["image"]); ?>,
                                    category: "Bundle",
                                    description: <?php echo json_encode($bundle["description"]); ?>
                                },
                                "bundle"
                            )'>

                            Buy Now

                        </button>

                    </div>

                </div>

            </div>

        </article>

        <?php endwhile; ?>

        <?php else: ?>

            <p>No bundles available.</p>

        <?php endif; ?>

    </section>

    <!-- GROW SECTION -->
    <section class="figma-grow-section">

        <h2>How These Bundles Help You Grow</h2>

        <div class="figma-grow-grid">

            <article>
                <strong>01</strong>
                <h3>Learn Skills</h3>
                <p>Master UI/UX fundamentals</p>
            </article>

            <article>
                <strong>02</strong>
                <h3>Build Portfolio</h3>
                <p>Create stunning case studies</p>
            </article>

            <article>
                <strong>03</strong>
                <h3>Practice UX</h3>
                <p>Apply real-world methods</p>
            </article>

            <article>
                <strong>04</strong>
                <h3>Apply for Jobs</h3>
                <p>Land your dream role</p>
            </article>

        </div>

    </section>

</main>

<?php include 'includes/footer.php'; ?>

<script src="script.js"></script>

</body>
</html>