import CategoryLanding from '@/components/category-landing';
import { useContent } from '@/hooks/use-content';

export default function PostcardsPage() {
    const c = useContent('postcards_page') as any;

    return <CategoryLanding content={c} />;
}
