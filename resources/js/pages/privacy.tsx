import LegalPage from '@/components/legal-page';

export default function Privacy() {
    return (
        <LegalPage
            eyebrow="Last updated June 2026"
            title="Privacy Policy"
            description="How PrintPandora collects, uses, and protects your personal information."
        >
            <p>
                This Privacy Policy explains how PrintPandora ("we", "us", or "our")
                collects, uses, shares, and protects information about you when you
                visit our website or use our services. This is sample copy provided for
                layout purposes and should be replaced with a privacy policy tailored
                to your business and reviewed by your legal counsel before you go live.
            </p>

            <h2>1. Information we collect</h2>
            <p>We collect three broad categories of information:</p>
            <ul>
                <li>
                    <strong>Information you provide.</strong> Account details, billing
                    and shipping addresses, artwork you upload, and any messages you
                    send to our support team.
                </li>
                <li>
                    <strong>Information collected automatically.</strong> Usage data
                    such as pages visited, device and browser type, IP address, and
                    cookies that help us recognise you across visits.
                </li>
                <li>
                    <strong>Information from third parties.</strong> Payment confirmation
                    from our payment processors and shipping events from carriers.
                </li>
            </ul>

            <h2>2. How we use your information</h2>
            <ul>
                <li>To process orders, fulfil them, and keep you updated on their progress.</li>
                <li>To provide customer support and respond to your enquiries.</li>
                <li>
                    To improve our products, services, and the way our website works.
                </li>
                <li>
                    To send marketing communications where you've opted in. You can
                    unsubscribe at any time using the link in any email.
                </li>
                <li>
                    To comply with legal obligations and protect against fraud or
                    misuse.
                </li>
            </ul>

            <h2>3. Sharing your information</h2>
            <p>
                We share data only with parties who help us operate the business —
                payment processors, fulfilment partners, shipping carriers, and the
                analytics tools we use to understand how the site is performing. We
                never sell your personal information.
            </p>

            <h2>4. Cookies</h2>
            <p>
                Cookies are small text files stored on your device. We use them to keep
                you signed in, remember items in your cart, and measure how visitors
                use the site. You can disable non-essential cookies at any time
                through your browser settings or our cookie preferences panel.
            </p>

            <h2>5. Your rights</h2>
            <p>
                Depending on where you live, you may have rights to access, correct, or
                delete the personal information we hold about you, to object to certain
                processing, or to receive your data in a portable format. To exercise
                any of these rights, email{' '}
                <a href="mailto:privacy@printpandora.com">privacy@printpandora.com</a>.
            </p>

            <h2>6. Data retention</h2>
            <p>
                We keep personal information only as long as necessary to provide our
                services, comply with legal obligations, and resolve disputes. Order
                records are typically kept for seven years to satisfy tax and accounting
                requirements.
            </p>

            <h2>7. Security</h2>
            <p>
                We use industry-standard administrative, technical, and physical
                safeguards to protect your information. No system is perfect, however,
                and we encourage you to use a strong, unique password and to keep your
                account credentials confidential.
            </p>

            <h2>8. Children's privacy</h2>
            <p>
                Our services are not directed to children under 16, and we do not
                knowingly collect personal information from them. If you believe a
                child has provided us with personal information, contact us and we'll
                delete it.
            </p>

            <h2>9. Changes to this policy</h2>
            <p>
                We may update this Privacy Policy from time to time. The most current
                version will always be posted on this page; we'll let you know about
                significant changes by email or through a notice on the site.
            </p>

            <h2>10. Contact</h2>
            <p>
                Questions about this Privacy Policy? Email{' '}
                <a href="mailto:privacy@printpandora.com">privacy@printpandora.com</a> or
                visit our <a href="/contact">contact page</a>.
            </p>
        </LegalPage>
    );
}
