import HeroCarousel from '@/components/hero-carousel';
import HomePerks from '@/components/home-perks';
import PopularProducts from '@/components/popular-products';
import RecentPosts from '@/components/recent-posts';
import type {RecentPost} from '@/components/recent-posts';
import SamplePackBanner from '@/components/sample-pack-banner';
import SEO from '@/components/seo';
import { useContent } from '@/hooks/use-content';
import StorefrontLayout from '@/layouts/storefront-layout';

type Props = {
    recentPosts: RecentPost[];
};

export default function Home({ recentPosts }: Props) {
    const c = useContent('home_page');

    return (
        <StorefrontLayout>
            <SEO
                title={c.seo.page_title ?? 'Welcome'}
                description={c.seo.page_description}
            />
            <HeroCarousel />
            <PopularProducts />
            <HomePerks />
            <SamplePackBanner />
            <RecentPosts posts={recentPosts} />
        </StorefrontLayout>
    );
}
