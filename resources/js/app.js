import Lenis from 'lenis';
import 'lenis/dist/lenis.css';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (! prefersReducedMotion) {
    const lenis = new Lenis({
        duration: 1.1,
        smoothWheel: true,
        wheelMultiplier: 0.85,
    });

    const updateScroll = (time) => {
        lenis.raf(time);
        window.requestAnimationFrame(updateScroll);
    };

    window.requestAnimationFrame(updateScroll);

    document.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const destination = new URL(link.href, window.location.href);
            const usesModifiedClick = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;

            if (
                event.defaultPrevented ||
                link.target === '_blank' ||
                usesModifiedClick ||
                link.hasAttribute('download') ||
                destination.origin !== window.location.origin ||
                (destination.pathname === window.location.pathname && destination.hash !== '')
            ) {
                return;
            }

            event.preventDefault();
            document.body.classList.add('page-leaving');

            window.setTimeout(() => {
                window.location.assign(destination.href);
            }, 180);
        });
    });
}
