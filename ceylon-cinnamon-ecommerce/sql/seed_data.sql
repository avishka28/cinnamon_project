-- ============================================================================
-- Ceylon Cinnamon E-commerce - Database Seed Data
-- ============================================================================
-- This file contains sample data for development and testing purposes.
-- Requirements: 6.1 (product details), 8.1 (blog posts)
-- 
-- USAGE:
-- 1. First run schema.sql to create the database structure
-- 2. Then run this file to populate with sample data
-- 
-- mysql -u root -p ceylon_cinnamon < sql/seed_data.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- ADMIN USER
-- ============================================================================
-- Default admin credentials:
-- Email: admin@ceyloncinnamon.com
-- Password: Admin@123 (hashed with bcrypt)
-- ============================================================================

INSERT INTO `users` (`email`, `password_hash`, `first_name`, `last_name`, `phone`, `role`, `is_wholesale`, `is_active`) VALUES
('admin@ceyloncinnamon.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '+94 11 234 5678', 'admin', 0, 1),
('content@ceyloncinnamon.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Content', 'Manager', '+94 11 234 5679', 'content_manager', 0, 1),
('customer@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '+1 555 123 4567', 'customer', 0, 1),
('wholesale@example.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '+1 555 987 6543', 'customer', 1, 1);

-- ============================================================================
-- PRODUCT CATEGORIES
-- ============================================================================

INSERT INTO `categories` (`name`, `slug`, `description`, `parent_id`, `image_url`, `is_active`, `sort_order`) VALUES
('Cinnamon Sticks', 'cinnamon-sticks', 'Premium Ceylon cinnamon sticks (quills) in various grades and sizes. Hand-rolled and sun-dried for authentic flavor.', NULL, '/uploads/categories/cinnamon-sticks.jpg', 1, 1),
('Cinnamon Powder', 'cinnamon-powder', 'Finely ground Ceylon cinnamon powder, perfect for baking, cooking, and beverages.', NULL, '/uploads/categories/cinnamon-powder.jpg', 1, 2),
('Cinnamon Oil', 'cinnamon-oil', 'Pure Ceylon cinnamon essential oils extracted from bark and leaves.', NULL, '/uploads/categories/cinnamon-oil.jpg', 1, 3),
('Cinnamon Tea', 'cinnamon-tea', 'Aromatic Ceylon cinnamon tea blends for a healthy and refreshing drink.', NULL, '/uploads/categories/cinnamon-tea.jpg', 1, 4),
('Gift Sets', 'gift-sets', 'Curated gift sets featuring our finest Ceylon cinnamon products.', NULL, '/uploads/categories/gift-sets.jpg', 1, 5),
('Bark Oil', 'bark-oil', 'Premium cinnamon bark oil with high cinnamaldehyde content.', 3, '/uploads/categories/bark-oil.jpg', 1, 1),
('Leaf Oil', 'leaf-oil', 'Ceylon cinnamon leaf oil, rich in eugenol.', 3, '/uploads/categories/leaf-oil.jpg', 1, 2);

-- ============================================================================
-- PRODUCTS
-- ============================================================================

INSERT INTO `products` (`sku`, `name`, `slug`, `description`, `short_description`, `price`, `sale_price`, `weight`, `dimensions`, `stock_quantity`, `category_id`, `is_organic`, `origin`, `tags`, `meta_title`, `meta_description`, `is_active`) VALUES

-- Cinnamon Sticks Products
('CS-ALBA-100', 'Ceylon Alba Cinnamon Sticks - 100g', 'ceylon-alba-cinnamon-sticks-100g', 
'<p>Our premium Alba grade Ceylon cinnamon sticks represent the finest quality available. These delicate, paper-thin quills are hand-rolled by skilled artisans and sun-dried to preserve their natural sweetness and complex flavor profile.</p><p><strong>Features:</strong></p><ul><li>Grade: Alba (Premium)</li><li>Diameter: Less than 6mm</li><li>Length: 10-12cm</li><li>Low coumarin content</li><li>Sweet, delicate flavor</li></ul><p>Perfect for gourmet cooking, specialty beverages, and as a premium gift.</p>',
'Premium Alba grade Ceylon cinnamon sticks, hand-rolled and sun-dried. The finest quality with delicate, sweet flavor.',
24.99, NULL, 0.100, '15x10x5 cm', 150, 1, 1, 'Galle, Sri Lanka', 'alba,premium,organic,sticks,quills', 
'Premium Ceylon Alba Cinnamon Sticks 100g | Ceylon Cinnamon', 
'Buy premium Alba grade Ceylon cinnamon sticks. Hand-rolled, organic, and sun-dried for authentic flavor. Free shipping on orders over $50.', 1),

('CS-C5-250', 'Ceylon C5 Cinnamon Sticks - 250g', 'ceylon-c5-cinnamon-sticks-250g',
'<p>Our C5 grade Ceylon cinnamon sticks offer excellent quality at an accessible price point. These medium-thickness quills are perfect for everyday cooking and baking.</p><p><strong>Features:</strong></p><ul><li>Grade: C5 (Standard)</li><li>Diameter: 16-17mm</li><li>Length: 10-12cm</li><li>Versatile for cooking and baking</li></ul>',
'Quality C5 grade Ceylon cinnamon sticks, perfect for everyday cooking and baking.',
18.99, 15.99, 0.250, '15x10x8 cm', 200, 1, 0, 'Matara, Sri Lanka', 'c5,standard,sticks,cooking',
'Ceylon C5 Cinnamon Sticks 250g | Everyday Quality',
'Quality C5 grade Ceylon cinnamon sticks for cooking and baking. Great value for everyday use.', 1),

('CS-C4-500', 'Ceylon C4 Cinnamon Sticks - 500g', 'ceylon-c4-cinnamon-sticks-500g',
'<p>Our C4 grade Ceylon cinnamon sticks are ideal for those who use cinnamon regularly. This bulk pack offers great value without compromising on quality.</p><p><strong>Features:</strong></p><ul><li>Grade: C4</li><li>Diameter: 14-16mm</li><li>Length: 10-12cm</li><li>Bulk pack for regular users</li></ul>',
'Bulk pack of C4 grade Ceylon cinnamon sticks. Great value for regular cinnamon users.',
32.99, NULL, 0.500, '20x15x10 cm', 100, 1, 0, 'Galle, Sri Lanka', 'c4,bulk,value,sticks',
'Ceylon C4 Cinnamon Sticks 500g Bulk Pack',
'Bulk pack of quality C4 Ceylon cinnamon sticks. Perfect for regular users and commercial kitchens.', 1),

-- Cinnamon Powder Products
('CP-ORG-100', 'Organic Ceylon Cinnamon Powder - 100g', 'organic-ceylon-cinnamon-powder-100g',
'<p>Our certified organic Ceylon cinnamon powder is made from premium Alba grade cinnamon sticks, finely ground to preserve maximum flavor and aroma.</p><p><strong>Features:</strong></p><ul><li>Certified Organic</li><li>Made from Alba grade sticks</li><li>Finely ground</li><li>No additives or preservatives</li><li>Resealable pouch</li></ul><p>Perfect for smoothies, baking, coffee, and health supplements.</p>',
'Certified organic Ceylon cinnamon powder made from premium Alba grade sticks.',
14.99, NULL, 0.100, '12x8x3 cm', 300, 2, 1, 'Galle, Sri Lanka', 'organic,powder,alba,health',
'Organic Ceylon Cinnamon Powder 100g | Premium Quality',
'Buy certified organic Ceylon cinnamon powder. Made from Alba grade sticks for superior flavor.', 1),

('CP-STD-250', 'Ceylon Cinnamon Powder - 250g', 'ceylon-cinnamon-powder-250g',
'<p>Our standard Ceylon cinnamon powder offers excellent quality for everyday use. Perfect for baking, cooking, and beverages.</p><p><strong>Features:</strong></p><ul><li>Finely ground</li><li>Fresh and aromatic</li><li>Resealable pouch</li><li>Great for baking</li></ul>',
'Quality Ceylon cinnamon powder for everyday cooking and baking.',
22.99, 19.99, 0.250, '15x10x5 cm', 250, 2, 0, 'Matara, Sri Lanka', 'powder,cooking,baking,standard',
'Ceylon Cinnamon Powder 250g | Everyday Quality',
'Quality Ceylon cinnamon powder for cooking and baking. Fresh ground for maximum flavor.', 1),

-- Cinnamon Oil Products
('CO-BARK-30', 'Ceylon Cinnamon Bark Oil - 30ml', 'ceylon-cinnamon-bark-oil-30ml',
'<p>Our pure Ceylon cinnamon bark oil is steam-distilled from premium cinnamon bark. It contains high levels of cinnamaldehyde, giving it a warm, spicy aroma.</p><p><strong>Features:</strong></p><ul><li>100% Pure essential oil</li><li>Steam distilled</li><li>High cinnamaldehyde content (65-75%)</li><li>Warm, spicy aroma</li><li>Glass bottle with dropper</li></ul><p>Uses: Aromatherapy, massage (diluted), natural cleaning products.</p>',
'Pure Ceylon cinnamon bark essential oil with high cinnamaldehyde content.',
34.99, NULL, 0.050, '8x3x3 cm', 80, 6, 1, 'Galle, Sri Lanka', 'bark oil,essential oil,aromatherapy,pure',
'Pure Ceylon Cinnamon Bark Oil 30ml | Essential Oil',
'Buy pure Ceylon cinnamon bark essential oil. Steam distilled with high cinnamaldehyde content.', 1),

('CO-LEAF-30', 'Ceylon Cinnamon Leaf Oil - 30ml', 'ceylon-cinnamon-leaf-oil-30ml',
'<p>Our Ceylon cinnamon leaf oil is steam-distilled from fresh cinnamon leaves. Rich in eugenol, it has a warm, clove-like aroma.</p><p><strong>Features:</strong></p><ul><li>100% Pure essential oil</li><li>Steam distilled from leaves</li><li>High eugenol content (70-85%)</li><li>Warm, clove-like aroma</li><li>Glass bottle with dropper</li></ul><p>Uses: Aromatherapy, natural insect repellent, massage (diluted).</p>',
'Pure Ceylon cinnamon leaf essential oil, rich in eugenol.',
19.99, NULL, 0.050, '8x3x3 cm', 120, 7, 1, 'Galle, Sri Lanka', 'leaf oil,essential oil,eugenol,natural',
'Pure Ceylon Cinnamon Leaf Oil 30ml | Essential Oil',
'Buy pure Ceylon cinnamon leaf essential oil. Rich in eugenol with warm, clove-like aroma.', 1),

-- Cinnamon Tea Products
('CT-PURE-20', 'Pure Ceylon Cinnamon Tea - 20 Bags', 'pure-ceylon-cinnamon-tea-20-bags',
'<p>Our pure Ceylon cinnamon tea is made from 100% Ceylon cinnamon bark pieces. Enjoy the natural sweetness and health benefits of authentic Ceylon cinnamon.</p><p><strong>Features:</strong></p><ul><li>100% Ceylon cinnamon</li><li>20 individually wrapped tea bags</li><li>Caffeine-free</li><li>Natural sweetness</li><li>No artificial flavors</li></ul><p>Health benefits: May help regulate blood sugar, support digestion, and boost immunity.</p>',
'Pure Ceylon cinnamon tea bags. 100% natural, caffeine-free.',
12.99, NULL, 0.060, '12x8x6 cm', 200, 4, 1, 'Galle, Sri Lanka', 'tea,pure,caffeine-free,health',
'Pure Ceylon Cinnamon Tea 20 Bags | Caffeine Free',
'Buy pure Ceylon cinnamon tea. 100% natural, caffeine-free with natural sweetness.', 1),

('CT-GINGER-20', 'Ceylon Cinnamon & Ginger Tea - 20 Bags', 'ceylon-cinnamon-ginger-tea-20-bags',
'<p>A warming blend of Ceylon cinnamon and Sri Lankan ginger. Perfect for cold days or when you need a soothing, spicy drink.</p><p><strong>Features:</strong></p><ul><li>Ceylon cinnamon + Sri Lankan ginger</li><li>20 individually wrapped tea bags</li><li>Caffeine-free</li><li>Warming and soothing</li></ul>',
'Warming blend of Ceylon cinnamon and Sri Lankan ginger tea.',
14.99, 12.99, 0.060, '12x8x6 cm', 150, 4, 1, 'Galle, Sri Lanka', 'tea,ginger,blend,warming',
'Ceylon Cinnamon & Ginger Tea 20 Bags',
'Warming Ceylon cinnamon and ginger tea blend. Perfect for cold days.', 1),

-- Gift Sets
('GS-PREMIUM', 'Premium Ceylon Cinnamon Gift Set', 'premium-ceylon-cinnamon-gift-set',
'<p>The perfect gift for cinnamon lovers! This premium gift set includes our finest Ceylon cinnamon products beautifully packaged in an elegant gift box.</p><p><strong>Includes:</strong></p><ul><li>Alba Cinnamon Sticks - 50g</li><li>Organic Cinnamon Powder - 50g</li><li>Cinnamon Bark Oil - 10ml</li><li>Pure Cinnamon Tea - 10 bags</li><li>Recipe booklet</li></ul><p>Presented in a handcrafted wooden gift box.</p>',
'Premium gift set with Alba sticks, organic powder, bark oil, and tea in elegant packaging.',
79.99, 69.99, 0.400, '25x20x10 cm', 50, 5, 1, 'Galle, Sri Lanka', 'gift,premium,set,wooden box',
'Premium Ceylon Cinnamon Gift Set | Luxury Gift Box',
'Premium Ceylon cinnamon gift set in handcrafted wooden box. Perfect gift for cinnamon lovers.', 1),

('GS-STARTER', 'Ceylon Cinnamon Starter Kit', 'ceylon-cinnamon-starter-kit',
'<p>New to Ceylon cinnamon? Start your journey with this carefully curated starter kit featuring our most popular products.</p><p><strong>Includes:</strong></p><ul><li>C5 Cinnamon Sticks - 50g</li><li>Cinnamon Powder - 50g</li><li>Pure Cinnamon Tea - 10 bags</li><li>Usage guide</li></ul>',
'Starter kit with cinnamon sticks, powder, and tea. Perfect for beginners.',
34.99, NULL, 0.200, '20x15x8 cm', 100, 5, 0, 'Sri Lanka', 'gift,starter,kit,beginner',
'Ceylon Cinnamon Starter Kit | Perfect for Beginners',
'Ceylon cinnamon starter kit with sticks, powder, and tea. Great introduction to Ceylon cinnamon.', 1);


-- ============================================================================
-- PRODUCT IMAGES
-- ============================================================================

INSERT INTO `product_images` (`product_id`, `image_url`, `alt_text`, `is_primary`, `sort_order`) VALUES
-- Alba Cinnamon Sticks
(1, '/uploads/products/alba-sticks-main.jpg', 'Ceylon Alba Cinnamon Sticks', 1, 1),
(1, '/uploads/products/alba-sticks-detail.jpg', 'Alba Cinnamon Sticks Close-up', 0, 2),
(1, '/uploads/products/alba-sticks-package.jpg', 'Alba Cinnamon Sticks Package', 0, 3),
-- C5 Cinnamon Sticks
(2, '/uploads/products/c5-sticks-main.jpg', 'Ceylon C5 Cinnamon Sticks', 1, 1),
(2, '/uploads/products/c5-sticks-detail.jpg', 'C5 Cinnamon Sticks Detail', 0, 2),
-- C4 Cinnamon Sticks
(3, '/uploads/products/c4-sticks-main.jpg', 'Ceylon C4 Cinnamon Sticks Bulk', 1, 1),
-- Organic Powder
(4, '/uploads/products/organic-powder-main.jpg', 'Organic Ceylon Cinnamon Powder', 1, 1),
(4, '/uploads/products/organic-powder-spoon.jpg', 'Organic Cinnamon Powder on Spoon', 0, 2),
-- Standard Powder
(5, '/uploads/products/powder-main.jpg', 'Ceylon Cinnamon Powder', 1, 1),
-- Bark Oil
(6, '/uploads/products/bark-oil-main.jpg', 'Ceylon Cinnamon Bark Oil', 1, 1),
(6, '/uploads/products/bark-oil-dropper.jpg', 'Bark Oil with Dropper', 0, 2),
-- Leaf Oil
(7, '/uploads/products/leaf-oil-main.jpg', 'Ceylon Cinnamon Leaf Oil', 1, 1),
-- Pure Tea
(8, '/uploads/products/pure-tea-main.jpg', 'Pure Ceylon Cinnamon Tea', 1, 1),
(8, '/uploads/products/pure-tea-cup.jpg', 'Cinnamon Tea in Cup', 0, 2),
-- Ginger Tea
(9, '/uploads/products/ginger-tea-main.jpg', 'Ceylon Cinnamon & Ginger Tea', 1, 1),
-- Premium Gift Set
(10, '/uploads/products/gift-premium-main.jpg', 'Premium Ceylon Cinnamon Gift Set', 1, 1),
(10, '/uploads/products/gift-premium-open.jpg', 'Premium Gift Set Contents', 0, 2),
(10, '/uploads/products/gift-premium-box.jpg', 'Wooden Gift Box', 0, 3),
-- Starter Kit
(11, '/uploads/products/starter-kit-main.jpg', 'Ceylon Cinnamon Starter Kit', 1, 1);

-- ============================================================================
-- PRODUCT REVIEWS
-- ============================================================================

INSERT INTO `product_reviews` (`product_id`, `user_id`, `rating`, `review_text`, `is_approved`) VALUES
(1, 3, 5, 'Absolutely the best cinnamon I have ever tasted! The Alba grade is so delicate and sweet. Worth every penny.', 1),
(1, NULL, 5, 'Amazing quality! I use these sticks in my morning coffee and the flavor is incredible.', 1),
(1, NULL, 4, 'Great product, fast shipping. The sticks are beautiful and fragrant.', 1),
(2, 3, 5, 'Perfect for everyday use. Great value for the quality.', 1),
(2, NULL, 4, 'Good quality cinnamon sticks. I use them for cooking and baking.', 1),
(4, 3, 5, 'The organic powder is so fresh and aromatic. I add it to my smoothies every day.', 1),
(4, NULL, 5, 'Best cinnamon powder I have found. You can really taste the difference from store-bought.', 1),
(6, NULL, 5, 'The bark oil is potent and pure. A little goes a long way. Great for aromatherapy.', 1),
(8, 3, 4, 'Nice tea with natural sweetness. I drink it before bed to help with sleep.', 1),
(10, NULL, 5, 'Bought this as a gift and it was a huge hit! Beautiful packaging and quality products.', 1);

-- ============================================================================
-- BLOG CATEGORIES
-- ============================================================================

INSERT INTO `blog_categories` (`name`, `slug`, `description`, `is_active`, `sort_order`) VALUES
('Health & Wellness', 'health-wellness', 'Articles about the health benefits of Ceylon cinnamon', 1, 1),
('Recipes', 'recipes', 'Delicious recipes featuring Ceylon cinnamon', 1, 2),
('Cinnamon Facts', 'cinnamon-facts', 'Educational content about Ceylon cinnamon', 1, 3),
('News & Updates', 'news-updates', 'Company news and product updates', 1, 4),
('Sustainability', 'sustainability', 'Our commitment to sustainable farming practices', 1, 5);

-- ============================================================================
-- BLOG POSTS
-- ============================================================================

INSERT INTO `blog_posts` (`title`, `slug`, `excerpt`, `content`, `featured_image`, `category_id`, `author_id`, `tags`, `meta_title`, `meta_description`, `status`, `published_at`) VALUES
('The Health Benefits of Ceylon Cinnamon', 'health-benefits-ceylon-cinnamon',
'Discover the amazing health benefits of true Ceylon cinnamon, from blood sugar regulation to anti-inflammatory properties.',
'<h2>Introduction</h2><p>Ceylon cinnamon, often called "true cinnamon," has been prized for centuries not only for its delicate flavor but also for its remarkable health benefits. Unlike its more common cousin, Cassia cinnamon, Ceylon cinnamon contains significantly lower levels of coumarin, making it safer for regular consumption.</p><h2>Blood Sugar Regulation</h2><p>One of the most well-researched benefits of Ceylon cinnamon is its ability to help regulate blood sugar levels. Studies have shown that cinnamon can improve insulin sensitivity and help lower fasting blood sugar levels.</p><h2>Anti-Inflammatory Properties</h2><p>Ceylon cinnamon contains powerful antioxidants and anti-inflammatory compounds that can help reduce inflammation in the body. This makes it beneficial for conditions like arthritis and heart disease.</p><h2>Heart Health</h2><p>Regular consumption of Ceylon cinnamon may help improve heart health by reducing cholesterol levels and blood pressure.</p><h2>How to Incorporate Ceylon Cinnamon</h2><ul><li>Add to morning coffee or tea</li><li>Sprinkle on oatmeal or yogurt</li><li>Use in baking and cooking</li><li>Make cinnamon water</li></ul><p>Start with 1/2 to 1 teaspoon daily and enjoy the benefits of this amazing spice!</p>',
'/uploads/blog/health-benefits.jpg', 1, 1, 'health,benefits,blood sugar,anti-inflammatory',
'Health Benefits of Ceylon Cinnamon | Ceylon Cinnamon',
'Discover the amazing health benefits of Ceylon cinnamon including blood sugar regulation and anti-inflammatory properties.',
'published', NOW() - INTERVAL 30 DAY),

('Ceylon Cinnamon vs Cassia: Know the Difference', 'ceylon-cinnamon-vs-cassia',
'Learn how to distinguish between true Ceylon cinnamon and common Cassia cinnamon, and why it matters for your health.',
'<h2>Not All Cinnamon is Created Equal</h2><p>When you buy cinnamon from your local grocery store, chances are you are getting Cassia cinnamon, not true Ceylon cinnamon. While both come from the bark of cinnamon trees, they have significant differences.</p><h2>Visual Differences</h2><p><strong>Ceylon Cinnamon:</strong></p><ul><li>Light tan to brown color</li><li>Thin, paper-like layers</li><li>Soft and crumbly texture</li><li>Multiple layers rolled together</li></ul><p><strong>Cassia Cinnamon:</strong></p><ul><li>Dark reddish-brown color</li><li>Thick, hard bark</li><li>Single rolled layer</li><li>Difficult to grind</li></ul><h2>Flavor Profile</h2><p>Ceylon cinnamon has a delicate, sweet, and complex flavor, while Cassia has a stronger, more pungent taste.</p><h2>Coumarin Content</h2><p>The most important difference is coumarin content. Cassia contains high levels of coumarin (up to 1%), which can be harmful to the liver in large amounts. Ceylon cinnamon contains only trace amounts (0.004%).</p><h2>Conclusion</h2><p>For regular consumption, Ceylon cinnamon is the safer and more flavorful choice.</p>',
'/uploads/blog/ceylon-vs-cassia.jpg', 3, 1, 'ceylon,cassia,difference,coumarin',
'Ceylon Cinnamon vs Cassia: Know the Difference',
'Learn the key differences between Ceylon cinnamon and Cassia cinnamon, including coumarin content and health implications.',
'published', NOW() - INTERVAL 20 DAY),

('5 Delicious Cinnamon Recipes for Fall', 'delicious-cinnamon-recipes-fall',
'Warm up your autumn with these five delicious recipes featuring Ceylon cinnamon.',
'<h2>Embrace the Season with Cinnamon</h2><p>Fall is the perfect time to enjoy the warm, comforting flavor of Ceylon cinnamon. Here are five recipes to try this season.</p><h2>1. Cinnamon Apple Crisp</h2><p>A classic fall dessert made even better with Ceylon cinnamon...</p><h2>2. Spiced Cinnamon Latte</h2><p>Skip the coffee shop and make your own at home...</p><h2>3. Cinnamon Roasted Sweet Potatoes</h2><p>A healthy side dish with a touch of sweetness...</p><h2>4. Ceylon Cinnamon Rolls</h2><p>Homemade cinnamon rolls using authentic Ceylon cinnamon...</p><h2>5. Warm Cinnamon Oatmeal</h2><p>Start your morning right with this healthy breakfast...</p>',
'/uploads/blog/fall-recipes.jpg', 2, 2, 'recipes,fall,baking,cooking',
'5 Delicious Cinnamon Recipes for Fall',
'Warm up your autumn with these five delicious recipes featuring Ceylon cinnamon.',
'published', NOW() - INTERVAL 10 DAY),

('Our Sustainable Farming Practices', 'sustainable-farming-practices',
'Learn about our commitment to sustainable and ethical cinnamon farming in Sri Lanka.',
'<h2>Sustainability at Our Core</h2><p>At Ceylon Cinnamon, we believe that great products come from responsible practices. Our commitment to sustainability guides everything we do.</p><h2>Organic Farming</h2><p>Many of our products are certified organic, grown without synthetic pesticides or fertilizers...</p><h2>Fair Trade Partnerships</h2><p>We work directly with local farmers, ensuring fair wages and good working conditions...</p><h2>Environmental Conservation</h2><p>Our farming practices protect local ecosystems and biodiversity...</p><h2>Community Support</h2><p>We invest in local communities through education and healthcare initiatives...</p>',
'/uploads/blog/sustainability.jpg', 5, 1, 'sustainability,organic,fair trade,environment',
'Our Sustainable Farming Practices | Ceylon Cinnamon',
'Learn about our commitment to sustainable and ethical cinnamon farming in Sri Lanka.',
'published', NOW() - INTERVAL 5 DAY);


-- ============================================================================
-- CERTIFICATES
-- ============================================================================

INSERT INTO `certificates` (`title`, `description`, `file_url`, `file_type`, `thumbnail_url`, `is_active`, `sort_order`) VALUES
('USDA Organic Certification', 'Our organic products are certified by the USDA National Organic Program, ensuring they meet strict organic standards.', '/uploads/certificates/usda-organic.pdf', 'pdf', '/uploads/certificates/usda-organic-thumb.jpg', 1, 1),
('EU Organic Certification', 'European Union organic certification for our products exported to EU countries.', '/uploads/certificates/eu-organic.pdf', 'pdf', '/uploads/certificates/eu-organic-thumb.jpg', 1, 2),
('Sri Lanka Export Development Board', 'Certified exporter registered with the Sri Lanka Export Development Board.', '/uploads/certificates/sledb.pdf', 'pdf', '/uploads/certificates/sledb-thumb.jpg', 1, 3),
('ISO 22000 Food Safety', 'Our processing facilities are ISO 22000 certified for food safety management.', '/uploads/certificates/iso-22000.pdf', 'pdf', '/uploads/certificates/iso-22000-thumb.jpg', 1, 4),
('Fair Trade Certified', 'Fair Trade certification ensuring ethical sourcing and fair wages for farmers.', '/uploads/certificates/fair-trade.jpg', 'image', '/uploads/certificates/fair-trade-thumb.jpg', 1, 5);

-- ============================================================================
-- GALLERY ITEMS
-- ============================================================================

INSERT INTO `gallery_items` (`title`, `description`, `file_url`, `file_type`, `thumbnail_url`, `is_active`, `sort_order`) VALUES
('Cinnamon Plantation', 'Our cinnamon plantation in Galle, Sri Lanka', '/uploads/gallery/plantation-1.jpg', 'image', '/uploads/gallery/plantation-1-thumb.jpg', 1, 1),
('Hand Peeling Process', 'Skilled workers hand-peeling cinnamon bark', '/uploads/gallery/peeling-1.jpg', 'image', '/uploads/gallery/peeling-1-thumb.jpg', 1, 2),
('Rolling Cinnamon Quills', 'Traditional method of rolling cinnamon sticks', '/uploads/gallery/rolling-1.jpg', 'image', '/uploads/gallery/rolling-1-thumb.jpg', 1, 3),
('Sun Drying', 'Cinnamon sticks drying in the Sri Lankan sun', '/uploads/gallery/drying-1.jpg', 'image', '/uploads/gallery/drying-1-thumb.jpg', 1, 4),
('Quality Inspection', 'Our quality control team inspecting cinnamon', '/uploads/gallery/inspection-1.jpg', 'image', '/uploads/gallery/inspection-1-thumb.jpg', 1, 5),
('Packaging Facility', 'Modern packaging facility ensuring freshness', '/uploads/gallery/packaging-1.jpg', 'image', '/uploads/gallery/packaging-1-thumb.jpg', 1, 6),
('Cinnamon Harvest Video', 'Watch how we harvest Ceylon cinnamon', '/uploads/gallery/harvest-video.mp4', 'video', '/uploads/gallery/harvest-video-thumb.jpg', 1, 7),
('Processing Tour', 'Virtual tour of our processing facility', '/uploads/gallery/processing-tour.mp4', 'video', '/uploads/gallery/processing-tour-thumb.jpg', 1, 8);

-- ============================================================================
-- SHIPPING ZONES
-- ============================================================================

INSERT INTO `shipping_zones` (`name`, `countries`, `is_active`, `sort_order`) VALUES
('Sri Lanka (Domestic)', '["LK"]', 1, 1),
('United States', '["US"]', 1, 2),
('European Union', '["AT","BE","BG","HR","CY","CZ","DK","EE","FI","FR","DE","GR","HU","IE","IT","LV","LT","LU","MT","NL","PL","PT","RO","SK","SI","ES","SE"]', 1, 3),
('United Kingdom', '["GB"]', 1, 4),
('Canada', '["CA"]', 1, 5),
('Australia & New Zealand', '["AU","NZ"]', 1, 6),
('Asia Pacific', '["JP","KR","SG","MY","TH","ID","PH","VN","HK","TW"]', 1, 7),
('Middle East', '["AE","SA","QA","KW","BH","OM"]', 1, 8),
('Rest of World', '["*"]', 1, 99);

-- ============================================================================
-- SHIPPING METHODS
-- ============================================================================

INSERT INTO `shipping_methods` (`zone_id`, `name`, `description`, `base_cost`, `cost_per_kg`, `min_weight`, `max_weight`, `min_order_amount`, `free_shipping_threshold`, `estimated_days_min`, `estimated_days_max`, `is_active`, `sort_order`) VALUES
-- Sri Lanka
(1, 'Standard Delivery', 'Delivery within 3-5 business days', 2.00, 0.50, NULL, NULL, NULL, 25.00, 3, 5, 1, 1),
(1, 'Express Delivery', 'Next day delivery in major cities', 5.00, 1.00, NULL, 5.000, NULL, NULL, 1, 2, 1, 2),
-- United States
(2, 'Standard Shipping', 'USPS Priority Mail', 9.99, 2.00, NULL, NULL, NULL, 75.00, 7, 14, 1, 1),
(2, 'Express Shipping', 'DHL Express', 24.99, 4.00, NULL, 10.000, NULL, NULL, 3, 5, 1, 2),
-- European Union
(3, 'Standard Shipping', 'Standard international shipping', 12.99, 2.50, NULL, NULL, NULL, 100.00, 10, 18, 1, 1),
(3, 'Express Shipping', 'DHL Express', 29.99, 5.00, NULL, 10.000, NULL, NULL, 4, 7, 1, 2),
-- United Kingdom
(4, 'Standard Shipping', 'Royal Mail International', 10.99, 2.00, NULL, NULL, NULL, 75.00, 7, 14, 1, 1),
(4, 'Express Shipping', 'DHL Express', 24.99, 4.00, NULL, 10.000, NULL, NULL, 3, 5, 1, 2),
-- Canada
(5, 'Standard Shipping', 'Canada Post', 11.99, 2.50, NULL, NULL, NULL, 80.00, 10, 18, 1, 1),
(5, 'Express Shipping', 'DHL Express', 29.99, 5.00, NULL, 10.000, NULL, NULL, 4, 7, 1, 2),
-- Australia & New Zealand
(6, 'Standard Shipping', 'Australia Post / NZ Post', 14.99, 3.00, NULL, NULL, NULL, 100.00, 12, 21, 1, 1),
(6, 'Express Shipping', 'DHL Express', 34.99, 6.00, NULL, 10.000, NULL, NULL, 5, 8, 1, 2),
-- Asia Pacific
(7, 'Standard Shipping', 'Standard international shipping', 11.99, 2.50, NULL, NULL, NULL, 80.00, 10, 18, 1, 1),
(7, 'Express Shipping', 'DHL Express', 29.99, 5.00, NULL, 10.000, NULL, NULL, 4, 7, 1, 2),
-- Middle East
(8, 'Standard Shipping', 'Standard international shipping', 14.99, 3.00, NULL, NULL, NULL, 100.00, 12, 21, 1, 1),
(8, 'Express Shipping', 'DHL Express', 34.99, 6.00, NULL, 10.000, NULL, NULL, 5, 8, 1, 2),
-- Rest of World
(9, 'Standard Shipping', 'International shipping', 19.99, 4.00, NULL, NULL, NULL, 150.00, 14, 28, 1, 1),
(9, 'Express Shipping', 'DHL Express', 44.99, 8.00, NULL, 10.000, NULL, NULL, 7, 14, 1, 2);

-- ============================================================================
-- WHOLESALE PRICE TIERS
-- ============================================================================

INSERT INTO `wholesale_price_tiers` (`product_id`, `min_quantity`, `max_quantity`, `price`, `discount_percentage`, `is_active`) VALUES
-- Alba Cinnamon Sticks
(1, 10, 24, 22.49, 10.00, 1),
(1, 25, 49, 19.99, 20.00, 1),
(1, 50, NULL, 17.49, 30.00, 1),
-- C5 Cinnamon Sticks
(2, 10, 24, 17.09, 10.00, 1),
(2, 25, 49, 15.19, 20.00, 1),
(2, 50, NULL, 13.29, 30.00, 1),
-- Organic Powder
(4, 10, 24, 13.49, 10.00, 1),
(4, 25, 49, 11.99, 20.00, 1),
(4, 50, NULL, 10.49, 30.00, 1),
-- Bark Oil
(6, 5, 9, 31.49, 10.00, 1),
(6, 10, 24, 27.99, 20.00, 1),
(6, 25, NULL, 24.49, 30.00, 1);

-- ============================================================================
-- SAMPLE ORDERS (for testing)
-- ============================================================================

INSERT INTO `orders` (`order_number`, `user_id`, `email`, `first_name`, `last_name`, `phone`, `shipping_address`, `billing_address`, `subtotal`, `shipping_cost`, `tax_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `notes`, `created_at`) VALUES
('CC2024000001', 3, 'customer@example.com', 'John', 'Doe', '+1 555 123 4567', '123 Main Street, Apt 4B, New York, NY 10001, United States', '123 Main Street, Apt 4B, New York, NY 10001, United States', 62.97, 9.99, 0.00, 72.96, 'stripe', 'paid', 'delivered', 'Please leave at door', NOW() - INTERVAL 15 DAY),
('CC2024000002', 3, 'customer@example.com', 'John', 'Doe', '+1 555 123 4567', '123 Main Street, Apt 4B, New York, NY 10001, United States', NULL, 34.99, 9.99, 0.00, 44.98, 'paypal', 'paid', 'shipped', NULL, NOW() - INTERVAL 5 DAY),
('CC2024000003', NULL, 'guest@example.com', 'Sarah', 'Johnson', '+1 555 456 7890', '456 Oak Avenue, Los Angeles, CA 90001, United States', NULL, 24.99, 9.99, 0.00, 34.98, 'stripe', 'paid', 'processing', NULL, NOW() - INTERVAL 2 DAY),
('CC2024000004', 4, 'wholesale@example.com', 'Jane', 'Smith', '+1 555 987 6543', '789 Business Park, Suite 100, Chicago, IL 60601, United States', '789 Business Park, Suite 100, Chicago, IL 60601, United States', 449.80, 0.00, 0.00, 449.80, 'bank_transfer', 'pending', 'pending', 'Wholesale order - awaiting bank transfer confirmation', NOW() - INTERVAL 1 DAY);

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `product_sku`, `quantity`, `price`, `total`) VALUES
-- Order 1
(1, 1, 'Ceylon Alba Cinnamon Sticks - 100g', 'CS-ALBA-100', 1, 24.99, 24.99),
(1, 4, 'Organic Ceylon Cinnamon Powder - 100g', 'CP-ORG-100', 1, 14.99, 14.99),
(1, 5, 'Ceylon Cinnamon Powder - 250g', 'CP-STD-250', 1, 19.99, 19.99),
(1, 8, 'Pure Ceylon Cinnamon Tea - 20 Bags', 'CT-PURE-20', 1, 12.99, 12.99),
-- Order 2
(2, 10, 'Premium Ceylon Cinnamon Gift Set', 'GS-PREMIUM', 1, 69.99, 69.99),
-- Order 3
(3, 1, 'Ceylon Alba Cinnamon Sticks - 100g', 'CS-ALBA-100', 1, 24.99, 24.99),
-- Order 4 (Wholesale)
(4, 1, 'Ceylon Alba Cinnamon Sticks - 100g', 'CS-ALBA-100', 20, 19.99, 399.80),
(4, 4, 'Organic Ceylon Cinnamon Powder - 100g', 'CP-ORG-100', 5, 11.99, 59.95);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF SEED DATA
-- ============================================================================
-- 
-- Default Login Credentials:
-- 
-- Admin User:
--   Email: admin@ceyloncinnamon.com
--   Password: Admin@123
-- 
-- Content Manager:
--   Email: content@ceyloncinnamon.com
--   Password: Admin@123
-- 
-- Test Customer:
--   Email: customer@example.com
--   Password: Admin@123
-- 
-- Wholesale Customer:
--   Email: wholesale@example.com
--   Password: Admin@123
-- 
-- ============================================================================
