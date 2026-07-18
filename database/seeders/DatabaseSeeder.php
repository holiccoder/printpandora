<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User'],
        );

        $admin = Admin::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $categories = [
            ['name' => 'Getting Started', 'slug' => 'getting-started'],
            ['name' => 'Design Tips', 'slug' => 'design-tips'],
            ['name' => 'Product Updates', 'slug' => 'product-updates'],
            ['name' => 'Tutorials', 'slug' => 'tutorials'],
            ['name' => 'Case Studies', 'slug' => 'case-studies'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $categoryIds = Category::pluck('id')->toArray();

        $samplePosts = [
            [
                'title' => 'Welcome to PrintPandora — Your Printing Partner',
                'category' => 'getting-started',
                'body' => <<<'HTML'
<p>We&rsquo;re thrilled to launch PrintPandora &mdash; the platform that makes custom printing effortless. Whether you need business cards, flyers, apparel, or large-format signage, we&rsquo;ve got you covered.</p>
<h2>Why we built PrintPandora</h2>
<p>After years in the printing industry, we noticed a gap: most online print shops are either too complicated or too limited. PrintPandora bridges that gap with a clean interface, transparent pricing, and lightning-fast turnaround times.</p>
<p>Our mission is simple: make professional printing accessible to everyone, from freelancers to Fortune 500 companies.</p>
<h2>What you can expect</h2>
<p>Over the coming weeks, we&rsquo;ll be sharing design tips, product spotlights, and behind-the-scenes looks at our printing process. Subscribe to stay in the loop!</p>
<p>Ready to get started? Browse our product catalog or reach out to our support team &mdash; we&rsquo;re here to help.</p>
HTML,
            ],
            [
                'title' => '5 Design Tips for Print-Ready Files',
                'category' => 'design-tips',
                'body' => <<<'HTML'
<p>Sending a file to print should be the easiest part of your project. But if your file isn&rsquo;t prepared correctly, you might end up with blurry images, wrong colors, or unexpected delays. Here are five tips to make sure your designs print perfectly every time.</p>
<h2>1. Use CMYK, not RGB</h2>
<p>Monitors use RGB (red, green, blue) to display colors, but printers use CMYK (cyan, magenta, yellow, black). Always convert your files to CMYK before exporting. This ensures the colors you see on screen translate accurately to paper.</p>
<h2>2. Set the right resolution</h2>
<p>For most print jobs, 300 DPI is the sweet spot. Anything lower risks pixelation; anything higher just bloats your file size without noticeable quality gains. For large-format prints (banners, billboards), 150 DPI is usually sufficient.</p>
<h2>3. Add bleed and safe zones</h2>
<p>Bleed is the extra 3&ndash;5 mm around your design that gets trimmed off. Always extend background colors and images into the bleed area. Keep important text and logos within the safe zone (at least 5 mm from the trim edge).</p>
<h2>4. Embed or outline fonts</h2>
<p>Missing fonts are one of the most common print issues. Either embed your fonts in the PDF or convert text to outlines before exporting. This guarantees your typography looks exactly as intended.</p>
<h2>5. Proofread twice, print once</h2>
<p>It sounds obvious, but a surprising number of reprints happen because of typos. Read your content out loud, ask a colleague to review it, and check names, dates, and phone numbers carefully. A few extra minutes of proofreading can save you days of reprinting.</p>
HTML,
            ],
            [
                'title' => 'Introducing Our New Custom Apparel Line',
                'category' => 'product-updates',
                'body' => <<<'HTML'
<p>We&rsquo;re excited to announce the launch of our custom apparel line! From t-shirts and hoodies to caps and tote bags, you can now design and order personalized clothing directly through PrintPandora.</p>
<h2>What&rsquo;s new</h2>
<p>Our apparel collection features premium-quality blanks from trusted brands. Choose from a wide range of colors, sizes, and styles. You can upload your own artwork or use our built-in design tool to create something unique.</p>
<h2>Printing techniques</h2>
<p>We offer multiple printing methods to suit your needs:</p>
<ul>
<li><strong>Screen printing</strong> &mdash; Best for bulk orders with simple designs. Vibrant colors that last wash after wash.</li>
<li><strong>Direct-to-garment (DTG)</strong> &mdash; Perfect for detailed, full-color designs and small runs. No setup fees.</li>
<li><strong>Embroidery</strong> &mdash; Adds a premium, professional touch to polo shirts, caps, and jackets.</li>
</ul>
<h2>Ordering is simple</h2>
<p>Just pick your product, upload your design, choose your quantity, and we&rsquo;ll handle the rest. Orders ship within 5&ndash;7 business days. Check out our apparel catalog today!</p>
HTML,
            ],
            [
                'title' => 'How to Create Stunning Business Cards',
                'category' => 'tutorials',
                'body' => <<<'HTML'
<p>A business card is often the first physical impression someone has of your brand. In this tutorial, we&rsquo;ll walk you through creating a memorable business card that stands out in any wallet.</p>
<h2>Step 1: Define your brand</h2>
<p>Before you open any design software, clarify your brand identity. What colors represent your business? What font matches your personality? Your card should be a miniature extension of your brand.</p>
<h2>Step 2: Choose the right format</h2>
<p>Standard business cards are 85 &times; 55 mm. Consider whether you want a horizontal or vertical layout. Vertical cards are less common and can help you stand out, but horizontal cards are easier to store and read.</p>
<h2>Step 3: Keep it clean</h2>
<p>Resist the urge to fill every inch of space. White space is your friend. Include only essential information: name, title, phone, email, and website. If you use social media, pick one or two platforms max.</p>
<h2>Step 4: Pick quality materials</h2>
<p>The paper stock matters as much as the design. We recommend at least 350 GSM matte or silk laminate for a premium feel. Consider spot UV coating on your logo for added texture and visual interest.</p>
<h2>Step 5: Order and review</h2>
<p>Once your design is ready, upload it to PrintPandora, select your options, and place your order. We&rsquo;ll send you a digital proof before printing so you can review every detail.</p>
HTML,
            ],
            [
                'title' => 'How BrightBox Doubled Orders with Custom Packaging',
                'category' => 'case-studies',
                'body' => <<<'HTML'
<p>BrightBox, a subscription box company for eco-friendly home goods, approached us with a challenge: their unboxing experience felt generic and didn&rsquo;t reflect their brand values. Here&rsquo;s how we helped them transform their packaging.</p>
<h2>The challenge</h2>
<p>BrightBox was using plain brown boxes with a single-color logo stamp. While functional, the packaging didn&rsquo;t excite customers or reinforce their eco-friendly mission. They wanted something sustainable, beautiful, and memorable.</p>
<h2>Our solution</h2>
<p>We worked with BrightBox to design fully custom boxes using recycled kraft paper with soy-based inks. The design featured botanical illustrations, a bold unboxing message inside the lid, and tissue paper printed with their brand story.</p>
<p>We also introduced a QR code on the inner flap linking to a &ldquo;thank you&rdquo; video from the founders.</p>
<h2>The results</h2>
<p>Within three months of launching the new packaging:</p>
<ul>
<li>Customer referrals increased by <strong>45%</strong></li>
<li>Social media shares of unboxing photos doubled</li>
<li>Repeat order rate climbed from 22% to <strong>34%</strong></li>
<li>BrightBox saw a <strong>2.1&times;</strong> increase in overall monthly orders</li>
</ul>
<h2>Key takeaways</h2>
<p>Packaging isn&rsquo;t just a container &mdash; it&rsquo;s a marketing channel. When your packaging delights customers, they become your best advocates. If you&rsquo;re interested in custom packaging for your business, get in touch with our team.</p>
HTML,
            ],
            [
                'title' => 'Understanding Paper Types: A Complete Guide',
                'category' => 'tutorials',
                'body' => <<<'HTML'
<p>Choosing the right paper can make or break your print project. With so many options available, it&rsquo;s easy to feel overwhelmed. This guide breaks down the most common paper types and when to use each one.</p>
<h2>Coated vs. Uncoated</h2>
<p><strong>Coated paper</strong> has a smooth, sealed surface that prevents ink from absorbing too deeply. This results in sharper images and more vibrant colors. It&rsquo;s ideal for brochures, magazines, and photo prints.</p>
<p><strong>Uncoated paper</strong> has a natural, textured feel. Ink absorbs more, creating a softer, warmer look. It&rsquo;s perfect for letterheads, notepads, and business stationery.</p>
<h2>Gloss, Matte, and Satin</h2>
<p>Within coated papers, you have several finish options:</p>
<ul>
<li><strong>Gloss</strong> &mdash; High shine, maximum color pop. Great for promotional materials.</li>
<li><strong>Matte</strong> &mdash; No shine, elegant and easy to read. Ideal for reports and catalogs.</li>
<li><strong>Satin</strong> &mdash; A middle ground with a subtle sheen. Works well for almost any project.</li>
</ul>
<h2>Weight and thickness</h2>
<p>Paper weight is measured in GSM (grams per square meter). Here&rsquo;s a quick reference:</p>
<ul>
<li><strong>80&ndash;100 GSM</strong> &mdash; Standard office paper</li>
<li><strong>120&ndash;170 GSM</strong> &mdash; Flyers and posters</li>
<li><strong>200&ndash;300 GSM</strong> &mdash; Business cards and postcards</li>
<li><strong>350+ GSM</strong> &mdash; Premium cards and covers</li>
</ul>
<h2>Specialty papers</h2>
<p>For truly unique projects, consider kraft (recycled, rustic), metallic (shimmer effect), or textured linen paper. Each adds a distinct tactile quality that can elevate your brand.</p>
<p>Still not sure? Our team can recommend the perfect paper for your project. Just reach out!</p>
HTML,
            ],
            [
                'title' => 'Behind the Scenes: A Day at Our Print Studio',
                'category' => 'getting-started',
                'body' => <<<'HTML'
<p>Ever wondered what happens after you click &ldquo;Order&rdquo;? Take a peek inside our print studio and see how your projects come to life.</p>
<h2>6:00 AM &mdash; Pre-press checks</h2>
<p>Our day starts early. The pre-press team reviews every incoming file for resolution, bleed, and color accuracy. If there&rsquo;s an issue, we flag it immediately and reach out to the customer. About 15% of files need minor adjustments &mdash; we handle those for free.</p>
<h2>8:00 AM &mdash; Printing begins</h2>
<p>Our digital and offset presses hum to life. Digital presses handle short runs with fast turnaround, while our offset machines tackle large-volume orders with unbeatable color consistency. On a typical day, we print over 50,000 sheets.</p>
<h2>12:00 PM &mdash; Finishing touches</h2>
<p>After printing, each job moves to our finishing department. Here, we handle cutting, folding, binding, laminating, and any special coatings. Our operators have decades of combined experience &mdash; they can spot a misalignment from across the room.</p>
<h2>3:00 PM &mdash; Quality control</h2>
<p>Every order goes through a final QC check. We inspect random samples for color accuracy, alignment, and finish quality. If anything looks off, the entire batch is reprinted. We&rsquo;d rather delay a shipment than send out subpar work.</p>
<h2>5:00 PM &mdash; Packing and shipping</h2>
<p>Approved orders are carefully packed and handed off to our shipping partners. Most local orders arrive within 1&ndash;2 business days.</p>
<p>Interested in visiting our studio? We offer tours on the first Friday of every month. Sign up on our website!</p>
HTML,
            ],
            [
                'title' => 'Sustainable Printing: Our Green Commitment',
                'category' => 'product-updates',
                'body' => <<<'HTML'
<p>Sustainability isn&rsquo;t just a buzzword for us &mdash; it&rsquo;s a core part of how we operate. Here&rsquo;s what we&rsquo;re doing to reduce our environmental footprint and how you can make greener print choices.</p>
<h2>Our initiatives</h2>
<p>We&rsquo;ve invested heavily in sustainable practices across our entire operation:</p>
<ul>
<li><strong>FSC-certified papers</strong> &mdash; 90% of our paper stock comes from responsibly managed forests.</li>
<li><strong>Soy-based inks</strong> &mdash; Unlike petroleum-based alternatives, soy inks are renewable and produce vibrant colors with lower VOC emissions.</li>
<li><strong>Zero-waste goal</strong> &mdash; We recycle 98% of our production waste, including paper offcuts, ink cartridges, and printing plates.</li>
<li><strong>Energy-efficient presses</strong> &mdash; Our newest digital presses consume 30% less energy than previous models.</li>
</ul>
<h2>How you can help</h2>
<p>Small choices add up. When placing your order, consider:</p>
<ul>
<li>Choosing recycled or FSC-certified paper stocks</li>
<li>Ordering the exact quantity you need to avoid waste</li>
<li>Opting for digital proofs instead of physical samples</li>
<li>Using eco-friendly finishes like water-based coatings</li>
</ul>
<h2>What&rsquo;s next</h2>
<p>We&rsquo;re currently piloting a carbon-offset program where we plant one tree for every 1,000 prints ordered. Early results are promising, and we plan to roll it out globally by next quarter. Stay tuned!</p>
HTML,
            ],
        ];

        foreach ($samplePosts as $data) {
            $categoryId = Category::where('slug', $data['category'])->value('id');

            Post::firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'category_id' => $categoryId,
                    'admin_id' => $admin->id,
                    'is_published' => true,
                    'published_at' => now()->subDays(rand(1, 90)),
                ],
            );
        }

        // Product categories
        $productCategories = [
            ['name' => 'Business Cards', 'slug' => 'business-cards'],
            ['name' => 'Flyers & Brochures', 'slug' => 'flyers-brochures'],
            ['name' => 'Apparel', 'slug' => 'apparel'],
            ['name' => 'Signage & Banners', 'slug' => 'signage-banners'],
            ['name' => 'Stationery', 'slug' => 'stationery'],
        ];

        foreach ($productCategories as $cat) {
            ProductCategory::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        $categoryMap = ProductCategory::pluck('id', 'slug')->toArray();

        $sampleProducts = [
            [
                'name' => 'Premium Business Cards (500 pack)',
                'slug' => 'premium-business-cards-500',
                'description' => '<p>Make a lasting first impression with our premium business cards. Printed on 400 GSM matte stock with your choice of finish.</p><ul><li>400 GSM premium matte paper</li><li>Full color, double-sided printing</li><li>Optional spot UV or foil stamping</li><li>500 cards per pack</li><li>2-3 business day turnaround</li></ul>',
                'price' => 29.99,
                'category' => 'business-cards',
            ],
            [
                'name' => 'Luxury Foil Business Cards (250 pack)',
                'slug' => 'luxury-foil-business-cards-250',
                'description' => '<p>Elevate your brand with our luxury foil-stamped business cards. Gold, silver, or rose gold foil on 450 GSM black matte stock.</p><ul><li>450 GSM black matte paper</li><li>Foil stamping on one or both sides</li><li>3 foil color options</li><li>250 cards per pack</li><li>3-4 business day turnaround</li></ul>',
                'price' => 49.99,
                'category' => 'business-cards',
            ],
            [
                'name' => 'Flyer Printing (1000 pack)',
                'slug' => 'flyer-printing-1000',
                'description' => '<p>High-quality flyer printing perfect for events, promotions, and announcements. Vibrant colors on 150 GSM gloss paper.</p><ul><li>150 GSM gloss paper</li><li>Full color, single or double-sided</li><li>A5 or A6 sizes available</li><li>1,000 flyers per pack</li><li>Next-day turnaround available</li></ul>',
                'price' => 39.99,
                'category' => 'flyers-brochures',
            ],
            [
                'name' => 'Tri-Fold Brochure Printing (500 pack)',
                'slug' => 'tri-fold-brochure-500',
                'description' => '<p>Professional tri-fold brochures that showcase your products and services beautifully. Printed on 170 GSM silk paper with a soft-touch laminate.</p><ul><li>170 GSM silk paper</li><li>Soft-touch matte laminate</li><li>Full color, double-sided</li><li>500 brochures per pack</li><li>3-4 business day turnaround</li></ul>',
                'price' => 69.99,
                'category' => 'flyers-brochures',
            ],
            [
                'name' => 'Custom T-Shirt Printing',
                'slug' => 'custom-t-shirt-printing',
                'description' => '<p>Design your own t-shirt with our direct-to-garment printing. Premium quality, soft-feel prints that last wash after wash.</p><ul><li>100% ring-spun cotton</li><li>DTG full-color printing</li><li>Unisex sizing S-3XL</li><li>10 color options</li><li>5-7 business day turnaround</li></ul>',
                'price' => 24.99,
                'category' => 'apparel',
            ],
            [
                'name' => 'Custom Hoodie Printing',
                'slug' => 'custom-hoodie-printing',
                'description' => '<p>Stay warm and stylish with our custom-printed hoodies. Perfect for teams, events, or your brand merch line.</p><ul><li>80% cotton, 20% polyester</li><li>DTG or screen printing available</li><li>Unisex sizing S-3XL</li><li>8 color options</li><li>7-10 business day turnaround</li></ul>',
                'price' => 44.99,
                'category' => 'apparel',
            ],
            [
                'name' => 'Vinyl Banner (3ft &times; 6ft)',
                'slug' => 'vinyl-banner-3x6',
                'description' => '<p>Durable outdoor vinyl banners with vibrant, weather-resistant printing. Ideal for events, storefronts, and trade shows.</p><ul><li>Heavy-duty 13oz vinyl</li><li>Weather and UV resistant</li><li>Hemmed edges with grommets</li><li>Custom sizes available</li><li>2-3 business day turnaround</li></ul>',
                'price' => 59.99,
                'category' => 'signage-banners',
            ],
            [
                'name' => 'Retractable Banner Stand (33" &times; 78")',
                'slug' => 'retractable-banner-stand',
                'description' => '<p>Portable, professional retractable banner stand with full-color graphic panel. Perfect for trade shows, lobbies, and presentations.</p><ul><li>Adjustable aluminum stand included</li><li>Premium matte laminate graphic</li><li>Carrying case included</li><li>33" x 78" display area</li><li>5-7 business day turnaround</li></ul>',
                'price' => 149.99,
                'category' => 'signage-banners',
            ],
            [
                'name' => 'Custom Letterhead (500 sheets)',
                'slug' => 'custom-letterhead-500',
                'description' => '<p>Professional letterhead stationery that reinforces your brand with every correspondence. Printed on premium uncoated paper.</p><ul><li>120 GSM premium uncoated</li><li>Full color or single color</li><li>8.5" x 11" standard size</li><li>500 sheets per pack</li><li>3-4 business day turnaround</li></ul>',
                'price' => 34.99,
                'category' => 'stationery',
            ],
            [
                'name' => 'Custom Notebooks (10 pack)',
                'slug' => 'custom-notebooks-10',
                'description' => '<p>Branded notebooks that make perfect corporate gifts or employee swag. Customize the cover with your logo and choose from lined or blank pages.</p><ul><li>A5 size (5.8" x 8.3")</li><li>80 lined or blank pages</li><li>Soft or hard cover options</li><li>Full-color cover printing</li><li>10 notebooks per pack</li></ul>',
                'price' => 54.99,
                'category' => 'stationery',
            ],
        ];

        foreach ($sampleProducts as $data) {
            $categoryId = $categoryMap[$data['category']] ?? null;
            Product::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'product_category_id' => $categoryId,
                    'is_active' => true,
                ],
            );
        }

        // Replay the live-data snapshot last so `db:seed` reproduces it exactly.
        // Regenerate the snapshot any time with: php artisan db:export-seeders
        $this->call(LiveDataSeeder::class);
    }
}
