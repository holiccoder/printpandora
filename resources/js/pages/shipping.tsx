import LegalPage from '@/components/legal-page';

export default function Shipping() {
    return (
        <LegalPage
            eyebrow="Last updated June 22, 2026"
            title="Shipping & Delivery"
            description="Production times, shipping methods, carriers, costs, and what to do if something goes wrong."
        >
            <p>
                Every PrintPandora order is printed to order — so the total time
                from checkout to doorstep is made up of two parts:{' '}
                <strong>production</strong> (the time it takes us to print,
                finish, and pack your order) and <strong>shipping</strong> (the
                time the carrier takes to deliver it). Below is everything you
                need to know about both.
            </p>

            <h2>1. Production times</h2>
            <p>
                Production starts the next business day after we receive your
                final, approved artwork. Standard production times are:
            </p>
            <ul>
                <li>
                    <strong>Business Cards, MiniCards, Postcards, Flyers</strong>
                    {' '}— 2 to 3 business days
                </li>
                <li>
                    <strong>Stickers & Labels</strong> — 3 to 4 business days
                </li>
                <li>
                    <strong>Letterpress, Foil, and Hot Foil products</strong>{' '}
                    — 5 to 7 business days
                </li>
                <li>
                    <strong>Notebooks, Journals, Planners</strong> — 4 to 6
                    business days
                </li>
                <li>
                    <strong>Display Boxes, Greeting Cards, Luxe Notecards</strong>
                    {' '}— 3 to 5 business days
                </li>
            </ul>
            <p>
                If you need your order sooner, select <strong>Rush
                production</strong> at checkout (where available) — this halves
                production time for an additional fee.
            </p>

            <h2>2. Shipping methods</h2>
            <p>
                We offer four shipping options. Available methods depend on your
                destination and product:
            </p>
            <ul>
                <li>
                    <strong>Standard</strong> — 3 to 5 business days (domestic),
                    7 to 14 business days (international). Tracked.
                </li>
                <li>
                    <strong>Express</strong> — 1 to 2 business days (domestic),
                    3 to 5 business days (international). Tracked, signature on
                    delivery.
                </li>
                <li>
                    <strong>Next-Day</strong> — Next business day for orders
                    placed before 12 noon local time. Available in the UK, most
                    of the EU, and major US metro areas. Tracked, signature on
                    delivery.
                </li>
                <li>
                    <strong>Economy</strong> — 5 to 10 business days, lowest
                    cost. Tracked. Not available for fragile or oversized
                    items.
                </li>
            </ul>

            <h2>3. Carriers</h2>
            <p>
                We work with a global network of trusted carriers — the actual
                carrier assigned to your order depends on your destination,
                shipping method, and parcel weight:
            </p>
            <ul>
                <li>
                    <strong>United Kingdom</strong> — Royal Mail (Standard,
                    Economy), DPD and Parcelforce (Express, Next-Day).
                </li>
                <li>
                    <strong>European Union</strong> — DHL, DPD, GLS, regional
                    posts.
                </li>
                <li>
                    <strong>United States & Canada</strong> — USPS, UPS, FedEx,
                    Canada Post.
                </li>
                <li>
                    <strong>Australia & New Zealand</strong> — Australia Post,
                    Aramex, NZ Post.
                </li>
                <li>
                    <strong>Rest of the world</strong> — DHL Express, FedEx
                    International, or your country's national post (depending on
                    method selected).
                </li>
            </ul>

            <h2>4. Shipping costs</h2>
            <p>
                Shipping is calculated at checkout based on your destination,
                parcel weight, and the method you choose. We negotiate volume
                rates with our carriers and pass the savings on to you.
            </p>
            <ul>
                <li>
                    <strong>Free Standard Shipping</strong> on orders over £50
                    / €60 / $65 / A$95 to the UK, EU, US, Canada, and
                    Australia.
                </li>
                <li>
                    <strong>Flat-rate Express</strong> — see the rates page at
                    checkout for current pricing.
                </li>
                <li>
                    Customs duties, taxes, and brokerage fees on international
                    orders are the responsibility of the recipient unless
                    otherwise stated. We ship DDU (Delivered Duty Unpaid) by
                    default — you'll be contacted by the carrier on arrival in
                    your country if duties are owed.
                </li>
            </ul>

            <h2>5. Tracking your order</h2>
            <p>
                You'll receive a shipping confirmation email with a tracking
                link as soon as your order leaves our facility. You can also
                track every order from{' '}
                <a href="/orders">Your account → Orders</a>.
            </p>
            <p>
                If tracking shows your parcel hasn't moved for 5 business days
                or more, contact us — we'll open an investigation with the
                carrier and either reship or refund.
            </p>

            <h2>6. International shipping</h2>
            <p>
                We ship to over 190 countries. A few things to know about
                international orders:
            </p>
            <ul>
                <li>
                    <strong>Customs delays.</strong> Parcels can be held by
                    customs for inspection. This is outside our control, and
                    delays of 2 to 10 days are common around peak periods.
                </li>
                <li>
                    <strong>Duties and taxes.</strong> You may be charged
                    import duty, VAT, or sales tax by your country's customs
                    authority. These charges are based on the declared value of
                    your order — we cannot mark parcels as gifts or under-declare
                    value.
                </li>
                <li>
                    <strong>Restricted destinations.</strong> We do not ship to
                    countries currently subject to comprehensive trade sanctions
                    (Cuba, Iran, North Korea, Syria, Crimea, and certain regions
                    of Ukraine).
                </li>
                <li>
                    <strong>PO Boxes and APO/FPO/DPO addresses.</strong>{' '}
                    Standard and Economy shipping are available; Express and
                    Next-Day are not.
                </li>
            </ul>

            <h2>7. Delivery issues</h2>
            <p>If your order arrives damaged, incomplete, or doesn't arrive at all:</p>
            <ul>
                <li>
                    <strong>Damaged in transit.</strong> Photograph the parcel
                    and the damaged contents within 48 hours of delivery and
                    contact us. We'll arrange a reprint or refund.
                </li>
                <li>
                    <strong>Missing item.</strong> Check the packing slip
                    against what arrived, then contact us. Items occasionally
                    ship from separate facilities and arrive on different days
                    — your packing slip will note this.
                </li>
                <li>
                    <strong>Lost in transit.</strong> If tracking shows no
                    movement after 5 business days (domestic) or 10 business
                    days (international), contact us and we'll open a claim
                    with the carrier.
                </li>
                <li>
                    <strong>Returned to sender.</strong> If a parcel is
                    returned because of an incorrect address, failed delivery,
                    or unpaid customs fees, we'll contact you to arrange a
                    reshipment. Reshipping fees may apply.
                </li>
            </ul>
            <p>
                File any claim within 14 days of the original delivery date (or
                the latest expected delivery date for non-arrivals) by{' '}
                <a href="/tickets/create">opening a support ticket</a> or
                emailing{' '}
                <a href="mailto:support@printpandora.com">
                    support@printpandora.com
                </a>
                .
            </p>

            <h2>8. Cut-off times</h2>
            <p>To make today's production run, place your order before:</p>
            <ul>
                <li>
                    <strong>12:00 noon GMT/BST</strong> for orders printing at
                    our UK facility.
                </li>
                <li>
                    <strong>12:00 noon ET</strong> for orders printing at our
                    US facility.
                </li>
                <li>
                    <strong>2:00 pm AEST</strong> for orders printing at our
                    AU partner facility.
                </li>
            </ul>
            <p>
                Orders placed after the cut-off start production the next
                business day. Orders placed on weekends or public holidays
                start production on the next business day.
            </p>

            <h2>9. Address accuracy</h2>
            <p>
                Please double-check your shipping address before submitting
                your order. We are not able to redirect a parcel once it has
                been handed to the carrier, and orders returned to us because
                of an incorrect address will incur a reshipping fee. Apartment
                numbers, postal codes, and recipient phone numbers are
                particularly important for international shipments.
            </p>

            <h2>10. Sustainability</h2>
            <p>
                We use recycled and recyclable packaging on every order:
                kraft-cardboard outer boxes, recycled tissue, and starch-based
                cushioning. Where available, we ship orders carbon-neutral
                through partnerships with DHL GoGreen, UPS Carbon Neutral, and
                Royal Mail. Read more on our{' '}
                <a href="/about/sustainability">sustainability page</a>.
            </p>

            <h2>11. Contact</h2>
            <p>
                Questions about an order, a delivery, or a shipping method?
                We'd love to help.
            </p>
            <ul>
                <li>
                    Email:{' '}
                    <a href="mailto:support@printpandora.com">
                        support@printpandora.com
                    </a>
                </li>
                <li>
                    Open a ticket:{' '}
                    <a href="/tickets/create">Submit a support request</a>
                </li>
                <li>
                    Live chat: Monday–Friday, 9 am – 6 pm GMT, via the chat
                    bubble in the bottom-right of any page.
                </li>
            </ul>
        </LegalPage>
    );
}
