<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('posts')
            ->where('slug', 'business-card-buying-ordering-guide')
            ->update([
                'title' => 'Business Card Buying & Ordering Guide: Materials, Sizes, Finishes, and Lead Times',
                'body' => self::body(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // The English article copy should not be reverted to untranslated content.
    }

    private static function body(): string
    {
        return <<<'HTML'
<p>Ordering business cards for the first time does not require memorizing every paper weight and printing term. Start by deciding how many you need, how you want them to feel, and whether you need special finishes; that will quickly narrow the options. This guide turns materials, sizes, finishes, artwork, production, delivery, and after-sales support into an easy-to-follow path.</p>

<blockquote>
    <p><strong>Quick path:</strong> Choose stock → Set size and quantity → Choose finishes → Prepare artwork → Approve the proof and start production → Delivery and inspection.</p>
</blockquote>

<p>If this is your first order, read the first two sections to choose a stock; if you already know what you want, jump directly to the sections on sizes, finishes, and ordering. The prices in this guide retain the “starting price per card” examples from the source material. Final pricing varies with quantity, size, stock, thickness, finishes, and shipping method. Check the checkout total or confirm with customer service whether taxes, shipping, and special-finish fees are included.</p>

<h2>Three questions to answer before buying business cards</h2>

<p>You do not need to research every paper stock first. Answer these three questions and you can narrow the choices to the product category that fits you best.</p>

<ol>
    <li><strong>How many do you need?</strong> Starting at 50 cards is suitable for a small test run or premium cards; starting at 200 cards is better for routine cards when budget and a stable demand are priorities.</li>
    <li><strong>How should they feel?</strong> If paper texture and impression depth matter most, look at Letterpress Cotton Business Cards; if you want more stock and finishing combinations, look at Super Business Cards; if you prefer a thick, clean, substantial feel, look at Luxe Business Cards.</li>
    <li><strong>Do you need special finishes?</strong> Hot foil, debossing, die cutting, edge finishing, laser cutting, cold foil, and raised spot UV are not available on every stock. Choose a product category first, then confirm the available finishes.</li>
</ol>

<h3>If you are still unsure</h3>

<ul>
    <li>For pronounced paper texture, impression depth, and a handcrafted feel: choose Letterpress Cotton Business Cards.</li>
    <li>For a range of stocks and finishes, with orders starting at 50 cards: choose Super Business Cards.</li>
    <li>For a thick, clean, immediately tactile feel: choose Luxe Business Cards.</li>
    <li>If you have already decided on PVC and fixed rounded corners: choose PVC Business Cards.</li>
    <li>If you have already decided on a metal stock: choose Metal Business Cards.</li>
    <li>For larger quantities and budget-first ordering, when you can accept the normal colour variation of gang-run printing: choose Classic Business Cards.</li>
</ul>

<p><strong>Please note:</strong> Do not judge thickness by gsm alone. Different stocks have different densities and structures, so stocks with similar gsm can feel different. Use the listed thickness in millimetres and the actual stock sample as your main references.</p>

<h2>How to choose among the six business card categories</h2>

<p>The sections below explain who each category suits, its core specifications, and what to confirm before ordering. If you want to decide quickly, start with the “Best for you” line at the beginning of each section.</p>

<h3>1. Letterpress Cotton Business Cards</h3>

<p><strong>Best for you:</strong> You care about paper texture, impression depth, and premium craftsmanship; this is a good fit for founders, studios, weddings, and high-end services.</p>

<ul>
    <li><strong>Minimum order:</strong> 50 cards; the source material lists a starting price of ¥1.9 per card.</li>
    <li><strong>Stock:</strong> 300–700 gsm cotton paper; finished thickness of approximately 0.35–1.1 mm.</li>
    <li><strong>Size:</strong> Customizable within a maximum of 3.5 × 2.1 in.</li>
    <li><strong>Available finishes:</strong> Spot colour, full-colour printing, debossing/impression, hot foil, die cutting, edge finishing, and laser cutting.</li>
</ul>

<p><strong>Confirm before ordering:</strong> Complex finishes can affect achievable line weights, registration, and production time. Confirm the finish combination before designing.</p>

<h3>2. Super Business Cards</h3>

<p><strong>Best for you:</strong> You want a small minimum order while keeping a broad range of stocks, laminations, and finishing options available.</p>

<ul>
    <li><strong>Minimum order:</strong> 50 cards; the source material lists a starting price of ¥0.4 per card.</li>
    <li><strong>Stock:</strong> Eight 350 gsm textured papers at approximately 0.4 mm thick; 320 gsm coated paper at approximately 0.4 mm; and 640 gsm coated paper at approximately 0.8 mm.</li>
    <li><strong>Size:</strong> Customizable within a maximum of 3.5 × 2.1 in.</li>
    <li><strong>Textured paper options:</strong> Four-colour printing, hot foil, and die cutting.</li>
    <li><strong>320 gsm/640 gsm coated paper options:</strong> Cold foil, raised spot UV, die cutting, and multiple laminations.</li>
</ul>

<p><strong>Confirm before ordering:</strong> Confirm the stock first and the finish second. The same finish can look different on textured paper and coated paper.</p>

<h3>3. Luxe Business Cards</h3>

<p><strong>Best for you:</strong> You prefer thick paper with a clean but substantial look and feel, and want an order starting at 50 cards.</p>

<ul>
    <li><strong>Minimum order:</strong> 50 cards; the source material lists a starting price of ¥0.7 per card.</li>
    <li><strong>Stock:</strong> Four 700 gsm textured papers; finished thickness of approximately 0.8 mm.</li>
    <li><strong>Size:</strong> Customizable within a maximum of 3.5 × 2.1 in.</li>
    <li><strong>Available finishes:</strong> Four-colour printing, hot foil, and die cutting.</li>
</ul>

<p><strong>Confirm before ordering:</strong> If the design includes large areas of dark colour or small reversed-out text, have the artwork reviewed for print suitability first.</p>

<h3>4. PVC Business Cards</h3>

<p><strong>Best for you:</strong> You have already decided on PVC, a standard size, and a rounded shape.</p>

<ul>
    <li><strong>Minimum order:</strong> 50 cards; the source material lists a starting price of ¥0.6 per card.</li>
    <li><strong>Thickness:</strong> 0.38 mm, 0.76 mm, or 0.84 mm.</li>
    <li><strong>Fixed size:</strong> 3.37 × 2.13 in with rounded corners.</li>
    <li><strong>Surface options:</strong> Frosted, matte, or glossy.</li>
</ul>

<p><strong>Confirm before ordering:</strong> PVC uses a fixed size and corner radius. Contact customer service first if you need a non-standard shape.</p>

<h3>5. Metal Business Cards</h3>

<p><strong>Best for you:</strong> You want metal to create a distinctive identity and can accommodate a longer production schedule.</p>

<ul>
    <li><strong>Minimum order:</strong> 50 cards; the source material lists a starting price of ¥4.6 per card.</li>
    <li><strong>Thickness:</strong> 0.3 mm or 0.5 mm.</li>
    <li><strong>Sizes:</strong> 89 × 51 mm (approximately 3.50 × 2.01 in), 85 × 54 mm (approximately 3.35 × 2.13 in), or 80 × 50 mm (approximately 3.15 × 1.97 in).</li>
    <li><strong>Finish details:</strong> The source material mentions multiple colours and finishes but does not list the specific options.</li>
</ul>

<p><strong>Confirm before ordering:</strong> Ask customer service for the current list of metal finishes, colours, minimum text sizes, and cutout limitations before designing.</p>

<h3>6. Classic Business Cards</h3>

<p><strong>Best for you:</strong> You need a larger quantity, want to prioritize budget, have a stable layout, and can accept normal batch-to-batch colour variation from gang-run printing.</p>

<ul>
    <li><strong>Minimum order:</strong> 200 cards; the source material lists a starting price of ¥0.065 per card.</li>
    <li><strong>Materials:</strong> Two coated papers with lamination/UV (approximately 0.32 mm thick) and six uncoated textured papers.</li>
    <li><strong>Common sizes:</strong> 3.5 × 2 in; maximum 85 × 85 mm.</li>
    <li><strong>Printing method:</strong> Gang-run printing, suited to conventional four-colour designs.</li>
</ul>

<p><strong>Confirm before ordering:</strong> The source material lists two square-size specifications, 2.5 × 2.5 in and 2.56 × 2.56 in. If you need a square card, use the options shown on the ordering page and confirm with customer service first. If your brand colours must be tightly controlled, discuss colour variation in advance as well.</p>

<h2>How to choose a size</h2>

<p>Confirm whether the product category supports custom sizes before deciding on the finished dimensions. Do not wait until the design is complete to discover that the stock only supports a fixed size.</p>

<h3>The simplest order of decisions</h3>

<ol>
    <li><strong>Choose the product category first:</strong> Letterpress, Super, and Luxe can be customized within the maximum range; PVC uses a fixed size; Metal offers three sizes; Classic follows the sizes available on the product page.</li>
    <li><strong>Set the finished size next:</strong> For a common horizontal business card, use 3.5 × 2 in as a reference. Size preferences vary by country, card holder, and use case.</li>
    <li><strong>Build the artwork last:</strong> Create the file at the final finished size and add bleed for trimming. Use the product-page template for the current bleed value, safe area, and template version.</li>
</ol>

<h3>Size notes by product</h3>

<ul>
    <li><strong>Letterpress, Super, and Luxe:</strong> Customizable within a maximum of 3.5 × 2.1 in.</li>
    <li><strong>PVC:</strong> Fixed at 3.37 × 2.13 in with rounded corners.</li>
    <li><strong>Metal:</strong> 89 × 51 mm, 85 × 54 mm, or 80 × 50 mm.</li>
    <li><strong>Classic:</strong> Commonly 3.5 × 2 in, with a maximum of 85 × 85 mm; confirm square specifications before ordering.</li>
</ul>

<p><strong>Please note:</strong> “Keeping the proportions” and “keeping the dimensions” are not the same thing. When a design is scaled, the width-to-height ratio can remain the same while text, lines, and QR codes become too small. Check legibility at the final finished size before submitting.</p>

<h3>What you can tell customer service directly</h3>

<p>“I need horizontal business cards, expect to order 100, and want paper texture with light foil. Please recommend compatible stock, thickness, size, and artwork template, and let me know the estimated production time and shipping methods.”</p>

<h2>Surface treatments and finishes, explained simply</h2>

<p>A surface treatment determines the overall sheen and feel; a special finish usually applies only to text, logos, or selected graphics. They can be combined, but only when the stock supports the combination.</p>

<h3>Three common surfaces</h3>

<ul>
    <li><strong>Matte:</strong> Low reflection and a more restrained look; the source material says it can be written on with a marker.</li>
    <li><strong>Gloss:</strong> A visibly shiny surface that makes colours and images appear more vivid, but also creates reflections.</li>
    <li><strong>Soft-touch film:</strong> A matte-oriented finish with a softer, smoother feel, often used to emphasize a premium tactile experience.</li>
</ul>

<h3>Common special finishes</h3>

<ul>
    <li><strong>Debossing/impression:</strong> Pressure creates a recessed mark or impression, emphasizing the feel of the paper.</li>
    <li><strong>Hot foil:</strong> Applies a metallic foil effect to selected text or graphics; the available area depends on the stock and line detail.</li>
    <li><strong>Cold foil:</strong> Listed in the source material as an option for some Super coated-paper configurations; confirm the available colours and design compatibility.</li>
    <li><strong>Raised spot UV:</strong> Gives selected text or graphics a slightly raised, glossy effect; the source material lists it for some Super coated-paper configurations.</li>
    <li><strong>Die cutting:</strong> Cuts the card into a non-standard rectangle or another special shape; rounded corners, sharp corners, and narrow structures need to be checked.</li>
    <li><strong>Edge finishing/laser cutting:</strong> Available for the Letterpress options listed in the source material; confirm the design limitations separately.</li>
</ul>

<p><strong>Please note:</strong> More finishes are not always better. For a first order, start with one primary finish and make the logo or key information the visual focus. The more finishes you combine, the higher the file requirements, cost, and production time will usually be.</p>

<h2>From choosing a product to receiving your cards: the complete ordering process</h2>

<p>Prepare the following six steps and customer service and artwork review can assess feasibility more easily, reducing back-and-forth revisions.</p>

<ol>
    <li><strong>Choose a product category:</strong> Start with Letterpress, Super, Luxe, PVC, Metal, or Classic. If you are unsure, share your quantity, budget, and desired feel and ask customer service for a recommendation.</li>
    <li><strong>Confirm the specifications:</strong> State the quantity, stock, thickness, finished size, surface treatment, special finishes, and whether printing is single-sided or double-sided.</li>
    <li><strong>Prepare the design:</strong> Design it yourself, download a template and build the artwork, provide the content for Inkpavo to place into a template, or choose a separate one-to-one design service.</li>
    <li><strong>Submit the artwork for manual review:</strong> The file is checked together with the selected stock and finishes. Review focuses on size, bleed, text and line work, image resolution, colour mode, and finish placement.</li>
    <li><strong>Approve it and start production:</strong> Once the specifications, artwork, and payment details are confirmed, the order is scheduled. Before production begins, check the name, title, phone number, email, website, QR code, and quantity one more time.</li>
    <li><strong>Choose shipping and inspect on arrival:</strong> The order ships using the selected method after production is complete. Check the quantity, text, colour, trimming, stock, and finish as soon as it arrives. Keep the packaging and evidence if you find a problem.</li>
</ol>

<h3>Seven checks to complete before submitting artwork</h3>

<ol>
    <li>Are the name, title, phone number, email, and website correct?</li>
    <li>Does the QR code scan correctly and point to the right page?</li>
    <li>Does the file size match the final finished size?</li>
    <li>Do the background and images extend into the bleed area according to the template?</li>
    <li>Are the text and logo inside the safe area?</li>
    <li>Are fine lines, small type, and reversed-out type suitable for the selected stock and finishes?</li>
    <li>Are the front/back orientation, single-sided or double-sided setting, quantity, and version correct?</li>
</ol>

<h2>When will they arrive? Add production time and shipping time</h2>

<p>Delivery time is not determined by the courier alone. Production comes first, followed by transit; complex finishes, metal stock, artwork revisions, and holidays can all affect the total timeline.</p>

<h3>Production-time reference</h3>

<ul>
    <li>Fastest production: approximately 2–3 business days, depending on the stock and finishes.</li>
    <li>Orders with finishes: usually approximately 5–7 business days.</li>
    <li>Metal Business Cards: usually approximately 15–20 business days.</li>
</ul>

<h3>Shipping-time reference</h3>

<ul>
    <li>Express shipping: approximately 3–7 business days.</li>
    <li>Standard shipping: approximately 10–18 business days.</li>
</ul>

<h3>How to estimate the arrival date</h3>

<p>Estimated arrival time ≈ production time after artwork approval + shipping time.</p>

<p>For example, a standard order with finishes may take 5–7 business days to produce, followed by 3–7 business days with express shipping. Reserve approximately 8–14 business days overall. This is an estimate, not a guaranteed delivery date.</p>

<p><strong>Order cutoff:</strong> The source material lists the cutoff as Monday through Saturday before 18:00 Beijing time. If the artwork has not been approved, information is incomplete, or the cutoff is missed, production scheduling may be delayed.</p>

<p><strong>Please note:</strong> If you have a fixed date for a promotion, exhibition, or wedding, do not count backward from the shortest estimate. Tell customer service the date the cards must arrive and the delivery location, and leave time for revisions, production, customs, or delivery fluctuations.</p>

<h2>Receiving, inspecting, and after-sales support</h2>

<p>The steps below turn the source material into actions you can take. Final handling depends on the order information, submitted evidence, and after-sales review.</p>

<h3>If you find a problem, do these three things first</h3>

<ol>
    <li><strong>Stop using the order and keep the packaging:</strong> Do not distribute, discard, or rework the cards yourself. Keep the outer packaging, labels, and all products.</li>
    <li><strong>Capture clear evidence:</strong> Provide photos or video that clearly show the problem, including a comparison between the affected cards and the approved artwork.</li>
    <li><strong>Add measurements for measurable problems:</strong> For trimming deviations, photograph the cards with a standard measuring tool. For batch issues, state the affected quantity and the total quantity.</li>
</ol>

<h3>Policies in the source material</h3>

<ul>
    <li><strong>Text errors:</strong> If the error came from the customer's submitted artwork, it is not covered; if it was caused by an Inkpavo production error, the cards will be reprinted free of charge using standard shipping.</li>
    <li><strong>Minor colour variation:</strong> The source material classifies 5%–15% as minor colour variation and offers either 15% compensation against the order value or a 15% coupon for the next order.</li>
    <li><strong>Trim skew greater than 1 mm:</strong> The entire batch will be reworked.</li>
    <li><strong>Rough edges on rounded or special-shape cuts:</strong> A refund is issued based on the defective quantity.</li>
    <li><strong>Incorrect stock:</strong> A full refund is issued and destruction costs are covered. The source material mentions video evidence but does not specify a format; confirm the filming requirements with customer service before destroying anything.</li>
    <li><strong>Usability defects:</strong> Compensation is calculated in proportion to the affected quantity. For example, if 20% of the cards are defective, compensation is calculated on 20% of the product charge.</li>
</ul>

<p><strong>Please note:</strong> Screens, paper, ink, and print batches can all affect how colour looks. If your brand colours must match closely, say so before ordering and confirm the available colour-management or proofing options.</p>

<h2>One last look before ordering</h2>

<p>Prepare the information below in one place and you will be ready for consultation, quoting, and artwork approval:</p>

<ol>
    <li><strong>Product category:</strong> Letterpress / Super / Luxe / PVC / Metal / Classic.</li>
    <li><strong>Quantity and budget:</strong> How many cards you need; whether unit price or tactile quality matters more.</li>
    <li><strong>Stock and thickness:</strong> Paper, PVC, or metal; whether you want lightweight, standard, or substantial.</li>
    <li><strong>Finished size:</strong> Horizontal, vertical, square, or custom; and whether the size is fixed.</li>
    <li><strong>Surface and finishes:</strong> Matte, gloss, soft-touch film, impression, hot foil, UV, die cutting, and so on.</li>
    <li><strong>Artwork:</strong> Final text, images, logo, QR code, front and back, and template.</li>
    <li><strong>Timing and shipping:</strong> Required arrival date, delivery location, and express or standard shipping.</li>
</ol>

<h3>Copy-and-paste request checklist for customer service</h3>

<ul>
    <li>Use: __________</li>
    <li>Product category: __________</li>
    <li>Quantity: __________ cards</li>
    <li>Stock / thickness: __________</li>
    <li>Finished size: __________</li>
    <li>Surface / special finishes: __________</li>
    <li>Single-sided or double-sided: __________</li>
    <li>Required arrival date and delivery location: __________</li>
    <li>Do you already have print-ready artwork: Yes / No</li>
</ul>

<p>Pricing, available stocks, finishing capabilities, templates, and lead times may change as products are updated. When placing an order, rely on the ordering page, final order confirmation, and customer service response.</p>

<p>If you already know your quantity and budget, start with the <a href="/business-cards">business card product page</a>. If you need help with artwork or layout, see the <a href="/business-card-design-service">business card design service</a>.</p>
HTML;
    }
};
