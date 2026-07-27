import { useContent } from '@/hooks/use-content';

const FONT_CLASSES: Record<string, string> = {
    'bold-sans': 'font-sans font-bold tracking-tight',
    script: 'font-serif italic',
    serif: 'font-serif',
    'serif-bold': 'font-serif font-bold',
    sans: 'font-sans font-medium',
    'black-sans': 'font-sans font-black tracking-tight',
};

export function HomeLogoWall() {
    const home = useContent('home_page');
    const lw = home.logo_wall;

    return (
        <section className="bg-[#F5F0E8]">
            <div className="mx-auto max-w-7xl px-4 py-14 lg:py-16">
                <h2 className="text-center text-lg font-medium text-[#2A2A28] md:text-xl">
                    {lw.heading}
                </h2>
                <div className="mt-10 flex flex-wrap items-center justify-center gap-x-12 gap-y-6 md:gap-x-16">
                    {lw.logos.map((logo) => (
                        <span
                            key={logo.text}
                            className={`text-xl text-[#2A2A28]/70 transition-colors hover:text-[#800020] md:text-2xl ${FONT_CLASSES[logo.font] ?? 'font-sans'}`}
                        >
                            {logo.text}
                        </span>
                    ))}
                </div>
            </div>
        </section>
    );
}

export default HomeLogoWall;
