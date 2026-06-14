import LegalPage from '@/components/legal-page';

export default function About() {
    return (
        <LegalPage
            eyebrow="Who we are"
            title="About PrintPandora"
            description="PrintPandora is a print-on-demand studio helping small businesses and creators bring their brands to life on premium paper."
        >
            <p>
                PrintPandora was founded with one belief: print is personal. From the
                weight of a business card to the texture of a postcard, the way your
                brand feels in someone's hands says as much about you as the design
                itself. We exist to make that experience effortless — beautifully
                designed, reliably produced, and shipped to your door.
            </p>

            <h2>Our story</h2>
            <p>
                We started in a small studio with a single press and a stack of paper
                samples. A decade later we've grown into a print-on-demand platform
                that serves tens of thousands of independent businesses across the
                world, but our obsession with craft hasn't budged. Every order — one
                card or ten thousand — runs through the same quality checks.
            </p>

            <h2>What we believe</h2>
            <p>
                Great print should not be a luxury, and it should never come at the
                expense of the planet. That's why we offer FSC® certified papers,
                soy-based inks, and a recyclable packaging programme on every order.
                When you choose PrintPandora, you're choosing print that's kinder by
                design.
            </p>

            <h3>Our values</h3>
            <ul>
                <li>
                    <strong>Craft over compromise.</strong> If a card isn't right, it
                    doesn't ship. Period.
                </li>
                <li>
                    <strong>People-first service.</strong> Real humans answer your
                    questions, every day of the week.
                </li>
                <li>
                    <strong>Sustainability as standard.</strong> Certified papers and
                    responsible logistics on every order.
                </li>
                <li>
                    <strong>Always learning.</strong> New finishes, new stocks, new
                    products — your feedback shapes everything we build.
                </li>
            </ul>

            <h2>Where we're going</h2>
            <p>
                Our roadmap is built around the small businesses, designers, and
                creators who put their trust in us. Whether you're printing your very
                first business card or rebranding for the third time, we want
                PrintPandora to be the easiest, most rewarding part of the process.
            </p>

            <p>
                Have an idea, a question, or a story to share?{' '}
                <a href="/contact">Get in touch</a> — we'd love to hear from you.
            </p>
        </LegalPage>
    );
}
