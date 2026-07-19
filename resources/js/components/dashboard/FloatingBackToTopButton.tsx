import { ArrowUp } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function FloatingBackToTopButton() {
    return (
        <Button
            type="button"
            size="icon"
            className="fixed right-5 bottom-5 z-40 shadow-md"
            aria-label="Back to top"
            onClick={() => window.scrollTo(0, 0)}
        >
            <ArrowUp />
        </Button>
    );
}
