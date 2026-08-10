import { useContent } from '@/hooks/use-content';

export function HomeLogoWall() {
    const home = useContent('home_page');
    const lw = home.logo_wall;

    return (
        <section className="bg-[#F5F0E8]">
            <div className="mx-auto max-w-7xl px-4 py-14 lg:py-16">
                <h2 className="mx-auto max-w-2xl text-center font-serif text-3xl leading-tight font-bold text-[#800020] pb-3 md:text-4xl">
                    {lw.heading}
                </h2>
                <div className="mt-10 flex flex-wrap items-center justify-center gap-x-12 gap-y-8 md:gap-x-16">
                    {lw.logos.map((logo) => (
                        <img
                            key={logo.alt}
                            src={logo.src}
                            alt={logo.alt}
                            className="h-12 w-auto object-contain transition-all duration-300"
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}

export default HomeLogoWall;
