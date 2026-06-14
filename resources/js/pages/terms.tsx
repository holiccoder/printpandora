import LegalPage from '@/components/legal-page';

export default function Terms() {
    return (
        <LegalPage
            eyebrow="Last updated June 2026"
            title="Terms & Conditions"
            description="The terms and conditions governing your use of the PrintPandora website and the products and services we provide."
        >
            <p>
                These Terms & Conditions ("Terms") govern your access to and use of the
                PrintPandora website and the products and services we offer. By placing
                an order, creating an account, or otherwise using our services, you
                agree to be bound by these Terms. Please read them carefully — this is
                sample copy provided for layout purposes and should be replaced with
                terms reviewed by your legal counsel before you go live.
            </p>

            <h2>1. About these terms</h2>
            <p>
                PrintPandora ("we", "us", or "our") provides on-demand print services
                to customers worldwide. By using our services you confirm that you are
                at least 18 years old, or that you have the consent of a parent or
                legal guardian, and that you have the authority to enter into a binding
                contract.
            </p>

            <h2>2. Your account</h2>
            <p>
                You are responsible for keeping your account credentials confidential
                and for all activity that takes place under your account. Notify us
                immediately if you suspect unauthorized use. We reserve the right to
                suspend or terminate accounts that violate these Terms.
            </p>

            <h2>3. Orders and payment</h2>
            <p>
                All orders are subject to acceptance and product availability. Prices
                shown on the site are inclusive of applicable taxes unless otherwise
                stated. Payment is taken at the point of order, and a confirmation
                email is sent once your order has been received. We reserve the right
                to cancel any order at our discretion, including for reasons of
                pricing errors or suspected fraud.
            </p>

            <h2>4. Intellectual property and your artwork</h2>
            <p>
                You retain ownership of any artwork or content you upload to
                PrintPandora. By uploading content, you confirm that you have the right
                to reproduce it and grant us a limited licence to print, fulfil, and
                ship your order. You agree not to upload content that infringes the
                rights of a third party or that is unlawful, defamatory, or obscene.
            </p>

            <h2>5. Delivery</h2>
            <p>
                Estimated delivery times are provided at checkout and are indicative
                only. We are not responsible for delays caused by carriers, customs, or
                events outside our reasonable control. Risk in the products passes to
                you on delivery.
            </p>

            <h2>6. Returns and refunds</h2>
            <p>
                Because each order is printed to your specification, products are
                generally non-returnable. If your order arrives damaged, defective, or
                materially different from what you ordered, contact us within 14 days
                of delivery and we'll arrange a reprint or refund.
            </p>

            <h2>7. Limitation of liability</h2>
            <p>
                To the fullest extent permitted by law, PrintPandora is not liable for
                indirect, incidental, or consequential losses arising from your use of
                our services. Our total liability for any claim relating to an order
                is limited to the amount you paid for that order.
            </p>

            <h2>8. Changes to these terms</h2>
            <p>
                We may update these Terms from time to time. The most current version
                will always be posted on this page; substantial changes will be
                highlighted at the top of the page. Continued use of the service after
                changes are posted constitutes acceptance.
            </p>

            <h2>9. Contact</h2>
            <p>
                Questions about these Terms? Email{' '}
                <a href="mailto:legal@printpandora.com">legal@printpandora.com</a> or
                visit our <a href="/contact">contact page</a>.
            </p>
        </LegalPage>
    );
}
