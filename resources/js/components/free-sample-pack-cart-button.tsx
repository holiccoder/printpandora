import { router } from '@inertiajs/react';
import { ShoppingCart } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';

interface Props {
    children: ReactNode;
    className: string;
}

export default function FreeSamplePackCartButton({
    children,
    className,
}: Props) {
    const [isAdding, setIsAdding] = useState(false);

    const addToCart = () => {
        if (isAdding) {
            return;
        }

        setIsAdding(true);
        router.post(
            '/cart/add/free-sample-pack',
            {},
            {
                onFinish: () => setIsAdding(false),
            },
        );
    };

    return (
        <button
            type="button"
            onClick={addToCart}
            disabled={isAdding}
            className={`${className} disabled:cursor-wait disabled:opacity-70`}
        >
            <ShoppingCart className="size-4" />
            {isAdding ? 'Adding...' : children}
        </button>
    );
}
