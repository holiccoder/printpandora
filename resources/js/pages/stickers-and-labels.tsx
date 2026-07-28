import CategoryLanding from '@/components/category-landing';
import { useContent } from '@/hooks/use-content';

export default function StickersAndLabelsPage() {
    const c = useContent('stickers_page') as any;

    return <CategoryLanding content={c} />;
}
