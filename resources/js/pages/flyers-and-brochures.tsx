import CategoryLanding from '@/components/category-landing';
import { useContent } from '@/hooks/use-content';

export default function FlyersAndBrochuresPage() {
    const c = useContent('flyers_page') as any;

    return <CategoryLanding content={c} />;
}
