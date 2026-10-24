// Interactions and motion for the public website. Dependency-free so pages stay light.
// Pages are swapped by Livewire's navigation (no full reload), so page features are set
// up again after every swap by initPage().

const root = document.documentElement;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let observer = null;

const setMenu = (open) => {
    root.classList.toggle('menu-open', open);
    document.querySelector('[data-menu-toggle]')?.setAttribute('aria-expanded', String(open));
};

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMenu(false);
    }
});

document.addEventListener('click', (event) => {
    // Light / dark switch, remembered on this device
    if (event.target.closest('[data-theme-toggle]')) {
        const current = root.dataset.theme
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        const next = current === 'dark' ? 'light' : 'dark';

        root.dataset.theme = next;

        try {
            localStorage.setItem('sr-theme', next);
        } catch (error) {
            // Storage can be blocked; the switch still works for this visit.
        }

        return;
    }

    if (event.target.closest('[data-menu-toggle]')) {
        setMenu(! root.classList.contains('menu-open'));

        return;
    }

    if (event.target.closest('.mobile-menu a')) {
        setMenu(false);
    }

    // Swap "From" and "To" in a search form
    const swap = event.target.closest('[data-swap]');

    if (swap) {
        const form = swap.closest('form');
        const from = form?.querySelector('[name="from"]');
        const to = form?.querySelector('[name="to"]');

        if (from && to) {
            [from.value, to.value] = [to.value, from.value];
            from.dispatchEvent(new Event('input', { bubbles: true }));
            to.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
});

// Typing "lagos" or "lhr" picks the airport code from the list, so the form sends a code.
document.addEventListener('change', (event) => {
    const input = event.target.closest('input[data-airport]');

    if (! input) {
        return;
    }

    const match = input.value.match(/\(([A-Za-z]{3})\)\s*$/);

    if (match) {
        input.value = match[1].toUpperCase();
    } else if (/^[a-z]{3}$/i.test(input.value.trim())) {
        input.value = input.value.trim().toUpperCase();
    }
});

const reveal = () => {
    observer?.disconnect();

    const items = document.querySelectorAll('[data-reveal]');

    if (reduceMotion || ! ('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));

        return;
    }

    observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });

    items.forEach((item) => observer.observe(item));
};

// A short burst of confetti when a ticket has just been issued
const confetti = () => {
    const holder = document.querySelector('[data-confetti]');

    if (! holder || reduceMotion || holder.childElementCount) {
        return;
    }

    const colours = ['#ff7a1a', '#ffb27a', '#1d5fd1', '#4f8bff', '#13a35a', '#ffffff'];

    for (let index = 0; index < 70; index++) {
        const piece = document.createElement('i');

        piece.style.left = `${Math.random() * 100}%`;
        piece.style.background = colours[index % colours.length];
        piece.style.animationDuration = `${2.2 + Math.random() * 2}s`;
        piece.style.animationDelay = `${Math.random() * 0.6}s`;
        piece.style.transform = `rotate(${Math.random() * 360}deg)`;
        holder.appendChild(piece);
    }

    setTimeout(() => holder.remove(), 5000);
};

const initPage = () => {
    setMenu(false);
    reveal();
    confetti();
};

document.addEventListener('DOMContentLoaded', initPage);
document.addEventListener('livewire:navigated', initPage);
