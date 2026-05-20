<?php
if (!function_exists('uxp_render_product_card')) {
    function uxp_render_product_card($id, $name, $category, $image, $raw_price, $raw_old, $rating, $desc, $specs) {
        $name_esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $category_esc = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
        $image_esc = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        $desc_esc = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
        $specs_esc = htmlspecialchars($specs, ENT_QUOTES, 'UTF-8');
        $rating_esc = htmlspecialchars((string)$rating, ENT_QUOTES, 'UTF-8');
        $price_fmt = number_format($raw_price);
        $old_price_html = '';
        if (!empty($raw_old) && $raw_old > 0) {
            $discount_pct = max(0, round((1 - $raw_price / $raw_old) * 100));
            $old_price_html = '<span class="uxp-old-price">&#8377;' . number_format($raw_old) . ' (' . $discount_pct . '% OFF)</span>';
        }
        $js_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $js_image = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        $product_url = 'product.php?id=' . (int)$id;

        return '
        <article class="uxp-product-card" data-category="' . $category_esc . '">
            <a href="' . $product_url . '" class="uxp-product-media" aria-label="View ' . $name_esc . '">
                <img src="' . $image_esc . '" alt="' . $name_esc . '" loading="lazy" width="480" height="360" onerror="this.src=\'img/poster.webp\'" />
                <span class="uxp-product-badge-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3v18M3 12h18"></path>
                        <circle cx="12" cy="12" r="8"></circle>
                    </svg>
                </span>
            </a>
            <div class="uxp-product-body">
                <div class="uxp-product-title-row">
                    <h3>' . $name_esc . '</h3>
                    <div class="uxp-rating" aria-label="Rating ' . $rating_esc . ' out of 5">
                        <span aria-hidden="true">&#9733;</span>
                        <b>' . $rating_esc . '</b>
                    </div>
                </div>
                <p>' . $desc_esc . '</p>
                <p class="uxp-product-spec">' . $specs_esc . '</p>
                <div class="uxp-product-meta">
                    <div class="uxp-product-price">
                        &#8377;' . $price_fmt . $old_price_html . '
                    </div>
                </div>
                <div class="uxp-product-actions">
                    <a href="' . $product_url . '" class="uxp-card-btn uxp-card-btn-primary">Buy Now</a>
                    <button class="uxp-card-btn uxp-card-btn-secondary" type="button" onclick="addToCart(\'' . (int)$id . '\', null, 1, {name: \'' . $js_name . '\', price: ' . (float)$raw_price . ', image: \'' . $js_image . '\', category: \'' . $category_esc . '\'})">Add to Cart</button>
                </div>
            </div>
        </article>';
    }
}
