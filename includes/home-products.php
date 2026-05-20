<?php
if (!isset($conn)) {
    @require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/uxp-product-card.php';
?>
<div class="uxp-product-grid">
<?php
if (isset($conn)) {
    $sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC LIMIT 4";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $name_raw = $row['name'];
            $desc = "A clean design asset designed for communities, profiles and achievements.";
            $specs = "Size: PNG / SVG / 1024px";
            if (strpos(strtolower($name_raw), 'workbook') !== false) {
                $desc = "A practical workbook for UX learners and designers.";
                $specs = "Size: A4 / Digital PDF";
            } elseif (strpos(strtolower($name_raw), 'template') !== false) {
                $desc = "Premium mobile mockup with clean branding and soft fabric shadows.";
                $specs = "Size: Figma File / Auto Layout Ready";
            }

            echo uxp_render_product_card(
                $row['id'],
                $name_raw,
                $row['category'] ?? '',
                $row['image'],
                (float)$row['price'],
                !empty($row['old_price']) ? (float)$row['old_price'] : 0,
                $row['rating'] ?: '4.5',
                $desc,
                $specs
            );
        }
    } else {
        $fallback = [
            ['id' => 1, 'name' => 'UXPacific Badge Pack', 'category' => 'Badges', 'image' => 'img/poster.webp', 'price' => 199, 'old' => 499, 'rating' => '4.5', 'desc' => 'A clean badge pack designed for communities, profiles, and achievements.', 'specs' => 'Size: PNG / SVG / 1024px'],
            ['id' => 2, 'name' => 'UXPacific UI Template', 'category' => 'Templates', 'image' => 'img/poster1.webp', 'price' => 399, 'old' => 499, 'rating' => '4.5', 'desc' => 'Premium hoodie mockup with clean branding and soft fabric shadows.', 'specs' => 'Size: Figma File / Auto Layout Ready'],
            ['id' => 3, 'name' => 'UXPacific UX Workbook', 'category' => 'Workbook', 'image' => 'img/poster2.webp', 'price' => 499, 'old' => 499, 'rating' => '4.5', 'desc' => 'A practical workbook for UX learners and designers.', 'specs' => 'Size: A4 / Digital PDF'],
            ['id' => 4, 'name' => 'UXPacific UX Workbook', 'category' => 'Workbook', 'image' => 'img/poster3.webp', 'price' => 499, 'old' => 499, 'rating' => '4.5', 'desc' => 'A practical workbook for UX learners and designers.', 'specs' => 'Size: A4 / Digital PDF'],
        ];
        foreach ($fallback as $item) {
            echo uxp_render_product_card($item['id'], $item['name'], $item['category'], $item['image'], $item['price'], $item['old'], $item['rating'], $item['desc'], $item['specs']);
        }
    }
}
?>
</div>
