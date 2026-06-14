import HeroCarousel from '@/components/hero-carousel';
import HomePerks from '@/components/home-perks';
import PopularProducts from '@/components/popular-products';
import RecentPosts, { type RecentPost } from '@/components/recent-posts';
import SamplePackBanner from '@/components/sample-pack-banner';
import SEO from '@/components/seo';
import StorefrontLayout from '@/layouts/storefront-layout';

type Props = {
    recentPosts: RecentPost[];
};

export default function Home({ recentPosts }: Props) {
    return (
        <StorefrontLayout>
            <SEO
                title="Welcome"
                description="PrintPandora - your go-to solution for printing, customization, and on-demand production."
            />
            <HeroCarousel />
            <PopularProducts />
            <HomePerks />
            <SamplePackBanner />
            <RecentPosts posts={recentPosts} />
        </StorefrontLayout>
    );
}
